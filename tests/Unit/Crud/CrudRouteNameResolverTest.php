<?php

declare(strict_types=1);

namespace App\Tests\Unit\Crud;

use App\Dto\Crud\CrudContext;
use App\Service\Crud\CrudRouteNameResolver;
use PHPUnit\Framework\TestCase;

final class CrudRouteNameResolverTest extends TestCase
{
    public function testResolveShowUsesSlugByDefault(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('public', 'show', 'product', 'App\\Entity\\CommerceProductEntity', 'slug', 'demo', null, 'product/product');

        self::assertSame('app_crud_show_slug', $resolver->resolveShow($context));
    }

    public function testResolveShowUsesIdWhenRequested(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('admin', 'show', 'product', 'App\\Entity\\CommerceProductEntity', 'id', 15, null, 'product/product');

        self::assertSame('app_crud_show_id', $resolver->resolveShow($context));
    }

    public function testParametersCarryResourcePathAndIdentifier(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('public', 'show', 'product/price', 'App\\Entity\\Price', 'slug', 'gold', null, 'product/price');

        self::assertSame([
            'resourcePath' => 'product/price',
            'slug' => 'gold',
        ], $resolver->parameters($context));
    }
}
