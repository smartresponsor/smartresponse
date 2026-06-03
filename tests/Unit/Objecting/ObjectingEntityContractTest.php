<?php

declare(strict_types=1);

namespace App\Tests\Unit\Objecting;

use App\Entity\Category\CategoryEntity;
use App\Entity\Featured\FeaturedEntity;
use App\Entity\Menu\MenuEntity;
use App\Entity\Module\ModuleEntity;
use App\Entity\Product\ProductTypeEntity;
use App\Entity\Project\ProjectTypeEntity;
use App\Entity\Review\ReviewEntity;
use App\Objecting\EntityInterface\ObjectAuditedInterface;
use App\Objecting\EntityInterface\ObjectCodedInterface;
use App\Objecting\EntityInterface\ObjectIdentifiedInterface;
use App\Objecting\EntityInterface\ObjectScopedInterface;
use App\Objecting\EntityInterface\ObjectStatefulInterface;
use App\Objecting\EntityInterface\ObjectTitledInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ObjectingEntityContractTest extends TestCase
{
    #[DataProvider('entityProvider')]
    public function testEntitiesImplementObjectingContracts(object $entity, array $interfaces): void
    {
        foreach ($interfaces as $interface) {
            self::assertInstanceOf($interface, $entity);
        }
    }

    #[DataProvider('moduleProvider')]
    public function testModuleEntityUsesObjectingFields(ModuleEntity $module): void
    {
        self::assertSame('catalog', $module->code());
        self::assertSame('catalog', $module->slug());
        self::assertSame('Catalog', $module->name());
        self::assertSame('Primary catalog module', $module->description());
        self::assertSame('default', $module->tenantId());
        self::assertTrue($module->isActive());
        self::assertInstanceOf(\DateTimeImmutable::class, $module->createdAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $module->updatedAt());
        self::assertSame('catalog', $module->getObjectCode());
        self::assertSame('catalog', $module->getObjectSlug());
        self::assertSame('default', $module->getObjectTenant());
        self::assertSame('Catalog', $module->getFirstTitle());
        self::assertSame('Primary catalog module', $module->getLastTitle());
        self::assertTrue($module->isObjectActive());
        self::assertTrue($module->isObjectEnabled());
    }

    #[DataProvider('reviewProvider')]
    public function testReviewEntityUsesObjectingFields(ReviewEntity $review): void
    {
        self::assertSame('catalog-review', $review->slug());
        self::assertSame('default', $review->tenantId());
        self::assertSame('Catalog review', $review->title());
        self::assertSame('Seeded review comment', $review->comment());
        self::assertSame('published', $review->status());
        self::assertTrue($review->isObjectActive());
        self::assertTrue($review->isObjectEnabled());
        self::assertSame('published', $review->getObjectStatus());
        self::assertSame('default', $review->getObjectTenant());
    }

    public static function entityProvider(): iterable
    {
        $module = self::createModule();
        $category = new CategoryEntity($module, 'catalog-category', 'catalog-category', 'Catalog category', 'Default category');
        $featured = new FeaturedEntity('catalog-featured', 'catalog-featured', 'Featured catalog item', $module);
        $menu = new MenuEntity($module, 'catalog-menu', 'catalog-menu', 'Catalog menu', '/catalog');
        $productType = new ProductTypeEntity($module, 'product-type', 'product-type', 'Product type');
        $projectType = new ProjectTypeEntity($module, 'project-type', 'project-type', 'Project type');
        $review = self::createReview();

        return [
            'module' => [$module, [ObjectAuditedInterface::class, ObjectCodedInterface::class, ObjectIdentifiedInterface::class, ObjectScopedInterface::class, ObjectStatefulInterface::class, ObjectTitledInterface::class]],
            'category' => [$category, [ObjectAuditedInterface::class, ObjectCodedInterface::class, ObjectIdentifiedInterface::class, ObjectScopedInterface::class, ObjectStatefulInterface::class, ObjectTitledInterface::class]],
            'featured' => [$featured, [ObjectAuditedInterface::class, ObjectCodedInterface::class, ObjectIdentifiedInterface::class, ObjectScopedInterface::class, ObjectStatefulInterface::class, ObjectTitledInterface::class]],
            'menu' => [$menu, [ObjectAuditedInterface::class, ObjectCodedInterface::class, ObjectIdentifiedInterface::class, ObjectScopedInterface::class, ObjectStatefulInterface::class, ObjectTitledInterface::class]],
            'productType' => [$productType, [ObjectAuditedInterface::class, ObjectCodedInterface::class, ObjectIdentifiedInterface::class, ObjectScopedInterface::class, ObjectStatefulInterface::class, ObjectTitledInterface::class]],
            'projectType' => [$projectType, [ObjectAuditedInterface::class, ObjectCodedInterface::class, ObjectIdentifiedInterface::class, ObjectScopedInterface::class, ObjectStatefulInterface::class, ObjectTitledInterface::class]],
            'review' => [$review, [ObjectAuditedInterface::class, ObjectIdentifiedInterface::class, ObjectScopedInterface::class, ObjectStatefulInterface::class, ObjectTitledInterface::class]],
        ];
    }

    public static function moduleProvider(): iterable
    {
        yield [self::createModule()];
    }

    public static function reviewProvider(): iterable
    {
        yield [self::createReview()];
    }

    private static function createModule(): ModuleEntity
    {
        return new ModuleEntity('catalog', 'catalog', 'Catalog', 'Primary catalog module', true, 'default');
    }

    private static function createReview(): ReviewEntity
    {
        return new ReviewEntity(
            'default',
            'catalog-review',
            'module',
            'catalog',
            'fixture-author',
            5,
            'Catalog review',
            'Seeded review comment',
            null,
            'published',
            true,
            'catalog',
            ['source' => 'fixture'],
            ['seeded' => true]
        );
    }
}
