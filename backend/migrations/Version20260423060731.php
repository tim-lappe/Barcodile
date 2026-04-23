<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423060731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PostgreSQL baseline schema including catalog_item_image.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cart_stock_automation_rule (rule_id UUID NOT NULL, stock_below INT NOT NULL, add_quantity INT NOT NULL, enabled BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, catalog_item_id UUID NOT NULL, shopping_cart_id UUID NOT NULL, PRIMARY KEY (rule_id))');
        $this->addSql('CREATE INDEX IDX_E8E9CA6E1DDDAF72 ON cart_stock_automation_rule (catalog_item_id)');
        $this->addSql('CREATE INDEX IDX_E8E9CA6E45F80CD ON cart_stock_automation_rule (shopping_cart_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_cart_automation_catalog_cart ON cart_stock_automation_rule (catalog_item_id, shopping_cart_id)');
        $this->addSql('CREATE TABLE catalog_item_image (body BYTEA NOT NULL, content_type VARCHAR(64) NOT NULL, catalog_item_id UUID NOT NULL, PRIMARY KEY (catalog_item_id))');
        $this->addSql('CREATE TABLE inventory_item (inventory_item_id UUID NOT NULL, public_code VARCHAR(32) NOT NULL, expiration_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, item_type_id UUID NOT NULL, location_id UUID DEFAULT NULL, PRIMARY KEY (inventory_item_id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55BDEA30C487249 ON inventory_item (public_code)');
        $this->addSql('CREATE INDEX IDX_55BDEA30CE11AAC7 ON inventory_item (item_type_id)');
        $this->addSql('CREATE INDEX IDX_55BDEA3064D218E ON inventory_item (location_id)');
        $this->addSql('CREATE TABLE item_type (catalog_item_id UUID NOT NULL, name VARCHAR(255) NOT NULL, image_file_name VARCHAR(255) DEFAULT NULL, volume_amount NUMERIC(12, 4) DEFAULT NULL, volume_unit VARCHAR(10) DEFAULT NULL, weight_amount NUMERIC(12, 4) DEFAULT NULL, weight_unit VARCHAR(10) DEFAULT NULL, barcode_code VARCHAR(100) DEFAULT NULL, barcode_type VARCHAR(50) DEFAULT NULL, PRIMARY KEY (catalog_item_id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_44EE13D27BAD6652 ON item_type (barcode_code)');
        $this->addSql('CREATE TABLE item_type_attribute (attribute_id UUID NOT NULL, item_attribute VARCHAR(32) NOT NULL, value JSON DEFAULT NULL, item_type_id UUID NOT NULL, PRIMARY KEY (attribute_id))');
        $this->addSql('CREATE INDEX IDX_5F51091FCE11AAC7 ON item_type_attribute (item_type_id)');
        $this->addSql('CREATE TABLE location (location_id UUID NOT NULL, name VARCHAR(255) NOT NULL, parent_id UUID DEFAULT NULL, PRIMARY KEY (location_id))');
        $this->addSql('CREATE INDEX IDX_5E9E89CB727ACA70 ON location (parent_id)');
        $this->addSql('CREATE TABLE persisted_domain_event (id UUID NOT NULL, event_dto JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX persisted_domain_event_created_at_idx ON persisted_domain_event (created_at)');
        $this->addSql('CREATE TABLE picnic_catalog_item_product_link (product_id VARCHAR(255) NOT NULL, catalog_item_id UUID NOT NULL, PRIMARY KEY (catalog_item_id))');
        $this->addSql('CREATE TABLE picnic_integration_settings (settings_id UUID NOT NULL, username VARCHAR(255) DEFAULT NULL, country_code VARCHAR(2) NOT NULL, password_cipher TEXT DEFAULT NULL, auth_key_cipher TEXT DEFAULT NULL, cart_display_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY (settings_id))');
        $this->addSql('CREATE TABLE scanner_device (scanner_device_id UUID NOT NULL, device_identifier VARCHAR(512) NOT NULL, name VARCHAR(255) NOT NULL, last_scanned_codes JSON DEFAULT NULL, automation_add_inventory_on_ean_scan BOOLEAN DEFAULT false NOT NULL, automation_create_catalog_item_if_missing_for_ean BOOLEAN DEFAULT false NOT NULL, automation_remove_inventory_on_public_code_scan BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY (scanner_device_id))');
        $this->addSql('CREATE TABLE shopping_cart (id UUID NOT NULL, name VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE shopping_cart_line (id UUID NOT NULL, quantity INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, shopping_cart_id UUID NOT NULL, item_type_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_2B958C1C45F80CD ON shopping_cart_line (shopping_cart_id)');
        $this->addSql('CREATE INDEX IDX_2B958C1CCE11AAC7 ON shopping_cart_line (item_type_id)');
        $this->addSql('ALTER TABLE cart_stock_automation_rule ADD CONSTRAINT FK_E8E9CA6E1DDDAF72 FOREIGN KEY (catalog_item_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE cart_stock_automation_rule ADD CONSTRAINT FK_E8E9CA6E45F80CD FOREIGN KEY (shopping_cart_id) REFERENCES shopping_cart (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE catalog_item_image ADD CONSTRAINT FK_5AB0ED541DDDAF72 FOREIGN KEY (catalog_item_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA30CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA3064D218E FOREIGN KEY (location_id) REFERENCES location (location_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE item_type_attribute ADD CONSTRAINT FK_5F51091FCE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB727ACA70 FOREIGN KEY (parent_id) REFERENCES location (location_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE picnic_catalog_item_product_link ADD CONSTRAINT FK_CC682E2C1DDDAF72 FOREIGN KEY (catalog_item_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE shopping_cart_line ADD CONSTRAINT FK_2B958C1C45F80CD FOREIGN KEY (shopping_cart_id) REFERENCES shopping_cart (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE shopping_cart_line ADD CONSTRAINT FK_2B958C1CCE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (catalog_item_id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cart_stock_automation_rule DROP CONSTRAINT FK_E8E9CA6E1DDDAF72');
        $this->addSql('ALTER TABLE cart_stock_automation_rule DROP CONSTRAINT FK_E8E9CA6E45F80CD');
        $this->addSql('ALTER TABLE catalog_item_image DROP CONSTRAINT FK_5AB0ED541DDDAF72');
        $this->addSql('ALTER TABLE inventory_item DROP CONSTRAINT FK_55BDEA30CE11AAC7');
        $this->addSql('ALTER TABLE inventory_item DROP CONSTRAINT FK_55BDEA3064D218E');
        $this->addSql('ALTER TABLE item_type_attribute DROP CONSTRAINT FK_5F51091FCE11AAC7');
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CB727ACA70');
        $this->addSql('ALTER TABLE picnic_catalog_item_product_link DROP CONSTRAINT FK_CC682E2C1DDDAF72');
        $this->addSql('ALTER TABLE shopping_cart_line DROP CONSTRAINT FK_2B958C1C45F80CD');
        $this->addSql('ALTER TABLE shopping_cart_line DROP CONSTRAINT FK_2B958C1CCE11AAC7');
        $this->addSql('DROP TABLE cart_stock_automation_rule');
        $this->addSql('DROP TABLE catalog_item_image');
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql('DROP TABLE item_type');
        $this->addSql('DROP TABLE item_type_attribute');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE persisted_domain_event');
        $this->addSql('DROP TABLE picnic_catalog_item_product_link');
        $this->addSql('DROP TABLE picnic_integration_settings');
        $this->addSql('DROP TABLE scanner_device');
        $this->addSql('DROP TABLE shopping_cart');
        $this->addSql('DROP TABLE shopping_cart_line');
    }
}
