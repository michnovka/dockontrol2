<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241230095041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE camera_backup (id INT AUTO_INCREMENT NOT NULL, dockontrol_node_payload JSON NOT NULL, parent_camera_name VARCHAR(63) NOT NULL, dockontrol_node_id INT UNSIGNED NOT NULL, INDEX IDX_3A8F6659BFE4103A (parent_camera_name), INDEX IDX_3A8F6659D0DE5EAE (dockontrol_node_id), UNIQUE INDEX camera_backup_unique (parent_camera_name, dockontrol_node_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE camera_backup ADD CONSTRAINT FK_3A8F6659BFE4103A FOREIGN KEY (parent_camera_name) REFERENCES cameras (name_id)');
        $this->addSql('ALTER TABLE camera_backup ADD CONSTRAINT FK_3A8F6659D0DE5EAE FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE camera_backup DROP FOREIGN KEY FK_3A8F6659BFE4103A');
        $this->addSql('ALTER TABLE camera_backup DROP FOREIGN KEY FK_3A8F6659D0DE5EAE');
        $this->addSql('DROP TABLE camera_backup');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
