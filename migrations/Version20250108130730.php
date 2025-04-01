<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250108130730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_user_order ON car_enter_details (user_id, `order`)');
        $this->addSql('CREATE INDEX idx_building_order ON car_enter_details (building_id, `order`)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_user_order ON car_enter_details');
        $this->addSql('DROP INDEX idx_building_order ON car_enter_details');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
