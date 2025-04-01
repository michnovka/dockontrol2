<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250101051858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX queue_executed_index ON action_queue');
        $this->addSql('ALTER TABLE action_queue ADD status ENUM(\'QUEUED\', \'EXECUTED\', \'FAILED\') DEFAULT \'QUEUED\' NOT NULL');
        $this->addSql('UPDATE action_queue SET status = \'EXECUTED\' WHERE executed = 1');
        $this->addSql('ALTER TABLE action_queue DROP executed');
        $this->addSql('CREATE INDEX queue_status_index ON action_queue (status)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX queue_status_index ON action_queue');
        $this->addSql('ALTER TABLE action_queue ADD executed TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE action_queue SET executed = 1 WHERE status = \'EXECUTED\'');
        $this->addSql('ALTER TABLE action_queue DROP status');
        $this->addSql('CREATE INDEX queue_executed_index ON action_queue (executed)');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
