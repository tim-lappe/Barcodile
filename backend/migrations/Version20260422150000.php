<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_scanned_codes JSON array to scanner_device.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scanner_device ADD COLUMN last_scanned_codes CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scanner_device DROP COLUMN last_scanned_codes');
    }
}
