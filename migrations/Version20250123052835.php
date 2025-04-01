<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250123052835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dockontrol_nodes ADD building_id INT DEFAULT NULL');
        $this->addSql('
            UPDATE dockontrol_nodes AS dn
            SET building_id = (
                SELECT b.id
                FROM buildings AS b
                WHERE b.name = dn.name
                LIMIT 1
            )
            WHERE EXISTS (
                SELECT 1
                FROM buildings AS b
                WHERE b.name = dn.name
            )
        ');

        $this->addSql('
            UPDATE dockontrol_nodes AS dn
            SET building_id = (
                SELECT b.id
                FROM buildings AS b
                ORDER BY b.id ASC
                LIMIT 1
            )
            WHERE building_id IS NULL
        ');

        $this->addSql('ALTER TABLE dockontrol_nodes MODIFY building_id INT NOT NULL');
        $this->addSql('ALTER TABLE dockontrol_nodes ADD CONSTRAINT FK_14729F974D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id)');
        $this->addSql('CREATE INDEX IDX_14729F974D2A7E12 ON dockontrol_nodes (building_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dockontrol_nodes DROP FOREIGN KEY FK_14729F974D2A7E12');
        $this->addSql('DROP INDEX IDX_14729F974D2A7E12 ON dockontrol_nodes');
        $this->addSql('ALTER TABLE dockontrol_nodes DROP building_id');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
