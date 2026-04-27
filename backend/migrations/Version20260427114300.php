<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427114300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store printed label PNG history per printer device.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE printed_label (printed_label_id UUID NOT NULL, printer_device_id UUID NOT NULL, driver_code VARCHAR(64) NOT NULL, label_width_millimeters INT NOT NULL, label_height_millimeters INT NOT NULL, png_bytes BYTEA NOT NULL, source VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (printed_label_id))');
        $this->addSql('CREATE INDEX idx_printed_label_printer_created_at ON printed_label (printer_device_id, created_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE printed_label');
    }
}
