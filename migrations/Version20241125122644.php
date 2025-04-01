<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241125122644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE custom_sorting_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, sort_index INT UNSIGNED NOT NULL, column_size INT UNSIGNED NOT NULL, user_id INT NOT NULL, INDEX IDX_ACF4DB7DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE custom_sorting (id INT AUTO_INCREMENT NOT NULL, sort_index INT UNSIGNED NOT NULL, custom_button_icon ENUM(\'building\', \'elevator\', \'entrance\', \'entrance_pedestrian\', \'garage\', \'gate\', \'nuki\', \'enter\', \'exit\') DEFAULT NULL, custom_button_style ENUM(\'basic\', \'blue\', \'red\') DEFAULT NULL, custom_name VARCHAR(255) DEFAULT NULL, button_id VARCHAR(63) NOT NULL, custom_sorting_group_id INT NOT NULL, INDEX IDX_B6F60793A123E519 (button_id), INDEX IDX_B6F60793E274144E (custom_sorting_group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE custom_sorting ADD CONSTRAINT FK_B6F60793A123E519 FOREIGN KEY (button_id) REFERENCES buttons (id)');
        $this->addSql('ALTER TABLE custom_sorting ADD CONSTRAINT FK_B6F60793E274144E FOREIGN KEY (custom_sorting_group_id) REFERENCES custom_sorting_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_sorting_group ADD CONSTRAINT FK_ACF4DB7DA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE users ADD custom_sorting TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE custom_sorting DROP FOREIGN KEY FK_B6F60793A123E519');
        $this->addSql('ALTER TABLE custom_sorting DROP FOREIGN KEY FK_B6F60793E274144E');
        $this->addSql('ALTER TABLE custom_sorting_group DROP FOREIGN KEY FK_ACF4DB7DA76ED395');
        $this->addSql('DROP TABLE custom_sorting');
        $this->addSql('DROP TABLE custom_sorting_group');
        $this->addSql('ALTER TABLE `users` DROP custom_sorting');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
