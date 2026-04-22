<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure catalog barcode columns exist on item_type and barcode table is merged (idempotent).';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $barcodeCodeCols = (int) $conn->fetchOne("SELECT COUNT(*) FROM pragma_table_info('item_type') WHERE name = 'barcode_code'");
        if (0 === $barcodeCodeCols) {
            $this->addSql('ALTER TABLE item_type ADD COLUMN barcode_code VARCHAR(100) DEFAULT NULL');
            $this->addSql('ALTER TABLE item_type ADD COLUMN barcode_type VARCHAR(50) DEFAULT NULL');
        }

        $barcodeTable = (int) $conn->fetchOne("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'barcode'");
        if (1 === $barcodeTable) {
            $this->addSql('UPDATE item_type SET barcode_code = (SELECT b.code FROM barcode b WHERE b.rowid = (SELECT MIN(b2.rowid) FROM barcode b2 WHERE b2.item_type_id = item_type.catalog_item_id)), barcode_type = (SELECT b.type FROM barcode b WHERE b.rowid = (SELECT MIN(b2.rowid) FROM barcode b2 WHERE b2.item_type_id = item_type.catalog_item_id))');
            $this->addSql('DROP TABLE barcode');
        }

        $uniq = (int) $conn->fetchOne("SELECT COUNT(*) FROM sqlite_master WHERE type = 'index' AND name = 'UNIQ_44EE13D27BAD6652'");
        if (0 === $uniq) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_44EE13D27BAD6652 ON item_type (barcode_code)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
