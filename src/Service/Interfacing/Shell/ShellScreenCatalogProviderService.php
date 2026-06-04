<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use App\Interfacing\ProviderInterface\Shell\InterfaceShellChromeProviderInterface;
use App\Interfacing\ProviderInterface\Shell\InterfaceShellNavigationMapProviderInterface;
use App\Interfacing\ProviderInterface\Shell\InterfaceShellScreenCatalogProviderInterface;

final readonly class ShellScreenCatalogProviderService implements InterfaceShellScreenCatalogProviderInterface
{
    public function __construct(
        private InterfaceShellChromeProviderInterface $shellChromeProvider,
        private InterfaceShellNavigationMapProviderInterface $shellNavigationMapProvider,
    ) {
    }

    public function catalog(?string $activeId = null): array
    {
        $shell = $this->shellChromeProvider->provide($activeId, true, false);
        $navigation = $this->shellNavigationMapProvider->map($activeId);
        $screens = [];

        foreach (($navigation['panels'] ?? []) as $panelId => $panel) {
            if (!is_array($panel)) {
                continue;
            }
            foreach (($panel['links'] ?? []) as $link) {
                if (is_array($link)) {
                    $this->appendScreen($screens, $this->screenFromLink((string) $panelId, (string) ($panel['label'] ?? $panelId), $link));
                }
            }
            foreach (($panel['groups'] ?? []) as $group) {
                if (!is_array($group)) {
                    continue;
                }
                foreach (($group['links'] ?? []) as $link) {
                    if (is_array($link)) {
                        $this->appendScreen($screens, $this->screenFromLink((string) $panelId, (string) ($group['title'] ?? $panelId), $link));
                    }
                }
            }
        }

        foreach (($shell['knownCrudResources'] ?? []) as $resource) {
            if (!is_array($resource)) {
                continue;
            }
            foreach ($this->crudScreens($resource) as $screen) {
                $this->appendScreen($screens, $screen);
            }
        }

        usort($screens, static fn (array $left, array $right): int => [$left['zone'], $left['component'], $left['title'], $left['operation']] <=> [$right['zone'], $right['component'], $right['title'], $right['operation']]);

        $byZone = [];
        $byComponent = [];
        foreach ($screens as $screen) {
            $zone = (string) $screen['zone'];
            $component = (string) $screen['component'];
            $byZone[$zone] ??= ['zone' => $zone, 'screenCount' => 0, 'screens' => []];
            ++$byZone[$zone]['screenCount'];
            $byZone[$zone]['screens'][] = $screen;

            $byComponent[$component] ??= ['component' => $component, 'screenCount' => 0, 'screens' => []];
            ++$byComponent[$component]['screenCount'];
            $byComponent[$component]['screens'][] = $screen;
        }

        ksort($byZone);
        ksort($byComponent);

        return [
            'schema' => 'smart-responsor.interfacing.shell-screen-catalog.v1',
            'summary' => [
                'screenCount' => count($screens),
                'zoneCount' => count($byZone),
                'componentCount' => count($byComponent),
                'crudResourceCount' => is_countable($shell['knownCrudResources'] ?? null) ? count($shell['knownCrudResources']) : 0,
                'topPanelRequired' => true,
                'leftPanelsRequired' => true,
                'rightPanelDefault' => true,
                'footerRequired' => true,
            ],
            'screens' => array_values($screens),
            'zones' => array_values($byZone),
            'components' => array_values($byComponent),
            'contract' => [
                'purpose' => 'Single screen catalog for shell pages, CRUD bridge pages, diagnostics, exports and known Smart Response application resources.',
                'crudLinkRule' => 'CRUD entries use the same bridge URL grammar exposed by CrudResourceExplorerProvider.',
                'shellRule' => 'All catalog UI screens must render through the shared Interfacing shell with top, left, body, optional right and footer panels.',
            ],
        ];
    }

    /** @param array<string,array<string,mixed>> $screens */
    private function appendScreen(array &$screens, array $screen): void
    {
        $key = $screen['key'];
        if (isset($screens[$key])) {
            return;
        }
        $screens[$key] = $screen;
    }

    /** @param array<string,mixed> $link */
    private function screenFromLink(string $zone, string $groupTitle, array $link): array
    {
        $id = (string) ($link['id'] ?? $link['title'] ?? $link['url'] ?? 'screen');
        $title = (string) ($link['title'] ?? $id);
        $url = (string) ($link['url'] ?? '#');

        return [
            'key' => 'shell:'.$zone.':'.$id.':'.$url,
            'id' => $id,
            'title' => $title,
            'component' => $this->componentFromId($id, $url),
            'zone' => $zone,
            'group' => $groupTitle,
            'operation' => $this->operationFromUrl($url),
            'url' => $url,
            'source' => 'shell-navigation',
            'status' => str_starts_with($url, '#') ? 'placeholder' : 'connected',
        ];
    }

    /** @param array<string,mixed> $resource */
    private function crudScreens(array $resource): array
    {
        $component = (string) ($resource['component'] ?? 'Application');
        $label = (string) ($resource['label'] ?? $resource['id'] ?? 'Resource');
        $id = (string) ($resource['id'] ?? strtolower($component).'.resource');
        $status = (string) ($resource['status'] ?? 'planned');
        $operations = [
            'index' => (string) ($resource['indexUrl'] ?? '#'),
            'new' => (string) ($resource['newUrl'] ?? '#'),
            'show' => (string) ($resource['showSampleUrl'] ?? '#'),
            'edit' => (string) ($resource['editSampleUrl'] ?? '#'),
            'delete' => (string) ($resource['deleteSampleUrl'] ?? '#'),
        ];

        $screens = [];
        foreach ($operations as $operation => $url) {
            $screens[] = [
                'key' => 'crud:'.$id.':'.$operation,
                'id' => 'crud.resource.'.$id.'.'.$operation,
                'title' => $component.' · '.$label.' · '.ucfirst($operation),
                'component' => $component,
                'zone' => 'crud',
                'group' => 'CRUD bridge resources',
                'operation' => $operation,
                'url' => $url,
                'source' => 'crud-bridge',
                'status' => $status,
            ];
        }

        return $screens;
    }

    private function componentFromId(string $id, string $url): string
    {
        $needle = strtolower($id.' '.$url);
        foreach (['crud' => 'CRUD', 'shell' => 'Shell', 'application' => 'Applications', 'screen' => 'Screens', 'message' => 'Messaging', 'order' => 'Ordering', 'billing' => 'Billing', 'catalog' => 'Cataloging', 'component' => 'Components', 'contract' => 'Contracts', 'field' => 'Schemas', 'evidence' => 'Evidence'] as $token => $component) {
            if (str_contains($needle, $token)) {
                return $component;
            }
        }

        return 'Interfacing';
    }

    private function operationFromUrl(string $url): string
    {
        if (str_ends_with($url, '.json')) {
            return 'json';
        }
        if (str_contains($url, '/new')) {
            return 'new';
        }
        if (str_contains($url, '/edit')) {
            return 'edit';
        }
        if (str_contains($url, '/delete')) {
            return 'delete';
        }

        return 'open';
    }
}
