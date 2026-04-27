<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename scanner_device automation columns from ean to barcode.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'scanner_device' AND column_name = 'automation_add_inventory_on_ean_scan') THEN
    ALTER TABLE scanner_device RENAME COLUMN automation_add_inventory_on_ean_scan TO automation_add_inventory_on_barcode_scan;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'scanner_device' AND column_name = 'automation_create_catalog_item_if_missing_for_ean') THEN
    ALTER TABLE scanner_device RENAME COLUMN automation_create_catalog_item_if_missing_for_ean TO automation_create_catalog_item_if_missing_for_barcode;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'scanner_device' AND column_name = 'automation_print_inventory_label_on_ean_scan') THEN
    ALTER TABLE scanner_device RENAME COLUMN automation_print_inventory_label_on_ean_scan TO automation_print_inventory_label_on_barcode_scan;
  END IF;
END$$;
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'scanner_device' AND column_name = 'automation_add_inventory_on_barcode_scan') THEN
    ALTER TABLE scanner_device RENAME COLUMN automation_add_inventory_on_barcode_scan TO automation_add_inventory_on_ean_scan;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'scanner_device' AND column_name = 'automation_create_catalog_item_if_missing_for_barcode') THEN
    ALTER TABLE scanner_device RENAME COLUMN automation_create_catalog_item_if_missing_for_barcode TO automation_create_catalog_item_if_missing_for_ean;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'scanner_device' AND column_name = 'automation_print_inventory_label_on_barcode_scan') THEN
    ALTER TABLE scanner_device RENAME COLUMN automation_print_inventory_label_on_barcode_scan TO automation_print_inventory_label_on_ean_scan;
  END IF;
END$$;
SQL);
    }
}
