<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber\Placement\Retail;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\EventSubscriber\Placement\Retail\RetailPlacementOrderLifecycleSubscriber;
use App\Retailing\Entity\Retail\RetailEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RetailPlacementOrderLifecycleSubscriberTest extends TestCase
{
    public function testRetailPublicationOrderLifecycleSubscriberIsDisabled(): void
    {
        $retail = new RetailEntity();
        $context = new CrudMutationLifecycleContext(
            new CrudContext('public', 'new', 'retail', RetailEntity::class, 'id', null, null),
            $retail,
            Request::create('/retail/new', 'POST'),
            'create',
        );

        self::assertFalse((new RetailPlacementOrderLifecycleSubscriber())->supports($context));
    }
}
