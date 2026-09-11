<?php

declare(strict_types=1);

namespace App\EventSubscriber\Placement\Retail;

use App\Cruding\Dto\CrudMutationLifecycleContextDTO;
use App\Cruding\ServiceInterface\CrudMutationLifecycleSubscriberInterface;

/**
 * Retained only until repository deletion tooling permits physical removal.
 * Retail publication no longer creates transactional orders.
 */
final class RetailPlacementOrderLifecycleSubscriber implements CrudMutationLifecycleSubscriberInterface
{
    public function supports(CrudMutationLifecycleContextDTO $context): bool
    {
        return false;
    }

    public function before(CrudMutationLifecycleContextDTO $context): void
    {
    }

    public function after(CrudMutationLifecycleContextDTO $context): void
    {
    }
}
