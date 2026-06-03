<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519012300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace legacy placeholder Payment outbox and DLQ slugs with real UUID values.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE payment_outbox_message SET slug = '3d1f5a7c-7b8a-4f1f-8b77-1d4b0a300101' WHERE type = 'payment.captured' AND slug = '5d1f5a7c-7b8a-4f1f-8b77-1d4b0a300101'");
        $this->addSql("UPDATE payment_outbox_message SET slug = '3d1f5a7c-7b8a-4f1f-8b77-1d4b0a300102' WHERE type = 'payment.failed' AND slug = '5d1f5a7c-7b8a-4f1f-8b77-1d4b0a300102'");
        $this->addSql("UPDATE payment_outbox_message SET slug = '3d1f5a7c-7b8a-4f1f-8b77-1d4b0a300103' WHERE type = 'payment.refunded' AND slug = '5d1f5a7c-7b8a-4f1f-8b77-1d4b0a300103'");

        $this->addSql("UPDATE payment_dlq SET outbox_id = '3d1f5a7c-7b8a-4f1f-8b77-1d4b0a300102' WHERE topic = 'payment.failed' AND outbox_id = '5d1f5a7c-7b8a-4f1f-8b77-1d4b0a300102'");
        $this->addSql("UPDATE payment_dlq SET outbox_id = '3d1f5a7c-7b8a-4f1f-8b77-1d4b0a300103' WHERE topic = 'payment.refunded' AND outbox_id = '5d1f5a7c-7b8a-4f1f-8b77-1d4b0a300103'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE payment_outbox_message SET slug = '5d1f5a7c-7b8a-4f1f-8b77-1d4b0a300101' WHERE type = 'payment.captured' AND slug = '3d1f5a7c-7b8a-4f1f-8b77-1d4b0a300101'");
        $this->addSql("UPDATE payment_outbox_message SET slug = '5d1f5a7c-7b8a-4f1f-8b77-1d4b0a300102' WHERE type = 'payment.failed' AND slug = '3d1f5a7c-7b8a-4f1f-8b77-1d4b0a300102'");
        $this->addSql("UPDATE payment_outbox_message SET slug = '5d1f5a7c-7b8a-4f1f-8b77-1d4b0a300103' WHERE type = 'payment.refunded' AND slug = '3d1f5a7c-7b8a-4f1f-8b77-1d4b0a300103'");

        $this->addSql("UPDATE payment_dlq SET outbox_id = '5d1f5a7c-7b8a-4f1f-8b77-1d4b0a300102' WHERE topic = 'payment.failed' AND outbox_id = '3d1f5a7c-7b8a-4f1f-8b77-1d4b0a300102'");
        $this->addSql("UPDATE payment_dlq SET outbox_id = '5d1f5a7c-7b8a-4f1f-8b77-1d4b0a300103' WHERE topic = 'payment.refunded' AND outbox_id = '3d1f5a7c-7b8a-4f1f-8b77-1d4b0a300103'");
    }
}
