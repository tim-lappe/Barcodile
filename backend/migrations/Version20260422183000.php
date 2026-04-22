<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Scanner device inventory automation toggles.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scanner_device ADD COLUMN automation_add_inventory_on_ean_scan BOOLEAN NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE scanner_device ADD COLUMN automation_create_catalog_item_if_missing_for_ean BOOLEAN NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE scanner_device ADD COLUMN automation_remove_inventory_on_public_code_scan BOOLEAN NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__scanner_device AS SELECT scanner_device_id, device_identifier, name, last_scanned_codes FROM scanner_device');
        $this->addSql('DROP TABLE scanner_device');
        $this->addSql('CREATE TABLE scanner_device (scanner_device_id BLOB NOT NULL, device_identifier VARCHAR(512) NOT NULL, name VARCHAR(255) NOT NULL, last_scanned_codes CLOB DEFAULT NULL, PRIMARY KEY (scanner_device_id))');
        $this->addSql('INSERT INTO scanner_device (scanner_device_id, device_identifier, name, last_scanned_codes) SELECT scanner_device_id, device_identifier, name, last_scanned_codes FROM __temp__scanner_device');
        $this->addSql('DROP TABLE __temp__scanner_device');
    }
}
