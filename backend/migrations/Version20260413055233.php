<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413055233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace stock-centric schema with category, item type, barcode, inventory row, location, and item events.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS stock_item');
        $this->addSql('DROP TABLE IF EXISTS item_event');
        $this->addSql('DROP TABLE IF EXISTS barcode');
        $this->addSql('DROP TABLE IF EXISTS "item"');
        $this->addSql('DROP TABLE IF EXISTS item_type');
        $this->addSql('DROP TABLE IF EXISTS location');
        $this->addSql('DROP TABLE IF EXISTS category');
        $this->addSql('CREATE TABLE category (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE location (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, parent_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_5E9E89CB727ACA70 FOREIGN KEY (parent_id) REFERENCES location (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5E9E89CB727ACA70 ON location (parent_id)');
        $this->addSql('CREATE TABLE item_type (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, default_attributes CLOB DEFAULT NULL, category_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_44EE13D212469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_44EE13D212469DE2 ON item_type (category_id)');
        $this->addSql('CREATE TABLE barcode (id BLOB NOT NULL, code VARCHAR(100) NOT NULL, type VARCHAR(50) NOT NULL, item_type_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_97AE0266CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97AE026677153098 ON barcode (code)');
        $this->addSql('CREATE INDEX IDX_97AE0266CE11AAC7 ON barcode (item_type_id)');
        $this->addSql('CREATE TABLE "item" (id BLOB NOT NULL, quantity NUMERIC(12, 4) NOT NULL, unit VARCHAR(50) NOT NULL, expiration_date DATETIME DEFAULT NULL, custom_attributes CLOB DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, item_type_id BLOB NOT NULL, location_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_1F1B251ECE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1F1B251E64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1F1B251ECE11AAC7 ON "item" (item_type_id)');
        $this->addSql('CREATE INDEX IDX_1F1B251E64D218E ON "item" (location_id)');
        $this->addSql('CREATE TABLE item_event (id BLOB NOT NULL, type VARCHAR(50) NOT NULL, payload CLOB DEFAULT NULL, created_at DATETIME NOT NULL, item_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_11091177126F525E FOREIGN KEY (item_id) REFERENCES "item" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_11091177126F525E ON item_event (item_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS item_event');
        $this->addSql('DROP TABLE IF EXISTS barcode');
        $this->addSql('DROP TABLE IF EXISTS "item"');
        $this->addSql('DROP TABLE IF EXISTS item_type');
        $this->addSql('DROP TABLE IF EXISTS location');
        $this->addSql('DROP TABLE IF EXISTS category');
        $this->addSql('CREATE TABLE item_type (id BLOB NOT NULL, barcode VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_44EE13D297AE0266 ON item_type (barcode)');
        $this->addSql('CREATE TABLE stock_item (id BLOB NOT NULL, amount NUMERIC(12, 4) NOT NULL, unit VARCHAR(32) NOT NULL, type_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_6017DDAC54C8C93 FOREIGN KEY (type_id) REFERENCES item_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6017DDAC54C8C93 ON stock_item (type_id)');
    }
}
