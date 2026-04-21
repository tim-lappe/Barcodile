<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shopping_cart and shopping_cart_line for persisted CRUD carts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shopping_cart (id BLOB NOT NULL, name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE shopping_cart_line (id BLOB NOT NULL, quantity INTEGER NOT NULL, created_at DATETIME NOT NULL, shopping_cart_id BLOB NOT NULL, item_type_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_CART_LINE_CART FOREIGN KEY (shopping_cart_id) REFERENCES shopping_cart (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CART_LINE_ITEM FOREIGN KEY (item_type_id) REFERENCES item_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_CART_LINE_CART ON shopping_cart_line (shopping_cart_id)');
        $this->addSql('CREATE INDEX IDX_CART_LINE_ITEM ON shopping_cart_line (item_type_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE shopping_cart_line');
        $this->addSql('DROP TABLE shopping_cart');
    }
}
