<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250305055849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nuki_logs CHANGE status status ENUM(\'ok\', \'incorrect_pin\', \'error\', \'incorrect_password1\') DEFAULT \'ok\' NOT NULL, CHANGE action action ENUM(\'lock\', \'unlock\', \'pin_check\', \'password1_check\') DEFAULT \'unlock\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nuki_logs CHANGE status status ENUM(\'ok\', \'incorrect_pin\', \'error\') DEFAULT \'ok\' NOT NULL, CHANGE action action ENUM(\'lock\', \'unlock\', \'pin_check\') DEFAULT \'unlock\' NOT NULL');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
