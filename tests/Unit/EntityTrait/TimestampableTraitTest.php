<?php

declare(strict_types=1);

namespace App\Tests\Unit\EntityTrait;

use App\EntityTrait\TimestampableTrait;
use PHPUnit\Framework\TestCase;

final class TimestampableTraitTest extends TestCase
{
    public function testCanonicalAuditSurfaceIsAvailableAndMutable(): void
    {
        $createdAt = new \DateTimeImmutable('2026-06-14 12:00:00 UTC');
        $modifiedAt = new \DateTimeImmutable('2026-06-14 13:15:00 UTC');

        $subject = new class {
            use TimestampableTrait;

            public function seed(?\DateTimeImmutable $createdAt = null, ?string $createdBy = null): void
            {
                $this->initializeObjectAudit($createdAt, $createdBy);
            }
        };

        self::assertFalse(method_exists($subject, 'normalizeTenantId'));

        $subject->seed($createdAt, 'vendor_001');
        $subject->touchModified($modifiedAt, 'vendor_002');

        self::assertSame($createdAt, $subject->getCreatedAt());
        self::assertSame($modifiedAt, $subject->getModifiedAt());
        self::assertSame('vendor_001', $subject->getCreatedBy());
        self::assertSame('vendor_002', $subject->getModifiedBy());
    }
}
