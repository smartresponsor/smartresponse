<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Materialize core Vendoring read tables required by mobile vendor read routes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS vendor (id SERIAL PRIMARY KEY, brand_name VARCHAR(255) NOT NULL, owner_user_id INT DEFAULT NULL, object_uuid VARCHAR(36) NOT NULL UNIQUE, object_slug VARCHAR(190) DEFAULT NULL, object_created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, object_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, object_created_by VARCHAR(190) DEFAULT NULL, object_modified_by VARCHAR(190) DEFAULT NULL, object_active BOOLEAN NOT NULL DEFAULT TRUE, object_enabled BOOLEAN NOT NULL DEFAULT TRUE, object_status VARCHAR(64) DEFAULT NULL)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vendor_owner_user_id ON vendor (owner_user_id)');

        $this->addSql("CREATE TABLE IF NOT EXISTS vendor_profile (id SERIAL PRIMARY KEY, vendor_id INT NOT NULL REFERENCES vendor(id) ON DELETE CASCADE, display_name VARCHAR(255) DEFAULT NULL, about TEXT DEFAULT NULL, website VARCHAR(512) DEFAULT NULL, socials JSON DEFAULT NULL, seo_title VARCHAR(255) DEFAULT NULL, seo_description TEXT DEFAULT NULL, public_profile_status VARCHAR(32) NOT NULL DEFAULT 'draft', public_profile_published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, object_uuid VARCHAR(36) NOT NULL UNIQUE, object_slug VARCHAR(190) DEFAULT NULL, object_created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, object_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, object_created_by VARCHAR(190) DEFAULT NULL, object_modified_by VARCHAR(190) DEFAULT NULL, object_active BOOLEAN NOT NULL DEFAULT TRUE, object_enabled BOOLEAN NOT NULL DEFAULT TRUE, object_status VARCHAR(64) DEFAULT NULL)");
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_vendor_profile_vendor_id ON vendor_profile (vendor_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vendor_profile_public_status ON vendor_profile (public_profile_status)');

        $this->addSql("CREATE TABLE IF NOT EXISTS vendor_transaction (id SERIAL PRIMARY KEY, vendor_id VARCHAR(64) NOT NULL, order_id VARCHAR(64) NOT NULL, project_id VARCHAR(64) DEFAULT NULL, amount NUMERIC(12, 2) NOT NULL, status VARCHAR(64) NOT NULL DEFAULT 'pending', created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, object_uuid VARCHAR(36) NOT NULL UNIQUE, object_slug VARCHAR(190) DEFAULT NULL, object_created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, object_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, object_created_by VARCHAR(190) DEFAULT NULL, object_modified_by VARCHAR(190) DEFAULT NULL, object_active BOOLEAN NOT NULL DEFAULT TRUE, object_enabled BOOLEAN NOT NULL DEFAULT TRUE, object_status VARCHAR(64) DEFAULT NULL)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_vendor_transaction_vendor_id ON vendor_transaction (vendor_id)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_vendor_transaction_vendor_order_project_nonnull ON vendor_transaction (vendor_id, order_id, project_id) WHERE project_id IS NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_vendor_transaction_vendor_order_nullproject ON vendor_transaction (vendor_id, order_id) WHERE project_id IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS vendor_transaction');
        $this->addSql('DROP TABLE IF EXISTS vendor_profile');
        $this->addSql('DROP TABLE IF EXISTS vendor');
    }
}
