<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429195000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing order number column for Ordering read models.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS number VARCHAR(64)');
        $this->addSql("UPDATE orders SET number = 'ORD-' || substring(replace(id::text, '-', ''), 1, 12) WHERE number IS NULL OR number = ''");
        $this->addSql('ALTER TABLE orders ALTER COLUMN number SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_orders_number ON orders (number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_orders_number');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS number');
    }
}
