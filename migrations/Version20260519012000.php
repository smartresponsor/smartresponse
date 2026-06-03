<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519012000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace placeholder Paying UUID slugs with realistic UUID fixtures.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE payment
SET slug = mapped.new_slug
FROM (
    VALUES
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5001'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5001'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5002'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5002'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5003'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5003'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5004'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5004'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5005'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5005'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5006'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5006'::uuid)
) AS mapped(old_slug, new_slug)
WHERE payment.slug = mapped.old_slug
SQL);

        $this->addSql(<<<'SQL'
UPDATE payment_transaction
SET payment_id = mapped.new_slug
FROM (
    VALUES
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5002'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5002'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5003'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5003'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5004'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5004'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5005'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5005'::uuid)
) AS mapped(old_slug, new_slug)
WHERE payment_transaction.payment_id = mapped.old_slug
SQL);

        $this->addSql(<<<'SQL'
UPDATE payment_refund
SET payment_id = mapped.new_slug
FROM (
    VALUES
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5004'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5004'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5005'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5005'::uuid)
) AS mapped(old_slug, new_slug)
WHERE payment_refund.payment_id = mapped.old_slug
SQL);

        foreach ([
            'payment_webhook_log',
            'payment_outbox_message',
            'payment_dlq',
        ] as $table) {
            $this->addSql(<<<SQL
UPDATE {$table}
SET payload = replace(payload::text, '9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5002', '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5002')::json
WHERE payload::text LIKE '%9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5002%'
SQL);
            $this->addSql(<<<SQL
UPDATE {$table}
SET payload = replace(payload::text, '9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5003', '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5003')::json
WHERE payload::text LIKE '%9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5003%'
SQL);
            $this->addSql(<<<SQL
UPDATE {$table}
SET payload = replace(payload::text, '9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5004', '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5004')::json
WHERE payload::text LIKE '%9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5004%'
SQL);
            $this->addSql(<<<SQL
UPDATE {$table}
SET payload = replace(payload::text, '9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5005', '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5005')::json
WHERE payload::text LIKE '%9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5005%'
SQL);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE payment
SET slug = mapped.old_slug
FROM (
    VALUES
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5001'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5001'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5002'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5002'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5003'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5003'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5004'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5004'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5005'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5005'::uuid),
        ('9a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5006'::uuid, '8a4f1c2e-9e33-4c1b-9a6f-1a2b3c4d5006'::uuid)
) AS mapped(old_slug, new_slug)
WHERE payment.slug = mapped.new_slug
SQL);
    }
}
