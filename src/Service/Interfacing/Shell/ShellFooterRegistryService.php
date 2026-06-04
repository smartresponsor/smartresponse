<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use App\Interfacing\Contract\View\InterfaceShellFooterGroup;
use App\Interfacing\Contract\View\InterfaceShellFooterLink;

final class ShellFooterRegistryService
{
    public function __construct(
        private readonly ShellChromeUrlResolverService $urlResolver,
    ) {
    }

    /** @return list<InterfaceShellFooterGroup> */
    public function provide(): array
    {
        return [
            new InterfaceShellFooterGroup('commerce-core', 'Commerce core', [
                new InterfaceShellFooterLink('Catalog', '/catalog/'),
                new InterfaceShellFooterLink('Search index', '/index-record/'),
                new InterfaceShellFooterLink('Cart', '/cart/'),
                new InterfaceShellFooterLink('Orders', '/manage/orders'),
                new InterfaceShellFooterLink('Payments', '/payment/'),
                new InterfaceShellFooterLink('Shipping', '/shipment/'),
                new InterfaceShellFooterLink('Taxation', '/taxation-api/'),
            ]),
            new InterfaceShellFooterGroup('commerce-finance', 'Commerce finance', [
                new InterfaceShellFooterLink('Currencies', '/currency/'),
                new InterfaceShellFooterLink('Money formats', '/money-format/'),
                new InterfaceShellFooterLink('Exchange rates', '/exchange-rate/'),
                new InterfaceShellFooterLink('Exchange quotes', '/exchange-quote/'),
                new InterfaceShellFooterLink('Subscriptions', '/subscription/'),
                new InterfaceShellFooterLink('Subscription plans', '/subscription-plan/'),
                new InterfaceShellFooterLink('Commission plans', '/commission-plan/'),
                new InterfaceShellFooterLink('Commission payouts', '/commission-payout/'),
            ]),
            new InterfaceShellFooterGroup('customer-account', 'Customer account', [
                new InterfaceShellFooterLink('My profile', '/profile/'),
                new InterfaceShellFooterLink('My security', '/security/'),
                new InterfaceShellFooterLink('My cart', '/cart/'),
                new InterfaceShellFooterLink('My orders', '/manage/orders'),
                new InterfaceShellFooterLink('My subscription', '/subscription/'),
                new InterfaceShellFooterLink('Notifications', $this->urlResolver->screenUrl('message.notifications.inbox')),
            ]),
            new InterfaceShellFooterGroup('application-indexes', 'Application indexes', [
                new InterfaceShellFooterLink('Applications', $this->urlResolver->safeUrl('interfacing_application_dashboard', '/interfacing/applications')),
                new InterfaceShellFooterLink('Components', $this->urlResolver->safeUrl('interfacing_component_roadmap', '/interfacing/components')),
                new InterfaceShellFooterLink('CRUD Explorer', $this->urlResolver->safeUrl('interfacing_crud_explorer', '/interfacing/crud/explorer')),
                new InterfaceShellFooterLink('Screen Directory', $this->urlResolver->safeUrl('interfacing_screen_directory', '/interfacing/screens')),
                new InterfaceShellFooterLink('Screen Catalog', $this->urlResolver->safeUrl('interfacing_shell_screen_catalog', '/interfacing/shell/screens')),
                new InterfaceShellFooterLink('Operations', $this->urlResolver->safeUrl('interfacing_operation_workbench', '/interfacing/operations')),
            ]),
            new InterfaceShellFooterGroup('system-links', 'System links', [
                new InterfaceShellFooterLink('Locale selector', $this->urlResolver->screenUrl('localizing.locale.selector')),
                new InterfaceShellFooterLink('Shell Guard', $this->urlResolver->safeUrl('interfacing_shell_diagnostics', '/interfacing/shell/diagnostics')),
                new InterfaceShellFooterLink('Shell Map', $this->urlResolver->safeUrl('interfacing_shell_navigation', '/interfacing/shell/navigation')),
                new InterfaceShellFooterLink('Layout Preview', $this->urlResolver->safeUrl('interfacing_shell_layout_preview', '/interfacing/shell/layout-preview')),
                new InterfaceShellFooterLink('Contracts', $this->urlResolver->safeUrl('interfacing_contract_registry', '/interfacing/contracts')),
                new InterfaceShellFooterLink('Schemas', $this->urlResolver->safeUrl('interfacing_field_schema_registry', '/interfacing/schemas')),
            ]),
            new InterfaceShellFooterGroup('support-policy', 'Support & policy', [
                new InterfaceShellFooterLink('Help', '#help'),
                new InterfaceShellFooterLink('Support', '#support'),
                new InterfaceShellFooterLink('Privacy', '#privacy'),
                new InterfaceShellFooterLink('Terms', '#terms'),
                new InterfaceShellFooterLink('Security policy', '#security'),
                new InterfaceShellFooterLink('Status', '#status'),
            ]),
        ];
    }
}
