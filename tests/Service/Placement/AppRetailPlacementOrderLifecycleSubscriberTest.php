<?php

declare(strict_types=1);

namespace App\Tests\Service\Placement;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\Retailing\Entity\Retail\RetailEntity;
use App\Service\Placement\AppRetailPlacementOrderLifecycleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class AppRetailPlacementOrderLifecycleSubscriberTest extends TestCase
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

        self::assertFalse((new AppRetailPlacementOrderLifecycleSubscriber())->supports($context));
    }
}
