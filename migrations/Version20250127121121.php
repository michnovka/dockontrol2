<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250127121121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX legacy_api_call_failed_username_index ON legacy_api_call_failed_logs');
        $this->addSql('ALTER TABLE legacy_api_call_failed_logs CHANGE username email VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX legacy_api_call_failed_email_index ON legacy_api_call_failed_logs (email)');
        $this->addSql('DROP INDEX login_logs_failed_username_index ON login_logs_failed');
        $this->addSql('ALTER TABLE login_logs_failed CHANGE username email VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX login_logs_failed_email_index ON login_logs_failed (email)');
        $this->addSql('DROP INDEX users_username_uindex ON users');
        $this->addSql('ALTER TABLE users DROP username');
        $this->addSql('ALTER TABLE users RENAME INDEX uniq_1483a5e9e7927c74 TO users_email_index');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX legacy_api_call_failed_email_index ON legacy_api_call_failed_logs');
        $this->addSql('ALTER TABLE legacy_api_call_failed_logs CHANGE email username VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX legacy_api_call_failed_username_index ON legacy_api_call_failed_logs (username)');
        $this->addSql('DROP INDEX login_logs_failed_email_index ON login_logs_failed');
        $this->addSql('ALTER TABLE login_logs_failed CHANGE email username VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX login_logs_failed_username_index ON login_logs_failed (username)');
        $this->addSql('ALTER TABLE `users` ADD username VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX users_username_uindex ON `users` (username)');
        $this->addSql('ALTER TABLE `users` RENAME INDEX users_email_index TO UNIQ_1483A5E9E7927C74');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
