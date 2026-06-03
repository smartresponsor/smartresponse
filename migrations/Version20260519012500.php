<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519012500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize attachment enum backing values in the live database.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        $this->addSql("UPDATE attachment SET type = lower(type) WHERE type IN ('Document', 'Media')");
        $this->addSql("UPDATE attachment SET media_kind = lower(media_kind) WHERE media_kind IN ('Image', 'Audio', 'Video', 'Other')");
        $this->addSql("UPDATE attachment SET document_kind = lower(replace(document_kind, ' ', '_')) WHERE document_kind IS NOT NULL");
        $this->addSql("UPDATE attachment SET storage_kind = lower(storage_kind) WHERE storage_kind IN ('Local')");
        $this->addSql("UPDATE attachment SET visibility = lower(visibility) WHERE visibility IN ('Private', 'Public', 'Restricted')");
        $this->addSql("UPDATE attachment SET status = lower(status) WHERE status IN ('Draft', 'Active', 'Deleted', 'Failed', 'Quarantined')");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        $this->addSql("UPDATE attachment SET type = initcap(type) WHERE type IN ('document', 'media')");
        $this->addSql("UPDATE attachment SET media_kind = initcap(media_kind) WHERE media_kind IN ('image', 'audio', 'video', 'other')");
        $this->addSql("UPDATE attachment SET document_kind = replace(initcap(replace(document_kind, '_', ' ')), ' ', '_') WHERE document_kind IS NOT NULL");
        $this->addSql("UPDATE attachment SET storage_kind = initcap(storage_kind) WHERE storage_kind = 'local'");
        $this->addSql("UPDATE attachment SET visibility = initcap(visibility) WHERE visibility IN ('private', 'public', 'restricted')");
        $this->addSql("UPDATE attachment SET status = initcap(status) WHERE status IN ('draft', 'active', 'deleted', 'failed', 'quarantined')");
    }
}
