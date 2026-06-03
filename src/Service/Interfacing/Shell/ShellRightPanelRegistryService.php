<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use App\Interfacing\Contract\View\ShellNavGroup;
use App\Interfacing\Contract\View\ShellNavItem;
use App\Interfacing\ServiceInterface\Crud\CrudResourceExplorerProviderInterface;

final class ShellRightPanelRegistryService
{
    public function __construct(
        private readonly ShellChromeUrlResolverService $urlResolver,
        private readonly CrudResourceExplorerProviderInterface $crudResourceExplorerProvider,
    ) {
    }

    /** @return list<ShellNavGroup> */
    public function provide(): array
    {
        return [
            new ShellNavGroup('application-dashboard', 'Application dashboard', [
                new ShellNavItem('applications.dashboard', 'Applications UI', $this->urlResolver->safeUrl('interfacing_application_dashboard', '/interfacing/applications'), 'applications', null, 10),
                new ShellNavItem('applications.dashboard.json', 'Applications JSON', $this->urlResolver->safeUrl('interfacing_application_dashboard_json', '/interfacing/applications.json'), 'applications', null, 20),
            ]),
            new ShellNavGroup('crud-exports', 'CRUD exports', [
                new ShellNavItem('crud.links.json', 'Links JSON', $this->urlResolver->safeUrl('interfacing_crud_explorer_links', '/interfacing/crud/explorer/links.json'), 'crud', null, 10),
                new ShellNavItem('crud.route.expectations', 'Route expectations', $this->urlResolver->safeUrl('interfacing_crud_explorer_route_expectations', '/interfacing/crud/explorer/route-expectations.json'), 'crud', null, 20),
                new ShellNavItem('crud.operations.json', 'Operations JSON', $this->urlResolver->safeUrl('interfacing_crud_explorer_operations', '/interfacing/crud/explorer/operations.json'), 'crud', null, 30),
                new ShellNavItem('crud.screens.json', 'Screens JSON', $this->urlResolver->safeUrl('interfacing_crud_explorer_screens', '/interfacing/crud/explorer/screens.json'), 'crud', null, 40),
            ]),
            new ShellNavGroup('shell-guard', 'Shell guard', [
                new ShellNavItem('shell.diagnostics', 'Panel diagnostics', $this->urlResolver->safeUrl('interfacing_shell_diagnostics', '/interfacing/shell/diagnostics'), 'shell', null, 10),
                new ShellNavItem('shell.diagnostics.json', 'Diagnostics JSON', $this->urlResolver->safeUrl('interfacing_shell_diagnostics_json', '/interfacing/shell/diagnostics.json'), 'shell', null, 20),
                new ShellNavItem('shell.navigation', 'Navigation map', $this->urlResolver->safeUrl('interfacing_shell_navigation', '/interfacing/shell/navigation'), 'shell', null, 30),
                new ShellNavItem('shell.navigation.json', 'Navigation JSON', $this->urlResolver->safeUrl('interfacing_shell_navigation_json', '/interfacing/shell/navigation.json'), 'shell', null, 40),
                new ShellNavItem('shell.screens', 'Screen catalog', $this->urlResolver->safeUrl('interfacing_shell_screen_catalog', '/interfacing/shell/screens'), 'shell', null, 50),
                new ShellNavItem('shell.screens.json', 'Screen catalog JSON', $this->urlResolver->safeUrl('interfacing_shell_screen_catalog_json', '/interfacing/shell/screens.json'), 'shell', null, 60),
                new ShellNavItem('shell.layout.preview', 'Layout preview', $this->urlResolver->safeUrl('interfacing_shell_layout_preview', '/interfacing/shell/layout-preview'), 'shell', null, 70),
                new ShellNavItem('shell.layout.preview.json', 'Layout preview JSON', $this->urlResolver->safeUrl('interfacing_shell_layout_preview_json', '/interfacing/shell/layout-preview.json'), 'shell', null, 80),
            ]),
            new ShellNavGroup('quick-crud', 'Quick CRUD', array_slice($this->crudSectionItems(), 0, 12)),
        ];
    }

    /** @return list<ShellNavItem> */
    private function crudSectionItems(): array
    {
        $items = [
            new ShellNavItem('crud.explorer', 'CRUD explorer', $this->urlResolver->safeUrl('interfacing_crud_explorer', '/interfacing/crud/explorer'), 'crud', null, 10),
        ];

        $order = 20;
        foreach ($this->crudResourceExplorerProvider->provide() as $resource) {
            $items[] = new ShellNavItem(
                id: 'crud.resource.'.$resource->id(),
                title: $resource->component().' · '.$resource->label(),
                url: $resource->indexUrl(),
                group: 'crud',
                icon: null,
                order: $order,
            );
            $order += 10;
        }

        return $items;
    }
}
