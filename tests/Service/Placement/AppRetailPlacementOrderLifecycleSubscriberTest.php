<?php

declare(strict_types=1);

namespace App\Tests\Service\Placement;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\Ordering\Entity\Order\OrderEntity;
use App\Retailing\Entity\Retail\RetailEntity;
use App\Service\Placement\AppRetailPlacementOrderLifecycleSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class AppRetailPlacementOrderLifecycleSubscriberTest extends TestCase
{
    public function testCreatesRealOrderAndStoresPlacementIdentity(): void
    {
        $retail = new RetailEntity();
        $retail->setOwner('vendor-17');
        $retail->setAmountMinor(12500);
        $retail->setCurrency('USD');
        (new \ReflectionProperty(RetailEntity::class, 'id'))->setValue($retail, 7);
        $persistedOrder = null;
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->willReturnCallback(static function (object $object) use (&$persistedOrder): void {
            self::assertInstanceOf(OrderEntity::class, $object);
            $persistedOrder = $object;
        });
        $em->expects(self::once())->method('flush')->willReturnCallback(static function () use (&$persistedOrder): void {
            self::assertInstanceOf(OrderEntity::class, $persistedOrder);
            (new \ReflectionProperty(OrderEntity::class, 'id'))->setValue($persistedOrder, 42);
        });
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::once())->method('getManagerForClass')->with(OrderEntity::class)->willReturn($em);
        $request = Request::create('/retail/new', 'POST');
        $request->attributes->set('tenantId', 'tenant-5');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $context = new CrudMutationLifecycleContext(new CrudContext('public', 'new', 'retail', RetailEntity::class, 'id', null, null), $retail, $request, 'create');
        $subscriber = new AppRetailPlacementOrderLifecycleSubscriber($registry);
        self::assertTrue($subscriber->supports($context));
        $subscriber->after($context);
        self::assertInstanceOf(OrderEntity::class, $persistedOrder);
        self::assertSame('vendor-17', $persistedOrder->getVendorId());
        self::assertSame('125.00', $persistedOrder->getGrandTotal());
        self::assertSame('USD', $persistedOrder->getCurrency());
        $placement = $request->getSession()->get('retail_placement');
        self::assertIsArray($placement);
        self::assertSame('7', $placement['retailId']);
        self::assertSame('42', $placement['orderId']);
        self::assertSame($persistedOrder->getSlug(), $placement['orderSlug']);
        self::assertSame('tenant-5', $placement['tenantId']);
        self::assertSame('vendor-17', $placement['vendorId']);
        self::assertSame(12500, $placement['amountMinor']);
        self::assertSame('USD', $placement['currency']);
    }
}
