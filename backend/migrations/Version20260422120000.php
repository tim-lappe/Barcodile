<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store catalog item barcode as 1:1 columns on item_type; drop barcode table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_type ADD COLUMN barcode_code VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE item_type ADD COLUMN barcode_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('UPDATE item_type SET barcode_code = (SELECT b.code FROM barcode b WHERE b.rowid = (SELECT MIN(b2.rowid) FROM barcode b2 WHERE b2.item_type_id = item_type.catalog_item_id)), barcode_type = (SELECT b.type FROM barcode b WHERE b.rowid = (SELECT MIN(b2.rowid) FROM barcode b2 WHERE b2.item_type_id = item_type.catalog_item_id))');
        $this->addSql('DROP TABLE barcode');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_44EE13D27BAD6652 ON item_type (barcode_code)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
