<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250109154339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_queue DROP FOREIGN KEY FK_9833B3DFA76ED395');
        $this->addSql('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DFA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE admin_action_log DROP FOREIGN KEY FK_7AFB5000A76ED395');
        $this->addSql('ALTER TABLE admin_action_log ADD CONSTRAINT FK_7AFB5000642B8210 FOREIGN KEY (admin_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api2_call_logs DROP FOREIGN KEY FK_C05CBC7AA76ED395');
        $this->addSql('ALTER TABLE api2_call_logs ADD CONSTRAINT FK_D678A733A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api_keys DROP FOREIGN KEY FK_9579321FA76ED395');
        $this->addSql('ALTER TABLE api_keys ADD CONSTRAINT FK_9579321FA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE camera_logs DROP FOREIGN KEY FK_7A64BA10A76ED395');
        $this->addSql('ALTER TABLE camera_logs DROP FOREIGN KEY FK_7A64BA10BCC5E5F2');
        $this->addSql('ALTER TABLE camera_logs ADD CONSTRAINT FK_7A64BA10A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE camera_logs ADD CONSTRAINT FK_7A64BA10BCC5E5F2 FOREIGN KEY (camera_name_id) REFERENCES cameras (name_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cron_log DROP FOREIGN KEY FK_7C0163B38377B003');
        $this->addSql('ALTER TABLE cron_log ADD CONSTRAINT FK_7C0163B38377B003 FOREIGN KEY (cron_group) REFERENCES cron_group (name) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_sorting_group DROP FOREIGN KEY FK_ACF4DB7DA76ED395');
        $this->addSql('ALTER TABLE custom_sorting_group ADD CONSTRAINT FK_ACF4DB7DA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dockontrol_node_api_call_logs DROP FOREIGN KEY FK_A215BFD6D0DE5EAE');
        $this->addSql('ALTER TABLE dockontrol_node_api_call_logs ADD CONSTRAINT FK_A215BFD6D0DE5EAE FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guests DROP FOREIGN KEY FK_4D11BCB2A76ED395');
        $this->addSql('ALTER TABLE guests ADD CONSTRAINT FK_4D11BCB2A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE legacy_api_call_logs DROP FOREIGN KEY FK_F0D1D6A0A76ED395');
        $this->addSql('ALTER TABLE legacy_api_call_logs ADD CONSTRAINT FK_F0D1D6A0A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE login_logs DROP FOREIGN KEY FK_36FD4D19A76ED395');
        $this->addSql('ALTER TABLE login_logs ADD CONSTRAINT FK_36FD4D19A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nuki DROP FOREIGN KEY FK_1AE09E07A76ED395');
        $this->addSql('ALTER TABLE nuki ADD CONSTRAINT FK_1AE09E07A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nuki_logs DROP FOREIGN KEY FK_5BDAD6BB2CC69643');
        $this->addSql('ALTER TABLE nuki_logs ADD CONSTRAINT FK_5BDAD6BB2CC69643 FOREIGN KEY (nuki_id) REFERENCES nuki (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signup_codes DROP FOREIGN KEY FK_728DBB44642B8210');
        $this->addSql('ALTER TABLE signup_codes ADD CONSTRAINT FK_728DBB44642B8210 FOREIGN KEY (admin_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9DE12AB56');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9DE12AB56 FOREIGN KEY (created_by) REFERENCES `users` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE webauthn_registrations DROP FOREIGN KEY FK_637C3C5FA76ED395');
        $this->addSql('ALTER TABLE webauthn_registrations ADD CONSTRAINT FK_637C3C5FA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nuki DROP FOREIGN KEY FK_1AE09E07A76ED395');
        $this->addSql('ALTER TABLE nuki ADD CONSTRAINT FK_1AE09E07A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE camera_logs DROP FOREIGN KEY FK_7A64BA10A76ED395');
        $this->addSql('ALTER TABLE camera_logs DROP FOREIGN KEY FK_7A64BA10BCC5E5F2');
        $this->addSql('ALTER TABLE camera_logs ADD CONSTRAINT FK_7A64BA10A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE camera_logs ADD CONSTRAINT FK_7A64BA10BCC5E5F2 FOREIGN KEY (camera_name_id) REFERENCES cameras (name_id)');
        $this->addSql('ALTER TABLE `users` DROP FOREIGN KEY FK_1483A5E9DE12AB56');
        $this->addSql('ALTER TABLE `users` ADD CONSTRAINT FK_1483A5E9DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE api2_call_logs DROP FOREIGN KEY FK_D678A733A76ED395');
        $this->addSql('ALTER TABLE api2_call_logs ADD CONSTRAINT FK_C05CBC7AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE login_logs DROP FOREIGN KEY FK_36FD4D19A76ED395');
        $this->addSql('ALTER TABLE login_logs ADD CONSTRAINT FK_36FD4D19A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE signup_codes DROP FOREIGN KEY FK_728DBB44642B8210');
        $this->addSql('ALTER TABLE signup_codes ADD CONSTRAINT FK_728DBB44642B8210 FOREIGN KEY (admin_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE guests DROP FOREIGN KEY FK_4D11BCB2A76ED395');
        $this->addSql('ALTER TABLE guests ADD CONSTRAINT FK_4D11BCB2A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE legacy_api_call_logs DROP FOREIGN KEY FK_F0D1D6A0A76ED395');
        $this->addSql('ALTER TABLE legacy_api_call_logs ADD CONSTRAINT FK_F0D1D6A0A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE nuki_logs DROP FOREIGN KEY FK_5BDAD6BB2CC69643');
        $this->addSql('ALTER TABLE nuki_logs ADD CONSTRAINT FK_5BDAD6BB2CC69643 FOREIGN KEY (nuki_id) REFERENCES nuki (id)');
        $this->addSql('ALTER TABLE dockontrol_node_api_call_logs DROP FOREIGN KEY FK_A215BFD6D0DE5EAE');
        $this->addSql('ALTER TABLE dockontrol_node_api_call_logs ADD CONSTRAINT FK_A215BFD6D0DE5EAE FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id)');
        $this->addSql('ALTER TABLE api_keys DROP FOREIGN KEY FK_9579321FA76ED395');
        $this->addSql('ALTER TABLE api_keys ADD CONSTRAINT FK_9579321FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE cron_log DROP FOREIGN KEY FK_7C0163B38377B003');
        $this->addSql('ALTER TABLE cron_log ADD CONSTRAINT FK_7C0163B38377B003 FOREIGN KEY (cron_group) REFERENCES cron_group (name)');
        $this->addSql('ALTER TABLE webauthn_registrations DROP FOREIGN KEY FK_637C3C5FA76ED395');
        $this->addSql('ALTER TABLE webauthn_registrations ADD CONSTRAINT FK_637C3C5FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE admin_action_log DROP FOREIGN KEY FK_7AFB5000642B8210');
        $this->addSql('ALTER TABLE admin_action_log ADD CONSTRAINT FK_7AFB5000A76ED395 FOREIGN KEY (admin_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE action_queue DROP FOREIGN KEY FK_9833B3DFA76ED395');
        $this->addSql('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE custom_sorting_group DROP FOREIGN KEY FK_ACF4DB7DA76ED395');
        $this->addSql('ALTER TABLE custom_sorting_group ADD CONSTRAINT FK_ACF4DB7DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
