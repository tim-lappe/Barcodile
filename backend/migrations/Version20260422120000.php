<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename code_scanner to scanner_device; rename columns to scanner_device_id and device_identifier.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE code_scanner RENAME TO scanner_device');
        $this->addSql('ALTER TABLE scanner_device RENAME COLUMN scanner_id TO scanner_device_id');
        $this->addSql('ALTER TABLE scanner_device RENAME COLUMN device TO device_identifier');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scanner_device RENAME COLUMN device_identifier TO device');
        $this->addSql('ALTER TABLE scanner_device RENAME COLUMN scanner_device_id TO scanner_id');
        $this->addSql('ALTER TABLE scanner_device RENAME TO code_scanner');
    }
}
