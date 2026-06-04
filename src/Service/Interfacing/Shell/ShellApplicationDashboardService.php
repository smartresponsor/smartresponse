<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use App\Interfacing\ProviderInterface\Crud\InterfaceCrudResourceExplorerProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class ShellApplicationDashboardService
{
    public function __construct(
        private readonly InterfaceCrudResourceExplorerProviderInterface $crudResourceExplorerProvider,
        #[Autowire(service: 'cache.app.recorder_inner')]
        private readonly CacheInterface $cache,
    ) {
    }

    /** @return array<string,mixed> */
    public function applicationDashboard(): array
    {
        return $this->cache->get('interfacing.shell.chrome.application-dashboard.v4', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            $components = [];
            $statusCounts = [
                'connected' => 0,
                'canonical' => 0,
                'planned' => 0,
                'other' => 0,
            ];
            $operationTotal = 0;

            foreach ($this->crudResourceExplorerProvider->provide() as $resource) {
                $component = $resource->component();
                $status = $resource->status();
                $statusKey = array_key_exists($status, $statusCounts) ? $status : 'other';
                ++$statusCounts[$statusKey];

                if (!isset($components[$component])) {
                    $components[$component] = [
                        'component' => $component,
                        'status' => $status,
                        'statusCounts' => [
                            'connected' => 0,
                            'canonical' => 0,
                            'planned' => 0,
                            'other' => 0,
                        ],
                        'resourceCount' => 0,
                        'operationCount' => 0,
                        'firstIndexUrl' => $resource->indexUrl(),
                        'resources' => [],
                    ];
                }

                ++$components[$component]['statusCounts'][$statusKey];
                ++$components[$component]['resourceCount'];
                $components[$component]['status'] = $this->strongerStatus((string) $components[$component]['status'], $status);

                $operations = $resource->operationUrls();
                $operationTotal += count($operations);
                $components[$component]['operationCount'] += count($operations);
                $components[$component]['resources'][] = [
                    'id' => $resource->id(),
                    'component' => $component,
                    'label' => $resource->label(),
                    'resourcePath' => $resource->resourcePath(),
                    'status' => $status,
                    'indexUrl' => $resource->indexUrl(),
                    'newUrl' => $resource->newUrl(),
                    'showSampleUrl' => $resource->showSampleUrl(),
                    'editSampleUrl' => $resource->editSampleUrl(),
                    'deleteSampleUrl' => $resource->deleteSampleUrl(),
                    'operations' => $operations,
                ];
            }

            $componentList = array_values($components);
            usort($componentList, static fn (array $left, array $right): int => [$left['component']] <=> [$right['component']]);

            return [
                'schema' => 'smart-responsor.interfacing.application-dashboard.v1',
                'summary' => [
                    'componentCount' => count($componentList),
                    'resourceCount' => array_sum(array_map(static fn (array $component): int => (int) $component['resourceCount'], $componentList)),
                    'operationCount' => $operationTotal,
                    'connectedResources' => $statusCounts['connected'],
                    'canonicalResources' => $statusCounts['canonical'],
                    'plannedResources' => $statusCounts['planned'],
                    'otherResources' => $statusCounts['other'],
                ],
                'statusCounts' => $statusCounts,
                'components' => $componentList,
                'contract' => [
                    'topPanelRequired' => true,
                    'leftPanelsRequired' => true,
                    'footerRequired' => true,
                    'crudBridgePatternRequired' => true,
                    'note' => 'Connected, canonical and planned Smart Response components are intentionally visible so the host application can validate real CRUD address-bar patterns early.',
                ],
            ];
        });
    }

    /**
     * @return list<array{id:string,component:string,label:string,resourcePath:string,status:string,indexUrl:string,newUrl:string,showSampleUrl:string,editSampleUrl:string,deleteSampleUrl:string}>
     */
    public function knownCrudResources(): array
    {
        return $this->cache->get('interfacing.shell.chrome.known-crud-resources.v4', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            $resources = [];
            foreach ($this->crudResourceExplorerProvider->provide() as $resource) {
                $resources[] = [
                    'id' => $resource->id(),
                    'component' => $resource->component(),
                    'label' => $resource->label(),
                    'resourcePath' => $resource->resourcePath(),
                    'status' => $resource->status(),
                    'indexUrl' => $resource->indexUrl(),
                    'newUrl' => $resource->newUrl(),
                    'showSampleUrl' => $resource->showSampleUrl(),
                    'editSampleUrl' => $resource->editSampleUrl(),
                    'deleteSampleUrl' => $resource->deleteSampleUrl(),
                ];
            }

            return $resources;
        });
    }

    private function strongerStatus(string $current, string $candidate): string
    {
        $priority = [
            'connected' => 300,
            'canonical' => 200,
            'planned' => 100,
        ];

        return ($priority[$candidate] ?? 0) > ($priority[$current] ?? 0) ? $candidate : $current;
    }
}
