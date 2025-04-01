<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250108114416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX queue_time_start_status_index ON action_queue (time_start, status)');
        $this->addSql('ALTER TABLE action_queue RENAME INDEX queue_users_id_fk TO IDX_9833B3DFA76ED395');
        $this->addSql('ALTER TABLE action_queue RENAME INDEX action_queue_guests_id_fk TO IDX_9833B3DF9A4AA658');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX queue_time_start_status_index ON action_queue');
        $this->addSql('ALTER TABLE action_queue RENAME INDEX idx_9833b3df9a4aa658 TO action_queue_guests_id_fk');
        $this->addSql('ALTER TABLE action_queue RENAME INDEX idx_9833b3dfa76ed395 TO queue_users_id_fk');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
