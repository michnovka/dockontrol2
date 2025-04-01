<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250127053135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change DROP FOREIGN KEY FK_58A9FD95A76ED395');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change DROP FOREIGN KEY FK_58A9FD95D0DE5EAE');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change ADD CONSTRAINT FK_39B077AFD0DE5EAE FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id)');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change ADD CONSTRAINT FK_39B077AFA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change RENAME INDEX idx_58a9fd95d0de5eae TO IDX_39B077AFD0DE5EAE');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change RENAME INDEX idx_58a9fd95a76ed395 TO IDX_39B077AFA76ED395');
        $this->addSql('ALTER TABLE users ADD last_email_sent_time DATETIME(6) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change DROP FOREIGN KEY FK_39B077AFD0DE5EAE');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change DROP FOREIGN KEY FK_39B077AFA76ED395');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change ADD CONSTRAINT FK_58A9FD95A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change ADD CONSTRAINT FK_58A9FD95D0DE5EAE FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change RENAME INDEX idx_39b077afd0de5eae TO IDX_58A9FD95D0DE5EAE');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change RENAME INDEX idx_39b077afa76ed395 TO IDX_58A9FD95A76ED395');
        $this->addSql('ALTER TABLE `users` DROP last_email_sent_time');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
