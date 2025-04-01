<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Enum\UserRole;
use App\Entity\Log\EmailChangeLog;
use App\Entity\Permission;
use App\Entity\User;
use App\Entity\UserDeletionRequest;
use App\Repository\UserRepository;
use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

class UserHelper
{
    /**
     * @var array<int,array{user: array<string>, admin: array<string>}> $cachedPermissions key here is user ID
     */
    private array $cachedPermissions = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    public function saveUser(
        User $user,
        #[SensitiveParameter] ?string $plainPassword = null,
        ?User $createdBy = null,
    ): void {
        if ($createdBy) {
            $user->setCreatedBy($createdBy);
        }

        if (!empty($plainPassword)) {
            $this->updateUserPassword($user, $plainPassword);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function updateUserPassword(User $user, #[SensitiveParameter] string $plainPassword): User
    {
        $encodedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($encodedPassword);
        $user->setPasswordSetTime(CarbonImmutable::now());
        return $user;
    }

    public function deleteUser(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function resetPassword(User $user, #[SensitiveParameter] string $plainPassword): void
    {
        $user = $this->updateUserPassword($user, $plainPassword);
        $user->setResetPasswordToken(null);
        $user->setResetPasswordTokenTimeCreated(null);
        $user->setResetPasswordTokenTimeExpires(null);

        $this->saveUser($user);
    }

    public function updateResetPasswordToken(User $user, Uuid $token): void
    {
        $user->setResetPasswordToken($token);
        $user->setResetPasswordTokenTimeCreated(CarbonImmutable::now());
        $user->setResetPasswordTokenTimeExpires(CarbonImmutable::now()->addHours(24));

        $this->updateLastEmailSentTimeForUser($user, false);
        $this->saveUser($user);
    }

    public function enableCustomSorting(User $user): void
    {
        $user->setCustomSorting(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function disableCustomSorting(User $user): void
    {
        $user->setCustomSorting(false);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /***
     * @return Collection<string>
     */
    public function getCachedPermissions(User $user, bool $includeAdmin = false): Collection
    {
        $userID = $user->getId();

        if (empty($this->cachedPermissions[$userID])) {
            $this->cachedPermissions[$userID] = [
                'user' => [],
                'admin' => [],
            ];

            $userPermissions = $this->entityManager->createQueryBuilder()
                ->select('p.name')
                ->from(Permission::class, 'p')
                ->innerJoin('p.groups', 'g')
                ->where('g IN (:groups)')
                ->setParameter('groups', $user->getGroups())
                ->getQuery()
                ->getSingleColumnResult();

            $this->cachedPermissions[$userID]['user'] = $userPermissions;
        }

        if ($user->isAdmin()) {
            if ($user->getRole() === UserRole::SUPER_ADMIN) {
                $adminPermissions = $this->entityManager->createQueryBuilder()
                    ->select('p.name')
                    ->from(Permission::class, 'p')
                    ->getQuery()
                    ->getSingleColumnResult();
            } elseif ($user->getRole() === UserRole::ADMIN) {
                $adminPermissions = $this->entityManager->createQueryBuilder()
                    ->select('p.name')
                    ->from(Permission::class, 'p')
                    ->innerJoin('p.buildings', 'b')
                    ->where('b IN (:adminBuildings)')
                    ->setParameter('adminBuildings', $user->getAdminBuildings())
                    ->groupBy('p.name')
                    ->getQuery()
                    ->getSingleColumnResult();
            } else {
                throw new InvalidArgumentException('Unexpected user role.');
            }

            $this->cachedPermissions[$userID]['admin'] = $adminPermissions;
        }

        $returnArray = $this->cachedPermissions[$userID]['user'];

        if ($includeAdmin && $user->isAdmin()) {
            $returnArray = array_unique(array_merge($returnArray, $this->cachedPermissions[$userID]['admin']));
        }
        return new ArrayCollection($returnArray);
    }

    public function requestEmailChange(
        User $user,
        string $oldEmail,
        string $newEmail,
    ): EmailChangeLog {
        $emailChangeLog = new EmailChangeLog();
        $emailChangeLog
            ->setUser($user)
            ->setOldEmail($oldEmail)
            ->setNewEmail($newEmail)
            ->setOldEmailConfirmHash(Uuid::v7())
            ->setNewEmailConfirmHash(Uuid::v7())
            ->setTimeCreated(CarbonImmutable::now());

        if (!$user->isEmailVerified()) {
            $emailChangeLog->setOldEmailConfirmedTime(CarbonImmutable::now());
        }

        $description = sprintf("requested email change from %s to %s, ", $oldEmail, $newEmail);
        $this->userActionLogHelper->addUserActionLog($description, $user);
        $this->entityManager->persist($emailChangeLog);
        $this->entityManager->flush();

        return $emailChangeLog;
    }

    public function updateLastEmailSentTimeForUser(User $user, bool $flush = true): void
    {
        $user->setLastEmailSentTime(CarbonImmutable::now());
        if ($flush) {
            $this->saveUser($user);
        }
    }

    public function updateEmailVerification(User $user, bool $isVerify): void
    {
        $user->setEmailVerified($isVerify);

        if ($isVerify) {
            $user->setEmailVerifiedTime(CarbonImmutable::now());
        } else {
            $user->setEmailVerifiedTime(null);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function updatePhoneVerification(User $user, bool $isVerify): void
    {
        $user->setPhoneVerified($isVerify);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function disableAccountsIfNotUsedForDays(int $intervalDays): void
    {
        $usersToDisable = $this->userRepository->getInactiveUsersToDisable($intervalDays);

        if (empty($usersToDisable)) {
            return;
        }

        foreach ($usersToDisable as $user) {
            $user->setEnabled(false);
            $description = sprintf(
                'User (%s) Account disabled due to inactivity for %d days.',
                $user->getEmail(),
                $intervalDays
            );
            $this->userActionLogHelper->addUserActionLog($description, flush: false);
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();
    }

    public function deleteAccountDeletionRequestAndEnableUserAccount(UserDeletionRequest $userDeletionRequest): void
    {
        $this->entityManager->beginTransaction();

        try {
            $user = $userDeletionRequest->getUser();
            $this->entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->lock($userDeletionRequest, LockMode::PESSIMISTIC_WRITE);

            $user->setEnabled(true);

            $this->entityManager->persist($user);
            $this->entityManager->remove($userDeletionRequest);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $e) {
            $this->entityManager->rollback();
            throw new RuntimeException('Could not delete user deletion request & re-enable user account.', 0, $e);
        }
    }
}
