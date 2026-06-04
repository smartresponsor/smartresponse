<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use App\Interfacing\Contract\View\InterfaceShellNavGroup;
use App\Interfacing\Contract\View\InterfaceShellNavItem;
use App\Interfacing\ProviderInterface\Crud\InterfaceCrudResourceExplorerProviderInterface;

final class ShellRightPanelRegistryService
{
    public function __construct(
        private readonly ShellChromeUrlResolverService $urlResolver,
        private readonly InterfaceCrudResourceExplorerProviderInterface $crudResourceExplorerProvider,
    ) {
    }

    /** @return list<InterfaceShellNavGroup> */
    public function provide(): array
    {
        return [
            new InterfaceShellNavGroup('application-dashboard', 'Application dashboard', [
                new InterfaceShellNavItem('applications.dashboard', 'Applications UI', $this->urlResolver->safeUrl('interfacing_application_dashboard', '/interfacing/applications'), 'applications', null, 10),
                new InterfaceShellNavItem('applications.dashboard.json', 'Applications JSON', $this->urlResolver->safeUrl('interfacing_application_dashboard_json', '/interfacing/applications.json'), 'applications', null, 20),
            ]),
            new InterfaceShellNavGroup('crud-exports', 'CRUD exports', [
                new InterfaceShellNavItem('crud.links.json', 'Links JSON', $this->urlResolver->safeUrl('interfacing_crud_explorer_links', '/interfacing/crud/explorer/links.json'), 'crud', null, 10),
                new InterfaceShellNavItem('crud.route.expectations', 'Route expectations', $this->urlResolver->safeUrl('interfacing_crud_explorer_route_expectations', '/interfacing/crud/explorer/route-expectations.json'), 'crud', null, 20),
                new InterfaceShellNavItem('crud.operations.json', 'Operations JSON', $this->urlResolver->safeUrl('interfacing_crud_explorer_operations', '/interfacing/crud/explorer/operations.json'), 'crud', null, 30),
                new InterfaceShellNavItem('crud.screens.json', 'Screens JSON', $this->urlResolver->safeUrl('interfacing_crud_explorer_screens', '/interfacing/crud/explorer/screens.json'), 'crud', null, 40),
            ]),
            new InterfaceShellNavGroup('shell-guard', 'Shell guard', [
                new InterfaceShellNavItem('shell.diagnostics', 'Panel diagnostics', $this->urlResolver->safeUrl('interfacing_shell_diagnostics', '/interfacing/shell/diagnostics'), 'shell', null, 10),
                new InterfaceShellNavItem('shell.diagnostics.json', 'Diagnostics JSON', $this->urlResolver->safeUrl('interfacing_shell_diagnostics_json', '/interfacing/shell/diagnostics.json'), 'shell', null, 20),
                new InterfaceShellNavItem('shell.navigation', 'Navigation map', $this->urlResolver->safeUrl('interfacing_shell_navigation', '/interfacing/shell/navigation'), 'shell', null, 30),
                new InterfaceShellNavItem('shell.navigation.json', 'Navigation JSON', $this->urlResolver->safeUrl('interfacing_shell_navigation_json', '/interfacing/shell/navigation.json'), 'shell', null, 40),
                new InterfaceShellNavItem('shell.screens', 'Screen catalog', $this->urlResolver->safeUrl('interfacing_shell_screen_catalog', '/interfacing/shell/screens'), 'shell', null, 50),
                new InterfaceShellNavItem('shell.screens.json', 'Screen catalog JSON', $this->urlResolver->safeUrl('interfacing_shell_screen_catalog_json', '/interfacing/shell/screens.json'), 'shell', null, 60),
                new InterfaceShellNavItem('shell.layout.preview', 'Layout preview', $this->urlResolver->safeUrl('interfacing_shell_layout_preview', '/interfacing/shell/layout-preview'), 'shell', null, 70),
                new InterfaceShellNavItem('shell.layout.preview.json', 'Layout preview JSON', $this->urlResolver->safeUrl('interfacing_shell_layout_preview_json', '/interfacing/shell/layout-preview.json'), 'shell', null, 80),
            ]),
            new InterfaceShellNavGroup('quick-crud', 'Quick CRUD', array_slice($this->crudSectionItems(), 0, 12)),
        ];
    }

    /** @return list<InterfaceShellNavItem> */
    private function crudSectionItems(): array
    {
        $items = [
            new InterfaceShellNavItem('crud.explorer', 'CRUD explorer', $this->urlResolver->safeUrl('interfacing_crud_explorer', '/interfacing/crud/explorer'), 'crud', null, 10),
        ];

        $order = 20;
        foreach ($this->crudResourceExplorerProvider->provide() as $resource) {
            $items[] = new InterfaceShellNavItem(
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
