<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add picnic_integration_settings.cart_display_name for Barcodile-local Picnic basket label.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE picnic_integration_settings ADD COLUMN cart_display_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE picnic_integration_settings DROP COLUMN cart_display_name');
    }
}
