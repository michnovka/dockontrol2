<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250204113104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE action_queue_cron_group (name VARCHAR(150) NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('INSERT INTO action_queue_cron_group (name) SELECT name FROM cron_group');

        $this->addSql('ALTER TABLE actions ADD action_queue_cron_group VARCHAR(150) NOT NULL');
        $this->addSql('UPDATE actions SET action_queue_cron_group = cron_group');

        $this->addSql('ALTER TABLE actions DROP FOREIGN KEY FK_548F1EF8377B003');
        $this->addSql('DROP INDEX IDX_548F1EF8377B003 ON actions');

        $this->addSql('ALTER TABLE actions DROP cron_group');
        $this->addSql('ALTER TABLE actions ADD CONSTRAINT FK_548F1EFF2833F6F FOREIGN KEY (action_queue_cron_group) REFERENCES action_queue_cron_group (name)');
        $this->addSql('CREATE INDEX IDX_548F1EFF2833F6F ON actions (action_queue_cron_group)');

        $this->addSql('ALTER TABLE cron_log DROP FOREIGN KEY FK_7C0163B38377B003');
        $this->addSql('ALTER TABLE cron_log ADD CONSTRAINT FK_7C0163B38377B003 FOREIGN KEY (cron_group) REFERENCES action_queue_cron_group (name) ON DELETE CASCADE');

        $this->addSql('DROP TABLE cron_group');
        $this->addSql('ALTER TABLE action_queue ADD is_immediate TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Step 1: Recreate old `cron_group` table
        $this->addSql('CREATE TABLE cron_group (name VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');

        // Step 2: Restore data from `action_queue_cron_group`
        $this->addSql('INSERT INTO cron_group (name) SELECT name FROM action_queue_cron_group');

        // Step 3: Restore old `actions` structure
        $this->addSql('ALTER TABLE actions ADD cron_group VARCHAR(150) NOT NULL');
        $this->addSql('UPDATE actions SET cron_group = action_queue_cron_group');

        $this->addSql('ALTER TABLE actions DROP FOREIGN KEY FK_548F1EFF2833F6F');
        $this->addSql('DROP INDEX IDX_548F1EFF2833F6F ON actions');

        $this->addSql('ALTER TABLE actions DROP action_queue_cron_group');
        $this->addSql('ALTER TABLE actions ADD CONSTRAINT FK_548F1EF8377B003 FOREIGN KEY (cron_group) REFERENCES cron_group (name)');
        $this->addSql('CREATE INDEX IDX_548F1EF8377B003 ON actions (cron_group)');

        // Step 4: Restore `cron_log` references
        $this->addSql('ALTER TABLE cron_log DROP FOREIGN KEY FK_7C0163B38377B003');
        $this->addSql('ALTER TABLE cron_log ADD CONSTRAINT FK_7C0163B38377B003 FOREIGN KEY (cron_group) REFERENCES cron_group (name) ON DELETE CASCADE');

        // Step 5: Drop the temporary table
        $this->addSql('DROP TABLE action_queue_cron_group');

        // Step 6: Remove the new column from `action_queue`
        $this->addSql('ALTER TABLE action_queue DROP is_immediate');
    }


    public function isTransactional(): bool
    {
        return false;
    }
}
