<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429192000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align host schema with catalog read models: category.parent_id and record_index.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category ADD COLUMN IF NOT EXISTS parent_id VARCHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD COLUMN IF NOT EXISTS locale VARCHAR(12) DEFAULT NULL');
        $this->addSql("ALTER TABLE category ADD COLUMN IF NOT EXISTS tenant VARCHAR(64) NOT NULL DEFAULT 'default'");
        $this->addSql("ALTER TABLE category ADD COLUMN IF NOT EXISTS workflow_state VARCHAR(32) NOT NULL DEFAULT 'draft'");
        $this->addSql('ALTER TABLE category ADD COLUMN IF NOT EXISTS published BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE category ADD COLUMN IF NOT EXISTS published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD COLUMN IF NOT EXISTS icon_url VARCHAR(255) DEFAULT NULL');
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS record_index (
    id VARCHAR(64) NOT NULL,
    brand VARCHAR(80) DEFAULT NULL,
    price NUMERIC(12, 2) DEFAULT NULL,
    stock INT DEFAULT NULL,
    tag_set JSON DEFAULT NULL,
    PRIMARY KEY(id)
)
SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_record_index_brand ON record_index (brand)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_record_index_price ON record_index (price)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_record_index_stock ON record_index (stock)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS record_index');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS icon_url');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS published_at');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS published');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS workflow_state');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS tenant');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS locale');
        $this->addSql('ALTER TABLE category DROP COLUMN IF EXISTS parent_id');
    }
}
