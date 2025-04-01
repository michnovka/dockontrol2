<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241212042556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cameras RENAME INDEX idx_6b5f276afa7f6adf TO IDX_6B5F276AD0DE5EAE');
        $this->addSql('ALTER TABLE car_enter_details ADD wait_seconds_after_enter INT NOT NULL, ADD wait_seconds_before_exit INT NOT NULL');
        $this->addSql('UPDATE car_enter_details SET wait_seconds_after_enter = 10, wait_seconds_before_exit = 10');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cameras RENAME INDEX idx_6b5f276ad0de5eae TO IDX_6B5F276AFA7F6ADF');
        $this->addSql('ALTER TABLE car_enter_details DROP wait_seconds_after_enter, DROP wait_seconds_before_exit');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
