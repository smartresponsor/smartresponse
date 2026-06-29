<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align pre-existing Vendoring read tables with current entity mapping columns.';
    }

    public function up(Schema $schema): void
    {
        foreach (['vendor', 'vendor_profile', 'vendor_transaction'] as $tableName) {
            $this->addObjectColumns($tableName);
        }

        foreach ([
            'ALTER TABLE vendor ADD COLUMN IF NOT EXISTS brand_name VARCHAR(255)',
            'ALTER TABLE vendor ADD COLUMN IF NOT EXISTS owner_user_id INT DEFAULT NULL',
            "UPDATE vendor SET brand_name = CONCAT('Vendor ', id::text) WHERE brand_name IS NULL",
            'ALTER TABLE vendor ALTER COLUMN brand_name SET NOT NULL',
            'CREATE INDEX IF NOT EXISTS idx_vendor_owner_user_id ON vendor (owner_user_id)',
            'ALTER TABLE vendor_profile ADD COLUMN IF NOT EXISTS vendor_id INT DEFAULT NULL',
            'ALTER TABLE vendor_profile ADD COLUMN IF NOT EXISTS display_name VARCHAR(255) DEFAULT NULL',
            'ALTER TABLE vendor_profile ADD COLUMN IF NOT EXISTS about TEXT DEFAULT NULL',
            'ALTER TABLE vendor_profile ADD COLUMN IF NOT EXISTS website VARCHAR(512) DEFAULT NULL',
            'ALTER TABLE vendor_profile ADD COLUMN IF NOT EXISTS socials JSON DEFAULT NULL',
            'ALTER TABLE vendor_profile ADD COLUMN IF NOT EXISTS seo_title VARCHAR(255) DEFAULT NULL',
            'ALTER TABLE vendor_profile ADD COLUMN IF NOT EXISTS seo_description TEXT DEFAULT NULL',
            "ALTER TABLE vendor_profile ADD COLUMN IF NOT EXISTS public_profile_status VARCHAR(32) NOT NULL DEFAULT 'draft'",
            'ALTER TABLE vendor_profile ADD COLUMN IF NOT EXISTS public_profile_published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL',
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_vendor_profile_vendor_id ON vendor_profile (vendor_id) WHERE vendor_id IS NOT NULL',
            'CREATE INDEX IF NOT EXISTS idx_vendor_profile_public_status ON vendor_profile (public_profile_status)',
            'ALTER TABLE vendor_transaction ADD COLUMN IF NOT EXISTS vendor_id VARCHAR(64)',
            'ALTER TABLE vendor_transaction ADD COLUMN IF NOT EXISTS order_id VARCHAR(64)',
            'ALTER TABLE vendor_transaction ADD COLUMN IF NOT EXISTS project_id VARCHAR(64) DEFAULT NULL',
            'ALTER TABLE vendor_transaction ADD COLUMN IF NOT EXISTS amount NUMERIC(12, 2)',
            "ALTER TABLE vendor_transaction ADD COLUMN IF NOT EXISTS status VARCHAR(64) NOT NULL DEFAULT 'pending'",
            'ALTER TABLE vendor_transaction ADD COLUMN IF NOT EXISTS created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP',
            "UPDATE vendor_transaction SET vendor_id = 'unknown' WHERE vendor_id IS NULL",
            "UPDATE vendor_transaction SET order_id = CONCAT('order-', id::text) WHERE order_id IS NULL",
            'UPDATE vendor_transaction SET amount = 0 WHERE amount IS NULL',
            'ALTER TABLE vendor_transaction ALTER COLUMN vendor_id SET NOT NULL',
            'ALTER TABLE vendor_transaction ALTER COLUMN order_id SET NOT NULL',
            'ALTER TABLE vendor_transaction ALTER COLUMN amount SET NOT NULL',
            'CREATE INDEX IF NOT EXISTS idx_vendor_transaction_vendor_id ON vendor_transaction (vendor_id)',
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_vendor_transaction_vendor_order_project_nonnull ON vendor_transaction (vendor_id, order_id, project_id) WHERE project_id IS NOT NULL',
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_vendor_transaction_vendor_order_nullproject ON vendor_transaction (vendor_id, order_id) WHERE project_id IS NULL',
        ] as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
    }

    private function addObjectColumns(string $tableName): void
    {
        foreach ([
            'object_uuid VARCHAR(36)',
            'object_slug VARCHAR(190) DEFAULT NULL',
            'object_created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'object_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL',
            'object_created_by VARCHAR(190) DEFAULT NULL',
            'object_modified_by VARCHAR(190) DEFAULT NULL',
            'object_active BOOLEAN NOT NULL DEFAULT TRUE',
            'object_enabled BOOLEAN NOT NULL DEFAULT TRUE',
            'object_status VARCHAR(64) DEFAULT NULL',
        ] as $columnDefinition) {
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN IF NOT EXISTS %s', $tableName, $columnDefinition));
        }

        $this->addSql(sprintf(
            "UPDATE %s SET object_uuid = CONCAT('00000000-0000-7000-8000-', LPAD(id::text, 12, '0')) WHERE object_uuid IS NULL",
            $tableName,
        ));
        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN object_uuid SET NOT NULL', $tableName));
    }
}
