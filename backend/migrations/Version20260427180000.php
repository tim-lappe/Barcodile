<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add barcode_lookup_provider for configurable barcode catalog lookup.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE barcode_lookup_provider (barcode_lookup_provider_id UUID NOT NULL, kind VARCHAR(64) NOT NULL, label VARCHAR(255) NOT NULL, enabled BOOLEAN DEFAULT true NOT NULL, sort_order INT DEFAULT 0 NOT NULL, api_key_cipher TEXT DEFAULT NULL, PRIMARY KEY (barcode_lookup_provider_id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE barcode_lookup_provider');
    }
}
