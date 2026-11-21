<?php

declare(strict_types=1);

namespace App\Tests\Unit\Provider\Attachment;

use App\Provider\Attachment\AttachmentContextTreeProjectionProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class AttachmentContextTreeProjectionProviderTest extends TestCase
{
    public function testProjectsMediaAndDocumentTaxonomy(): void
    {
        $service = new AttachmentContextTreeProjectionProvider();

        self::assertSame('attachment.tree', $service->key());

        $tree = $service->project([], [], Request::create('/attachment/index'));

        self::assertCount(2, $tree);
        self::assertSame('Media', $tree[0]['label']);
        self::assertSame('Documents', $tree[1]['label']);

        self::assertIsArray($tree[0]['children']);
        self::assertIsArray($tree[1]['children']);
        self::assertSame(
            ['Avatars', 'Cover images', 'Images', 'Video', 'Audio', 'Other media'],
            array_column($tree[0]['children'], 'label'),
        );
        self::assertSame(
            ['Verifications', 'Personal identity', 'PDF', 'Text documents', 'Spreadsheets', 'Presentations', 'Archives', 'Other documents'],
            array_column($tree[1]['children'], 'label'),
        );

        self::assertIsArray($tree[0]['children'][0]);
        self::assertIsArray($tree[1]['children'][1]);
        self::assertSame(
            '/attachment/index?type=media&mediaKind=image&context=profile&slot=avatar',
            $tree[0]['children'][0]['href'],
        );
        self::assertSame(
            '/attachment/index?type=document&context=verification&slot=personal_identity',
            $tree[1]['children'][1]['href'],
        );
    }
}
