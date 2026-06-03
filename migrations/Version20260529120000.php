<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create vendoring public profile tables for vendor avatar, cover, and profile metadata.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS vendor_profile (
    id SERIAL NOT NULL,
    vendor_id INT NOT NULL,
    display_name VARCHAR(255) DEFAULT NULL,
    about TEXT DEFAULT NULL,
    website VARCHAR(512) DEFAULT NULL,
    socials JSON DEFAULT NULL,
    seo_title VARCHAR(255) DEFAULT NULL,
    seo_description TEXT DEFAULT NULL,
    public_profile_status VARCHAR(32) NOT NULL DEFAULT 'draft',
    public_profile_published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id)
)
SQL);

        $this->addSql('ALTER TABLE vendor_profile ADD CONSTRAINT uniq_vendor_profile_vendor UNIQUE (vendor_id)');
        $this->addSql('ALTER TABLE vendor_profile ADD CONSTRAINT fk_vendor_profile_vendor FOREIGN KEY (vendor_id) REFERENCES vendor (id) ON DELETE CASCADE');

        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS vendor_profile_avatar (
    id SERIAL NOT NULL,
    vendor_id INT NOT NULL,
    file_path VARCHAR(1024) NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
)
SQL);
        $this->addSql('ALTER TABLE vendor_profile_avatar ADD CONSTRAINT uniq_vendor_profile_avatar_vendor UNIQUE (vendor_id)');
        $this->addSql('ALTER TABLE vendor_profile_avatar ADD CONSTRAINT fk_vendor_profile_avatar_vendor FOREIGN KEY (vendor_id) REFERENCES vendor (id) ON DELETE CASCADE');

        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS vendor_profile_cover (
    id SERIAL NOT NULL,
    vendor_id INT NOT NULL,
    file_path VARCHAR(1024) NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
)
SQL);
        $this->addSql('ALTER TABLE vendor_profile_cover ADD CONSTRAINT uniq_vendor_profile_cover_vendor UNIQUE (vendor_id)');
        $this->addSql('ALTER TABLE vendor_profile_cover ADD CONSTRAINT fk_vendor_profile_cover_vendor FOREIGN KEY (vendor_id) REFERENCES vendor (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        $this->addSql('DROP TABLE IF EXISTS vendor_profile_cover');
        $this->addSql('DROP TABLE IF EXISTS vendor_profile_avatar');
        $this->addSql('DROP TABLE IF EXISTS vendor_profile');
    }
}
