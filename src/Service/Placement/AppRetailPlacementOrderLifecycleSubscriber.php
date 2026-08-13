<?php

declare(strict_types=1);

namespace App\Service\Placement;

use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\Cruding\ServiceInterface\Crud\CrudMutationLifecycleSubscriberInterface;
use App\Ordering\Entity\Order\OrderEntity;
use App\Ordering\ValueObject\Pricing\Order\Money;
use App\Retailing\Entity\Retail\RetailEntity;
use Doctrine\Persistence\ManagerRegistry;

final readonly class AppRetailPlacementOrderLifecycleSubscriber implements CrudMutationLifecycleSubscriberInterface
{
    private const SESSION_KEY = 'retail_placement';

    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function supports(CrudMutationLifecycleContext $context): bool
    {
        return 'create' === $context->operation && $context->object instanceof RetailEntity;
    }

    public function before(CrudMutationLifecycleContext $context): void
    {
    }

    public function after(CrudMutationLifecycleContext $context): void
    {
        if (!$context->object instanceof RetailEntity || !$context->request->hasSession()) {
            return;
        }

        $vendorId = $context->object->getOwner();
        if (null === $vendorId || '' === trim($vendorId)) {
            throw new \DomainException('Retail placement requires a persisted vendor owner before order creation.');
        }

        $amountMinor = $context->object->getAmountMinor() ?? 0;
        $currency = $context->object->getCurrency();
        $order = new OrderEntity($vendorId, new Money(number_format($amountMinor / 100, 2, '.', ''), $currency));
        $manager = $this->managerRegistry->getManagerForClass(OrderEntity::class) ?? $this->managerRegistry->getManager();
        $manager->persist($order);
        $manager->flush();

        if (null === $order->getId()) {
            throw new \RuntimeException('Retail placement order did not receive a persisted identifier.');
        }

        $tenantId = 'default';
        foreach (['tenantId', 'tenant_id', '_tenant_id'] as $attribute) {
            $value = $context->request->attributes->get($attribute);
            if (is_scalar($value) && '' !== trim((string) $value)) {
                $tenantId = trim((string) $value);
                break;
            }
        }

        $context->request->getSession()->set(self::SESSION_KEY, [
            'retailId' => (string) $context->object->getId(),
            'orderId' => (string) $order->getId(),
            'orderSlug' => $order->getSlug(),
            'tenantId' => $tenantId,
            'vendorId' => $vendorId,
            'amountMinor' => $amountMinor,
            'currency' => $currency,
        ]);
    }
}
