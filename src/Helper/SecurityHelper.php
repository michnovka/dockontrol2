<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Log\EmailChangeLog;
use App\Entity\Log\LoginLog;
use App\Entity\Log\LoginLogFailed;
use App\Entity\User;
use App\Entity\UserDeletionRequest;
use App\Repository\Log\EmailChangeLogRepository;
use App\Repository\UserDeletionRequestRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

readonly class SecurityHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire('%app_secret%')]
        private string $appSecret,
        #[Autowire('%email_verification_grace_period%')]
        private int $gracePeriod,
        private UrlGeneratorInterface $urlGenerator,
        private EmailChangeLogRepository $emailChangeLogRepository,
        private TranslatorInterface $translator,
        private UserDeletionRequestRepository $userDeletionRequestRepository,
    ) {
    }

    public function logUserLoginSuccess(Request $request, User $user, bool $fromRememberMe): void
    {
        $loginLog = new LoginLog();
        $loginLog->setUser($user);
        $loginLog->setFromRememberMe($fromRememberMe);

        $this->persistUserLoginLog($loginLog, $request);

        $user->setLastLoginTime(new CarbonImmutable());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function logUserLoginFailed(Request $request, ?string $email): void
    {
        $loginLog = new LoginLogFailed();
        $loginLog->setEmail($email ?? '');
        $this->persistUserLoginLog($loginLog, $request);
    }

    public function createEmailConfirmationLink(User $user, EmailConfirmationType $type): string
    {
        $uuid = Uuid::v7();
        $userId = $user->getId();
        $userEmail = $user->getEmail();

        $hashPayload = implode('|', [$userId, $userEmail, $type->value, $uuid]);
        $hash = hash_hmac('sha256', $hashPayload, $this->appSecret);

        $path = match ($type) {
            EmailConfirmationType::VERIFY_EMAIL => 'dockontrol_verify_email',
            EmailConfirmationType::REQUEST_ACCOUNT_DELETION => 'dockontrol_verify_account_deletion_request',
            default => throw new InvalidArgumentException('Invalid e-mail configuration type'),
        };

        return $this->urlGenerator->generate($path, [
            'id' => $userId,
            'uuid' => $uuid,
            'hash' => $hash,
            'type' => $type->value,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function processVerifyEmailLink(User $user, string $uuid, string $hash): void
    {
        $this->validateEmailRequest($user, $uuid, $hash, 'verify_email');

        $user->setEmailVerified(true);
        $user->setEmailVerifiedTime(CarbonImmutable::now());

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function processAccountDeletionRequest(
        User $user,
        string $uuid,
        string $hash,
    ): void {
        $this->validateEmailRequest($user, $uuid, $hash, 'request_account_deletion');
        $this->createAccountDeletionRequestAndDisableAccount($user);
    }

    /**
     * @throws Exception
     */
    public function verifyEmailChangeHash(string $hash, bool $isOldHash): void
    {
        $this->entityManager->beginTransaction();

        try {
            if (!Uuid::isValid($hash)) {
                throw new InvalidArgumentException('Invalid UUID provided.');
            }

            $uuid = UuidV7::fromString($hash);

            if ($isOldHash) {
                $emailChangeLog = $this->emailChangeLogRepository->findOneBy(['oldEmailConfirmHash' => $uuid]);
            } else {
                $emailChangeLog = $this->emailChangeLogRepository->findOneBy(['newEmailConfirmHash' => $uuid]);
            }

            if (!$emailChangeLog instanceof EmailChangeLog) {
                throw new RuntimeException('No record found for the provided hash.');
            }

            $currentTime = CarbonImmutable::now();
            $timeElapsed = $currentTime->diffInHours($emailChangeLog->getTimeCreated());

            if ($timeElapsed > 1) {
                throw new RuntimeException('The verification link has expired.');
            }

            $user = $emailChangeLog->getUser();
            $this->entityManager->lock($emailChangeLog, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);

            if ($user->getEmail() !== $emailChangeLog->getOldEmail()) {
                throw new RuntimeException('The provided old user e-mail is invalid.');
            }

            if ($isOldHash) {
                $emailChangeLog->setOldEmailConfirmedTime($currentTime);
            } else {
                $emailChangeLog->setNewEmailConfirmedTime($currentTime);
            }

            if (!empty($emailChangeLog->getOldEmailConfirmedTime()) && !empty($emailChangeLog->getNewEmailConfirmedTime())) {
                /** @var non-empty-string $newEmail*/
                $newEmail = $emailChangeLog->getNewEmail();
                $user->setEmail($newEmail);
                $user->setEmailVerified(true);
                $user->setEmailVerifiedTime(CarbonImmutable::now());
                $this->entityManager->persist($user);
            }

            $this->entityManager->persist($emailChangeLog);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $e) {
            $this->entityManager->rollback();
            throw new RuntimeException('Could not verify e-mail change hash.', 0, $e);
        }
    }


    private function persistUserLoginLog(LoginLog|LoginLogFailed $loginLog, Request $request): void
    {
        $loginLog->setBrowser($request->headers->get('User-Agent') ?? '');
        $loginLog->setPlatform('');
        $loginLog->setTime(new CarbonImmutable());
        $loginLog->setIp($request->getClientIp() ?? '');
        $this->entityManager->persist($loginLog);
        $this->entityManager->flush();
    }

    private function validateEmailRequest(User $user, string $uuid, string $hash, string $type): void
    {
        if (!Uuid::isValid($uuid)) {
            throw new InvalidArgumentException($this->translator->trans('dockontrol.security.email_confirmation.messages.invalid_uuid'));
        }

        $uuidV7 = UuidV7::fromString($uuid);
        $timestamp = $uuidV7->getDateTime()->getTimestamp();
        $currentTimestamp = CarbonImmutable::now()->getTimestamp();

        if (($currentTimestamp - $timestamp) > $this->gracePeriod) {
            throw new RuntimeException($this->translator->trans('dockontrol.security.email_confirmation.messages.expired_link'));
        }

        $userEmail = $user->getEmail();
        $hashPayload = implode('|', [$user->getId(), $userEmail, $type, $uuid]);
        $expectedHash = hash_hmac('sha256', $hashPayload, $this->appSecret);

        if (!hash_equals($expectedHash, $hash)) {
            throw new RuntimeException($this->translator->trans('dockontrol.security.email_confirmation.messages.invalid_link'));
        }
    }

    private function createAccountDeletionRequestAndDisableAccount(User $user): void
    {
        $this->entityManager->beginTransaction();

        try {
            $this->entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);

            $checkForExistingRequest = $this->userDeletionRequestRepository->findOneBy(['user' => $user]);
            if ($checkForExistingRequest instanceof UserDeletionRequest) {
                throw new RuntimeException('account deletion request already created');
            }

            $user->setEnabled(false);
            $accountDeletionRequest = new UserDeletionRequest();
            $accountDeletionRequest->setTime(CarbonImmutable::now());
            $accountDeletionRequest->setUser($user);

            $this->entityManager->persist($accountDeletionRequest);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $e) {
            $this->entityManager->rollback();
            throw new RuntimeException($e->getMessage());
        }
    }
}
