<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\DockontrolNode;
use App\Entity\Enum\ConfigName;
use App\Entity\Enum\DockontrolNodeStatus;
use App\Entity\Log\EmailChangeLog;
use App\Entity\Log\EmailLog;
use App\Entity\User;
use App\Exception\EmailException;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class MailerHelper
{
    public function __construct(
        private Environment $twig,
        private UserHelper $userHelper,
        private EntityManagerInterface $entityManager,
        private ConfigHelper $configHelper,
        private SecurityHelper $securityHelper,
    ) {
    }

    /**
     * @throws EmailException
     */
    public function sendEmail(string $email, string $subject, string $body): void
    {
        $emailConfigNames = [
            ConfigName::EMAIL_HOST,
            ConfigName::EMAIL_PORT,
            ConfigName::EMAIL_AUTHENTICATION_EMAIL,
            ConfigName::EMAIL_AUTHENTICATION_PASSWORD,
            ConfigName::EMAIL_SENDER_MAIL,
            ConfigName::EMAIL_USE_TLS,
            ConfigName::EMAIL_IGNORE_SSL_ERROR,
        ];

        $emailConfigs = $this->configHelper->getMultipleConfigValues($emailConfigNames);

        if (!empty($emailConfigs)) {
            $dsn = 'smtp://' . $emailConfigs[ConfigName::EMAIL_AUTHENTICATION_EMAIL->value] . ':' .
                $emailConfigs[ConfigName::EMAIL_AUTHENTICATION_PASSWORD->value] . '@' .
                $emailConfigs[ConfigName::EMAIL_HOST->value] . ':' .
                $emailConfigs[ConfigName::EMAIL_PORT->value] . '?verify_peer' . $emailConfigs[ConfigName::EMAIL_IGNORE_SSL_ERROR->value];
        } else {
            $dsn = 'smtp://localhost';
        }

        $transport = Transport::fromDsn($dsn);

        $fromMail = $emailConfigs[ConfigName::EMAIL_SENDER_MAIL->value];

        if (empty($fromMail)) {
            throw new EmailException('From mail can not be null.');
        }

        $mail = (new Email())
            ->from($fromMail)
            ->to($email)
            ->subject($subject)
            ->html($body);
        $mailer = new Mailer($transport);
        try {
            $mailer->send($mail);
            $this->saveEmailLog($email, $subject);
        } catch (TransportExceptionInterface $e) {
            throw new EmailException('Failed to send e-mail: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws EmailException
     */
    public function sendResetPasswordMail(
        User $user,
        string $ip,
        string $browser,
        CarbonImmutable $requestedTime,
    ): void {
        try {
            $currentDateTime = CarbonImmutable::now();
            if (!empty($user->getResetPasswordToken()) && !empty($user->getResetPasswordTokenTimeExpires()) && $user->getResetPasswordTokenTimeExpires() > $currentDateTime && !$this->checkIfEmailCanBeSentToUser($user)) {
                throw new EmailException('You have already requested a reset password e-mail. Please check your e-mail or try again soon.');
            }

            $token = Uuid::v7();
            $htmlContent = $this->twig->render('email/forgot_password_email.html.twig', [
                'token' => $token,
                'user' => $user,
                'ipAddress' => $ip,
                'browserInfo' => $browser,
                'requestedTime' => $requestedTime,
            ]);
            $this->sendEmailForUser($user, 'DOCKontrol | Reset password', $htmlContent);
            $this->userHelper->updateResetPasswordToken($user, $token);
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            throw new EmailException('Failed to generate reset password page, ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws EmailException
     */
    public function sendDockontrolNodeStatusChangeMail(
        User $user,
        DockontrolNode $dockontrolNode,
        DockontrolNodeStatus $oldStatus,
        DockontrolNodeStatus $currentStatus,
    ): void {
        try {
            $htmlContent = $this->twig->render('email/node_status_change_email.html.twig', [
                'user' => $user,
                'dockontrolNode' => $dockontrolNode,
                'oldStatus' => $oldStatus,
                'currentStatus' => $currentStatus,
            ]);
            $subject = 'DOCKontrol Node ' . $dockontrolNode->getName() . ' - ' . $currentStatus->getReadable();
            $this->sendEmail($user->getEmail(), $subject, $htmlContent);
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            throw new EmailException('Failed to send dockontrol node status change mail: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws EmailException
     */
    public function sendVerificationEmail(User $user): void
    {
        try {
            if ($user->isEmailVerified() && !empty($user->getEmailVerifiedTime())) {
                throw new EmailException('Your e-mail is already verified.');
            }

            if (!$this->checkIfEmailCanBeSentToUser($user)) {
                throw new EmailException('You have already requested a verification e-mail.');
            }

            $verifyEmailURL = $this->securityHelper->createEmailConfirmationLink($user, EmailConfirmationType::VERIFY_EMAIL);
            $htmlContent = $this->twig->render('email/user_verification_email.html.twig', [
                'user' => $user,
                'verifyEmailURL' => $verifyEmailURL,
            ]);
            $this->sendEmailForUser($user, 'DOCKontrol | Verify Email', $htmlContent);
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            throw new RuntimeException('Failed to generate reset password page, ' . $e->getMessage());
        }
    }

    /**
     * @throws EmailException
     */
    public function sendAccountDeletionRequestEmail(User $user): void
    {
        try {
            if (!$this->checkIfEmailCanBeSentToUser($user)) {
                throw new EmailException('You have already requested a account deletion confirmation e-mail.');
            }

            $verifyEmailURL = $this->securityHelper->createEmailConfirmationLink($user, EmailConfirmationType::REQUEST_ACCOUNT_DELETION);
            $htmlContent = $this->twig->render('email/user_account_deletion_request.html.twig', [
                'user' => $user,
                'confirmDeletionURL' => $verifyEmailURL,
            ]);
            $this->sendEmailForUser($user, 'DOCKontrol | Account Deletion Request', $htmlContent);
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            throw new RuntimeException('Failed to generate reset password page, ' . $e->getMessage());
        }
    }

    /**
     * @throws EmailException
     */
    public function sendEmailChangeConfirmation(EmailChangeLog $emailChangeLog, string $ip, string $browser): void
    {
        try {
            $user = $emailChangeLog->getUser();
            if (!$this->checkIfEmailCanBeSentToUser($user)) {
                throw new EmailException('You have already requested a verification e-mail.');
            }

            $templateData = [
                'user' => $user,
                'oldEmail' => $emailChangeLog->getOldEmail(),
                'newEmail' => $emailChangeLog->getNewEmail(),
                'browserInfo' => $browser,
                'ipAddress' => $ip,
                'requestedTime' => $emailChangeLog->getTimeCreated(),
            ];
            $emails = [];

            if ($user->isEmailVerified()) {
                $emails[] = [
                    'recipient' => $emailChangeLog->getOldEmail(),
                    'hash' => $emailChangeLog->getOldEmailConfirmHash(),
                    'oldOrNew' => 'old',
                ];
            }

            $emails[] = [
                'recipient' => $emailChangeLog->getNewEmail(),
                'hash' => $emailChangeLog->getNewEmailConfirmHash(),
                'oldOrNew' => 'new',
            ];

            foreach ($emails as $email) {
                $templateData['hash'] = $email['hash'];
                $templateData['oldOrNew'] = $email['oldOrNew'];
                $emailContent = $this->twig->render('email/change_email_email.html.twig', $templateData);
                $this->sendEmail($email['recipient'], 'DOCKontrol | Change Email', $emailContent);
            }
            $this->userHelper->updateLastEmailSentTimeForUser($user);
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            throw new EmailException(
                'Failed to send e-mail change confirmation: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function checkIfEmailCanBeSentToUser(User $user): bool
    {
        $lastEmailSentTime = $user->getLastEmailSentTime();
        $currentDateTime = CarbonImmutable::now();

        if ($lastEmailSentTime === null) {
            return true;
        }

        return $lastEmailSentTime->diffInMinutes($currentDateTime) >= 5;
    }

    private function saveEmailLog(string $email, string $subject): void
    {
        $emailLog = new EmailLog();
        $emailLog->setEmail($email);
        $emailLog->setSubject($subject);

        $this->entityManager->persist($emailLog);
        $this->entityManager->flush();
    }

    /**
     * @throws EmailException
     */
    private function sendEmailForUser(User $user, string $subject, string $htmlContent): void
    {
        $this->sendEmail($user->getEmail(), $subject, $htmlContent);
        $this->userHelper->updateLastEmailSentTimeForUser($user);
    }
}
