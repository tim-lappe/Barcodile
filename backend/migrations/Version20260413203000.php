<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove picnic_integration_settings.api_version and custom_api_url.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE picnic_integration_settings DROP COLUMN api_version');
        $this->addSql('ALTER TABLE picnic_integration_settings DROP COLUMN custom_api_url');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE picnic_integration_settings ADD COLUMN api_version VARCHAR(16) NOT NULL DEFAULT \'15\'');
        $this->addSql('ALTER TABLE picnic_integration_settings ADD COLUMN custom_api_url VARCHAR(2048) DEFAULT NULL');
    }
}
