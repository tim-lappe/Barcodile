<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop picnic_integration_settings.enabled; integration is implied by stored credentials.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE picnic_integration_settings DROP COLUMN enabled');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE picnic_integration_settings ADD COLUMN enabled BOOLEAN DEFAULT false NOT NULL');
    }
}
