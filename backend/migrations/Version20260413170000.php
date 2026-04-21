<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add item_type.image_file_name for object storage key of the primary image.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_type ADD COLUMN image_file_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_type DROP COLUMN image_file_name');
    }
}
