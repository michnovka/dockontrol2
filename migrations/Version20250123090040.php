<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250123090040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dockontrol_node_users_to_notify_when_status_change (dockontrol_node_id INT UNSIGNED NOT NULL, user_id INT NOT NULL, INDEX IDX_58A9FD95D0DE5EAE (dockontrol_node_id), INDEX IDX_58A9FD95A76ED395 (user_id), PRIMARY KEY(dockontrol_node_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change ADD CONSTRAINT FK_58A9FD95D0DE5EAE FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change ADD CONSTRAINT FK_58A9FD95A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dockontrol_nodes ADD notify_when_status_change TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('
            INSERT INTO dockontrol_node_users_to_notify_when_status_change (dockontrol_node_id, user_id)
            SELECT dn.id, u.id
            FROM dockontrol_nodes dn
            CROSS JOIN users u
            WHERE u.role = "ROLE_SUPER_ADMIN"
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change DROP FOREIGN KEY FK_58A9FD95D0DE5EAE');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change DROP FOREIGN KEY FK_58A9FD95A76ED395');
        $this->addSql('DROP TABLE dockontrol_node_users_to_notify_when_status_change');
        $this->addSql('ALTER TABLE dockontrol_nodes DROP notify_when_status_change');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
