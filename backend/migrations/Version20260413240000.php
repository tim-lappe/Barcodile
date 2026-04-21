<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413240000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shopping_cart.readonly flag for carts that cannot be edited.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shopping_cart ADD COLUMN readonly BOOLEAN DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__shopping_cart AS SELECT id, name, created_at FROM shopping_cart');
        $this->addSql('DROP TABLE shopping_cart');
        $this->addSql('CREATE TABLE shopping_cart (id BLOB NOT NULL, name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id))');
        $this->addSql('INSERT INTO shopping_cart (id, name, created_at) SELECT id, name, created_at FROM __temp__shopping_cart');
        $this->addSql('DROP TABLE __temp__shopping_cart');
    }
}
