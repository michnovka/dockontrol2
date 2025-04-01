<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250107103152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_queue DROP FOREIGN KEY FK_9833B3DF9A4AA658');
        $this->addSql('ALTER TABLE action_queue CHANGE guest_id guest_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DF9A4AA658 FOREIGN KEY (guest_id) REFERENCES guests (hash)');
        $this->addSql('ALTER TABLE guests MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `primary` ON guests');
        $this->addSql('ALTER TABLE guests DROP id');
        $this->addSql('ALTER TABLE guests ADD PRIMARY KEY (hash)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guests ADD id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE action_queue DROP FOREIGN KEY FK_9833B3DF9A4AA658');
        $this->addSql('ALTER TABLE action_queue CHANGE guest_id guest_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DF9A4AA658 FOREIGN KEY (guest_id) REFERENCES guests (id)');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
