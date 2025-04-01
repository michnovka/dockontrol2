<?php

declare(strict_types=1);

namespace App\Entity\Enum;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ExtrasTrait;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;
use InvalidArgumentException;

enum ConfigName: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;
    use ExtrasTrait;

    #[EnumCase('Logs lifetime - Failed logins (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case LOGIN_LOGS_FAILED_TIME_LIFE_DAYS = 'login_logs_failed_timelife_days';

    #[EnumCase('Logs lifetime - Successful logins (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case LOGIN_LOGS_TIME_LIFE_DAYS = 'login_logs_timelife_days';

    #[EnumCase('Logs lifetime - Camera access (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case CAMERA_LOGS_TIMELIFE_DAYS = 'camera_logs_timelife_days';

    #[EnumCase('Logs lifetime - Nuki access (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case NUKI_LOGS_TIMELIFE_DAYS = 'nuki_logs_timelife_days';

    #[EnumCase('Logs lifetime - E-mail (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case EMAIL_LOGS_TIMELIFE_DAYS = 'email_logs_timelife_days';

    #[EnumCase('Logs lifetime - Cron (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case CRON_LOGS_TIMELIFE_DAYS = 'cron_logs_timelife_days';

    #[EnumCase('Logs lifetime - Admin actions (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case ADMIN_ACTION_LOGS_TIMELIFE_DAYS = 'admin_action_logs_timelife_days';

    #[EnumCase('Clear WebAuthn unused registration after (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case WEBAUTHN_REGISTRATIONS_UNUSED_TIMELIFE_DAYS = 'webauthn_registrations_unused_timelife_days';

    #[EnumCase('Logs lifetime - API calls (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case API_CALL_LOGS_TIMELIFE_DAYS = 'api_call_logs_timelife_days';

    #[EnumCase('Logs lifetime - API failed calls (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case FAILED_API_CALL_LOGS_TIMELIFE_DAYS = 'failed_api_call_logs_timelife_days';

    #[EnumCase('CRON - Last camera cache update time', extras: ['config_type' => ConfigType::DATETIME, 'read_only' => true, 'config_group' => ConfigGroup::LOGS])]
    case LAST_CRON_UPDATE_CAMERA_CACHE_TIME = 'last_cron_update_camera_cache_time';

    #[EnumCase('Logs lifetime - E-mail change (days)', extras: ['config_type' => ConfigType::INT, 'default' => 365, 'config_group' => ConfigGroup::LOGS])]
    case EMAIL_CHANGE_LOGS_TIMELIFE_DAYS = 'email_change_logs_timelife_days';

    #[EnumCase('DOCKontrol node issue admin notified', extras: ['config_type' => ConfigType::BOOLEAN, 'default' => false, 'config_group' => ConfigGroup::GENERAL])]
    case DOCKONTROL_NODE_ISSUE_ADMIN_NOTIFIED = 'dockontrol_node_issue_admin_notified';

    #[EnumCase('E-mail - Host', extras: ['config_type' => ConfigType::STRING, 'default' => null, 'config_group' => ConfigGroup::EMAIL])]
    case EMAIL_HOST = 'email_host';

    #[EnumCase('E-mail - Port', extras: ['config_type' => ConfigType::INT, 'default' => null, 'config_group' => ConfigGroup::EMAIL])]
    case EMAIL_PORT = 'email_port';

    #[EnumCase('E-mail - Sender E-mail', extras: ['config_type' => ConfigType::STRING, 'default' => null, 'config_group' => ConfigGroup::EMAIL])]
    case EMAIL_SENDER_MAIL = 'email_sender_mail';

    #[EnumCase('E-mail - Authentication mail', extras: ['config_type' => ConfigType::STRING, 'default' => null, 'config_group' => ConfigGroup::EMAIL])]
    case EMAIL_AUTHENTICATION_EMAIL = 'email_authentication_email';

    #[EnumCase('E-mail - Authentication password', extras: ['config_type' => ConfigType::SECRET, 'default' => null, 'config_group' => ConfigGroup::EMAIL])]
    case EMAIL_AUTHENTICATION_PASSWORD = 'email_authentication_password';

    #[EnumCase('E-mail - Use TLS', extras: ['config_type' => ConfigType::BOOLEAN, 'default' => false, 'config_group' => ConfigGroup::EMAIL])]
    case EMAIL_USE_TLS = 'email_use_tls';

    #[EnumCase('E-mail - Ignore SSL error', extras: ['config_type' => ConfigType::BOOLEAN, 'default' => false, 'config_group' => ConfigGroup::EMAIL])]
    case EMAIL_IGNORE_SSL_ERROR = 'email_ignore_ssl_error';

    #[EnumCase('Logs lifetime - expired guest passes (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case EXPIRED_GUEST_PASS_TIMELIFE_DAYS = 'expired_guest_pass_timelife_days';

    #[EnumCase('Disable accounts if not used for days', extras: ['config_type' => ConfigType::INT, 'default' => 365, 'config_group' => ConfigGroup::GENERAL])]
    case DISABLE_ACCOUNTS_IF_NOT_USED_FOR_DAYS = 'disable_accounts_if_not_used_for_days';

    #[EnumCase('Action Queue lifetime (days)', extras: ['config_type' => ConfigType::INT, 'default' => 365, 'config_group' => ConfigGroup::LOGS])]
    case ACTION_QUEUE_TIMELIFE_DAYS = 'action_queue_timelife_days';

    #[EnumCase('Expired Announcements lifetime (days)', extras: ['config_type' => ConfigType::INT, 'default' => 30, 'config_group' => ConfigGroup::LOGS])]
    case EXPIRED_ANNOUNCEMENTS_LIFETIME_DAYS = 'expired_announcements_lifetime_days';

    /**
     * @throws InvalidArgumentException
     */
    public function getConfigType(): ConfigType
    {
        return $this->getExtra('config_type', true);
    }

    public function getDefault(): mixed
    {
        return $this->getExtra('default');
    }

    public function isReadOnly(): bool
    {
        return $this->getExtra('read_only') ?? false;
    }

    public function getConfigGroup(): ConfigGroup
    {
        return $this->getExtra('config_group', true);
    }
}
