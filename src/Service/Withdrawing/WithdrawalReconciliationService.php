<?php

declare(strict_types=1);

namespace App\Service\Withdrawing;

use Doctrine\DBAL\Connection;

final readonly class WithdrawalReconciliationService
{
    public function __construct(private Connection $connection)
    {
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function report(?\DateTimeImmutable $staleBefore = null): array
    {
        $staleBefore ??= new \DateTimeImmutable('-30 minutes');

        return [
            'processing_without_rail_reference' => $this->connection->fetchAllAssociative(
                "SELECT id, status, object_modified_at FROM withdrawal_request WHERE status = 'processing' AND rail_reference IS NULL ORDER BY object_modified_at NULLS FIRST, id",
            ),
            'stale_processing' => $this->connection->fetchAllAssociative(
                "SELECT id, rail_reference, object_modified_at FROM withdrawal_request WHERE status = 'processing' AND COALESCE(object_modified_at, object_created_at) < :stale_before ORDER BY COALESCE(object_modified_at, object_created_at), id",
                ['stale_before' => $staleBefore->format('Y-m-d H:i:sP')],
            ),
            'succeeded_without_references' => $this->connection->fetchAllAssociative(
                "SELECT id, source_reference, rail_reference FROM withdrawal_request WHERE status = 'succeeded' AND (source_reference IS NULL OR rail_reference IS NULL) ORDER BY id",
            ),
            'failed_settlement_events' => $this->connection->fetchAllAssociative(
                "SELECT provider, provider_event_id, event_type, rail_reference, failure_code, failure_message, created_at, processed_at FROM withdrawal_settlement_event WHERE outcome = 'failed' ORDER BY created_at, provider_event_id",
            ),
        ];
    }
}
