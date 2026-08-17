<?php

declare(strict_types=1);

namespace App\Service\Placement;

use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\Cruding\ServiceInterface\Crud\CrudMutationLifecycleSubscriberInterface;

/**
 * Retained only until repository deletion tooling permits physical removal.
 * Retail publication no longer creates transactional orders.
 */
final class AppRetailPlacementOrderLifecycleSubscriber implements CrudMutationLifecycleSubscriberInterface
{
    public function supports(CrudMutationLifecycleContext $context): bool
    {
        return false;
    }

    public function before(CrudMutationLifecycleContext $context): void
    {
    }

    public function after(CrudMutationLifecycleContext $context): void
    {
    }
}
