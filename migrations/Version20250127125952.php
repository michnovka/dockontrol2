<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250127125952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_change_log (id INT AUTO_INCREMENT NOT NULL, time_created DATETIME(6) NOT NULL, old_email VARCHAR(150) NOT NULL, new_email VARCHAR(150) NOT NULL, old_email_confirmed_time DATETIME(6) DEFAULT NULL, new_email_confirmed_time DATETIME(6) DEFAULT NULL, old_email_confirm_hash BINARY(16) NOT NULL, new_email_confirm_hash BINARY(16) NOT NULL, user_id INT NOT NULL, INDEX IDX_F2A9F807A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE email_change_log ADD CONSTRAINT FK_F2A9F807A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F2A9F80761905BDA ON email_change_log (old_email_confirm_hash)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F2A9F80783FF531D ON email_change_log (new_email_confirm_hash)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_F2A9F80761905BDA ON email_change_log');
        $this->addSql('DROP INDEX UNIQ_F2A9F80783FF531D ON email_change_log');
        $this->addSql('ALTER TABLE email_change_log DROP FOREIGN KEY FK_F2A9F807A76ED395');
        $this->addSql('DROP TABLE email_change_log');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
