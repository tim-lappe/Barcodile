<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move Picnic catalog product link from item_type to picnic_catalog_item_product_link.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE picnic_catalog_item_product_link (catalog_item_id BLOB NOT NULL, product_id VARCHAR(255) NOT NULL, PRIMARY KEY(catalog_item_id), CONSTRAINT FK_PICNIC_CAT_ITEM_PRODUCT FOREIGN KEY (catalog_item_id) REFERENCES item_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO picnic_catalog_item_product_link (catalog_item_id, product_id) SELECT id, picnic_linked_product_id FROM item_type WHERE picnic_linked_product_id IS NOT NULL');
        $this->addSql('ALTER TABLE item_type DROP COLUMN picnic_linked_product_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_type ADD COLUMN picnic_linked_product_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE item_type SET picnic_linked_product_id = (SELECT product_id FROM picnic_catalog_item_product_link l WHERE l.catalog_item_id = item_type.id) WHERE EXISTS (SELECT 1 FROM picnic_catalog_item_product_link l2 WHERE l2.catalog_item_id = item_type.id)');
        $this->addSql('DROP TABLE picnic_catalog_item_product_link');
    }
}
