<?php

declare(strict_types=1);

namespace App\EventSubscriber\Crud;

use App\Attaching\ServiceInterface\Attachment\AttachmentOwnerPurgeServiceInterface;
use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\Cruding\ServiceInterface\Crud\CrudMutationLifecycleSubscriberInterface;

final readonly class AttachmentOwnerPurgeLifecycleSubscriber implements CrudMutationLifecycleSubscriberInterface
{
    /**
     * @param array<string, string> $ownerTypeByResourcePath
     */
    public function __construct(
        private AttachmentOwnerPurgeServiceInterface $attachmentOwnerPurgeService,
        private array $ownerTypeByResourcePath,
    ) {
    }

    public function supports(CrudMutationLifecycleContext $context): bool
    {
        return 'delete' === $context->operation
            && isset($this->ownerTypeByResourcePath[$context->crudContext->resourcePath]);
    }

    public function before(CrudMutationLifecycleContext $context): void
    {
        $resourcePath = $context->crudContext->resourcePath;
        $ownerId = $context->crudContext->identifierValue;

        if (null === $ownerId && method_exists($context->object, 'getId')) {
            $ownerId = $context->object->getId();
        }

        if (!is_string($ownerId) && !is_int($ownerId)) {
            throw new \LogicException('crud_attachment_owner_identifier_unavailable');
        }

        $this->attachmentOwnerPurgeService->purge($this->ownerTypeByResourcePath[$resourcePath], (string) $ownerId);
    }

    public function after(CrudMutationLifecycleContext $context): void
    {
    }
}
