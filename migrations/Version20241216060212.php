<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241216060212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cron_log (id INT AUTO_INCREMENT NOT NULL, time_start DATETIME(6) NOT NULL, time_end DATETIME(6) NOT NULL, type ENUM(\'Monitor\', \'Database Cleanup\', \'Action Queue\') NOT NULL, output LONGTEXT NOT NULL, cron_group VARCHAR(150) DEFAULT NULL, INDEX IDX_7C0163B38377B003 (cron_group), INDEX cron_group_time_start (time_start), INDEX cron_group_time_end (time_end), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cron_log ADD CONSTRAINT FK_7C0163B38377B003 FOREIGN KEY (cron_group) REFERENCES cron_group (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cron_log DROP FOREIGN KEY FK_7C0163B38377B003');
        $this->addSql('DROP TABLE cron_log');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
