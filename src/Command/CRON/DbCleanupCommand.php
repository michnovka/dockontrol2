<?php

declare(strict_types=1);

namespace App\Command\CRON;

use App\Console\LoggableIO;
use App\Entity\Enum\ConfigName;
use App\Entity\Enum\CronType;
use App\Helper\ConfigHelper;
use App\Helper\CronHelper;
use App\Helper\UserHelper;
use App\Repository\ActionQueueRepository;
use App\Repository\AnnouncementRepository;
use App\Repository\GuestRepository;
use App\Repository\Log\ApiCallFailedLog\ApiCallFailedLogRepository;
use App\Repository\Log\ApiCallLog\LegacyAPICallLogRepository;
use App\Repository\Log\CameraLogRepository;
use App\Repository\Log\CronLogRepository;
use App\Repository\Log\EmailChangeLogRepository;
use App\Repository\Log\EmailLogRepository;
use App\Repository\Log\LoginLogFailedRepository;
use App\Repository\Log\LoginLogRepository;
use App\Repository\Log\NukiLogRepository;
use App\Repository\SignupCodeRepository;
use App\Repository\WebauthnRegistrationRepository;
use Carbon\CarbonImmutable;
use Override;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'cron:db-cleanup',
    description: 'This command will clean up the database.',
)]
class DbCleanupCommand extends Command
{
    private LoggableIO $io;

