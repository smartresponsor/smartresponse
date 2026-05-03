<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429195600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Complete the host orders schema for Ordering read models.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS number VARCHAR(64)');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS currency VARCHAR(3) NOT NULL DEFAULT \'USD\'');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS grand_total NUMERIC(12, 2) NOT NULL DEFAULT 0.00');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS paid_total NUMERIC(12, 2) NOT NULL DEFAULT 0.00');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS refunded_total NUMERIC(12, 2) NOT NULL DEFAULT 0.00');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS subtotal NUMERIC(12, 2) NOT NULL DEFAULT 0.00');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_total NUMERIC(12, 2) NOT NULL DEFAULT 0.00');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS tax_total NUMERIC(12, 2) NOT NULL DEFAULT 0.00');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(32) NOT NULL DEFAULT \'draft\'');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS vendor_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_code VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql("UPDATE orders SET number = 'ORD-' || substring(replace(id::text, '-', ''), 1, 12) WHERE number IS NULL OR number = ''");
        $this->addSql('ALTER TABLE orders ALTER COLUMN number SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_orders_number ON orders (number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_orders_number');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS updated_at');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS created_at');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS tracking_code');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS vendor_id');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS customer_id');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS status');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS tax_total');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS discount_total');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS subtotal');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS refunded_total');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS paid_total');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS grand_total');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS currency');
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS number');
    }
}
