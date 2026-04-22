<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align item_type barcode unique index name with Doctrine defaults.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $old = (int) $conn->fetchOne("SELECT COUNT(*) FROM sqlite_master WHERE type = 'index' AND name = 'UNIQ_item_type_barcode_code'");
        $new = (int) $conn->fetchOne("SELECT COUNT(*) FROM sqlite_master WHERE type = 'index' AND name = 'UNIQ_44EE13D27BAD6652'");
        if (1 === $old && 0 === $new) {
            $this->addSql('DROP INDEX UNIQ_item_type_barcode_code');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_44EE13D27BAD6652 ON item_type (barcode_code)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
