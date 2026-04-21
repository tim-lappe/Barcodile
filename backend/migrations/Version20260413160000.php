<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename item table to inventory_item (InventoryItem entity).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "item" RENAME TO inventory_item');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_item RENAME TO "item"');
    }
}
