<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205085906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_queue ADD time_executed DATETIME(6) DEFAULT NULL');
        $this->addSql('CREATE INDEX action_queue_time_executed ON action_queue (time_executed)');
        $this->addSql('UPDATE action_queue SET time_executed = time_start WHERE executed = 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX action_queue_time_executed ON action_queue');
        $this->addSql('ALTER TABLE action_queue DROP time_executed');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
