<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create persisted_domain_event for audit of dispatched domain events.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE persisted_domain_event (id BLOB NOT NULL, event_dto CLOB NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id))
SQL);
        $this->addSql('CREATE INDEX persisted_domain_event_created_at_idx ON persisted_domain_event (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE persisted_domain_event');
    }
}
