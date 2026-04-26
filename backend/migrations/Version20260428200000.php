<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'printer_device: add driver print settings JSON.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE printer_device ADD print_settings JSON DEFAULT \'{}\' NOT NULL');
        $this->addSql("UPDATE printer_device SET print_settings = jsonb_build_object('labelSize', COALESCE(connection->>'labelSize', '62'), 'red', CASE WHEN connection::jsonb ? 'red' THEN (connection->>'red')::boolean ELSE true END)");
        $this->addSql("UPDATE printer_device SET connection = (connection::jsonb - 'labelSize' - 'red')::json");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE printer_device SET connection = connection::jsonb || jsonb_build_object('labelSize', print_settings->>'labelSize', 'red', CASE WHEN print_settings::jsonb ? 'red' THEN (print_settings->>'red')::boolean ELSE false END)");
        $this->addSql('ALTER TABLE printer_device DROP COLUMN print_settings');
    }
}
