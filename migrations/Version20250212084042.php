<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250212084042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE custom_sorting_group ADD is_group_for_modal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE custom_sorting_group ADD CONSTRAINT FK_ACF4DB7DA7F08D15 FOREIGN KEY (is_group_for_modal_id) REFERENCES custom_sorting (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_ACF4DB7DA7F08D15 ON custom_sorting_group (is_group_for_modal_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE custom_sorting_group DROP FOREIGN KEY FK_ACF4DB7DA7F08D15');
        $this->addSql('DROP INDEX IDX_ACF4DB7DA7F08D15 ON custom_sorting_group');
        $this->addSql('ALTER TABLE custom_sorting_group DROP is_group_for_modal_id');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
