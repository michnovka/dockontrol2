<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250320072657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dockontrol_nodes ADD fail_count INT UNSIGNED DEFAULT 0 NOT NULL, ADD last_notify_status ENUM(\'online\', \'pingable\', \'offline\', \'invalid_api_secret\') DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dockontrol_nodes DROP fail_count, DROP last_notify_status');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
