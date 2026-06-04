<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use App\Interfacing\Contract\View\InterfaceShellNavItem;

final class ShellTopLinkRegistryService
{
    public function __construct(
        private readonly ShellChromeUrlResolverService $urlResolver,
    ) {
    }

    /** @return list<InterfaceShellNavItem> */
    public function provide(): array
    {
        return [
            new InterfaceShellNavItem('workspace', 'Workspace', $this->urlResolver->safeUrl('interfacing_index', '/interfacing'), 'workspace', null, 10),
            new InterfaceShellNavItem('provider.catalog', 'Provider Catalog', '/catalog/', 'workspace', null, 11),
            new InterfaceShellNavItem('provider.vendor', 'Provider Vendors', '/vendor/', 'workspace', null, 12),
            new InterfaceShellNavItem('applications.dashboard', 'Applications', $this->urlResolver->safeUrl('interfacing_application_dashboard', '/interfacing/applications'), 'workspace', null, 15),
            new InterfaceShellNavItem('notifications', 'Notifications', $this->urlResolver->screenUrl('message.notifications.inbox'), 'workspace', null, 20),
            new InterfaceShellNavItem('admin.launchpad', 'Launchpad', $this->urlResolver->safeUrl('interfacing_admin_launchpad', '/interfacing/launchpad'), 'workspace', null, 28),
            new InterfaceShellNavItem('crud.explorer', 'CRUD Explorer', $this->urlResolver->safeUrl('interfacing_crud_explorer', '/interfacing/crud/explorer'), 'workspace', null, 30),
            new InterfaceShellNavItem('screen.directory', 'Screens', $this->urlResolver->safeUrl('interfacing_screen_directory', '/interfacing/screens'), 'workspace', null, 35),
            new InterfaceShellNavItem('shell.screens', 'Screen Catalog', $this->urlResolver->safeUrl('interfacing_shell_screen_catalog', '/interfacing/shell/screens'), 'workspace', null, 36),
            new InterfaceShellNavItem('shell.layout.preview', 'Layout Preview', $this->urlResolver->safeUrl('interfacing_shell_layout_preview', '/interfacing/shell/layout-preview'), 'workspace', null, 365),
            new InterfaceShellNavItem('operation.workbench', 'Operations', $this->urlResolver->safeUrl('interfacing_operation_workbench', '/interfacing/operations'), 'workspace', null, 37),
            new InterfaceShellNavItem('admin.tables', 'Tables', $this->urlResolver->safeUrl('interfacing_admin_tables', '/interfacing/tables'), 'workspace', null, 38),
            new InterfaceShellNavItem('crud.frames', 'Forms', $this->urlResolver->safeUrl('interfacing_crud_frames', '/interfacing/forms'), 'workspace', null, 385),
            new InterfaceShellNavItem('crud.affordances', 'Affordances', $this->urlResolver->safeUrl('interfacing_crud_affordances', '/interfacing/affordances'), 'workspace', null, 386),
            new InterfaceShellNavItem('crud.readiness', 'Readiness', $this->urlResolver->safeUrl('interfacing_crud_readiness', '/interfacing/readiness'), 'workspace', null, 387),
            new InterfaceShellNavItem('component.obligations', 'Obligations', $this->urlResolver->safeUrl('interfacing_component_obligations', '/interfacing/obligations'), 'workspace', null, 388),
            new InterfaceShellNavItem('runtime.bridges', 'Runtime bridges', $this->urlResolver->safeUrl('interfacing_runtime_bridges', '/interfacing/bridges'), 'workspace', null, 389),
            new InterfaceShellNavItem('promotion.gates', 'Promotion gates', $this->urlResolver->safeUrl('interfacing_promotion_gates', '/interfacing/promotions'), 'workspace', null, 390),
            new InterfaceShellNavItem('evidence.registry', 'Evidence', $this->urlResolver->safeUrl('interfacing_evidence_registry', '/interfacing/evidence'), 'workspace', null, 391),
            new InterfaceShellNavItem('contract.registry', 'Contracts', $this->urlResolver->safeUrl('interfacing_contract_registry', '/interfacing/contracts'), 'workspace', null, 392),
            new InterfaceShellNavItem('field.schema.registry', 'Schemas', $this->urlResolver->safeUrl('interfacing_field_schema_registry', '/interfacing/schemas'), 'workspace', null, 393),
            new InterfaceShellNavItem('shell.audit', 'Shell Audit', $this->urlResolver->safeUrl('interfacing_shell_audit', '/interfacing/surface'), 'workspace', null, 39),
            new InterfaceShellNavItem('shell.diagnostics', 'Shell Guard', $this->urlResolver->safeUrl('interfacing_shell_diagnostics', '/interfacing/shell/diagnostics'), 'workspace', null, 395),
            new InterfaceShellNavItem('shell.navigation', 'Shell Map', $this->urlResolver->safeUrl('interfacing_shell_navigation', '/interfacing/shell/navigation'), 'workspace', null, 396),
            new InterfaceShellNavItem('component.roadmap', 'Components', $this->urlResolver->safeUrl('interfacing_component_roadmap', '/interfacing/components'), 'workspace', null, 40),
            new InterfaceShellNavItem('ecommerce.matrix', 'E-commerce Matrix', '/interfacing#ecommerce-screen-matrix', 'workspace', null, 42),
            new InterfaceShellNavItem('help', 'Help', '#help', 'workspace', null, 50),
            new InterfaceShellNavItem('account', 'Account', '#account', 'workspace', null, 60),
        ];
    }
}
