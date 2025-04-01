<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250203095401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE cron_log MODIFY type ENUM('Node Monitor','Database Cleanup','Action Queue','MONITOR','DB_CLEANUP','ACTION_QUEUE') NOT NULL");
        $this->addSql("UPDATE cron_log SET type = 'MONITOR' WHERE type = 'Node Monitor'");
        $this->addSql("UPDATE cron_log SET type = 'DB_CLEANUP' WHERE type = 'Database Cleanup'");
        $this->addSql("UPDATE cron_log SET type = 'ACTION_QUEUE' WHERE type = 'Action Queue'");
        $this->addSql('ALTER TABLE cron_log CHANGE type type ENUM(\'MONITOR\', \'DB_CLEANUP\', \'ACTION_QUEUE\') NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE cron_log MODIFY type ENUM('MONITOR','DB_CLEANUP','ACTION_QUEUE','Node Monitor','Database Cleanup','Action Queue') NOT NULL");
        $this->addSql("UPDATE cron_log SET type = 'Node Monitor' WHERE type = 'MONITOR'");
        $this->addSql("UPDATE cron_log SET type = 'Database Cleanup' WHERE type = 'DB_CLEANUP'");
        $this->addSql("UPDATE cron_log SET type = 'Action Queue' WHERE type = 'ACTION_QUEUE'");
        $this->addSql('ALTER TABLE cron_log CHANGE type type ENUM(\'Monitor\', \'Database Cleanup\', \'Action Queue\') NOT NULL');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
