<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731210500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the entity-first Retailing retail marketplace table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS retail (
    id SERIAL NOT NULL,
    kind VARCHAR(16) NOT NULL,
    owner_vendor_id VARCHAR(64) DEFAULT NULL,
    category_id VARCHAR(64) DEFAULT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT DEFAULT NULL,
    amount_minor BIGINT DEFAULT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    location VARCHAR(255) DEFAULT NULL,
    object_code VARCHAR(190) DEFAULT NULL,
    object_active BOOLEAN NOT NULL DEFAULT TRUE,
    object_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    object_status VARCHAR(64) DEFAULT NULL,
    object_created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    object_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    object_created_by VARCHAR(190) DEFAULT NULL,
    object_modified_by VARCHAR(190) DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT chk_retail_kind CHECK (kind IN ('task', 'service', 'goods', 'project')),
    CONSTRAINT chk_retail_amount_non_negative CHECK (amount_minor IS NULL OR amount_minor >= 0)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_retail_object_code ON retail (object_code) WHERE object_code IS NOT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_retail_owner_kind ON retail (owner_vendor_id, kind)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_retail_category_kind ON retail (category_id, kind)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS retail');
    }
}
