<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Complete the host category schema for Cataloging read models.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category ADD COLUMN IF NOT EXISTS locale VARCHAR(12) DEFAULT NULL');
        $this->addSql("ALTER TABLE category ADD COLUMN IF NOT EXISTS tenant VARCHAR(64) NOT NULL DEFAULT 'default'");
        $this->addSql("ALTER TABLE category ADD COLUMN IF NOT EXISTS workflow_state VARCHAR(32) NOT NULL DEFAULT 'draft'");
        $this->addSql('ALTER TABLE category ADD COLUMN IF NOT EXISTS published BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE category ADD COLUMN IF NOT EXISTS published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD COLUMN IF NOT EXISTS icon_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_category_slug ON category (slug)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category_path ON category (path)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category_tenant_workflow ON category (tenant, workflow_state)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_category_tenant_workflow');
        $this->addSql('DROP INDEX IF EXISTS idx_category_path');
        $this->addSql('DROP INDEX IF EXISTS uniq_category_slug');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS icon_url');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS published_at');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS published');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS workflow_state');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS tenant');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS locale');
    }
}
