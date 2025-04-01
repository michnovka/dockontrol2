<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241216035345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dockontrol_nodes CHANGE device device VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dockontrol_nodes DROP port');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dockontrol_nodes CHANGE device device VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE dockontrol_nodes ADD port INT UNSIGNED NOT NULL');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
