<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241111042735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action_queue (id BIGINT AUTO_INCREMENT NOT NULL, time_created DATETIME(6) NOT NULL, time_start DATETIME(6) NOT NULL, executed TINYINT(1) DEFAULT 0 NOT NULL, count_into_stats TINYINT(1) DEFAULT 1 NOT NULL, action VARCHAR(63) NOT NULL, user_id INT NOT NULL, guest_id INT DEFAULT NULL, INDEX queue_time_created_index (time_created), INDEX queue_time_start_index (time_start), INDEX queue_executed_index (executed), INDEX action_queue_count_into_stats_index (count_into_stats), INDEX action_queue_action_index (action), INDEX queue_users_id_fk (user_id), INDEX action_queue_guests_id_fk (guest_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE actions (name VARCHAR(63) NOT NULL, type ENUM(\'dockontrol_node_relay\', \'multi\') NOT NULL, action_payload JSON DEFAULT NULL, dockontrol_node_id INT UNSIGNED DEFAULT NULL, cron_group VARCHAR(150) NOT NULL, INDEX actions_dockontrol_nodes_id_fk (dockontrol_node_id), INDEX IDX_548F1EF8377B003 (cron_group), PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE admin_action_log (id INT AUTO_INCREMENT NOT NULL, time DATETIME(6) NOT NULL, description LONGTEXT NOT NULL, admin_id INT NOT NULL, INDEX IDX_7AFB5000642B8210 (admin_id), INDEX IDX_7AFB50006F949845 (time), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE apartment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, building_id INT NOT NULL, default_group_id INT DEFAULT NULL, INDEX IDX_4D7E68544D2A7E12 (building_id), INDEX IDX_4D7E6854F810E909 (default_group_id), UNIQUE INDEX apartment_name (building_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE api2_call_failed_logs (api_key VARCHAR(255) NOT NULL, id BIGINT NOT NULL, INDEX api2_call_failed_api_key_index (api_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE api2_call_logs (api_key VARCHAR(255) NOT NULL, user_id INT NOT NULL, id BIGINT NOT NULL, INDEX IDX_D678A733A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE api_call_failed_logs (id BIGINT AUTO_INCREMENT NOT NULL, time DATETIME(6) NOT NULL, ip VARCHAR(64) NOT NULL, api_endpoint VARCHAR(255) NOT NULL, reason VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, INDEX api_calls_failed_time_index (time), INDEX api_calls_failed_ip_index (ip), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE api_call_logs (id BIGINT AUTO_INCREMENT NOT NULL, time DATETIME(6) NOT NULL, ip VARCHAR(63) NOT NULL, api_action VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, INDEX api_calls_time_index (time), INDEX api_calls_ip_index (ip), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE api_keys (public_key BINARY(16) NOT NULL, private_key BINARY(16) NOT NULL, time_created DATETIME(6) NOT NULL, time_last_used DATETIME(6) DEFAULT NULL, name VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX IDX_9579321FA76ED395 (user_id), PRIMARY KEY(public_key)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE buildings (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(31) NOT NULL, default_group_id INT DEFAULT NULL, INDEX IDX_9A51B6A7F810E909 (default_group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE buttons (id VARCHAR(63) NOT NULL, type ENUM(\'gate\', \'entrance\', \'elevator\', \'multi\', \'custom\') DEFAULT \'entrance\' NOT NULL, action_multi VARCHAR(255) DEFAULT NULL, action_multi_description VARCHAR(255) DEFAULT NULL, name VARCHAR(63) NOT NULL, name_specification VARCHAR(63) DEFAULT NULL, allow_1min_open TINYINT(1) DEFAULT 0 NOT NULL, sort_index INT NOT NULL, button_style ENUM(\'basic\', \'blue\', \'red\') DEFAULT \'basic\' NOT NULL, icon ENUM(\'building\', \'elevator\', \'entrance\', \'entrance_pedestrian\', \'garage\', \'gate\', \'nuki\', \'enter\', \'exit\') DEFAULT \'entrance\' NOT NULL, action VARCHAR(63) NOT NULL, permission VARCHAR(63) DEFAULT NULL, camera1 VARCHAR(63) DEFAULT NULL, camera2 VARCHAR(63) DEFAULT NULL, camera3 VARCHAR(63) DEFAULT NULL, camera4 VARCHAR(63) DEFAULT NULL, INDEX buttons_type_index (type), INDEX button_cameras_name_id_fk (camera1), INDEX button_cameras_name_id_fk_2 (camera2), INDEX button_cameras_name_id_fk_3 (camera3), INDEX button_cameras_name_id_fk_4 (camera4), INDEX button_permission_index (permission), INDEX buttons_order_index (sort_index), INDEX buttons_actions_name_fk (action), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE camera_logs (id BIGINT AUTO_INCREMENT NOT NULL, time DATETIME(6) NOT NULL, user_id INT NOT NULL, camera_name_id VARCHAR(63) NOT NULL, INDEX camera_log_time_index (time), INDEX camera_log_users_id_fk (user_id), INDEX camera_log_cameras_name_id_fk (camera_name_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cameras (name_id VARCHAR(63) NOT NULL, last_fetched DATETIME(6) DEFAULT NULL, data_jpg LONGBLOB DEFAULT NULL, stream_url VARCHAR(255) NOT NULL, stream_login VARCHAR(255) DEFAULT NULL, permission_required VARCHAR(63) DEFAULT NULL, INDEX cameras_permissions_name_fk (permission_required), PRIMARY KEY(name_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE car_enter_details (id INT AUTO_INCREMENT NOT NULL, `order` INT NOT NULL, building_id INT DEFAULT NULL, user_id INT DEFAULT NULL, action VARCHAR(63) NOT NULL, INDEX IDX_B76F13A44D2A7E12 (building_id), INDEX IDX_B76F13A4A76ED395 (user_id), INDEX IDX_B76F13A447CC8C92 (action), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE config (`key` VARCHAR(63) NOT NULL, value LONGTEXT NOT NULL, PRIMARY KEY(`key`)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cron_group (name VARCHAR(150) NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dockontrol_node_api_call_failed_logs (dockontrol_node_api_key VARCHAR(255) NOT NULL, id BIGINT NOT NULL, INDEX dockontrol_node_api_call_failed_api_key_index (dockontrol_node_api_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dockontrol_node_api_call_logs (dockontrol_node_id INT UNSIGNED NOT NULL, id BIGINT NOT NULL, INDEX IDX_A215BFD6D0DE5EAE (dockontrol_node_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dockontrol_nodes (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(63) NOT NULL, ip VARCHAR(63) NOT NULL, last_command_executed_time DATETIME(6) DEFAULT NULL, status ENUM(\'online\', \'pingable\', \'offline\', \'invalid_api_secret\') DEFAULT \'offline\' NOT NULL, ping DOUBLE PRECISION DEFAULT NULL, last_ping_time DATETIME(6) DEFAULT NULL, dockontrol_node_version VARCHAR(63) DEFAULT \'\', last_monitor_check_time DATETIME(6) DEFAULT NULL, comment VARCHAR(255) DEFAULT NULL, kernel_version VARCHAR(255) DEFAULT NULL, os_version VARCHAR(255) DEFAULT NULL, uptime BIGINT UNSIGNED DEFAULT NULL, device VARCHAR(255) NOT NULL, api_public_key BINARY(16) NOT NULL, api_secret_key BINARY(16) NOT NULL, wireguard_public_key VARCHAR(44) NOT NULL, wireguard_private_key VARCHAR(44) NOT NULL, port INT UNSIGNED NOT NULL, UNIQUE INDEX UNIQ_14729F97A153A872 (api_public_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE email_setting (id INT AUTO_INCREMENT NOT NULL, host VARCHAR(255) NOT NULL, port INT UNSIGNED NOT NULL, use_tls TINYINT(1) DEFAULT 0 NOT NULL, ignore_sslerror TINYINT(1) DEFAULT 0 NOT NULL, senders_email VARCHAR(255) NOT NULL, login VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `groups` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE group_permission (group_id INT NOT NULL, permission VARCHAR(63) NOT NULL, INDEX IDX_3784F318FE54D947 (group_id), INDEX IDX_3784F318E04992AA (permission), PRIMARY KEY(group_id, permission)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE guests (id INT AUTO_INCREMENT NOT NULL, hash BINARY(16) NOT NULL, expires DATETIME(6) NOT NULL, remaining_actions INT DEFAULT -1 NOT NULL COMMENT \'-1 unlimited 0 no actions left N actions left\', created DATETIME(6) NOT NULL, enabled TINYINT(1) NOT NULL, note VARCHAR(255) DEFAULT NULL, time_last_action DATETIME(6) DEFAULT NULL, user_id INT NOT NULL, INDEX guests_users_id_fk (user_id), UNIQUE INDEX guests_hash_uindex (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE legacy_api_call_failed_logs (username VARCHAR(255) NOT NULL, api_action VARCHAR(255) NOT NULL, id BIGINT NOT NULL, INDEX legacy_api_call_failed_username_index (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE legacy_api_call_logs (user_id INT NOT NULL, id BIGINT NOT NULL, INDEX IDX_F0D1D6A0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE login_logs (id BIGINT AUTO_INCREMENT NOT NULL, ip VARCHAR(255) NOT NULL, browser VARCHAR(255) NOT NULL, platform VARCHAR(255) NOT NULL, from_remember_me TINYINT(1) DEFAULT 0 NOT NULL, time DATETIME(6) NOT NULL, user_id INT NOT NULL, INDEX login_logs_users_id_fk (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE login_logs_failed (id BIGINT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, ip VARCHAR(64) NOT NULL, browser VARCHAR(255) NOT NULL, platform VARCHAR(255) NOT NULL, time DATETIME(6) NOT NULL, INDEX login_logs_failed_ip_index (ip), INDEX login_logs_failed_time_index (time), INDEX login_logs_failed_username_index (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE nuki (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, dockontrol_nuki_api_server VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, password1 VARCHAR(255) NOT NULL, can_lock TINYINT(1) DEFAULT 0 NOT NULL, pin INT DEFAULT NULL, user_id INT NOT NULL, INDEX nuki_users_id_fk (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE nuki_logs (id BIGINT AUTO_INCREMENT NOT NULL, status ENUM(\'ok\', \'incorrect_pin\', \'error\') DEFAULT \'ok\' NOT NULL, action ENUM(\'lock\', \'unlock\', \'pin_check\') DEFAULT \'unlock\' NOT NULL, time DATETIME(6) NOT NULL, nuki_id INT NOT NULL, INDEX nuki_logs_nuki_id_fk (nuki_id), INDEX nuki_logs_status_index (status), INDEX nuki_logs_time_index (time), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE permissions (name VARCHAR(63) NOT NULL, name_pretty VARCHAR(63) DEFAULT \'\' NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE signup_codes (hash BINARY(16) NOT NULL, expires DATETIME(6) NOT NULL, created_time DATETIME(6) NOT NULL, used_time DATETIME(6) DEFAULT NULL, admin_id INT NOT NULL, apartment_id INT NOT NULL, new_user_id INT DEFAULT NULL, INDEX IDX_728DBB44642B8210 (admin_id), INDEX IDX_728DBB44176DFE85 (apartment_id), INDEX IDX_728DBB447C2D807B (new_user_id), PRIMARY KEY(hash)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `users` (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, button_press_type ENUM(\'click\', \'hold\') DEFAULT \'hold\' NOT NULL, enabled TINYINT(1) DEFAULT 1 NOT NULL, has_camera_access TINYINT(1) DEFAULT 1 NOT NULL, can_create_guests TINYINT(1) DEFAULT 1 NOT NULL, last_login_time DATETIME(6) DEFAULT NULL, created_time DATETIME(6) NOT NULL, reset_password_token BINARY(16) DEFAULT NULL, reset_password_token_time_created DATETIME(6) DEFAULT NULL, reset_password_token_time_expires DATETIME(6) DEFAULT NULL, role ENUM(\'ROLE_USER\', \'ROLE_ADMIN\', \'ROLE_SUPER_ADMIN\') DEFAULT \'ROLE_USER\' NOT NULL, custom_car_enter_details TINYINT(1) DEFAULT 0 NOT NULL, password_set_time DATETIME(6) NOT NULL, time_last_action DATETIME(6) DEFAULT NULL, created_by INT DEFAULT 1, apartment_id INT DEFAULT NULL, INDEX IDX_1483A5E9DE12AB56 (created_by), INDEX IDX_1483A5E9176DFE85 (apartment_id), UNIQUE INDEX users_username_uindex (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_group (user_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_8F02BF9DA76ED395 (user_id), INDEX IDX_8F02BF9DFE54D947 (group_id), PRIMARY KEY(user_id, group_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_admin_buildings (user_id INT NOT NULL, building_id INT NOT NULL, INDEX IDX_617ADF1AA76ED395 (user_id), INDEX IDX_617ADF1A4D2A7E12 (building_id), PRIMARY KEY(user_id, building_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE webauthn_registrations (id BIGINT AUTO_INCREMENT NOT NULL, data LONGBLOB NOT NULL, credential_id VARCHAR(255) NOT NULL, created_time DATETIME(6) NOT NULL, last_used_time DATETIME(6) NOT NULL, user_id INT NOT NULL, INDEX webauthn_registrations_users_id_fk (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DF47CC8C92 FOREIGN KEY (action) REFERENCES actions (name)');
        $this->addSql('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DFA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DF9A4AA658 FOREIGN KEY (guest_id) REFERENCES guests (id)');
        $this->addSql('ALTER TABLE actions ADD CONSTRAINT FK_548F1EFD0DE5EAE FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id)');
        $this->addSql('ALTER TABLE actions ADD CONSTRAINT FK_548F1EF8377B003 FOREIGN KEY (cron_group) REFERENCES cron_group (name)');
        $this->addSql('ALTER TABLE admin_action_log ADD CONSTRAINT FK_7AFB5000642B8210 FOREIGN KEY (admin_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE apartment ADD CONSTRAINT FK_4D7E68544D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id)');
        $this->addSql('ALTER TABLE apartment ADD CONSTRAINT FK_4D7E6854F810E909 FOREIGN KEY (default_group_id) REFERENCES `groups` (id)');
        $this->addSql('ALTER TABLE api2_call_failed_logs ADD CONSTRAINT FK_7A3FF3DABF396750 FOREIGN KEY (id) REFERENCES api_call_failed_logs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api2_call_logs ADD CONSTRAINT FK_D678A733A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE api2_call_logs ADD CONSTRAINT FK_D678A733BF396750 FOREIGN KEY (id) REFERENCES api_call_logs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api_keys ADD CONSTRAINT FK_9579321FA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE buildings ADD CONSTRAINT FK_9A51B6A7F810E909 FOREIGN KEY (default_group_id) REFERENCES `groups` (id)');
        $this->addSql('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B647CC8C92 FOREIGN KEY (action) REFERENCES actions (name)');
        $this->addSql('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B6E04992AA FOREIGN KEY (permission) REFERENCES permissions (name)');
        $this->addSql('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B6F38D07D6 FOREIGN KEY (camera1) REFERENCES cameras (name_id)');
        $this->addSql('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B66A84566C FOREIGN KEY (camera2) REFERENCES cameras (name_id)');
        $this->addSql('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B61D8366FA FOREIGN KEY (camera3) REFERENCES cameras (name_id)');
        $this->addSql('ALTER TABLE buttons ADD CONSTRAINT FK_435C85B683E7F359 FOREIGN KEY (camera4) REFERENCES cameras (name_id)');
        $this->addSql('ALTER TABLE camera_logs ADD CONSTRAINT FK_7A64BA10A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE camera_logs ADD CONSTRAINT FK_7A64BA10BCC5E5F2 FOREIGN KEY (camera_name_id) REFERENCES cameras (name_id)');
        $this->addSql('ALTER TABLE cameras ADD CONSTRAINT FK_6B5F276A15A370BF FOREIGN KEY (permission_required) REFERENCES permissions (name)');
        $this->addSql('ALTER TABLE car_enter_details ADD CONSTRAINT FK_B76F13A44D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id)');
        $this->addSql('ALTER TABLE car_enter_details ADD CONSTRAINT FK_B76F13A4A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE car_enter_details ADD CONSTRAINT FK_B76F13A447CC8C92 FOREIGN KEY (action) REFERENCES actions (name)');
        $this->addSql('ALTER TABLE dockontrol_node_api_call_failed_logs ADD CONSTRAINT FK_41DD7EABBF396750 FOREIGN KEY (id) REFERENCES api_call_failed_logs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dockontrol_node_api_call_logs ADD CONSTRAINT FK_A215BFD6D0DE5EAE FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id)');
        $this->addSql('ALTER TABLE dockontrol_node_api_call_logs ADD CONSTRAINT FK_A215BFD6BF396750 FOREIGN KEY (id) REFERENCES api_call_logs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE group_permission ADD CONSTRAINT FK_3784F318FE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id)');
        $this->addSql('ALTER TABLE group_permission ADD CONSTRAINT FK_3784F318E04992AA FOREIGN KEY (permission) REFERENCES permissions (name)');
        $this->addSql('ALTER TABLE guests ADD CONSTRAINT FK_4D11BCB2A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE legacy_api_call_failed_logs ADD CONSTRAINT FK_9C0F4C1ABF396750 FOREIGN KEY (id) REFERENCES api_call_failed_logs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE legacy_api_call_logs ADD CONSTRAINT FK_F0D1D6A0A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE legacy_api_call_logs ADD CONSTRAINT FK_F0D1D6A0BF396750 FOREIGN KEY (id) REFERENCES api_call_logs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE login_logs ADD CONSTRAINT FK_36FD4D19A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE nuki ADD CONSTRAINT FK_1AE09E07A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE nuki_logs ADD CONSTRAINT FK_5BDAD6BB2CC69643 FOREIGN KEY (nuki_id) REFERENCES nuki (id)');
        $this->addSql('ALTER TABLE signup_codes ADD CONSTRAINT FK_728DBB44642B8210 FOREIGN KEY (admin_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE signup_codes ADD CONSTRAINT FK_728DBB44176DFE85 FOREIGN KEY (apartment_id) REFERENCES apartment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signup_codes ADD CONSTRAINT FK_728DBB447C2D807B FOREIGN KEY (new_user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE `users` ADD CONSTRAINT FK_1483A5E9DE12AB56 FOREIGN KEY (created_by) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE `users` ADD CONSTRAINT FK_1483A5E9176DFE85 FOREIGN KEY (apartment_id) REFERENCES apartment (id)');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DFE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_admin_buildings ADD CONSTRAINT FK_617ADF1AA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_admin_buildings ADD CONSTRAINT FK_617ADF1A4D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE webauthn_registrations ADD CONSTRAINT FK_637C3C5FA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('CREATE TABLE building_permission (building_id INT NOT NULL, permission VARCHAR(63) NOT NULL, INDEX IDX_CE01FD2F4D2A7E12 (building_id), INDEX IDX_CE01FD2FE04992AA (permission), PRIMARY KEY(building_id, permission)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE building_permission ADD CONSTRAINT FK_CE01FD2F4D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id)');
        $this->addSql('ALTER TABLE building_permission ADD CONSTRAINT FK_CE01FD2FE04992AA FOREIGN KEY (permission) REFERENCES permissions (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_queue DROP FOREIGN KEY FK_9833B3DF47CC8C92');
        $this->addSql('ALTER TABLE action_queue DROP FOREIGN KEY FK_9833B3DFA76ED395');
        $this->addSql('ALTER TABLE action_queue DROP FOREIGN KEY FK_9833B3DF9A4AA658');
        $this->addSql('ALTER TABLE actions DROP FOREIGN KEY FK_548F1EFD0DE5EAE');
        $this->addSql('ALTER TABLE actions DROP FOREIGN KEY FK_548F1EF8377B003');
        $this->addSql('ALTER TABLE admin_action_log DROP FOREIGN KEY FK_7AFB5000642B8210');
        $this->addSql('ALTER TABLE apartment DROP FOREIGN KEY FK_4D7E68544D2A7E12');
        $this->addSql('ALTER TABLE apartment DROP FOREIGN KEY FK_4D7E6854F810E909');
        $this->addSql('ALTER TABLE api2_call_failed_logs DROP FOREIGN KEY FK_7A3FF3DABF396750');
        $this->addSql('ALTER TABLE api2_call_logs DROP FOREIGN KEY FK_D678A733A76ED395');
        $this->addSql('ALTER TABLE api2_call_logs DROP FOREIGN KEY FK_D678A733BF396750');
        $this->addSql('ALTER TABLE api_keys DROP FOREIGN KEY FK_9579321FA76ED395');
        $this->addSql('ALTER TABLE buildings DROP FOREIGN KEY FK_9A51B6A7F810E909');
        $this->addSql('ALTER TABLE buttons DROP FOREIGN KEY FK_435C85B647CC8C92');
        $this->addSql('ALTER TABLE buttons DROP FOREIGN KEY FK_435C85B6E04992AA');
        $this->addSql('ALTER TABLE buttons DROP FOREIGN KEY FK_435C85B6F38D07D6');
        $this->addSql('ALTER TABLE buttons DROP FOREIGN KEY FK_435C85B66A84566C');
        $this->addSql('ALTER TABLE buttons DROP FOREIGN KEY FK_435C85B61D8366FA');
        $this->addSql('ALTER TABLE buttons DROP FOREIGN KEY FK_435C85B683E7F359');
        $this->addSql('ALTER TABLE camera_logs DROP FOREIGN KEY FK_7A64BA10A76ED395');
        $this->addSql('ALTER TABLE camera_logs DROP FOREIGN KEY FK_7A64BA10BCC5E5F2');
        $this->addSql('ALTER TABLE cameras DROP FOREIGN KEY FK_6B5F276A15A370BF');
        $this->addSql('ALTER TABLE car_enter_details DROP FOREIGN KEY FK_B76F13A44D2A7E12');
        $this->addSql('ALTER TABLE car_enter_details DROP FOREIGN KEY FK_B76F13A4A76ED395');
        $this->addSql('ALTER TABLE car_enter_details DROP FOREIGN KEY FK_B76F13A447CC8C92');
        $this->addSql('ALTER TABLE dockontrol_node_api_call_failed_logs DROP FOREIGN KEY FK_41DD7EABBF396750');
        $this->addSql('ALTER TABLE dockontrol_node_api_call_logs DROP FOREIGN KEY FK_A215BFD6D0DE5EAE');
        $this->addSql('ALTER TABLE dockontrol_node_api_call_logs DROP FOREIGN KEY FK_A215BFD6BF396750');
        $this->addSql('ALTER TABLE group_permission DROP FOREIGN KEY FK_3784F318FE54D947');
        $this->addSql('ALTER TABLE group_permission DROP FOREIGN KEY FK_3784F318E04992AA');
        $this->addSql('ALTER TABLE guests DROP FOREIGN KEY FK_4D11BCB2A76ED395');
        $this->addSql('ALTER TABLE legacy_api_call_failed_logs DROP FOREIGN KEY FK_9C0F4C1ABF396750');
        $this->addSql('ALTER TABLE legacy_api_call_logs DROP FOREIGN KEY FK_F0D1D6A0A76ED395');
        $this->addSql('ALTER TABLE legacy_api_call_logs DROP FOREIGN KEY FK_F0D1D6A0BF396750');
        $this->addSql('ALTER TABLE login_logs DROP FOREIGN KEY FK_36FD4D19A76ED395');
        $this->addSql('ALTER TABLE nuki DROP FOREIGN KEY FK_1AE09E07A76ED395');
        $this->addSql('ALTER TABLE nuki_logs DROP FOREIGN KEY FK_5BDAD6BB2CC69643');
        $this->addSql('ALTER TABLE signup_codes DROP FOREIGN KEY FK_728DBB44642B8210');
        $this->addSql('ALTER TABLE signup_codes DROP FOREIGN KEY FK_728DBB44176DFE85');
        $this->addSql('ALTER TABLE signup_codes DROP FOREIGN KEY FK_728DBB447C2D807B');
        $this->addSql('ALTER TABLE `users` DROP FOREIGN KEY FK_1483A5E9DE12AB56');
        $this->addSql('ALTER TABLE `users` DROP FOREIGN KEY FK_1483A5E9176DFE85');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9DA76ED395');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9DFE54D947');
        $this->addSql('ALTER TABLE user_admin_buildings DROP FOREIGN KEY FK_617ADF1AA76ED395');
        $this->addSql('ALTER TABLE user_admin_buildings DROP FOREIGN KEY FK_617ADF1A4D2A7E12');
        $this->addSql('ALTER TABLE webauthn_registrations DROP FOREIGN KEY FK_637C3C5FA76ED395');
        $this->addSql('DROP TABLE action_queue');
        $this->addSql('DROP TABLE actions');
        $this->addSql('DROP TABLE admin_action_log');
        $this->addSql('DROP TABLE apartment');
        $this->addSql('DROP TABLE api2_call_failed_logs');
        $this->addSql('DROP TABLE api2_call_logs');
        $this->addSql('DROP TABLE api_call_failed_logs');
        $this->addSql('DROP TABLE api_call_logs');
        $this->addSql('DROP TABLE api_keys');
        $this->addSql('DROP TABLE buildings');
        $this->addSql('DROP TABLE buttons');
        $this->addSql('DROP TABLE camera_logs');
        $this->addSql('DROP TABLE cameras');
        $this->addSql('DROP TABLE car_enter_details');
        $this->addSql('DROP TABLE config');
        $this->addSql('DROP TABLE cron_group');
        $this->addSql('DROP TABLE dockontrol_node_api_call_failed_logs');
        $this->addSql('DROP TABLE dockontrol_node_api_call_logs');
        $this->addSql('DROP TABLE dockontrol_nodes');
        $this->addSql('DROP TABLE email_setting');
        $this->addSql('DROP TABLE `groups`');
        $this->addSql('DROP TABLE group_permission');
        $this->addSql('DROP TABLE guests');
        $this->addSql('DROP TABLE legacy_api_call_failed_logs');
        $this->addSql('DROP TABLE legacy_api_call_logs');
        $this->addSql('DROP TABLE login_logs');
        $this->addSql('DROP TABLE login_logs_failed');
        $this->addSql('DROP TABLE nuki');
        $this->addSql('DROP TABLE nuki_logs');
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE signup_codes');
        $this->addSql('DROP TABLE `users`');
        $this->addSql('DROP TABLE user_group');
        $this->addSql('DROP TABLE user_admin_buildings');
        $this->addSql('DROP TABLE webauthn_registrations');
        $this->addSql('ALTER TABLE building_permission DROP FOREIGN KEY FK_CE01FD2F4D2A7E12');
        $this->addSql('ALTER TABLE building_permission DROP FOREIGN KEY FK_CE01FD2FE04992AA');
        $this->addSql('DROP TABLE building_permission');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
