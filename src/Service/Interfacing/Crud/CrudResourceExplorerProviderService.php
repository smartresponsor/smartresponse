<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Crud;

use App\Interfacing\Contract\View\CrudResourceLinkSet;
use App\Interfacing\ProviderInterface\Crud\InterfaceCrudResourceExplorerProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final readonly class CrudResourceExplorerProviderService implements InterfaceCrudResourceExplorerProviderInterface
{
    public function __construct(
        #[TaggedIterator('app.interfacing.crud_resource_contribution')]
        private iterable $contributions,
    ) {
    }

    public function provide(): array
    {
        $resources = [];

        foreach ($this->contributions as $contribution) {
            if (!method_exists($contribution, 'provide')) {
                continue;
            }

            foreach ((array) $contribution->provide() as $descriptor) {
                if (!is_array($descriptor)) {
                    continue;
                }

                $id = (string) ($descriptor['resourceKey'] ?? $descriptor['resource'] ?? '');
                if ('' === $id || isset($resources[$id])) {
                    continue;
                }

                $component = (string) ($descriptor['component'] ?? 'Application');
                $label = (string) ($descriptor['label'] ?? $id);
                $path = trim((string) ($descriptor['pathSegment'] ?? $descriptor['resourcePath'] ?? $id), '/');
                $status = (string) ($descriptor['status'] ?? 'planned');
                $note = isset($descriptor['description']) ? (string) $descriptor['description'] : null;

                $resources[$id] = new CrudResourceLinkSet(
                    id: $id,
                    component: $component,
                    label: $label,
                    resourcePath: $path,
                    status: $status,
                    indexUrl: '/'.$path.'/',
                    newUrl: '/'.$path.'/new',
                    showSampleUrl: '/'.$path.'/sample',
                    editSampleUrl: '/'.$path.'/sample/edit',
                    deleteSampleUrl: '/'.$path.'/sample/delete',
                    note: $note,
                );
            }
        }

        uasort(
            $resources,
            static fn (CrudResourceLinkSet $left, CrudResourceLinkSet $right): int => [
                $left->component(),
                $left->label(),
                $left->id(),
            ] <=> [
                $right->component(),
                $right->label(),
                $right->id(),
            ],
        );

        return array_values($resources);
    }
}
