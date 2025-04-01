<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250110093156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE building_permission DROP FOREIGN KEY FK_CE01FD2F4D2A7E12');
        $this->addSql('ALTER TABLE building_permission ADD CONSTRAINT FK_CE01FD2F4D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE group_permission DROP FOREIGN KEY FK_3784F318FE54D947');
        $this->addSql('ALTER TABLE group_permission ADD CONSTRAINT FK_3784F318FE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9DFE54D947');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DFE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE group_permission DROP FOREIGN KEY FK_3784F318FE54D947');
        $this->addSql('ALTER TABLE group_permission ADD CONSTRAINT FK_3784F318FE54D947 FOREIGN KEY (group_id) REFERENCES groups (id)');
        $this->addSql('ALTER TABLE user_group DROP FOREIGN KEY FK_8F02BF9DFE54D947');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DFE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE building_permission DROP FOREIGN KEY FK_CE01FD2F4D2A7E12');
        $this->addSql('ALTER TABLE building_permission ADD CONSTRAINT FK_CE01FD2F4D2A7E12 FOREIGN KEY (building_id) REFERENCES buildings (id)');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
