<?php

declare(strict_types=1);

namespace App\Command\Maintenance;

use App\Entity\Enum\UserRole;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'maintenance:migrate-from-v1',
    description: 'Migrates a legacy database to the Symfony database schema'
)]
class MaintenanceMigrateFromV1Command extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Migrates a legacy database to the Symfony database schema.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migrating from Legacy Database');
        $connection = $this->entityManager->getConnection();
        $startTime = CarbonImmutable::now()->getTimestamp();

        try {
            $connection->executeStatement('ALTER TABLE action_queue DROP FOREIGN KEY queue_users_id_fk');
            $connection->executeStatement('ALTER TABLE action_queue DROP FOREIGN KEY action_queue_guests_id_fk');
            $connection->executeStatement('ALTER TABLE action_queue DROP FOREIGN KEY action_queue_actions_name_fk');
            $connection->executeStatement('UPDATE action_queue SET guest_id = null');
            $connection->executeStatement('TRUNCATE guests');
            $connection->executeStatement('ALTER TABLE action_queue CHANGE time_created time_created DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE time_start time_start DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DF47CC8C92 FOREIGN KEY (action) REFERENCES actions (name)');
            $connection->executeStatement('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DFA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DF9A4AA658 FOREIGN KEY (guest_id) REFERENCES guests (id)');
            $connection->executeStatement('ALTER TABLE actions DROP FOREIGN KEY actions_dockontrol_nodes_id_fk');
            $connection->executeStatement('ALTER TABLE actions CHANGE type type enum(\'openwebnet\', \'dockontrol_node_relay\', \'multi\') NOT NULL COMMENT \'(DC2Type:App\\\\Entity\\\\Enum\\\\ActionType)\'');
            $connection->executeStatement('ALTER TABLE actions ADD CONSTRAINT FK_548F1EFD0DE5EAE FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id)');
            $connection->executeStatement('ALTER TABLE admin_buildings DROP FOREIGN KEY admin_buildings_groups_id_fk');
            $connection->executeStatement('ALTER TABLE admin_buildings ADD id INT AUTO_INCREMENT NOT NULL, ADD PRIMARY KEY (id)');
            $connection->executeStatement('ALTER TABLE admin_buildings ADD CONSTRAINT FK_73A6B3956AF4DE41 FOREIGN KEY (admin_group_id) REFERENCES `groups` (id)');
            $connection->executeStatement('ALTER TABLE api_calls DROP FOREIGN KEY api_calls_users_id_fk');
            $connection->executeStatement('ALTER TABLE api_calls CHANGE time time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE api_calls ADD CONSTRAINT FK_FE36085FA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE api_calls_failed CHANGE time time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE buttons DROP FOREIGN KEY button_cameras_name_id_fk_4');
            $connection->executeStatement('ALTER TABLE buttons DROP FOREIGN KEY button_cameras_name_id_fk_3');
            $connection->executeStatement('ALTER TABLE buttons DROP FOREIGN KEY button_cameras_name_id_fk_2');
            $connection->executeStatement('ALTER TABLE buttons DROP FOREIGN KEY button_cameras_name_id_fk');
            $connection->executeStatement('ALTER TABLE buttons DROP FOREIGN KEY buttons_users_id_fk');
            $connection->executeStatement('ALTER TABLE buttons DROP FOREIGN KEY buttons_permissions_name_fk');
            $connection->executeStatement('ALTER TABLE buttons DROP FOREIGN KEY buttons_actions_name_fk');
            $connection->executeStatement('ALTER TABLE buttons CHANGE type type enum(\'gate\', \'entrance\', \'elevator\', \'multi\', \'custom\') DEFAULT \'entrance\' NOT NULL COMMENT \'(DC2Type:App\\\\Entity\\\\Enum\\\\ButtonType)\', CHANGE button_style button_style enum(\'basic\', \'blue\', \'red\') DEFAULT \'basic\' NOT NULL COMMENT \'(DC2Type:App\\\\Entity\\\\Enum\\\\ButtonStyle)\', CHANGE icon icon enum(\'building\', \'elevator\', \'entrance\', \'entrance_pedestrian\', \'garage\', \'gate\', \'nuki\', \'enter\', \'exit\') DEFAULT \'entrance\' NOT NULL COMMENT \'(DC2Type:App\\\\Entity\\\\Enum\\\\ButtonIcon)\'');
            $connection->executeStatement('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B647CC8C92 FOREIGN KEY (action) REFERENCES actions (name)');
            $connection->executeStatement('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B6E04992AA FOREIGN KEY (permission) REFERENCES permissions (name)');
            $connection->executeStatement('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B6A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B6F38D07D6 FOREIGN KEY (camera1) REFERENCES cameras (name_id)');
            $connection->executeStatement('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B66A84566C FOREIGN KEY (camera2) REFERENCES cameras (name_id)');
            $connection->executeStatement('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B61D8366FA FOREIGN KEY (camera3) REFERENCES cameras (name_id)');
            $connection->executeStatement('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B683E7F359 FOREIGN KEY (camera4) REFERENCES cameras (name_id)');
            $connection->executeStatement('ALTER TABLE camera_logs DROP FOREIGN KEY camera_log_users_id_fk');
            $connection->executeStatement('ALTER TABLE camera_logs DROP FOREIGN KEY camera_log_cameras_name_id_fk');
            $connection->executeStatement('ALTER TABLE camera_logs CHANGE time time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE camera_logs ADD CONSTRAINT FK_7A64BA10A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE camera_logs ADD CONSTRAINT FK_7A64BA10BCC5E5F2 FOREIGN KEY (camera_name_id) REFERENCES cameras (name_id)');
            $connection->executeStatement('ALTER TABLE cameras DROP FOREIGN KEY cameras_permissions_name_fk');
            $connection->executeStatement('ALTER TABLE cameras CHANGE last_fetched last_fetched DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE cameras ADD CONSTRAINT FK_6B5F276A15A370BF FOREIGN KEY (permission_required) REFERENCES permissions (name)');
            $connection->executeStatement('ALTER TABLE config CHANGE value value LONGTEXT NOT NULL');
            $connection->executeStatement('ALTER TABLE dockontrol_nodes CHANGE last_command_executed_time last_command_executed_time DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE status status enum(\'online\', \'pingable\', \'offline\', \'invalid_api_secret\') DEFAULT \'offline\' NOT NULL COMMENT \'(DC2Type:App\\\\Entity\\\\Enum\\\\DockontrolNodeStatus)\', CHANGE last_ping_time last_ping_time DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_monitor_check_time last_monitor_check_time DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE group_permission DROP FOREIGN KEY group_permission_permissions_name_fk');
            $connection->executeStatement('ALTER TABLE group_permission DROP FOREIGN KEY group_permission_groups_id_fk');
            $connection->executeStatement('DROP INDEX group_permission_group_id_permission_uindex ON group_permission');
            $connection->executeStatement('ALTER TABLE group_permission CHANGE permission permission VARCHAR(63) NOT NULL, ADD PRIMARY KEY (group_id, permission)');
            $connection->executeStatement('ALTER TABLE group_permission ADD CONSTRAINT FK_3784F318FE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id)');
            $connection->executeStatement('ALTER TABLE group_permission ADD CONSTRAINT FK_3784F318E04992AA FOREIGN KEY (permission) REFERENCES permissions (name)');
            $connection->executeStatement('ALTER TABLE group_permission RENAME INDEX group_permission_permissions_name_fk TO IDX_3784F318E04992AA');
            $connection->executeStatement('ALTER TABLE guests DROP FOREIGN KEY guests_users_id_fk');
            $connection->executeStatement('ALTER TABLE guests CHANGE expires expires DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE remaining_actions remaining_actions INT DEFAULT -1 NOT NULL COMMENT \'-1 unlimited 0 no actions left N actions left\'');
            $connection->executeStatement('ALTER TABLE guests ADD CONSTRAINT FK_4D11BCB2A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE login_logs DROP FOREIGN KEY login_logs_users_id_fk');
            $connection->executeStatement('ALTER TABLE login_logs CHANGE time time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE login_logs ADD CONSTRAINT FK_36FD4D19A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE login_logs_failed CHANGE time time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE nuki DROP FOREIGN KEY nuki_users_id_fk');
            $connection->executeStatement('ALTER TABLE nuki ADD CONSTRAINT FK_1AE09E07A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE nuki_logs DROP FOREIGN KEY nuki_logs_nuki_id_fk');
            $connection->executeStatement('ALTER TABLE nuki_logs CHANGE time time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE status status enum(\'ok\', \'incorrect_pin\', \'error\') DEFAULT \'ok\' NOT NULL COMMENT \'(DC2Type:App\\\\Entity\\\\Enum\\\\NukiStatus)\', CHANGE action action enum(\'lock\', \'unlock\', \'pin_check\') DEFAULT \'unlock\' NOT NULL COMMENT \'(DC2Type:App\\\\Entity\\\\Enum\\\\NukiAction)\'');
            $connection->executeStatement('ALTER TABLE nuki_logs ADD CONSTRAINT FK_5BDAD6BB2CC69643 FOREIGN KEY (nuki_id) REFERENCES nuki (id)');
            $connection->executeStatement('ALTER TABLE phone_control DROP FOREIGN KEY phone_control_users_id_fk');
            $connection->executeStatement('ALTER TABLE phone_control CHANGE time_added time_added DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE phone_control ADD CONSTRAINT FK_7D99A079A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE phone_control_log DROP FOREIGN KEY phone_control_log_users_id_fk');
            $connection->executeStatement('ALTER TABLE phone_control_log CHANGE time time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE phone_control_log ADD CONSTRAINT FK_832CFF9BA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE signup_codes DROP FOREIGN KEY signup_codes_users_id_fk');
            $connection->executeStatement('ALTER TABLE signup_codes CHANGE expires expires DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_time created_time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE signup_codes ADD CONSTRAINT FK_728DBB44642B8210 FOREIGN KEY (admin_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE users CHANGE created created DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_login_time last_login_time DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE button_press_type button_press_type enum(\'click\', \'hold\') DEFAULT \'hold\' NOT NULL COMMENT \'(DC2Type:App\\\\Entity\\\\Enum\\\\ButtonPressType)\'');
            $connection->executeStatement('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9DE12AB56 FOREIGN KEY (created_by) REFERENCES `users` (id)');
            $connection->executeStatement('CREATE INDEX IDX_1483A5E9DE12AB56 ON users (created_by)');
            $connection->executeStatement('ALTER TABLE user_group DROP FOREIGN KEY user_group_users_id_fk');
            $connection->executeStatement('ALTER TABLE user_group DROP FOREIGN KEY user_group_groups_id_fk');
            $connection->executeStatement('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
            $connection->executeStatement('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DFE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id) ON DELETE CASCADE');
            $connection->executeStatement('ALTER TABLE user_group RENAME INDEX user_group_groups_id_fk TO IDX_8F02BF9DFE54D947');
            $connection->executeStatement('ALTER TABLE webauthn_registrations DROP FOREIGN KEY webauthn_registrations_users_id_fk');
            $connection->executeStatement('ALTER TABLE webauthn_registrations CHANGE created_time created_time DATETIME(6) NOT NULL, CHANGE last_used_time last_used_time DATETIME(6) NOT NULL, CHANGE credentialId credential_id VARCHAR(255) NOT NULL');
            $connection->executeStatement('ALTER TABLE webauthn_registrations ADD CONSTRAINT FK_637C3C5FA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE users ADD role enum(\'ROLE_USER\', \'ROLE_ADMIN\', \'ROLE_SUPER_ADMIN\') DEFAULT \'ROLE_USER\' NOT NULL COMMENT \'(DC2Type:App\\\\Entity\\\\Enum\\\\UserRole)\'');

            $connection->executeStatement(
                "UPDATE users 
                INNER JOIN user_group ON users.id = user_group.user_id 
                INNER JOIN group_permission ON user_group.group_id = group_permission.group_id 
                SET role = ? 
                WHERE group_permission.permission = ?",
                [UserRole::ADMIN->value, 'admin']
            );

            $connection->executeStatement(
                "UPDATE users 
                INNER JOIN user_group ON users.id = user_group.user_id 
                INNER JOIN group_permission ON user_group.group_id = group_permission.group_id 
                SET role = ? 
                WHERE group_permission.permission = ?",
                [UserRole::SUPER_ADMIN->value, 'super_admin']
            );

            $connection->executeStatement("DELETE FROM group_permission WHERE group_id IN (SELECT id FROM `groups` WHERE name IN ('ADMIN', 'SUPER ADMIN'))");
            $connection->executeStatement("DELETE FROM user_group WHERE group_id IN (SELECT id FROM `groups` WHERE name IN ('ADMIN', 'SUPER ADMIN'))");
            $connection->executeStatement("DELETE FROM group_permission WHERE permission IN ('admin', 'super_admin')");
            $connection->executeStatement("DELETE FROM `groups` WHERE name IN ('ADMIN', 'SUPER ADMIN')");
            $connection->executeStatement("DELETE FROM permissions WHERE name IN ('admin', 'super_admin')");

            $connection->executeStatement("ALTER TABLE admin_buildings CHANGE building name varchar(31) NOT NULL, RENAME TO buildings");
            $connection->executeStatement("ALTER TABLE buildings ADD default_group_id int NULL, ADD INDEX IDX_9A51B6A7F810E909 (default_group_id)");
            $connection->executeStatement('ALTER TABLE buildings ADD CONSTRAINT FK_E16F61D4F810E909 FOREIGN KEY (default_group_id) REFERENCES `groups` (id)');
            $connection->executeStatement('CREATE TABLE user_admin_buildings (user_id INT NOT NULL, building_id INT NOT NULL, INDEX IDX_617ADF1AA76ED395 (user_id), INDEX IDX_617ADF1A4D2A7E12 (building_id), PRIMARY KEY(user_id, building_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
            $connection->executeStatement('ALTER TABLE user_admin_buildings ADD CONSTRAINT FK_49B90D67A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
            $connection->executeStatement('ALTER TABLE user_admin_buildings ADD CONSTRAINT FK_49B90D674D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id) ON DELETE CASCADE');
            $connection->executeStatement("INSERT IGNORE INTO user_admin_buildings(user_id, building_id)
                        SELECT
                           u.id, b.id
                        FROM
                            buildings b
                                INNER JOIN `groups` g ON g.id = b.admin_group_id
                                INNER JOIN user_group ug ON ug.group_id = g.id
                                INNER JOIN users u ON u.id = ug.user_id");
            $connection->executeStatement('ALTER TABLE buildings DROP FOREIGN KEY FK_73A6B3956AF4DE41');
            $connection->executeStatement('ALTER TABLE buildings DROP COLUMN admin_group_id');
            $connection->executeStatement('UPDATE buildings b INNER JOIN `groups` g ON g.name = b.name SET b.default_group_id = g.id');

            $connection->executeStatement('ALTER TABLE users ADD building_id INT DEFAULT NULL');
            $connection->executeStatement('ALTER TABLE users ADD CONSTRAINT FK_1483A5E94D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id)');
            $connection->executeStatement('CREATE INDEX IDX_1483A5E94D2A7E12 ON users (building_id)');
            $connection->executeStatement("UPDATE users SET building_id = ( SELECT id FROM buildings WHERE users.apartment LIKE CONCAT(buildings.name, '%') LIMIT 1 )");
            $connection->executeStatement('CREATE INDEX IDX_548F1EF8377B003 ON actions (cron_group)');
            $connection->executeStatement('CREATE TABLE cron_group (name VARCHAR(150) NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
            $connection->executeStatement('INSERT IGNORE INTO `cron_group` (`name`) SELECT DISTINCT cron_group FROM actions');
            $connection->executeStatement('ALTER TABLE actions MODIFY cron_group VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
            $connection->executeStatement('ALTER TABLE actions ADD CONSTRAINT FK_548F1EF8377B003 FOREIGN KEY (cron_group) REFERENCES cron_group (name)');
            $connection->executeStatement('ALTER TABLE actions CHANGE cron_group cron_group VARCHAR(150) NOT NULL');
            $connection->executeStatement('ALTER TABLE users DROP geolocation_enabled');
            $connection->executeStatement('ALTER TABLE guests CHANGE hash hash BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
            $connection->executeStatement('ALTER TABLE buttons DROP FOREIGN KEY FK_435C85B6A76ED395');
            $connection->executeStatement('DROP INDEX buttons_users_id_fk ON buttons');
            $connection->executeStatement('ALTER TABLE buttons DROP user_id');
            $connection->executeStatement('ALTER TABLE guests ADD created DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD enabled TINYINT(1) NOT NULL, ADD note VARCHAR(255) DEFAULT NULL');
            $connection->executeStatement('ALTER TABLE actions ADD action_payload JSON DEFAULT NULL');

            // Update action_payload for type "dockontrol_node_relay"
            $connection->executeStatement("
            UPDATE actions
            SET action_payload = JSON_OBJECT('type', 'relay', 'channel', channel)
            WHERE type = 'dockontrol_node_relay'
        ");

            // Update action_payload for type "openwebnet"
            $connection->executeStatement("
            UPDATE actions
            SET action_payload = JSON_OBJECT('type', 'openwebnet', 'channel', channel)
            WHERE type = 'openwebnet'
        ");

            // Drop the channel field
            $connection->executeStatement('ALTER TABLE actions DROP channel');

            $connection->executeStatement('ALTER TABLE buttons CHANGE action action VARCHAR(63) NOT NULL');

            // add new cron group called: general
            $connection->executeStatement('INSERT INTO `cron_group`(`name`) VALUES (\'general\')');

            $connection->executeStatement('CREATE TABLE car_enter_details (id INT AUTO_INCREMENT NOT NULL, building_id INT DEFAULT NULL, user_id INT DEFAULT NULL, action VARCHAR(63) NOT NULL, `order` INT NOT NULL, INDEX IDX_B76F13A44D2A7E12 (building_id), INDEX IDX_B76F13A4A76ED395 (user_id), INDEX IDX_B76F13A447CC8C92 (action), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
            $connection->executeStatement('ALTER TABLE car_enter_details ADD CONSTRAINT FK_B76F13A44D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id)');
            $connection->executeStatement('ALTER TABLE car_enter_details ADD CONSTRAINT FK_B76F13A4A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE car_enter_details ADD CONSTRAINT FK_B76F13A447CC8C92 FOREIGN KEY (action) REFERENCES actions (name)');

            $connection->executeStatement("
            INSERT INTO car_enter_details (building_id, action, `order`)
            SELECT b.id, a.name, 1
            FROM buildings b
            JOIN actions a ON a.name = CASE 
                WHEN b.name LIKE 'Z1.%' THEN 'open_gate_rw1'
                WHEN b.name LIKE 'Z2.%' THEN 'open_gate_rw1'
                WHEN b.name LIKE 'Z3.%' THEN 'open_gate_rw1'
                WHEN b.name LIKE 'Z7.%' THEN 'open_gate_rw3'
                WHEN b.name LIKE 'Z8.%' THEN 'open_gate_rw3'
                WHEN b.name LIKE 'Z9.%' THEN 'open_gate_rw3'
                ELSE NULL
            END
        ");

            $connection->executeStatement("
            INSERT INTO car_enter_details (building_id, action, `order`)
            SELECT b.id, a.name, 2
            FROM buildings b
            JOIN actions a ON a.name = CONCAT('open_garage_', LOWER(SUBSTRING_INDEX(b.name, '.', 1)))
        ");

            $connection->executeStatement('ALTER TABLE users DROP default_garage');

            $connection->executeStatement('ALTER TABLE users ADD custom_car_enter_details TINYINT(1) DEFAULT 0 NOT NULL');

            $connection->executeStatement('ALTER TABLE users ADD password_set_time DATETIME(6) NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE apartment apartment VARCHAR(63) DEFAULT NULL, CHANGE created created_time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

            $connection->executeStatement('UPDATE users SET apartment = NULL WHERE apartment=""');
            $connection->executeStatement('UPDATE users SET password_set_time = created_time');
            $connection->executeStatement('ALTER TABLE users MODIFY password_set_time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('TRUNCATE signup_codes');
            $connection->executeStatement('ALTER TABLE signup_codes ADD building_id INT NOT NULL, ADD new_user_id INT DEFAULT NULL, ADD used_time DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP signups_count, CHANGE hash hash BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE expires expires DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('ALTER TABLE signup_codes ADD CONSTRAINT FK_728DBB444D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id)');
            $connection->executeStatement('ALTER TABLE signup_codes ADD CONSTRAINT FK_728DBB447C2D807B FOREIGN KEY (new_user_id) REFERENCES `users` (id)');
            $connection->executeStatement('CREATE INDEX IDX_728DBB444D2A7E12 ON signup_codes (building_id)');
            $connection->executeStatement('CREATE INDEX IDX_728DBB447C2D807B ON signup_codes (new_user_id)');
            $connection->executeStatement('ALTER TABLE signup_codes RENAME INDEX signup_codes_users_id_fk TO IDX_728DBB44642B8210');

            // set all openwebnet types to dockontrol_node_relay, and we will set dockontrol_node_id=1
            $connection->executeStatement('UPDATE `actions` SET `type`=\'dockontrol_node_relay\',`dockontrol_node_id`= 1 WHERE type = \'openwebnet\'');

            $connection->executeStatement('ALTER TABLE actions CHANGE type type enum(\'dockontrol_node_relay\', \'multi\') NOT NULL COMMENT \'(DC2Type:App\\\\Entity\\\\Enum\\\\ActionType)\'');

            $connection->executeStatement('ALTER TABLE api_calls RENAME TO api_call_logs');
            $connection->executeStatement('ALTER TABLE api_calls_failed RENAME TO api_call_failed_logs');
            $connection->executeStatement('ALTER TABLE signup_codes ADD apartment VARCHAR(63) DEFAULT NULL, DROP apartment_mask');

            // drop api_secret, add new columns api_public_key, api_secret_key, wireguard_public_key, wireguard_private_key, port
            $connection->executeStatement('ALTER TABLE dockontrol_nodes ADD api_public_key BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', ADD api_secret_key BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', ADD wireguard_public_key VARCHAR(44) NOT NULL, ADD wireguard_private_key VARCHAR(44) NOT NULL, ADD port INT UNSIGNED NOT NULL, DROP api_secret');

            $connection->executeStatement('UPDATE dockontrol_nodes 
               SET api_public_key = UNHEX(REPLACE(UUID(), \'-\', \'\')), 
                   api_secret_key = UNHEX(REPLACE(UUID(), \'-\', \'\')), 
                   wireguard_private_key = \'qX8koGFX1jxlWlLDHDVR/2cU64xbASwEIfJVeOmtU90=\', 
                   wireguard_public_key = \'BS7wCDN+cs2aAY4iPJzZAJ84VtIBGNoRCvNeQD7pLwQ=\', 
                   port = FLOOR(1024 + (RAND() * (65535 - 1024 + 1)))');

            $connection->executeStatement('CREATE UNIQUE INDEX UNIQ_14729F97A153A872 ON dockontrol_nodes (api_public_key)');
            $connection->executeStatement('CREATE TABLE admin_action_log (id INT AUTO_INCREMENT NOT NULL, admin_id INT NOT NULL, time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', description LONGTEXT NOT NULL, INDEX IDX_7AFB5000A76ED395 (admin_id), INDEX IDX_7AFB50006F949845 (time), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $connection->executeStatement('ALTER TABLE admin_action_log ADD CONSTRAINT FK_7AFB5000A76ED395 FOREIGN KEY (admin_id) REFERENCES `users` (id)');

            $connection->executeStatement('CREATE TABLE `doctrine_migration_versions` (
              `version` varchar(191) NOT NULL,
              `executed_at` datetime(6) DEFAULT NULL,
              `execution_time` int(11) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

            $connection->executeStatement('ALTER TABLE action_queue CHANGE time_created time_created DATETIME(6) NOT NULL, CHANGE time_start time_start DATETIME(6) NOT NULL');
            $connection->executeStatement('ALTER TABLE actions CHANGE type type enum(\'dockontrol_node_relay\', \'multi\') NOT NULL');
            $connection->executeStatement('ALTER TABLE admin_action_log CHANGE time time DATETIME(6) NOT NULL');
            $connection->executeStatement('ALTER TABLE admin_action_log RENAME INDEX idx_7afb5000a76ed395 TO IDX_7AFB5000642B8210');
            $connection->executeStatement('ALTER TABLE api_call_failed_logs CHANGE time time DATETIME(6) NOT NULL');
            $connection->executeStatement('ALTER TABLE api_call_logs CHANGE time time DATETIME(6) NOT NULL');
            $connection->executeStatement('ALTER TABLE buttons CHANGE type type enum(\'gate\', \'entrance\', \'elevator\', \'multi\', \'custom\') DEFAULT \'entrance\' NOT NULL, CHANGE button_style button_style enum(\'basic\', \'blue\', \'red\') DEFAULT \'basic\' NOT NULL, CHANGE icon icon enum(\'building\', \'elevator\', \'entrance\', \'entrance_pedestrian\', \'garage\', \'gate\', \'nuki\', \'enter\', \'exit\') DEFAULT \'entrance\' NOT NULL');
            $connection->executeStatement('ALTER TABLE camera_logs CHANGE time time DATETIME(6) NOT NULL');
            $connection->executeStatement('ALTER TABLE cameras CHANGE last_fetched last_fetched DATETIME(6) DEFAULT NULL');
            $connection->executeStatement('ALTER TABLE dockontrol_nodes CHANGE last_command_executed_time last_command_executed_time DATETIME(6) DEFAULT NULL, CHANGE status status enum(\'online\', \'pingable\', \'offline\', \'invalid_api_secret\') DEFAULT \'offline\' NOT NULL, CHANGE ping ping DOUBLE PRECISION DEFAULT NULL, CHANGE last_ping_time last_ping_time DATETIME(6) DEFAULT NULL, CHANGE last_monitor_check_time last_monitor_check_time DATETIME(6) DEFAULT NULL, CHANGE api_public_key api_public_key BINARY(16) NOT NULL, CHANGE api_secret_key api_secret_key BINARY(16) NOT NULL');
            $connection->executeStatement('ALTER TABLE guests CHANGE hash hash BINARY(16) NOT NULL, CHANGE expires expires DATETIME(6) NOT NULL, CHANGE created created DATETIME(6) NOT NULL');
            $connection->executeStatement('ALTER TABLE login_logs CHANGE time time DATETIME(6) NOT NULL');
            $connection->executeStatement('ALTER TABLE login_logs_failed CHANGE time time DATETIME(6) NOT NULL');
            $connection->executeStatement('ALTER TABLE nuki_logs CHANGE time time DATETIME(6) NOT NULL, CHANGE status status enum(\'ok\', \'incorrect_pin\', \'error\') DEFAULT \'ok\' NOT NULL, CHANGE action action enum(\'lock\', \'unlock\', \'pin_check\') DEFAULT \'unlock\' NOT NULL');
            $connection->executeStatement('ALTER TABLE signup_codes CHANGE hash hash BINARY(16) NOT NULL, CHANGE expires expires DATETIME(6) NOT NULL, CHANGE created_time created_time DATETIME(6) NOT NULL, CHANGE used_time used_time DATETIME(6) DEFAULT NULL');
            $connection->executeStatement('ALTER TABLE users CHANGE created_time created_time DATETIME(6) NOT NULL, CHANGE last_login_time last_login_time DATETIME(6) DEFAULT NULL, CHANGE button_press_type button_press_type enum(\'click\', \'hold\') DEFAULT \'hold\' NOT NULL, CHANGE role role enum(\'ROLE_USER\', \'ROLE_ADMIN\', \'ROLE_SUPER_ADMIN\') DEFAULT \'ROLE_USER\' NOT NULL, CHANGE password_set_time password_set_time DATETIME(6) NOT NULL');

            $connection->executeStatement('ALTER TABLE phone_control DROP FOREIGN KEY FK_7D99A079A76ED395');
            $connection->executeStatement('ALTER TABLE phone_control_log DROP FOREIGN KEY FK_832CFF9BA76ED395');
            $connection->executeStatement('DROP TABLE phone_control');
            $connection->executeStatement('DROP TABLE phone_control_log');
            $connection->executeStatement('ALTER TABLE users ADD time_last_action DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

            $connection->executeStatement('UPDATE users u 
            SET u.time_last_action = (
                SELECT aq.time_created
                FROM action_queue aq
                WHERE aq.user_id = u.id
                ORDER BY aq.time_created DESC
                LIMIT 1
            )');

            $connection->executeStatement('ALTER TABLE `guests` ADD time_last_action DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $connection->executeStatement('UPDATE `guests` g 
            SET g.time_last_action = (
                SELECT aq.time_created
                FROM action_queue aq
                WHERE aq.guest_id = g.id
                ORDER BY aq.time_created DESC
                LIMIT 1
            )');
            $connection->executeStatement('ALTER TABLE guests CHANGE time_last_action time_last_action DATETIME(6) DEFAULT NULL');
            $connection->executeStatement('ALTER TABLE users CHANGE time_last_action time_last_action DATETIME(6) DEFAULT NULL');
            $connection->executeStatement('ALTER TABLE users ADD reset_password_token BINARY(16) DEFAULT NULL, ADD reset_password_token_time_created DATETIME(6) DEFAULT NULL, ADD reset_password_token_time_expires DATETIME(6) DEFAULT NULL');
            $connection->executeStatement('CREATE TABLE email_setting (id INT AUTO_INCREMENT NOT NULL, host VARCHAR(255) NOT NULL, port INT UNSIGNED NOT NULL, use_tls TINYINT(1) DEFAULT 0 NOT NULL, ignore_sslerror TINYINT(1) DEFAULT 0 NOT NULL, senders_email VARCHAR(255) NOT NULL, login VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
            $connection->executeStatement('CREATE TABLE api_keys (public_key BINARY(16) NOT NULL, private_key BINARY(16) NOT NULL, time_created DATETIME(6) NOT NULL, time_last_used DATETIME(6) DEFAULT NULL, name VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX IDX_9579321FA76ED395 (user_id), PRIMARY KEY(public_key)) DEFAULT CHARACTER SET utf8mb4');
            $connection->executeStatement('ALTER TABLE api_keys ADD CONSTRAINT FK_9579321FA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');

            $connection->executeStatement('CREATE TABLE apartment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, building_id INT NOT NULL, default_group_id INT DEFAULT NULL, INDEX IDX_4D7E68544D2A7E12 (building_id), INDEX IDX_4D7E6854F810E909 (default_group_id), UNIQUE INDEX apartment_name (building_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
            $connection->executeStatement('ALTER TABLE apartment ADD CONSTRAINT FK_4D7E68544D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id)');
            $connection->executeStatement('ALTER TABLE apartment ADD CONSTRAINT FK_4D7E6854F810E909 FOREIGN KEY (default_group_id) REFERENCES `groups` (id)');
            $connection->executeStatement('
            INSERT INTO apartment (name, building_id, default_group_id)
            SELECT DISTINCT u.apartment, u.building_id, b.default_group_id
            FROM users u INNER JOIN buildings b ON u.building_id = b.id
            WHERE u.apartment IS NOT NULL;
        ');

            $connection->executeStatement('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E94D2A7E12');
            $connection->executeStatement('DROP INDEX IDX_1483A5E94D2A7E12 ON users');
            $connection->executeStatement('ALTER TABLE users CHANGE building_id apartment_id INT DEFAULT NULL');
            $connection->executeStatement('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9176DFE85 FOREIGN KEY (apartment_id) REFERENCES apartment (id)');
            $connection->executeStatement('CREATE INDEX IDX_1483A5E9176DFE85 ON users (apartment_id)');
            $connection->executeStatement('
            UPDATE users u
            JOIN apartment a ON u.apartment = a.name
            SET u.apartment_id = a.id;
        ');

            $connection->executeStatement('ALTER TABLE users DROP apartment');

            $connection->executeStatement('ALTER TABLE signup_codes DROP FOREIGN KEY FK_728DBB444D2A7E12');
            $connection->executeStatement('DROP INDEX IDX_728DBB444D2A7E12 ON signup_codes');
            $connection->executeStatement('ALTER TABLE signup_codes DROP apartment, CHANGE building_id apartment_id INT NOT NULL');
            $connection->executeStatement('ALTER TABLE signup_codes ADD CONSTRAINT FK_728DBB44176DFE85 FOREIGN KEY (apartment_id) REFERENCES apartment (id) ON DELETE CASCADE');
            $connection->executeStatement('CREATE INDEX IDX_728DBB44176DFE85 ON signup_codes (apartment_id)');
            $connection->executeStatement('ALTER TABLE users CHANGE created_by created_by INT DEFAULT 1');

            $connection->executeStatement('CREATE TABLE dockontrol_node_api_call_logs (dockontrol_node_id INT UNSIGNED NOT NULL, id BIGINT NOT NULL, INDEX IDX_A215BFD6D0DE5EAE (dockontrol_node_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
            $connection->executeStatement('CREATE TABLE legacy_api_call_logs (user_id INT NOT NULL, id BIGINT NOT NULL, INDEX IDX_F0D1D6A0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
            $connection->executeStatement('CREATE TABLE api2_call_logs (api_key VARCHAR(255) NOT NULL, user_id INT NOT NULL, id BIGINT NOT NULL, INDEX IDX_D678A733A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
            $connection->executeStatement('ALTER TABLE dockontrol_node_api_call_logs ADD CONSTRAINT FK_A215BFD6D0DE5EAE FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id)');
            $connection->executeStatement('ALTER TABLE dockontrol_node_api_call_logs ADD CONSTRAINT FK_A215BFD6BF396750 FOREIGN KEY (id) REFERENCES api_call_logs (id) ON DELETE CASCADE');
            $connection->executeStatement('ALTER TABLE legacy_api_call_logs ADD CONSTRAINT FK_F0D1D6A0A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE legacy_api_call_logs ADD CONSTRAINT FK_F0D1D6A0BF396750 FOREIGN KEY (id) REFERENCES api_call_logs (id) ON DELETE CASCADE');
            $connection->executeStatement('ALTER TABLE api2_call_logs ADD CONSTRAINT FK_C05CBC7AA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
            $connection->executeStatement('ALTER TABLE api2_call_logs ADD CONSTRAINT FK_C05CBC7ABF396750 FOREIGN KEY (id) REFERENCES api_call_logs (id) ON DELETE CASCADE');
            $connection->executeStatement('INSERT INTO legacy_api_call_logs (user_id, id) SELECT user_id, id from api_call_logs');
            $connection->executeStatement('ALTER TABLE api_call_logs DROP FOREIGN KEY FK_FE36085FA76ED395');
            $connection->executeStatement('DROP INDEX api_calls_users_id_fk ON api_call_logs');
            $connection->executeStatement('ALTER TABLE api_call_logs ADD type VARCHAR(255) NOT NULL, DROP user_id');
            $connection->executeStatement('UPDATE api_call_logs SET type = "legacy"');


            $connection->executeStatement('CREATE TABLE dockontrol_node_api_call_failed_logs (dockontrol_node_api_key VARCHAR(255) NOT NULL, id BIGINT NOT NULL, INDEX dockontrol_node_api_call_failed_api_key_index (dockontrol_node_api_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
            $connection->executeStatement('CREATE TABLE legacy_api_call_failed_logs (username VARCHAR(255) NOT NULL, api_action VARCHAR(255) NOT NULL, id BIGINT NOT NULL, INDEX legacy_api_call_failed_username_index (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
            $connection->executeStatement('CREATE TABLE api2_call_failed_logs (api_key VARCHAR(255) NOT NULL, id BIGINT NOT NULL, INDEX api2_call_failed_api_key_index (api_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
            $connection->executeStatement('ALTER TABLE dockontrol_node_api_call_failed_logs ADD CONSTRAINT FK_41DD7EABBF396750 FOREIGN KEY (id) REFERENCES api_call_failed_logs (id) ON DELETE CASCADE');
            $connection->executeStatement('ALTER TABLE legacy_api_call_failed_logs ADD CONSTRAINT FK_9C0F4C1ABF396750 FOREIGN KEY (id) REFERENCES api_call_failed_logs (id) ON DELETE CASCADE');
            $connection->executeStatement('ALTER TABLE api2_call_failed_logs ADD CONSTRAINT FK_838EB0AFBF396750 FOREIGN KEY (id) REFERENCES api_call_failed_logs (id) ON DELETE CASCADE');
            $connection->executeStatement('INSERT INTO legacy_api_call_failed_logs (username, api_action, id) SELECT username, api_action, id from api_call_failed_logs');
            $connection->executeStatement('DROP INDEX api_calls_failed_username_index ON api_call_failed_logs');
            $connection->executeStatement('ALTER TABLE api_call_failed_logs ADD type VARCHAR(255) NOT NULL, ADD api_endpoint VARCHAR(255) NOT NULL, ADD reason VARCHAR(255) NOT NULL, DROP username, DROP api_action');
            $connection->executeStatement('UPDATE api_call_failed_logs SET type = "legacy"');
            $connection->executeStatement("DELETE FROM config WHERE `config`.`key` IN ('openwebnet_ip', 'openwebnet_password', 'openwebnet_port', 'signup_key')");

            $connection->executeStatement('CREATE TABLE building_permission (building_id INT NOT NULL, permission VARCHAR(63) NOT NULL, INDEX IDX_CE01FD2F4D2A7E12 (building_id), INDEX IDX_CE01FD2FE04992AA (permission), PRIMARY KEY(building_id, permission)) DEFAULT CHARACTER SET utf8mb4');
            $connection->executeStatement('ALTER TABLE building_permission ADD CONSTRAINT FK_CE01FD2F4D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id)');
            $connection->executeStatement('ALTER TABLE building_permission ADD CONSTRAINT FK_CE01FD2FE04992AA FOREIGN KEY (permission) REFERENCES permissions (name)');

            $connection->executeStatement("INSERT INTO `building_permission` (`building_id`, `permission`) VALUES
            (1, 'entrance_menclova'),
            (1, 'entrance_menclova_z1'),
            (1, 'entrance_smrckova_river'),
            (1, 'entrance_z1b1'),
            (1, 'garage_z1'),
            (1, 'gate_rw1'),
            (2, 'entrance_smrckova_river'),
            (2, 'entrance_z2b1'),
            (2, 'garage_z2'),
            (2, 'gate_rw1'),
            (3, 'entrance_menclova_z3'),
            (3, 'entrance_smrckova_river'),
            (3, 'entrance_z3b1'),
            (3, 'garage_z3'),
            (3, 'gate_rw1'),
            (4, 'entrance_menclova_z3'),
            (4, 'entrance_smrckova_river'),
            (4, 'entrance_z3b2'),
            (4, 'garage_z3'),
            (4, 'gate_rw1'),
            (5, 'elevator_z7b1'),
            (5, 'entrance_smrckova'),
            (5, 'entrance_smrckova_river'),
            (5, 'entrance_z7b1'),
            (5, 'garage_z7'),
            (5, 'gate_rw3'),
            (6, 'entrance_smrckova'),
            (6, 'entrance_smrckova_river'),
            (6, 'entrance_z7b2'),
            (6, 'garage_z7'),
            (6, 'gate_rw3'),
            (7, 'elevator_z8b1'),
            (7, 'entrance_smrckova'),
            (7, 'entrance_smrckova_river'),
            (7, 'entrance_z8b1'),
            (7, 'garage_z8'),
            (7, 'gate_rw3'),
            (8, 'entrance_smrckova'),
            (8, 'entrance_smrckova_river'),
            (8, 'entrance_z8b2'),
            (8, 'garage_z8'),
            (8, 'gate_rw3'),
            (9, 'elevator_z9b1'),
            (9, 'entrance_smrckova'),
            (9, 'entrance_smrckova_river'),
            (9, 'entrance_z9b1'),
            (9, 'garage_z9'),
            (9, 'gate_rw3'),
            (10, 'elevator_z9b2'),
            (10, 'entrance_smrckova'),
            (10, 'entrance_smrckova_river'),
            (10, 'entrance_z9b2'),
            (10, 'garage_z9'),
            (10, 'gate_rw3')");

            $endTime = CarbonImmutable::now()->getTimestamp();

            $connection->executeStatement('ALTER TABLE `doctrine_migration_versions` ADD PRIMARY KEY (`version`)');

            $connection->insert('doctrine_migration_versions', [
                'version' => 'DoctrineMigrations\Version20241111042735',
                'executed_at' => CarbonImmutable::now()->format('Y-m-d H:i:s'),
                'execution_time' => $endTime - $startTime,
            ]);

            $io->success('Migration from legacy database completed successfully.');
        } catch (Throwable $e) {
            $io->error('An error occurred during migration: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
