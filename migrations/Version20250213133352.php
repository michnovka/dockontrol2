<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250213133352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD phone_verified TINYINT(1) DEFAULT 0 NOT NULL, ADD phone_country_prefix INT NOT NULL, CHANGE phone phone VARCHAR(32) NOT NULL');


        $this->addSql("UPDATE users SET phone = REGEXP_REPLACE(phone, '^00', '') WHERE phone LIKE '00%'");
        $this->addSql("UPDATE users SET phone = REGEXP_REPLACE(phone, '^0', '') WHERE phone LIKE '0%'");
        $this->addSql(" UPDATE users SET phone_country_prefix = 
                            CASE WHEN phone LIKE '972%' THEN '972' WHEN phone LIKE '420%' THEN '420' 
                            WHEN phone LIKE '421%' THEN '421' WHEN phone LIKE '49%' THEN '49' WHEN phone LIKE '380%' 
                            THEN '380' WHEN phone LIKE '34%' THEN '34' ELSE phone_country_prefix END ");
        $this->addSql(" UPDATE users SET phone = CASE WHEN phone LIKE '972%' THEN SUBSTRING(phone FROM 4) 
                            WHEN phone LIKE '420%' THEN SUBSTRING(phone FROM 4) WHEN phone LIKE '421%' 
                            THEN SUBSTRING(phone FROM 4) WHEN phone LIKE '49%' THEN SUBSTRING(phone FROM 3) WHEN phone 
                            LIKE '380%' THEN SUBSTRING(phone FROM 4) WHEN phone LIKE '34%' THEN SUBSTRING(phone FROM 3) ELSE phone END ");
        $this->addSql(" UPDATE users SET phone_country_prefix = '420' WHERE phone_country_prefix = 0 ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `users` DROP phone_verified, DROP phone_country_prefix, CHANGE phone phone VARCHAR(255) NOT NULL');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
