<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807035500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Materialize Objecting object_title columns on vendor and backfill firstTitle from existing Vendor identity.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vendor ADD COLUMN IF NOT EXISTS object_first_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE vendor ADD COLUMN IF NOT EXISTS object_middle_title TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE vendor ADD COLUMN IF NOT EXISTS object_last_title TEXT DEFAULT NULL');
        $this->addSql(<<<'SQL'
UPDATE vendor v
SET object_first_title = COALESCE(NULLIF(BTRIM(vp.display_name), ''), NULLIF(BTRIM(v.brand_name), ''))
FROM vendor_profile vp
WHERE vp.vendor_id = v.id
  AND v.object_first_title IS NULL
SQL);
        $this->addSql(<<<'SQL'
UPDATE vendor
SET object_first_title = NULLIF(BTRIM(brand_name), '')
WHERE object_first_title IS NULL
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vendor DROP COLUMN IF EXISTS object_first_title');
        $this->addSql('ALTER TABLE vendor DROP COLUMN IF EXISTS object_middle_title');
        $this->addSql('ALTER TABLE vendor DROP COLUMN IF EXISTS object_last_title');
    }
}
