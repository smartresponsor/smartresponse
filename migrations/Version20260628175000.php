<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628175000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Materialize Vendoring payout tables required by mobile vendor payout reads.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS vendor_payout (id SERIAL PRIMARY KEY, payout_id VARCHAR(64) NOT NULL, vendor_id VARCHAR(64) NOT NULL, currency VARCHAR(8) NOT NULL, gross_cents INT NOT NULL, fee_cents INT NOT NULL, net_cents INT NOT NULL, status VARCHAR(32) NOT NULL, processed_at VARCHAR(32) DEFAULT NULL, meta JSON NOT NULL DEFAULT '{}', object_uuid VARCHAR(36) NOT NULL UNIQUE, object_slug VARCHAR(190) DEFAULT NULL, object_created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, object_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, object_created_by VARCHAR(190) DEFAULT NULL, object_modified_by VARCHAR(190) DEFAULT NULL, object_active BOOLEAN NOT NULL DEFAULT TRUE, object_enabled BOOLEAN NOT NULL DEFAULT TRUE, object_status VARCHAR(64) DEFAULT NULL)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vendor_payout_vendor_id ON vendor_payout (vendor_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vendor_payout_status ON vendor_payout (status)');

        $this->addSql('CREATE TABLE IF NOT EXISTS vendor_payout_account (id SERIAL PRIMARY KEY, tenant_id VARCHAR(64) NOT NULL, vendor_id VARCHAR(64) NOT NULL, provider VARCHAR(64) NOT NULL, account_ref VARCHAR(128) NOT NULL, currency VARCHAR(8) NOT NULL, active BOOLEAN NOT NULL DEFAULT TRUE, object_uuid VARCHAR(36) NOT NULL UNIQUE, object_slug VARCHAR(190) DEFAULT NULL, object_created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, object_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, object_created_by VARCHAR(190) DEFAULT NULL, object_modified_by VARCHAR(190) DEFAULT NULL, object_active BOOLEAN NOT NULL DEFAULT TRUE, object_enabled BOOLEAN NOT NULL DEFAULT TRUE, object_status VARCHAR(64) DEFAULT NULL)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vendor_payout_account_vendor_id ON vendor_payout_account (vendor_id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS vendor_payout_item (id SERIAL PRIMARY KEY, payout_id INT NOT NULL REFERENCES vendor_payout(id) ON DELETE CASCADE, entry_id VARCHAR(64) NOT NULL, amount_cents INT NOT NULL, object_uuid VARCHAR(36) NOT NULL UNIQUE, object_slug VARCHAR(190) DEFAULT NULL, object_created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, object_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, object_created_by VARCHAR(190) DEFAULT NULL, object_modified_by VARCHAR(190) DEFAULT NULL, object_active BOOLEAN NOT NULL DEFAULT TRUE, object_enabled BOOLEAN NOT NULL DEFAULT TRUE, object_status VARCHAR(64) DEFAULT NULL)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vendor_payout_item_payout_id ON vendor_payout_item (payout_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS vendor_payout_item');
        $this->addSql('DROP TABLE IF EXISTS vendor_payout_account');
        $this->addSql('DROP TABLE IF EXISTS vendor_payout');
    }
}
