<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421163818 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__barcode AS SELECT id, code, type, item_type_id FROM barcode');
        $this->addSql('DROP TABLE barcode');
        $this->addSql('CREATE TABLE barcode (id BLOB NOT NULL, code VARCHAR(100) NOT NULL, type VARCHAR(50) NOT NULL, item_type_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_97AE0266CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO barcode (id, code, type, item_type_id) SELECT id, code, type, item_type_id FROM __temp__barcode');
        $this->addSql('DROP TABLE __temp__barcode');
        $this->addSql('CREATE INDEX IDX_97AE0266CE11AAC7 ON barcode (item_type_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97AE026677153098 ON barcode (code)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__cart_stock_automation_rule AS SELECT id, stock_below, add_quantity, enabled, created_at, catalog_item_id, shopping_cart_id FROM cart_stock_automation_rule');
        $this->addSql('DROP TABLE cart_stock_automation_rule');
        $this->addSql('CREATE TABLE cart_stock_automation_rule (rule_id BLOB NOT NULL, stock_below INTEGER NOT NULL, add_quantity INTEGER NOT NULL, enabled BOOLEAN DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, catalog_item_id BLOB NOT NULL, shopping_cart_id BLOB NOT NULL, PRIMARY KEY (rule_id), CONSTRAINT FK_CART_AUTOMATION_CART FOREIGN KEY (shopping_cart_id) REFERENCES shopping_cart (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E8E9CA6E1DDDAF72 FOREIGN KEY (catalog_item_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO cart_stock_automation_rule (rule_id, stock_below, add_quantity, enabled, created_at, catalog_item_id, shopping_cart_id) SELECT id, stock_below, add_quantity, enabled, created_at, catalog_item_id, shopping_cart_id FROM __temp__cart_stock_automation_rule');
        $this->addSql('DROP TABLE __temp__cart_stock_automation_rule');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CART_AUTOMATION_CATALOG_CART ON cart_stock_automation_rule (catalog_item_id, shopping_cart_id)');
        $this->addSql('CREATE INDEX IDX_E8E9CA6E1DDDAF72 ON cart_stock_automation_rule (catalog_item_id)');
        $this->addSql('CREATE INDEX IDX_E8E9CA6E45F80CD ON cart_stock_automation_rule (shopping_cart_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__code_scanner AS SELECT id, device, name FROM code_scanner');
        $this->addSql('DROP TABLE code_scanner');
        $this->addSql('CREATE TABLE code_scanner (scanner_id BLOB NOT NULL, device VARCHAR(512) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (scanner_id))');
        $this->addSql('INSERT INTO code_scanner (scanner_id, device, name) SELECT id, device, name FROM __temp__code_scanner');
        $this->addSql('DROP TABLE __temp__code_scanner');
        $this->addSql('CREATE TEMPORARY TABLE __temp__inventory_item AS SELECT id, quantity, expiration_date, created_at, item_type_id, location_id FROM inventory_item');
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql('CREATE TABLE inventory_item (inventory_item_id BLOB NOT NULL, quantity NUMERIC(12, 4) NOT NULL, expiration_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, item_type_id BLOB NOT NULL, location_id BLOB DEFAULT NULL, PRIMARY KEY (inventory_item_id), CONSTRAINT FK_55BDEA30CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_55BDEA3064D218E FOREIGN KEY (location_id) REFERENCES location (location_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO inventory_item (inventory_item_id, quantity, expiration_date, created_at, item_type_id, location_id) SELECT id, quantity, expiration_date, created_at, item_type_id, location_id FROM __temp__inventory_item');
        $this->addSql('DROP TABLE __temp__inventory_item');
        $this->addSql('CREATE INDEX IDX_55BDEA30CE11AAC7 ON inventory_item (item_type_id)');
        $this->addSql('CREATE INDEX IDX_55BDEA3064D218E ON inventory_item (location_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__item_type AS SELECT id, name, volume_amount, volume_unit, weight_amount, weight_unit, image_file_name FROM item_type');
        $this->addSql('DROP TABLE item_type');
        $this->addSql('CREATE TABLE item_type (catalog_item_id BLOB NOT NULL, name VARCHAR(255) NOT NULL, volume_amount NUMERIC(12, 4) DEFAULT NULL, volume_unit VARCHAR(10) DEFAULT NULL, weight_amount NUMERIC(12, 4) DEFAULT NULL, weight_unit VARCHAR(10) DEFAULT NULL, image_file_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY (catalog_item_id))');
        $this->addSql('INSERT INTO item_type (catalog_item_id, name, volume_amount, volume_unit, weight_amount, weight_unit, image_file_name) SELECT id, name, volume_amount, volume_unit, weight_amount, weight_unit, image_file_name FROM __temp__item_type');
        $this->addSql('DROP TABLE __temp__item_type');
        $this->addSql('CREATE TEMPORARY TABLE __temp__item_type_attribute AS SELECT id, item_type_id, item_attribute, value FROM item_type_attribute');
        $this->addSql('DROP TABLE item_type_attribute');
        $this->addSql('CREATE TABLE item_type_attribute (attribute_id BLOB NOT NULL, item_type_id BLOB NOT NULL, item_attribute VARCHAR(32) NOT NULL, value CLOB DEFAULT NULL, PRIMARY KEY (attribute_id), CONSTRAINT FK_5F51091FCE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO item_type_attribute (attribute_id, item_type_id, item_attribute, value) SELECT id, item_type_id, item_attribute, value FROM __temp__item_type_attribute');
        $this->addSql('DROP TABLE __temp__item_type_attribute');
        $this->addSql('CREATE INDEX IDX_5F51091FCE11AAC7 ON item_type_attribute (item_type_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__location AS SELECT id, name, parent_id FROM location');
        $this->addSql('DROP TABLE location');
        $this->addSql('CREATE TABLE location (location_id BLOB NOT NULL, name VARCHAR(255) NOT NULL, parent_id BLOB DEFAULT NULL, PRIMARY KEY (location_id), CONSTRAINT FK_5E9E89CB727ACA70 FOREIGN KEY (parent_id) REFERENCES location (location_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO location (location_id, name, parent_id) SELECT id, name, parent_id FROM __temp__location');
        $this->addSql('DROP TABLE __temp__location');
        $this->addSql('CREATE INDEX IDX_5E9E89CB727ACA70 ON location (parent_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__picnic_catalog_item_product_link AS SELECT catalog_item_id, product_id FROM picnic_catalog_item_product_link');
        $this->addSql('DROP TABLE picnic_catalog_item_product_link');
        $this->addSql('CREATE TABLE picnic_catalog_item_product_link (catalog_item_id BLOB NOT NULL, product_id VARCHAR(255) NOT NULL, PRIMARY KEY (catalog_item_id), CONSTRAINT FK_CC682E2C1DDDAF72 FOREIGN KEY (catalog_item_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO picnic_catalog_item_product_link (catalog_item_id, product_id) SELECT catalog_item_id, product_id FROM __temp__picnic_catalog_item_product_link');
        $this->addSql('DROP TABLE __temp__picnic_catalog_item_product_link');
        $this->addSql('CREATE TEMPORARY TABLE __temp__picnic_integration_settings AS SELECT id, username, country_code, password_cipher, auth_key_cipher, cart_display_name FROM picnic_integration_settings');
        $this->addSql('DROP TABLE picnic_integration_settings');
        $this->addSql('CREATE TABLE picnic_integration_settings (settings_id BLOB NOT NULL, username VARCHAR(255) DEFAULT NULL, country_code VARCHAR(2) NOT NULL, password_cipher CLOB DEFAULT NULL, auth_key_cipher CLOB DEFAULT NULL, cart_display_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY (settings_id))');
        $this->addSql('INSERT INTO picnic_integration_settings (settings_id, username, country_code, password_cipher, auth_key_cipher, cart_display_name) SELECT id, username, country_code, password_cipher, auth_key_cipher, cart_display_name FROM __temp__picnic_integration_settings');
        $this->addSql('DROP TABLE __temp__picnic_integration_settings');
        $this->addSql('CREATE TEMPORARY TABLE __temp__shopping_cart_line AS SELECT id, quantity, created_at, shopping_cart_id, item_type_id FROM shopping_cart_line');
        $this->addSql('DROP TABLE shopping_cart_line');
        $this->addSql('CREATE TABLE shopping_cart_line (id BLOB NOT NULL, quantity INTEGER NOT NULL, created_at DATETIME NOT NULL, shopping_cart_id BLOB NOT NULL, item_type_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_CART_LINE_CART FOREIGN KEY (shopping_cart_id) REFERENCES shopping_cart (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2B958C1CCE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO shopping_cart_line (id, quantity, created_at, shopping_cart_id, item_type_id) SELECT id, quantity, created_at, shopping_cart_id, item_type_id FROM __temp__shopping_cart_line');
        $this->addSql('DROP TABLE __temp__shopping_cart_line');
        $this->addSql('CREATE INDEX IDX_2B958C1C45F80CD ON shopping_cart_line (shopping_cart_id)');
        $this->addSql('CREATE INDEX IDX_2B958C1CCE11AAC7 ON shopping_cart_line (item_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__barcode AS SELECT id, code, type, item_type_id FROM barcode');
        $this->addSql('DROP TABLE barcode');
        $this->addSql('CREATE TABLE barcode (id BLOB NOT NULL, code VARCHAR(100) NOT NULL, type VARCHAR(50) NOT NULL, item_type_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_97AE0266CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO barcode (id, code, type, item_type_id) SELECT id, code, type, item_type_id FROM __temp__barcode');
        $this->addSql('DROP TABLE __temp__barcode');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97AE026677153098 ON barcode (code)');
        $this->addSql('CREATE INDEX IDX_97AE0266CE11AAC7 ON barcode (item_type_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__cart_stock_automation_rule AS SELECT rule_id, stock_below, add_quantity, enabled, created_at, catalog_item_id, shopping_cart_id FROM cart_stock_automation_rule');
        $this->addSql('DROP TABLE cart_stock_automation_rule');
        $this->addSql('CREATE TABLE cart_stock_automation_rule (id BLOB NOT NULL, stock_below INTEGER NOT NULL, add_quantity INTEGER NOT NULL, enabled BOOLEAN DEFAULT true NOT NULL, created_at DATETIME NOT NULL, catalog_item_id BLOB NOT NULL, shopping_cart_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_E8E9CA6E45F80CD FOREIGN KEY (shopping_cart_id) REFERENCES shopping_cart (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CART_AUTOMATION_CATALOG FOREIGN KEY (catalog_item_id) REFERENCES item_type (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO cart_stock_automation_rule (id, stock_below, add_quantity, enabled, created_at, catalog_item_id, shopping_cart_id) SELECT rule_id, stock_below, add_quantity, enabled, created_at, catalog_item_id, shopping_cart_id FROM __temp__cart_stock_automation_rule');
        $this->addSql('DROP TABLE __temp__cart_stock_automation_rule');
        $this->addSql('CREATE UNIQUE INDEX uniq_cart_automation_catalog_cart ON cart_stock_automation_rule (catalog_item_id, shopping_cart_id)');
        $this->addSql('CREATE INDEX IDX_CART_AUTOMATION_CART ON cart_stock_automation_rule (shopping_cart_id)');
        $this->addSql('CREATE INDEX IDX_CART_AUTOMATION_CATALOG ON cart_stock_automation_rule (catalog_item_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__code_scanner AS SELECT scanner_id, device, name FROM code_scanner');
        $this->addSql('DROP TABLE code_scanner');
        $this->addSql('CREATE TABLE code_scanner (id BLOB NOT NULL, device VARCHAR(512) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('INSERT INTO code_scanner (id, device, name) SELECT scanner_id, device, name FROM __temp__code_scanner');
        $this->addSql('DROP TABLE __temp__code_scanner');
        $this->addSql('CREATE TEMPORARY TABLE __temp__inventory_item AS SELECT inventory_item_id, quantity, expiration_date, created_at, item_type_id, location_id FROM inventory_item');
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql('CREATE TABLE inventory_item (id BLOB NOT NULL, quantity NUMERIC(12, 4) NOT NULL, expiration_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, item_type_id BLOB NOT NULL, location_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_1F1B251ECE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1F1B251E64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO inventory_item (id, quantity, expiration_date, created_at, item_type_id, location_id) SELECT inventory_item_id, quantity, expiration_date, created_at, item_type_id, location_id FROM __temp__inventory_item');
        $this->addSql('DROP TABLE __temp__inventory_item');
        $this->addSql('CREATE INDEX IDX_1F1B251E64D218E ON inventory_item (location_id)');
        $this->addSql('CREATE INDEX IDX_1F1B251ECE11AAC7 ON inventory_item (item_type_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__item_type AS SELECT catalog_item_id, name, image_file_name, volume_amount, volume_unit, weight_amount, weight_unit FROM item_type');
        $this->addSql('DROP TABLE item_type');
        $this->addSql('CREATE TABLE item_type (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, image_file_name VARCHAR(255) DEFAULT NULL, volume_amount NUMERIC(12, 4) DEFAULT NULL, volume_unit VARCHAR(10) DEFAULT NULL, weight_amount NUMERIC(12, 4) DEFAULT NULL, weight_unit VARCHAR(10) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('INSERT INTO item_type (id, name, image_file_name, volume_amount, volume_unit, weight_amount, weight_unit) SELECT catalog_item_id, name, image_file_name, volume_amount, volume_unit, weight_amount, weight_unit FROM __temp__item_type');
        $this->addSql('DROP TABLE __temp__item_type');
        $this->addSql('CREATE TEMPORARY TABLE __temp__item_type_attribute AS SELECT attribute_id, item_attribute, value, item_type_id FROM item_type_attribute');
        $this->addSql('DROP TABLE item_type_attribute');
        $this->addSql('CREATE TABLE item_type_attribute (id BLOB NOT NULL, item_attribute VARCHAR(32) NOT NULL, value CLOB DEFAULT NULL, item_type_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_8F8F44F7CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO item_type_attribute (id, item_attribute, value, item_type_id) SELECT attribute_id, item_attribute, value, item_type_id FROM __temp__item_type_attribute');
        $this->addSql('DROP TABLE __temp__item_type_attribute');
        $this->addSql('CREATE UNIQUE INDEX uniq_item_type_attribute ON item_type_attribute (item_type_id, item_attribute)');
        $this->addSql('CREATE INDEX IDX_8F8F44F7CE11AAC7 ON item_type_attribute (item_type_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__location AS SELECT location_id, name, parent_id FROM location');
        $this->addSql('DROP TABLE location');
        $this->addSql('CREATE TABLE location (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, parent_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_5E9E89CB727ACA70 FOREIGN KEY (parent_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO location (id, name, parent_id) SELECT location_id, name, parent_id FROM __temp__location');
        $this->addSql('DROP TABLE __temp__location');
        $this->addSql('CREATE INDEX IDX_5E9E89CB727ACA70 ON location (parent_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__picnic_catalog_item_product_link AS SELECT product_id, catalog_item_id FROM picnic_catalog_item_product_link');
        $this->addSql('DROP TABLE picnic_catalog_item_product_link');
        $this->addSql('CREATE TABLE picnic_catalog_item_product_link (product_id VARCHAR(255) NOT NULL, catalog_item_id BLOB NOT NULL, PRIMARY KEY (catalog_item_id), CONSTRAINT FK_PICNIC_CAT_ITEM_PRODUCT FOREIGN KEY (catalog_item_id) REFERENCES item_type (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO picnic_catalog_item_product_link (product_id, catalog_item_id) SELECT product_id, catalog_item_id FROM __temp__picnic_catalog_item_product_link');
        $this->addSql('DROP TABLE __temp__picnic_catalog_item_product_link');
        $this->addSql('CREATE TEMPORARY TABLE __temp__picnic_integration_settings AS SELECT settings_id, username, country_code, password_cipher, auth_key_cipher, cart_display_name FROM picnic_integration_settings');
        $this->addSql('DROP TABLE picnic_integration_settings');
        $this->addSql('CREATE TABLE picnic_integration_settings (id BLOB NOT NULL, username VARCHAR(255) DEFAULT NULL, country_code VARCHAR(2) NOT NULL, password_cipher CLOB DEFAULT NULL, auth_key_cipher CLOB DEFAULT NULL, cart_display_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('INSERT INTO picnic_integration_settings (id, username, country_code, password_cipher, auth_key_cipher, cart_display_name) SELECT settings_id, username, country_code, password_cipher, auth_key_cipher, cart_display_name FROM __temp__picnic_integration_settings');
        $this->addSql('DROP TABLE __temp__picnic_integration_settings');
        $this->addSql('CREATE TEMPORARY TABLE __temp__shopping_cart_line AS SELECT id, quantity, created_at, shopping_cart_id, item_type_id FROM shopping_cart_line');
        $this->addSql('DROP TABLE shopping_cart_line');
        $this->addSql('CREATE TABLE shopping_cart_line (id BLOB NOT NULL, quantity INTEGER NOT NULL, created_at DATETIME NOT NULL, shopping_cart_id BLOB NOT NULL, item_type_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_2B958C1C45F80CD FOREIGN KEY (shopping_cart_id) REFERENCES shopping_cart (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CART_LINE_ITEM FOREIGN KEY (item_type_id) REFERENCES item_type (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO shopping_cart_line (id, quantity, created_at, shopping_cart_id, item_type_id) SELECT id, quantity, created_at, shopping_cart_id, item_type_id FROM __temp__shopping_cart_line');
        $this->addSql('DROP TABLE __temp__shopping_cart_line');
        $this->addSql('CREATE INDEX IDX_CART_LINE_ITEM ON shopping_cart_line (item_type_id)');
        $this->addSql('CREATE INDEX IDX_CART_LINE_CART ON shopping_cart_line (shopping_cart_id)');
    }
}
