<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413260000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop inventory_item.unit.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_item DROP COLUMN unit');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_item ADD COLUMN unit VARCHAR(50) NOT NULL DEFAULT \'piece\'');
    }
}
