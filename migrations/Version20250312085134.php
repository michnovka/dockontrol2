<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250312085134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE announcement (id INT AUTO_INCREMENT NOT NULL, start_time DATETIME(6) DEFAULT NULL, end_time DATETIME(6) DEFAULT NULL, created_time DATETIME(6) NOT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, building_id INT DEFAULT NULL, created_by_id INT NOT NULL, INDEX IDX_4DB9D91C4D2A7E12 (building_id), INDEX IDX_4DB9D91CB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE announcement ADD CONSTRAINT FK_4DB9D91C4D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id)');
        $this->addSql('ALTER TABLE announcement ADD CONSTRAINT FK_4DB9D91CB03A8386 FOREIGN KEY (created_by_id) REFERENCES `users` (id)');

        $this->addSql('CREATE INDEX announcement_start_time_index ON announcement (start_time)');
        $this->addSql('CREATE INDEX announcement_end_time_index ON announcement (end_time)');
        $this->addSql('CREATE INDEX announcement_start_time_and_end_time_index ON announcement (start_time, end_time)');

        $this->addSql('DELETE FROM config WHERE `key` = ?', ['announcement']);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE announcement DROP FOREIGN KEY FK_4DB9D91C4D2A7E12');
        $this->addSql('ALTER TABLE announcement DROP FOREIGN KEY FK_4DB9D91CB03A8386');
        $this->addSql('DROP INDEX announcement_start_time_index ON announcement');
        $this->addSql('DROP INDEX announcement_end_time_index ON announcement');
        $this->addSql('DROP INDEX announcement_start_time_and_end_time_index ON announcement');
        $this->addSql('DROP TABLE announcement');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
