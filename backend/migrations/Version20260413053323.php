<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413053323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace greeting with item_type and stock_item tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE item_type (id BLOB NOT NULL, barcode VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_44EE13D297AE0266 ON item_type (barcode)');
        $this->addSql('CREATE TABLE stock_item (id BLOB NOT NULL, amount NUMERIC(12, 4) NOT NULL, unit VARCHAR(32) NOT NULL, type_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_6017DDAC54C8C93 FOREIGN KEY (type_id) REFERENCES item_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6017DDAC54C8C93 ON stock_item (type_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE item_type');
        $this->addSql('DROP TABLE stock_item');
    }
}
