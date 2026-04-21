<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store type attribute values on item_type_attribute; drop inventory_item.custom_attributes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_type_attribute ADD COLUMN value CLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE item_type_attribute DROP COLUMN required');
        $this->addSql('ALTER TABLE inventory_item DROP COLUMN custom_attributes');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_item ADD COLUMN custom_attributes CLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE item_type_attribute ADD COLUMN required BOOLEAN NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE item_type_attribute DROP COLUMN value');
    }
}
