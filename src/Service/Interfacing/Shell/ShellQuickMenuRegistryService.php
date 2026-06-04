<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use App\Interfacing\Contract\View\InterfaceShellNavGroup;
use App\Interfacing\Contract\View\InterfaceShellNavItem;

final class ShellQuickMenuRegistryService
{
    public function __construct(
        private readonly ShellChromeUrlResolverService $urlResolver,
    ) {
    }

    /** @return list<InterfaceShellNavGroup> */
    public function provide(): array
    {
        return [
            new InterfaceShellNavGroup('account-quick', 'My account', [
                new InterfaceShellNavItem('quick.profile', 'My profile', '/profile/', 'account-quick', null, 10),
                new InterfaceShellNavItem('quick.security', 'My security', '/security/', 'account-quick', null, 20),
                new InterfaceShellNavItem('quick.notifications', 'Notifications', $this->urlResolver->screenUrl('message.notifications.inbox'), 'account-quick', null, 30),
                new InterfaceShellNavItem('quick.locale', 'Locale selector', $this->urlResolver->screenUrl('localizing.locale.selector'), 'account-quick', null, 40),
                new InterfaceShellNavItem('quick.switch-account', 'Switch account', $this->urlResolver->safeUrl('accessing_switch_account', '/switch-account'), 'account-quick', null, 50),
                new InterfaceShellNavItem('quick.sign-out', 'Sign out', $this->urlResolver->safeUrl('accessing_sign_out', '/sign-out'), 'account-quick', null, 60),
            ]),
            new InterfaceShellNavGroup('commerce-quick', 'My commerce', [
                new InterfaceShellNavItem('quick.cart', 'My cart', '/cart/', 'commerce-quick', null, 10),
                new InterfaceShellNavItem('quick.orders', 'My orders', '/manage/orders', 'commerce-quick', null, 20),
                new InterfaceShellNavItem('quick.subscription', 'My subscription', '/subscription/', 'commerce-quick', null, 30),
                new InterfaceShellNavItem('quick.payments', 'Payments', '/payment/', 'commerce-quick', null, 40),
            ]),
            new InterfaceShellNavGroup('system-quick', 'System shortcuts', [
                new InterfaceShellNavItem('quick.applications', 'Applications', $this->urlResolver->safeUrl('interfacing_application_dashboard', '/interfacing/applications'), 'system-quick', null, 10),
                new InterfaceShellNavItem('quick.components', 'Components', $this->urlResolver->safeUrl('interfacing_component_roadmap', '/interfacing/components'), 'system-quick', null, 20),
                new InterfaceShellNavItem('quick.crud', 'CRUD Explorer', $this->urlResolver->safeUrl('interfacing_crud_explorer', '/interfacing/crud/explorer'), 'system-quick', null, 30),
                new InterfaceShellNavItem('quick.shell', 'Shell Map', $this->urlResolver->safeUrl('interfacing_shell_navigation', '/interfacing/shell/navigation'), 'system-quick', null, 40),
            ]),
        ];
    }
}
