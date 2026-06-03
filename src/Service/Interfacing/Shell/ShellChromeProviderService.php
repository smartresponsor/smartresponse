<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Shell;

use App\Interfacing\Contract\ValueObject\ShellSlot;
use App\Interfacing\ServiceInterface\Shell\ShellChromeProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class ShellChromeProviderService implements ShellChromeProviderInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ShellTopLinkRegistryService $topLinkRegistry,
        private readonly ShellFooterRegistryService $footerRegistry,
        private readonly ShellQuickMenuRegistryService $quickMenuRegistry,
        private readonly ShellRightPanelRegistryService $rightPanelRegistry,
        private readonly ShellApplicationDashboardService $applicationDashboardService,
        #[Autowire(service: 'cache.app.recorder_inner')]
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @param bool $includeResourceSummaries    include known CRUD resources and related summaries
     * @param bool $includeApplicationDashboard include the heavy application dashboard payload
     */
    public function provide(?string $activeId = null, bool $includeResourceSummaries = false, bool $includeApplicationDashboard = false): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $query = null !== $request ? (string) $request->query->get('q', '') : '';

        $staticChrome = $this->cache->get('interfacing.shell.chrome.static.v14', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            return [
                'topLink' => $this->topLinkRegistry->provide(),
                'footerGroup' => $this->footerRegistry->provide(),
                'quickMenuGroup' => $this->quickMenuRegistry->provide(),
                'rightPanelGroup' => $this->rightPanelRegistry->provide(),
                'rightPanelEnabled' => true,
                'slotMap' => ShellSlot::labelMap(),
            ];
        });

        $shell = $staticChrome + [
            'activeId' => $activeId,
            'query' => $query,
            'navigation' => [],
        ];

        if ($includeResourceSummaries) {
            $shell['knownCrudResources'] = $this->applicationDashboardService->knownCrudResources();
        }

        if ($includeApplicationDashboard) {
            $shell['applicationDashboard'] = $this->applicationDashboardService->applicationDashboard();
        }

        return $shell;
    }
}
