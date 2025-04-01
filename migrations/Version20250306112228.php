<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250306112228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE actions ADD friendly_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE cameras ADD friendly_name VARCHAR(255) DEFAULT NULL');

        $this->addSql('UPDATE actions SET friendly_name = name');
        $this->addSql('UPDATE cameras SET friendly_name = name_id');

        $this->addSql('ALTER TABLE actions MODIFY friendly_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE cameras MODIFY friendly_name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE actions DROP friendly_name');
        $this->addSql('ALTER TABLE cameras DROP friendly_name');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
