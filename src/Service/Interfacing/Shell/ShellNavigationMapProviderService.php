<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use App\Interfacing\Contract\View\InterfaceShellFooterGroup;
use App\Interfacing\Contract\View\InterfaceShellFooterLinkInterface;
use App\Interfacing\Contract\View\InterfaceShellNavGroup;
use App\Interfacing\Contract\View\InterfaceShellNavItem;
use App\Interfacing\ProviderInterface\Shell\InterfaceShellChromeProviderInterface;
use App\Interfacing\ProviderInterface\Shell\InterfaceShellNavigationMapProviderInterface;

final readonly class ShellNavigationMapProviderService implements InterfaceShellNavigationMapProviderInterface
{
    public function __construct(
        private InterfaceShellChromeProviderInterface $shellChromeProvider,
        private ShellQuickMenuRegistryService $quickMenuRegistry,
        private ShellRightPanelRegistryService $rightPanelRegistry,
    ) {
    }

    public function map(?string $activeId = null): array
    {
        $shell = $this->shellChromeProvider->provide($activeId, true, false);
        $topLinks = array_map([$this, 'navItemToArray'], $shell['topLink'] ?? []);
        $primaryGroups = array_map([$this, 'navGroupToArray'], $this->quickMenuRegistry->provide());
        $secondaryGroups = array_map([$this, 'navGroupToArray'], $this->rightPanelRegistry->provide());
        $footerGroups = array_map([$this, 'footerGroupToArray'], $shell['footerGroup'] ?? []);

        return [
            'schema' => 'smart-responsor.interfacing.shell-navigation-map.v1',
            'activeId' => $activeId,
            'summary' => [
                'topLinkCount' => count($topLinks),
                'primaryGroupCount' => count($primaryGroups),
                'secondaryGroupCount' => count($secondaryGroups),
                'footerGroupCount' => count($footerGroups),
                'knownCrudResourceCount' => count($shell['knownCrudResources'] ?? []),
            ],
            'locations' => [
                'shell.top' => $topLinks,
                'shell.left.primary' => $primaryGroups,
                'shell.left.section' => $secondaryGroups,
                'shell.footer' => $footerGroups,
            ],
            'panels' => [
                'top' => [
                    'label' => 'Top links',
                    'links' => $topLinks,
                    'groups' => [],
                ],
                'leftPrimary' => [
                    'label' => 'Primary left navigation',
                    'links' => [],
                    'groups' => $primaryGroups,
                ],
                'leftSecondary' => [
                    'label' => 'Secondary left navigation',
                    'links' => [],
                    'groups' => $secondaryGroups,
                ],
                'footer' => [
                    'label' => 'Footer',
                    'links' => [],
                    'groups' => $footerGroups,
                ],
            ],
        ];
    }

    private function navItemToArray(mixed $item): array
    {
        if (!$item instanceof InterfaceShellNavItem) {
            return [];
        }

        return [
            'id' => $item->id(),
            'title' => $item->title(),
            'url' => $item->url(),
            'group' => $item->group(),
            'icon' => $item->icon(),
            'order' => $item->order(),
        ];
    }

    private function navGroupToArray(mixed $group): array
    {
        if (!$group instanceof InterfaceShellNavGroup) {
            return [];
        }

        return [
            'id' => $group->id(),
            'title' => $group->title(),
            'links' => array_map([$this, 'navItemToArray'], $group->item()),
        ];
    }

    private function footerGroupToArray(mixed $group): array
    {
        if (!$group instanceof InterfaceShellFooterGroup) {
            return [];
        }

        return [
            'id' => $group->id(),
            'title' => $group->title(),
            'links' => array_map(
                static fn (InterfaceShellFooterLinkInterface $link): array => [
                    'title' => $link->title(),
                    'url' => $link->url(),
                ],
                $group->link(),
            ),
        ];
    }
}
