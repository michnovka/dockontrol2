<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250228072237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_deletion_request (id INT AUTO_INCREMENT NOT NULL, time DATETIME(6) NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_2AF6E2DFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_deletion_request ADD CONSTRAINT FK_2AF6E2DFA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE signup_codes DROP FOREIGN KEY FK_728DBB447C2D807B');
        $this->addSql('ALTER TABLE signup_codes ADD CONSTRAINT FK_728DBB447C2D807B FOREIGN KEY (new_user_id) REFERENCES `users` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE car_enter_details DROP FOREIGN KEY FK_B76F13A4A76ED395');
        $this->addSql('ALTER TABLE car_enter_details ADD CONSTRAINT FK_B76F13A4A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE email_change_log DROP FOREIGN KEY FK_F2A9F807A76ED395');
        $this->addSql('ALTER TABLE email_change_log ADD CONSTRAINT FK_F2A9F807A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change DROP FOREIGN KEY FK_39B077AFA76ED395');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change ADD CONSTRAINT FK_39B077AFA76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE action_queue DROP FOREIGN KEY FK_9833B3DF9A4AA658');
        $this->addSql('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DF9A4AA658 FOREIGN KEY (guest_id) REFERENCES guests (hash) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_deletion_request DROP FOREIGN KEY FK_2AF6E2DFA76ED395');
        $this->addSql('DROP TABLE user_deletion_request');

        $this->addSql('ALTER TABLE signup_codes DROP FOREIGN KEY FK_728DBB447C2D807B');
        $this->addSql('ALTER TABLE signup_codes ADD CONSTRAINT FK_728DBB447C2D807B FOREIGN KEY (new_user_id) REFERENCES users (id)');

        $this->addSql('ALTER TABLE car_enter_details DROP FOREIGN KEY FK_B76F13A4A76ED395');
        $this->addSql('ALTER TABLE car_enter_details ADD CONSTRAINT FK_B76F13A4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');

        $this->addSql('ALTER TABLE email_change_log DROP FOREIGN KEY FK_F2A9F807A76ED395');
        $this->addSql('ALTER TABLE email_change_log ADD CONSTRAINT FK_F2A9F807A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');

        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change DROP FOREIGN KEY FK_39B077AFA76ED395');
        $this->addSql('ALTER TABLE dockontrol_node_users_to_notify_when_status_change ADD CONSTRAINT FK_39B077AFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');

        $this->addSql('ALTER TABLE action_queue DROP FOREIGN KEY FK_9833B3DF9A4AA658');
        $this->addSql('ALTER TABLE action_queue ADD CONSTRAINT FK_9833B3DF9A4AA658 FOREIGN KEY (guest_id) REFERENCES guests (hash)');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
