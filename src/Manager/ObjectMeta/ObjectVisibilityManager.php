<?php

declare(strict_types=1);

namespace App\Manager\ObjectMeta;

use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class ObjectVisibilityManager
{
    public function __construct(
        private CrudFormHandlerInterface $formHandler,
        private array $transitionMap = [],
    ) {
    }

    public function inspect(object $object): object
    {
        return (object) [
            'visible' => $this->readBoolean($object, 'isVisible'),
            'published' => $this->readBoolean($object, 'isPublished'),
            'archived' => $this->readBoolean($object, 'isArchived'),
            'draft' => $this->readBoolean($object, 'isDraft'),
        ];
    }

    public function apply(object $object, string $transition): object
    {
        $transitionConfig = $this->transitionMap[$transition] ?? null;
        $writes = is_array($transitionConfig) ? ($transitionConfig['writes'] ?? null) : null;

        if (!is_array($writes)) {
            throw new BadRequestHttpException(sprintf('Unsupported visibility transition: %s', $transition));
        }

        foreach ($writes as $method => $value) {
            if (!is_string($method) || !method_exists($object, $method)) {
                throw new BadRequestHttpException(sprintf('Unsupported visibility transition: %s', $transition));
            }

            $object->{$method}(true === $value);
        }

        $this->formHandler->flush($object);

        return $this->inspect($object);
    }

    private function readBoolean(object $object, string $method): bool
    {
        if (!method_exists($object, $method)) {
            return false;
        }

        return true === $object->{$method}();
    }
}
