<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427163520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add scanner automation printer settings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scanner_device ADD automation_print_inventory_label_on_barcode_scan BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE scanner_device ADD automation_printer_device_id UUID DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scanner_device DROP automation_print_inventory_label_on_barcode_scan');
        $this->addSql('ALTER TABLE scanner_device DROP automation_printer_device_id');
    }
}
