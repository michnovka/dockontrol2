<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250219133832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users CHANGE role role ENUM(\'ROLE_USER\', \'ROLE_ADMIN\', \'ROLE_SUPER_ADMIN\', \'ROLE_LANDLORD\', \'ROLE_TENANT\') DEFAULT \'ROLE_USER\' NOT NULL');
        $this->addSql("UPDATE users SET role = 'ROLE_LANDLORD' WHERE role = 'ROLE_USER'");
        $this->addSql("ALTER TABLE users CHANGE role role ENUM('ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_LANDLORD', 'ROLE_TENANT') DEFAULT 'ROLE_LANDLORD' NOT NULL");
        $this->addSql('ALTER TABLE users ADD landlord_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9D48E7AED FOREIGN KEY (landlord_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_1483A5E9D48E7AED ON users (landlord_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE users CHANGE role role ENUM('ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_LANDLORD', 'ROLE_TENANT') DEFAULT 'ROLE_USER' NOT NULL");
        $this->addSql("UPDATE users SET role = 'ROLE_USER' WHERE role IN ('ROLE_LANDLORD', 'ROLE_TENANT')");
        $this->addSql('ALTER TABLE `users` CHANGE role role ENUM(\'ROLE_USER\', \'ROLE_ADMIN\', \'ROLE_SUPER_ADMIN\') DEFAULT \'ROLE_USER\' NOT NULL');
        $this->addSql('ALTER TABLE `users` DROP FOREIGN KEY FK_1483A5E9D48E7AED');
        $this->addSql('DROP INDEX IDX_1483A5E9D48E7AED ON `users`');
        $this->addSql('ALTER TABLE `users` DROP landlord_id');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
