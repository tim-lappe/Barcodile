<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Inventory item: public_code sequence, one row per physical unit; drop quantity.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_item ADD COLUMN public_code VARCHAR(32) DEFAULT NULL');
        $this->addSql('UPDATE inventory_item SET public_code = (SELECT CAST(t.row_num AS TEXT) FROM (SELECT inventory_item_id AS iid, ROW_NUMBER() OVER (ORDER BY datetime(created_at), lower(hex(inventory_item_id))) AS row_num FROM inventory_item) AS t WHERE t.iid = inventory_item.inventory_item_id) WHERE public_code IS NULL');
        $this->addSql('CREATE TABLE inventory_item_public_code_seq (id INTEGER PRIMARY KEY NOT NULL, next_value INTEGER NOT NULL, CHECK (id = 1))');
        $this->addSql('INSERT INTO inventory_item_public_code_seq (id, next_value) SELECT 1, COALESCE((SELECT MAX(CAST(public_code AS INTEGER)) FROM inventory_item), 0)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__inventory_item AS SELECT inventory_item_id, public_code, expiration_date, created_at, item_type_id, location_id FROM inventory_item');
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql('CREATE TABLE inventory_item (inventory_item_id BLOB NOT NULL, public_code VARCHAR(32) NOT NULL, expiration_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, item_type_id BLOB NOT NULL, location_id BLOB DEFAULT NULL, PRIMARY KEY (inventory_item_id), CONSTRAINT FK_55BDEA30CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_55BDEA3064D218E FOREIGN KEY (location_id) REFERENCES location (location_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO inventory_item (inventory_item_id, public_code, expiration_date, created_at, item_type_id, location_id) SELECT inventory_item_id, public_code, expiration_date, created_at, item_type_id, location_id FROM __temp__inventory_item');
        $this->addSql('DROP TABLE __temp__inventory_item');
        $this->addSql('CREATE INDEX IDX_55BDEA30CE11AAC7 ON inventory_item (item_type_id)');
        $this->addSql('CREATE INDEX IDX_55BDEA3064D218E ON inventory_item (location_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55BDEA30C487249 ON inventory_item (public_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_55BDEA30C487249');
        $this->addSql('CREATE TEMPORARY TABLE __temp__inventory_item AS SELECT inventory_item_id, public_code, expiration_date, created_at, item_type_id, location_id FROM inventory_item');
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql('CREATE TABLE inventory_item (inventory_item_id BLOB NOT NULL, quantity NUMERIC(12, 4) NOT NULL DEFAULT 1, expiration_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, item_type_id BLOB NOT NULL, location_id BLOB DEFAULT NULL, PRIMARY KEY (inventory_item_id), CONSTRAINT FK_55BDEA30CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_55BDEA3064D218E FOREIGN KEY (location_id) REFERENCES location (location_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO inventory_item (inventory_item_id, quantity, expiration_date, created_at, item_type_id, location_id) SELECT inventory_item_id, 1, expiration_date, created_at, item_type_id, location_id FROM __temp__inventory_item');
        $this->addSql('DROP TABLE __temp__inventory_item');
        $this->addSql('CREATE INDEX IDX_55BDEA30CE11AAC7 ON inventory_item (item_type_id)');
        $this->addSql('CREATE INDEX IDX_55BDEA3064D218E ON inventory_item (location_id)');
        $this->addSql('DROP TABLE inventory_item_public_code_seq');
    }
}
