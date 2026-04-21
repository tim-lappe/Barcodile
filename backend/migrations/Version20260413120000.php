<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace item_type.default_attributes with item_type_attribute (enum-backed item_attribute).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE item_type_attribute (id BLOB NOT NULL, required BOOLEAN NOT NULL, item_type_id BLOB NOT NULL, item_attribute VARCHAR(32) NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_8F8F44F7CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES item_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX uniq_item_type_attribute ON item_type_attribute (item_type_id, item_attribute)');
        $this->addSql('CREATE INDEX IDX_8F8F44F7CE11AAC7 ON item_type_attribute (item_type_id)');
        $this->addSql('ALTER TABLE item_type DROP COLUMN default_attributes');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE item_type_attribute');
        $this->addSql('ALTER TABLE item_type ADD COLUMN default_attributes CLOB DEFAULT NULL');
    }
}
