<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250203104704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD disable_automatically_due_to_inactivity TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE admin_action_log CHANGE admin_id admin_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `users` DROP disable_automatically_due_to_inactivity');
        $this->addSql('ALTER TABLE admin_action_log CHANGE admin_id admin_id INT NOT NULL');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
