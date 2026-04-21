<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413250000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove demo shopping carts named Test.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM shopping_cart WHERE LOWER(TRIM(COALESCE(name, ''))) = 'test'");
    }

    public function down(Schema $schema): void
    {
    }
}
