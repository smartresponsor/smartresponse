<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260530121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing lifecycle columns to taxation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE taxation ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE taxation ADD COLUMN IF NOT EXISTS locked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE taxation ADD COLUMN IF NOT EXISTS locked_by VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE taxation ADD COLUMN IF NOT EXISTS modified_by VARCHAR(128) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE taxation DROP COLUMN IF EXISTS modified_by');
        $this->addSql('ALTER TABLE taxation DROP COLUMN IF EXISTS locked_by');
        $this->addSql('ALTER TABLE taxation DROP COLUMN IF EXISTS locked_at');
        $this->addSql('ALTER TABLE taxation DROP COLUMN IF EXISTS deleted_by');
    }
}
