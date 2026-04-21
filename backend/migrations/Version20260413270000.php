<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413270000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cart_stock_automation_rule; drop picnic cart automation columns from item_type.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cart_stock_automation_rule (id BLOB NOT NULL, stock_below INTEGER NOT NULL, add_quantity INTEGER NOT NULL, enabled BOOLEAN DEFAULT true NOT NULL, created_at DATETIME NOT NULL, catalog_item_id BLOB NOT NULL, shopping_cart_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_CART_AUTOMATION_CATALOG FOREIGN KEY (catalog_item_id) REFERENCES item_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CART_AUTOMATION_CART FOREIGN KEY (shopping_cart_id) REFERENCES shopping_cart (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CART_AUTOMATION_CATALOG_CART ON cart_stock_automation_rule (catalog_item_id, shopping_cart_id)');
        $this->addSql('CREATE INDEX IDX_CART_AUTOMATION_CATALOG ON cart_stock_automation_rule (catalog_item_id)');
        $this->addSql('CREATE INDEX IDX_CART_AUTOMATION_CART ON cart_stock_automation_rule (shopping_cart_id)');
        $this->addSql('ALTER TABLE item_type DROP COLUMN picnic_cart_automation_enabled');
        $this->addSql('ALTER TABLE item_type DROP COLUMN picnic_cart_automation_stock_below');
        $this->addSql('ALTER TABLE item_type DROP COLUMN picnic_cart_automation_add_quantity');
        $this->addSql('ALTER TABLE item_type DROP COLUMN picnic_cart_automation_product_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cart_stock_automation_rule');
        $this->addSql('ALTER TABLE item_type ADD COLUMN picnic_cart_automation_enabled BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE item_type ADD COLUMN picnic_cart_automation_stock_below INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE item_type ADD COLUMN picnic_cart_automation_add_quantity INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE item_type ADD COLUMN picnic_cart_automation_product_id VARCHAR(255) DEFAULT NULL');
    }
}
