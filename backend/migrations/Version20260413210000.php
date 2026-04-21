<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add picnic_integration_settings.auth_key_cipher for stored Picnic session.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE picnic_integration_settings ADD COLUMN auth_key_cipher CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE picnic_integration_settings DROP COLUMN auth_key_cipher');
    }
}
