<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add code_scanner (CodeScanner entity).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE code_scanner (id BLOB NOT NULL, device VARCHAR(512) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE code_scanner');
    }
}
