<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add llm_profile for admin-configured LLM integrations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE llm_profile (llm_profile_id UUID NOT NULL, kind VARCHAR(32) NOT NULL, label VARCHAR(255) NOT NULL, model VARCHAR(255) NOT NULL, base_url TEXT DEFAULT NULL, api_key_cipher TEXT DEFAULT NULL, enabled BOOLEAN DEFAULT true NOT NULL, sort_order INT DEFAULT 0 NOT NULL, PRIMARY KEY (llm_profile_id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE llm_profile');
    }
}
