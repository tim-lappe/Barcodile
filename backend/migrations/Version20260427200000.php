<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'printer_device: driver_code + connection JSON; drop cups_queue_name.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE printer_device ADD driver_code VARCHAR(64) DEFAULT \'brother_ql\' NOT NULL');
        $this->addSql('ALTER TABLE printer_device ADD connection JSON DEFAULT \'{}\' NOT NULL');
        $this->addSql("UPDATE printer_device SET connection = jsonb_build_object('model', 'QL-800', 'printerIdentifier', cups_queue_name, 'backend', 'pyusb', 'labelSize', '29x90') WHERE cups_queue_name IS NOT NULL AND cups_queue_name <> ''");
        $this->addSql('ALTER TABLE printer_device DROP COLUMN cups_queue_name');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE printer_device ADD cups_queue_name VARCHAR(512) DEFAULT \'\' NOT NULL');
        $this->addSql("UPDATE printer_device SET cups_queue_name = COALESCE(connection->>'printerIdentifier', '')");
        $this->addSql('ALTER TABLE printer_device DROP COLUMN connection');
        $this->addSql('ALTER TABLE printer_device DROP COLUMN driver_code');
    }
}
