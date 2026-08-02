<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the entity-first Navigating navigation_item table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS navigation_item (
    id SERIAL NOT NULL,
    navigation_key VARCHAR(160) NOT NULL,
    parent_key VARCHAR(160) DEFAULT NULL,
    label VARCHAR(140) NOT NULL,
    slug VARCHAR(180) DEFAULT NULL,
    route_name VARCHAR(180) NOT NULL,
    route_parameters JSON NOT NULL,
    location VARCHAR(120) NOT NULL,
    operation VARCHAR(60) NOT NULL,
    icon VARCHAR(80) DEFAULT NULL,
    required_role VARCHAR(80) DEFAULT NULL,
    position INT NOT NULL,
    enabled BOOLEAN NOT NULL,
    metadata JSON NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    archived_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_navigation_item_navigation_key ON navigation_item (navigation_key)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_navigation_item_slug ON navigation_item (slug)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_navigation_item_route_name ON navigation_item (route_name)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_navigation_item_operation ON navigation_item (operation)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_navigation_item_parent_key ON navigation_item (parent_key)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_navigation_item_archived_at ON navigation_item (archived_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_navigation_item_enabled_location_position ON navigation_item (enabled, location, position)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS navigation_item');
    }
}
