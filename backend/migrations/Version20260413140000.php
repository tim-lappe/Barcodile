<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove category and item_event; trim item_attribute usage; add item_type volume and weight columns.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM item_type_attribute WHERE item_attribute IN (\'brand\', \'batch_number\', \'volume_ml\')');
        $this->addSql('DROP TABLE item_event');
        $this->addSql('PRAGMA foreign_keys = OFF');
        $this->addSql('CREATE TEMPORARY TABLE __temp__item_type AS SELECT id, name FROM item_type');
        $this->addSql('DROP TABLE item_type');
        $this->addSql('CREATE TABLE item_type (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, volume_amount NUMERIC(12, 4) DEFAULT NULL, volume_unit VARCHAR(10) DEFAULT NULL, weight_amount NUMERIC(12, 4) DEFAULT NULL, weight_unit VARCHAR(10) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('INSERT INTO item_type (id, name) SELECT id, name FROM __temp__item_type');
        $this->addSql('DROP TABLE __temp__item_type');
        $this->addSql('PRAGMA foreign_keys = ON');
        $this->addSql('DROP TABLE category');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE category (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('PRAGMA foreign_keys = OFF');
        $this->addSql('CREATE TEMPORARY TABLE __temp__item_type AS SELECT id, name FROM item_type');
        $this->addSql('DROP TABLE item_type');
        $this->addSql('CREATE TABLE item_type (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, category_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_44EE13D212469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO item_type (id, name) SELECT id, name FROM __temp__item_type');
        $this->addSql('DROP TABLE __temp__item_type');
        $this->addSql('PRAGMA foreign_keys = ON');
        $this->addSql('CREATE INDEX IDX_44EE13D212469DE2 ON item_type (category_id)');
        $this->addSql('CREATE TABLE item_event (id BLOB NOT NULL, type VARCHAR(50) NOT NULL, payload CLOB DEFAULT NULL, created_at DATETIME NOT NULL, item_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_11091177126F525E FOREIGN KEY (item_id) REFERENCES "item" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_11091177126F525E ON item_event (item_id)');
    }
}