    public function __construct(
        private readonly LoginLogRepository $loginLogRepository,
        private readonly LoginLogFailedRepository $loginLogFailedRepository,
        private readonly CameraLogRepository $cameraLogRepository,
        private readonly NukiLogRepository $nukiLogRepository,
        private readonly WebauthnRegistrationRepository $webauthnRegistrationRepository,
        private readonly SignupCodeRepository $signupCodeRepository,
        private readonly ConfigHelper $configHelper,
        private readonly LegacyAPICallLogRepository $legacyAPICallLogRepository,
        private readonly ApiCallFailedLogRepository $apiCallFailedLogRepository,
        private readonly EmailLogRepository $emailLogRepository,
        private readonly CronLogRepository $cronLogRepository,
        private readonly CronHelper $cronHelper,
        private readonly EmailChangeLogRepository $emailChangeLogRepository,
        private readonly GuestRepository $guestRepository,
        private readonly UserHelper $userHelper,
        private readonly ActionQueueRepository $actionQueueRepository,
        private readonly AnnouncementRepository $announcementRepository,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $success = false;
        $this->io = new LoggableIO($input, $output);
        $startTime = CarbonImmutable::now();

        $this->io->title('Cleaning up database.');

        try {
            $this->clearLoginLogs();
            $this->clearLoginFailLogs();
            $this->clearCameraLogs();
            $this->clearNukiLogs();
            $this->clearWebAuthnRegistrations();
            $this->clearSignupCodes();
            $this->clearApiCallLogs();
            $this->clearFailedApiCallLogs();
            $this->clearEmailLogs();
            $this->clearCronLog();
            $this->clearEmailChangeLog();
            $this->clearExpiredGuestPasses();
            $this->disableAccountsIfNotUsed();
            $this->clearActionQueue();
            $this->clearExpiredAnnouncements();
            $success = true;

            $this->io->success('All Done');
        } catch (Throwable $e) {
            $this->io->error('Failed to clean up database,' . $e->getMessage());
        } finally {
            $this->cronHelper->addLog(CronType::DB_CLEANUP, $startTime, CarbonImmutable::now(), $this->io->getOutput(), success: $success);
        }

        if (!$success) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function clearLoginLogs(): void
    {
        $configName = ConfigName::LOGIN_LOGS_TIME_LIFE_DAYS;
        $clearLoginLogIntervalDays = $this->configHelper->getConfigValue($configName);
        if (!is_int($clearLoginLogIntervalDays)) {
            throw new RuntimeException("Invalid config for $configName->name");
        }

        $this->io->text('Clearing Login Logs for the past ' . $clearLoginLogIntervalDays . ' days.');

        $this->loginLogRepository->cleanuplogs($clearLoginLogIntervalDays);
        $this->io->success('Login logs cleared.');
    }

    protected function clearLoginFailLogs(): void
    {
        $configName = ConfigName::LOGIN_LOGS_FAILED_TIME_LIFE_DAYS;
        $clearLoginLogFailedIntervalTime = $this->configHelper->getConfigValue($configName);
        if (!is_int($clearLoginLogFailedIntervalTime)) {
            throw new RuntimeException("Invalid config for $configName->name");
        }
        $this->io->text('Clearing login failed logs for the past ' . $clearLoginLogFailedIntervalTime . ' days.');
        $this->loginLogFailedRepository->cleanupLogs($clearLoginLogFailedIntervalTime);
        $this->io->success('Login failed logs cleared.');
    }

    protected function clearCameraLogs(): void
    {
        $configName = ConfigName::CAMERA_LOGS_TIMELIFE_DAYS;
        $clearCameraLogIntervalDays = $this->configHelper->getConfigValue($configName);

        if (!is_int($clearCameraLogIntervalDays)) {
            throw new RuntimeException("Invalid config for $configName->name");
        }
        $this->io->text('Clearing camera logs for the past ' . $clearCameraLogIntervalDays . ' days.');
        $this->cameraLogRepository->cleanupLogs($clearCameraLogIntervalDays);
        $this->io->success('Camera logs cleared.');
    }

    protected function clearNukiLogs(): void
    {
        $configName = ConfigName::NUKI_LOGS_TIMELIFE_DAYS;
        $clearNukiLogIntervalDays = $this->configHelper->getConfigValue($configName);
        if (!is_int($clearNukiLogIntervalDays)) {
            throw new RuntimeException("Invalid config for $configName->name");
        }
        $this->io->text('Clearing nuki logs for the past ' . $clearNukiLogIntervalDays . ' days.');
        $this->nukiLogRepository->cleanupLogs($clearNukiLogIntervalDays);
        $this->io->success('Nuki logs cleared.');
    }

    protected function clearWebAuthnRegistrations(): void
    {
        $configName = ConfigName::WEBAUTHN_REGISTRATIONS_UNUSED_TIMELIFE_DAYS;
        $clearWebAuthnRegistrationsDays = $this->configHelper->getConfigValue($configName);

        if (!is_int($clearWebAuthnRegistrationsDays)) {
            throw new RuntimeException("Invalid config for $configName->name");
        }
        $this->io->text('Clearing unused webauthn unused registration for the past ' . $clearWebAuthnRegistrationsDays . ' days.');
        $this->webauthnRegistrationRepository->cleanupRegistrations($clearWebAuthnRegistrationsDays);
        $this->io->success('WebAuthn unused registration cleared.');
    }

    protected function clearSignupCodes(): void
    {
        $this->io->text('Clearing expired signup codes.');
        $this->signupCodeRepository->cleanupSignupCodes();
        $this->io->success('Expired signup codes cleared.');
    }

    protected function clearApiCallLogs(): void
    {
        $configName = ConfigName::API_CALL_LOGS_TIMELIFE_DAYS;
        $clearApiLogIntervalDays = $this->configHelper->getConfigValue($configName);

        if (!is_int($clearApiLogIntervalDays)) {
            throw new RuntimeException("Invalid config for $configName->name");
        }
        $this->io->text('Clearing API call logs for the past ' . $clearApiLogIntervalDays . ' days.');
        $this->legacyAPICallLogRepository->cleanupApiCallLogs($clearApiLogIntervalDays);
        $this->io->success('Api call logs cleared.');
    }

    protected function clearFailedApiCallLogs(): void
    {
        $configName = ConfigName::FAILED_API_CALL_LOGS_TIMELIFE_DAYS;
        $clearFailedApiLogIntervalDays = $this->configHelper->getConfigValue($configName);

        if (!is_int($clearFailedApiLogIntervalDays)) {
            throw new RuntimeException("Invalid config for $configName->name");
        }
        $this->io->text('Clearing failed API call logs for the past ' . $clearFailedApiLogIntervalDays . ' days.');
        $this->apiCallFailedLogRepository->cleanupFailedApiCallLogs($clearFailedApiLogIntervalDays);
        $this->io->success('Failed API call logs cleared.');
    }

    protected function clearEmailLogs(): void
    {
        $configName = ConfigName::EMAIL_LOGS_TIMELIFE_DAYS;
        $clearEmailLogIntervalDays = $this->configHelper->getConfigValue($configName);

        if (!is_int($clearEmailLogIntervalDays)) {
            throw new RuntimeException("Invalid config for $configName->name");
        }
        $this->io->text('Clearing e-mail logs for the past ' . $clearEmailLogIntervalDays . ' days.');
        $this->emailLogRepository->cleanupLogs($clearEmailLogIntervalDays);
        $this->io->success('Email logs cleared.');
    }

    protected function clearCronLog(): void
    {
        $configName = ConfigName::CRON_LOGS_TIMELIFE_DAYS;
        $clearCronLogIntervalDays = $this->configHelper->getConfigValue($configName);

        if (!is_int($clearCronLogIntervalDays)) {
            throw new RuntimeException("Invalid config for $configName->name");
        }
        $this->io->text('Clearing cron logs for the past ' . $clearCronLogIntervalDays . ' days.');
        $this->cronLogRepository->cleanupLogs($clearCronLogIntervalDays);
        $this->io->success('Cron logs cleared.');
    }

    protected function clearEmailChangeLog(): void
    {
        $configName = ConfigName::EMAIL_CHANGE_LOGS_TIMELIFE_DAYS;
        $clearCronLogIntervalDays = $this->configHelper->getConfigValue($configName);

        if (!is_int($clearCronLogIntervalDays)) {
            throw new RuntimeException("Invalid config for $configName->name");
        }
        $this->io->text('Clearing Email Change Logs for the past ' . $clearCronLogIntervalDays . ' days.');
        $this->emailChangeLogRepository->cleanupLogs($clearCronLogIntervalDays);
        $this->io->success('Email change logs cleared.');
    }

    protected function clearExpiredGuestPasses(): void
    {
        $configName = ConfigName::EXPIRED_GUEST_PASS_TIMELIFE_DAYS;
        $clearCronLogIntervalDays = $this->configHelper->getConfigValue($configName);

        if (!is_int($clearCronLogIntervalDays)) {
            throw new RuntimeException("invalid config for $configName->name");
        }
        $this->io->text('Clearing guest passes that expired more than ' . $clearCronLogIntervalDays . ' days ago.');
        $this->guestRepository->cleanupGuestPasses($clearCronLogIntervalDays);
        $this->io->success('Guest passes expired more than ' . $clearCronLogIntervalDays . ' days ago have been cleared.');
    }

    protected function disableAccountsIfNotUsed(): void
    {
        $configName = ConfigName::DISABLE_ACCOUNTS_IF_NOT_USED_FOR_DAYS;
        $inactiveDays = $this->configHelper->getConfigValue($configName);

        if (!is_int($inactiveDays)) {
            throw new RuntimeException("Invalid config value for {$configName->name}. Expected an integer.");
        }

        $this->io->text("Disabling user accounts that have been inactive for more than {$inactiveDays} days.");
        $this->userHelper->disableAccountsIfNotUsedForDays($inactiveDays);
        $this->io->success("User accounts inactive for more than {$inactiveDays} days have been disabled.");
    }

    protected function clearActionQueue(): void
    {
        $configName = ConfigName::ACTION_QUEUE_TIMELIFE_DAYS;
        $clearActionQueueIntervalDays = $this->configHelper->getConfigValue($configName);

        if (!is_int($clearActionQueueIntervalDays)) {
            throw new RuntimeException("Invalid config value for {$configName->name}. Expected an integer.");
        }

        $this->io->text("Clearing action queues for the past  {$clearActionQueueIntervalDays} days.");
        $this->actionQueueRepository->cleanupActionQueue($clearActionQueueIntervalDays);
        $this->io->success("Action queue cleared.");
    }

    protected function clearExpiredAnnouncements(): void
    {
        $configName = ConfigName::EXPIRED_ANNOUNCEMENTS_LIFETIME_DAYS;
        $clearExpiredAnnouncementsIntervalDays = $this->configHelper->getConfigValue($configName);

        if (!is_int($clearExpiredAnnouncementsIntervalDays)) {
            throw new RuntimeException("Invalid config value for {$configName->name}. Expected an integer.");
        }

        $this->io->text("Clearing action queues for the past  {$clearExpiredAnnouncementsIntervalDays} days.");
        $this->announcementRepository->cleanupAnnouncements($clearExpiredAnnouncementsIntervalDays);
        $this->io->success("Expired announcements cleared.");
    }
}
