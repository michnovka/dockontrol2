<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250226101634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD time_tos_accepted DATETIME(6) DEFAULT NULL');
        $this->addSql('ALTER TABLE guests ADD time_tos_accepted DATETIME(6) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `users` DROP time_tos_accepted');
        $this->addSql('ALTER TABLE guests DROP time_tos_accepted');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
