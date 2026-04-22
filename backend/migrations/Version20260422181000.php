<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop inventory_item_public_code_seq; public codes are allocated randomly in application code.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS inventory_item_public_code_seq');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE inventory_item_public_code_seq (id INTEGER PRIMARY KEY NOT NULL, next_value INTEGER NOT NULL, CHECK (id = 1))');
        $this->addSql('INSERT INTO inventory_item_public_code_seq (id, next_value) SELECT 1, COALESCE((SELECT MAX(CAST(public_code AS INTEGER)) FROM inventory_item), 0)');
    }
}
