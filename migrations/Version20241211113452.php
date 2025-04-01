<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241211113452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cameras ADD dockontrol_node_payload JSON NOT NULL, ADD dockontrol_node_id INT UNSIGNED DEFAULT NULL');
        $this->addSql("
            UPDATE cameras
            SET 
                dockontrol_node_id = CASE 
                    WHEN stream_url LIKE 'http://10.8.0.11%' THEN 5 -- ID for Z1
                    WHEN stream_url LIKE 'http://192.168.1.100%' THEN 1 -- ID for Z9.B2
                END,
                dockontrol_node_payload = CASE
                    WHEN stream_url LIKE 'http://10.8.0.11%' THEN 
                        JSON_OBJECT(
                            'protocol', 'http',
                            'host', SUBSTRING_INDEX(SUBSTRING_INDEX(stream_url, 'ip=', -1), '&', 1),
                            'login', SUBSTRING_INDEX(SUBSTRING_INDEX(stream_url, 'login=', -1), '&', 1),
                            'channel', SUBSTRING_INDEX(stream_url, 'channel=', -1)
                        )
                    WHEN stream_url LIKE 'http://192.168.1.100%' THEN
                        JSON_OBJECT(
                            'protocol', 'http',
                            'host', SUBSTRING_INDEX(SUBSTRING_INDEX(stream_url, '/', 3), '//', -1),
                            'login', stream_login,
                            'channel', SUBSTRING_INDEX(SUBSTRING_INDEX(stream_url, '/', -2), '/', 1)
                        )
                END
        ");
        $this->addSql('ALTER TABLE cameras ADD CONSTRAINT FK_6B5F276AFA7F6ADF FOREIGN KEY (dockontrol_node_id) REFERENCES dockontrol_nodes (id)');
        $this->addSql("ALTER TABLE cameras DROP stream_url, DROP stream_login");
        $this->addSql('CREATE INDEX IDX_6B5F276AFA7F6ADF ON cameras (dockontrol_node_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cameras DROP FOREIGN KEY FK_6B5F276AFA7F6ADF');
        $this->addSql('DROP INDEX IDX_6B5F276AFA7F6ADF ON cameras');
        $this->addSql('ALTER TABLE cameras ADD stream_url VARCHAR(255) NOT NULL, ADD stream_login VARCHAR(255) DEFAULT NULL, DROP dockontrol_node_payload, DROP dockontrol_node_id');
        $this->addSql('ALTER TABLE car_enter_details ADD wait_seconds_after_enter INT NOT NULL, ADD wait_seconds_before_exit INT NOT NULL');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
