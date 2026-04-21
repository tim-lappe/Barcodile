<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop inventory_item.status.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_item DROP COLUMN status');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_item ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT \'active\'');
    }
}
