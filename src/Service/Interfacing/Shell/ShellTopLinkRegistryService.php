<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use App\Interfacing\Contract\View\ShellNavItem;

final class ShellTopLinkRegistryService
{
    public function __construct(
        private readonly ShellChromeUrlResolverService $urlResolver,
    ) {
    }

    /** @return list<ShellNavItem> */
    public function provide(): array
    {
        return [
            new ShellNavItem('workspace', 'Workspace', $this->urlResolver->safeUrl('interfacing_index', '/interfacing'), 'workspace', null, 10),
            new ShellNavItem('provider.catalog', 'Provider Catalog', '/catalog/', 'workspace', null, 11),
            new ShellNavItem('provider.vendor', 'Provider Vendors', '/vendor/', 'workspace', null, 12),
            new ShellNavItem('applications.dashboard', 'Applications', $this->urlResolver->safeUrl('interfacing_application_dashboard', '/interfacing/applications'), 'workspace', null, 15),
            new ShellNavItem('notifications', 'Notifications', $this->urlResolver->screenUrl('message.notifications.inbox'), 'workspace', null, 20),
            new ShellNavItem('admin.launchpad', 'Launchpad', $this->urlResolver->safeUrl('interfacing_admin_launchpad', '/interfacing/launchpad'), 'workspace', null, 28),
            new ShellNavItem('crud.explorer', 'CRUD Explorer', $this->urlResolver->safeUrl('interfacing_crud_explorer', '/interfacing/crud/explorer'), 'workspace', null, 30),
            new ShellNavItem('screen.directory', 'Screens', $this->urlResolver->safeUrl('interfacing_screen_directory', '/interfacing/screens'), 'workspace', null, 35),
            new ShellNavItem('shell.screens', 'Screen Catalog', $this->urlResolver->safeUrl('interfacing_shell_screen_catalog', '/interfacing/shell/screens'), 'workspace', null, 36),
            new ShellNavItem('shell.layout.preview', 'Layout Preview', $this->urlResolver->safeUrl('interfacing_shell_layout_preview', '/interfacing/shell/layout-preview'), 'workspace', null, 365),
            new ShellNavItem('operation.workbench', 'Operations', $this->urlResolver->safeUrl('interfacing_operation_workbench', '/interfacing/operations'), 'workspace', null, 37),
            new ShellNavItem('admin.tables', 'Tables', $this->urlResolver->safeUrl('interfacing_admin_tables', '/interfacing/tables'), 'workspace', null, 38),
            new ShellNavItem('crud.frames', 'Forms', $this->urlResolver->safeUrl('interfacing_crud_frames', '/interfacing/forms'), 'workspace', null, 385),
            new ShellNavItem('crud.affordances', 'Affordances', $this->urlResolver->safeUrl('interfacing_crud_affordances', '/interfacing/affordances'), 'workspace', null, 386),
            new ShellNavItem('crud.readiness', 'Readiness', $this->urlResolver->safeUrl('interfacing_crud_readiness', '/interfacing/readiness'), 'workspace', null, 387),
            new ShellNavItem('component.obligations', 'Obligations', $this->urlResolver->safeUrl('interfacing_component_obligations', '/interfacing/obligations'), 'workspace', null, 388),
            new ShellNavItem('runtime.bridges', 'Runtime bridges', $this->urlResolver->safeUrl('interfacing_runtime_bridges', '/interfacing/bridges'), 'workspace', null, 389),
            new ShellNavItem('promotion.gates', 'Promotion gates', $this->urlResolver->safeUrl('interfacing_promotion_gates', '/interfacing/promotions'), 'workspace', null, 390),
            new ShellNavItem('evidence.registry', 'Evidence', $this->urlResolver->safeUrl('interfacing_evidence_registry', '/interfacing/evidence'), 'workspace', null, 391),
            new ShellNavItem('contract.registry', 'Contracts', $this->urlResolver->safeUrl('interfacing_contract_registry', '/interfacing/contracts'), 'workspace', null, 392),
            new ShellNavItem('field.schema.registry', 'Schemas', $this->urlResolver->safeUrl('interfacing_field_schema_registry', '/interfacing/schemas'), 'workspace', null, 393),
            new ShellNavItem('surface.audit', 'Surface Audit', $this->urlResolver->safeUrl('interfacing_surface_audit', '/interfacing/surface'), 'workspace', null, 39),
            new ShellNavItem('shell.diagnostics', 'Shell Guard', $this->urlResolver->safeUrl('interfacing_shell_diagnostics', '/interfacing/shell/diagnostics'), 'workspace', null, 395),
            new ShellNavItem('shell.navigation', 'Shell Map', $this->urlResolver->safeUrl('interfacing_shell_navigation', '/interfacing/shell/navigation'), 'workspace', null, 396),
            new ShellNavItem('component.roadmap', 'Components', $this->urlResolver->safeUrl('interfacing_component_roadmap', '/interfacing/components'), 'workspace', null, 40),
            new ShellNavItem('ecommerce.matrix', 'E-commerce Matrix', '/interfacing#ecommerce-screen-matrix', 'workspace', null, 42),
            new ShellNavItem('help', 'Help', '#help', 'workspace', null, 50),
            new ShellNavItem('account', 'Account', '#account', 'workspace', null, 60),
        ];
    }
}
