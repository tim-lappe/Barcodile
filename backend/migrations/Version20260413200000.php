<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add picnic_integration_settings for Picnic integration preferences.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE picnic_integration_settings (id BLOB NOT NULL, enabled BOOLEAN NOT NULL, username VARCHAR(255) DEFAULT NULL, country_code VARCHAR(2) NOT NULL, api_version VARCHAR(16) NOT NULL, custom_api_url VARCHAR(2048) DEFAULT NULL, password_cipher CLOB DEFAULT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE picnic_integration_settings');
    }
}
