<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429191000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_sign_in_at to accessing_account.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accessing_account ADD COLUMN IF NOT EXISTS last_sign_in_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accessing_account DROP COLUMN IF EXISTS last_sign_in_at');
    }
}
