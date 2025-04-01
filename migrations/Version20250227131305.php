<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250227131305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_action_log RENAME TO user_action_log');
        $this->addSql('ALTER TABLE user_action_log RENAME INDEX idx_7afb5000642b8210 TO IDX_15A23069642B8210');
        $this->addSql('ALTER TABLE user_action_log RENAME INDEX idx_7afb50006f949845 TO IDX_15A230696F949845');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_action_log RENAME TO admin_action_log');
        $this->addSql('ALTER TABLE user_action_log RENAME INDEX idx_15a23069642b8210 TO IDX_7AFB5000642B8210');
        $this->addSql('ALTER TABLE user_action_log RENAME INDEX idx_15a230696f949845 TO IDX_7AFB50006F949845');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
