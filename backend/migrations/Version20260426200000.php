<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260426200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add printer_device for CUPS-backed printers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE printer_device (printer_device_id UUID NOT NULL, cups_queue_name VARCHAR(512) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (printer_device_id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE printer_device');
    }
}
