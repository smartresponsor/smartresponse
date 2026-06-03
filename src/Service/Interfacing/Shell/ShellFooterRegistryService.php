<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use App\Interfacing\Contract\View\ShellFooterGroup;
use App\Interfacing\Contract\View\ShellFooterLink;

final class ShellFooterRegistryService
{
    public function __construct(
        private readonly ShellChromeUrlResolverService $urlResolver,
    ) {
    }

    /** @return list<ShellFooterGroup> */
    public function provide(): array
    {
        return [
            new ShellFooterGroup('commerce-core', 'Commerce core', [
                new ShellFooterLink('Catalog', '/catalog/'),
                new ShellFooterLink('Search index', '/index-record/'),
                new ShellFooterLink('Cart', '/cart/'),
                new ShellFooterLink('Orders', '/manage/orders'),
                new ShellFooterLink('Payments', '/payment/'),
                new ShellFooterLink('Shipping', '/shipment/'),
                new ShellFooterLink('Taxation', '/taxation-api/'),
            ]),
            new ShellFooterGroup('commerce-finance', 'Commerce finance', [
                new ShellFooterLink('Currencies', '/currency/'),
                new ShellFooterLink('Money formats', '/money-format/'),
                new ShellFooterLink('Exchange rates', '/exchange-rate/'),
                new ShellFooterLink('Exchange quotes', '/exchange-quote/'),
                new ShellFooterLink('Subscriptions', '/subscription/'),
                new ShellFooterLink('Subscription plans', '/subscription-plan/'),
                new ShellFooterLink('Commission plans', '/commission-plan/'),
                new ShellFooterLink('Commission payouts', '/commission-payout/'),
            ]),
            new ShellFooterGroup('customer-account', 'Customer account', [
                new ShellFooterLink('My profile', '/profile/'),
                new ShellFooterLink('My security', '/security/'),
                new ShellFooterLink('My cart', '/cart/'),
                new ShellFooterLink('My orders', '/manage/orders'),
                new ShellFooterLink('My subscription', '/subscription/'),
                new ShellFooterLink('Notifications', $this->urlResolver->screenUrl('message.notifications.inbox')),
            ]),
            new ShellFooterGroup('application-indexes', 'Application indexes', [
                new ShellFooterLink('Applications', $this->urlResolver->safeUrl('interfacing_application_dashboard', '/interfacing/applications')),
                new ShellFooterLink('Components', $this->urlResolver->safeUrl('interfacing_component_roadmap', '/interfacing/components')),
                new ShellFooterLink('CRUD Explorer', $this->urlResolver->safeUrl('interfacing_crud_explorer', '/interfacing/crud/explorer')),
                new ShellFooterLink('Screen Directory', $this->urlResolver->safeUrl('interfacing_screen_directory', '/interfacing/screens')),
                new ShellFooterLink('Screen Catalog', $this->urlResolver->safeUrl('interfacing_shell_screen_catalog', '/interfacing/shell/screens')),
                new ShellFooterLink('Operations', $this->urlResolver->safeUrl('interfacing_operation_workbench', '/interfacing/operations')),
            ]),
            new ShellFooterGroup('system-links', 'System links', [
                new ShellFooterLink('Locale selector', $this->urlResolver->screenUrl('localizing.locale.selector')),
                new ShellFooterLink('Shell Guard', $this->urlResolver->safeUrl('interfacing_shell_diagnostics', '/interfacing/shell/diagnostics')),
                new ShellFooterLink('Shell Map', $this->urlResolver->safeUrl('interfacing_shell_navigation', '/interfacing/shell/navigation')),
                new ShellFooterLink('Layout Preview', $this->urlResolver->safeUrl('interfacing_shell_layout_preview', '/interfacing/shell/layout-preview')),
                new ShellFooterLink('Contracts', $this->urlResolver->safeUrl('interfacing_contract_registry', '/interfacing/contracts')),
                new ShellFooterLink('Schemas', $this->urlResolver->safeUrl('interfacing_field_schema_registry', '/interfacing/schemas')),
            ]),
            new ShellFooterGroup('support-policy', 'Support & policy', [
                new ShellFooterLink('Help', '#help'),
                new ShellFooterLink('Support', '#support'),
                new ShellFooterLink('Privacy', '#privacy'),
                new ShellFooterLink('Terms', '#terms'),
                new ShellFooterLink('Security policy', '#security'),
                new ShellFooterLink('Status', '#status'),
            ]),
        ];
    }
}
