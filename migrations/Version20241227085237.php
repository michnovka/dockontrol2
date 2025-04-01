<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241227085237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action_backup_dockontrol_node (id INT AUTO_INCREMENT NOT NULL, action_payload JSON NOT NULL, dockontrol_node_id INT UNSIGNED NOT NULL, parent_action VARCHAR(63) NOT NULL, INDEX IDX_1DC93878D0DE5EAE (dockontrol_node_id), INDEX IDX_1DC938788DF4F54 (parent_action), UNIQUE INDEX action_backup_dockontrol_node_unique (dockontrol_node_id, parent_action), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE action_backup_dockontrol_node ADD CONSTRAINT FK_1DC93878D0DE5EAE FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id)');
        $this->addSql('ALTER TABLE action_backup_dockontrol_node ADD CONSTRAINT FK_1DC938788DF4F54 FOREIGN KEY (parent_action) REFERENCES actions (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_backup_dockontrol_node DROP FOREIGN KEY FK_1DC93878D0DE5EAE');
        $this->addSql('ALTER TABLE action_backup_dockontrol_node DROP FOREIGN KEY FK_1DC938788DF4F54');
        $this->addSql('DROP TABLE action_backup_dockontrol_node');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
