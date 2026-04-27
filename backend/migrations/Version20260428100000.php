<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Scanner: optional post-EAN print automation and label printer id.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scanner_device ADD automation_print_label_after_ean_add_inventory BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE scanner_device ADD automation_label_printer_device_id UUID DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scanner_device DROP automation_label_printer_device_id');
        $this->addSql('ALTER TABLE scanner_device DROP automation_print_label_after_ean_add_inventory');
    }
}
