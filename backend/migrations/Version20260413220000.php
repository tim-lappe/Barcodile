<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Picnic product link and cart automation settings to item_type.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_type ADD picnic_linked_product_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE item_type ADD picnic_cart_automation_enabled BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE item_type ADD picnic_cart_automation_stock_below INT DEFAULT NULL');
        $this->addSql('ALTER TABLE item_type ADD picnic_cart_automation_add_quantity INT DEFAULT NULL');
        $this->addSql('ALTER TABLE item_type ADD picnic_cart_automation_product_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_type DROP picnic_linked_product_id');
        $this->addSql('ALTER TABLE item_type DROP picnic_cart_automation_enabled');
        $this->addSql('ALTER TABLE item_type DROP picnic_cart_automation_stock_below');
        $this->addSql('ALTER TABLE item_type DROP picnic_cart_automation_add_quantity');
        $this->addSql('ALTER TABLE item_type DROP picnic_cart_automation_product_id');
    }
}
