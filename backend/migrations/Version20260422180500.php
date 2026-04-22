<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422180500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align inventory_item public_code unique index name with Doctrine mapping.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_2FBE34F8B5D7D71C');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_55BDEA30C487249 ON inventory_item (public_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_55BDEA30C487249');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_2FBE34F8B5D7D71C ON inventory_item (public_code)');
    }
}
