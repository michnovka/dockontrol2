<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\ConfigName;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250127101658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate email settings to the config table and drop the email_setting table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO config (`key`, value)
            SELECT '" . ConfigName::EMAIL_HOST->value . "', host FROM email_setting
        ");
        $this->addSql("
            INSERT INTO config (`key`, value)
            SELECT '" . ConfigName::EMAIL_PORT->value . "', CAST(port AS CHAR) FROM email_setting
        ");
        $this->addSql("
            INSERT INTO config (`key`, value)
            SELECT '" . ConfigName::EMAIL_SENDER_MAIL->value . "', senders_email FROM email_setting
        ");
        $this->addSql("
            INSERT INTO config (`key`, value)
            SELECT '" . ConfigName::EMAIL_AUTHENTICATION_EMAIL->value . "', login FROM email_setting
        ");
        $this->addSql("
            INSERT INTO config (`key`, value)
            SELECT '" . ConfigName::EMAIL_AUTHENTICATION_PASSWORD->value . "', password FROM email_setting
        ");
        $this->addSql("
            INSERT INTO config (`key`, value)
            SELECT '" . ConfigName::EMAIL_USE_TLS->value . "', IF(use_tls = 1, 'yes', 'no') FROM email_setting
        ");
        $this->addSql("
            INSERT INTO config (`key`, value)
            SELECT '" . ConfigName::EMAIL_IGNORE_SSL_ERROR->value . "', IF(ignore_sslerror = 1, 'yes', 'no') FROM email_setting
        ");

        // Drop the email_setting table
        $this->addSql('DROP TABLE email_setting');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_setting (id INT AUTO_INCREMENT NOT NULL, host VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE utf8mb4_general_ci, port INT UNSIGNED NOT NULL, use_tls TINYINT(1) DEFAULT 0 NOT NULL, ignore_sslerror TINYINT(1) DEFAULT 0 NOT NULL, senders_email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE utf8mb4_general_ci, login VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE utf8mb4_general_ci, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE utf8mb4_general_ci, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql("
            INSERT INTO email_setting (host, port, use_tls, ignore_sslerror, senders_email, login, password)
            SELECT 
                (SELECT value FROM config WHERE `key` = '" . ConfigName::EMAIL_HOST->value . "') AS host,
                CAST((SELECT value FROM config WHERE `key` = '" . ConfigName::EMAIL_PORT->value . "') AS UNSIGNED) AS port,
                IF((SELECT value FROM config WHERE `key` = '" . ConfigName::EMAIL_USE_TLS->value . "') = 'yes', 1, 0) AS use_tls,
                IF((SELECT value FROM config WHERE `key` = '" . ConfigName::EMAIL_IGNORE_SSL_ERROR->value . "') = 'yes', 1, 0) AS ignore_sslerror,
                (SELECT value FROM config WHERE `key` = '" . ConfigName::EMAIL_SENDER_MAIL->value . "') AS senders_email,
                (SELECT value FROM config WHERE `key` = '" . ConfigName::EMAIL_AUTHENTICATION_EMAIL->value . "') AS login,
                (SELECT value FROM config WHERE `key` = '" . ConfigName::EMAIL_AUTHENTICATION_PASSWORD->value . "') AS password
        ");
    }


    public function isTransactional(): bool
    {
        return true;
    }
}
