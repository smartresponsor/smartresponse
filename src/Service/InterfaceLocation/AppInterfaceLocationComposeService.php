<?php

declare(strict_types=1);

namespace App\Service\InterfaceLocation;

use App\Navigating\ServiceInterface\Navigation\Provide\NavigationTemplateDataProvideServiceInterface;
use App\Service\InterfaceLocation\AppInterfaceLocationComposeServiceInterface;
use App\Viewing\ServiceInterface\View\ViewInterfaceLocationComposeServiceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Host application composition boundary for neutral Interfacing locations.
 *
 * App may compose UI projections from installed runtime components, but the
 * components stay decoupled from each other: Viewing does not know Navigating,
 * Interfacing does not know Navigating, and Navigating does not know Viewing.
 */
final readonly class AppInterfaceLocationComposeService implements AppInterfaceLocationComposeServiceInterface, ViewInterfaceLocationComposeServiceInterface
{
    public function __construct(
        private ?NavigationTemplateDataProvideServiceInterface $navigationTemplateDataProvider = null,
    ) {
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function composeLocations(Request $request): array
    {
        $locations = [];

        if (null !== $this->navigationTemplateDataProvider) {
            $navigationData = $this->navigationTemplateDataProvider->provide($request);
            $locations = $this->mergeLocations(
                $locations,
                $this->extractLocations($navigationData['interface']['locations'] ?? [])
            );
        }

        $locations = $this->hydrateLocaleNavigationItems($locations, $request);

        return $locations;
    }

    /**
     * @param array<string, list<array<string, mixed>>> $locations
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function hydrateLocaleNavigationItems(array $locations, Request $request): array
    {
        $currentLocale = strtolower(trim($request->getLocale()));

        foreach ($locations['shell.footer.main'] ?? [] as $index => $item) {
            if (!\is_array($item)) {
                continue;
            }

            $metadata = $item['metadata'] ?? [];
            if (!\is_array($metadata)) {
                continue;
            }

            $localeCode = strtolower(trim((string) ($metadata['locale_code'] ?? '')));
            if ('' === $localeCode || true !== ($metadata['locale_featured'] ?? false)) {
                continue;
            }

            $href = $this->createLocaleHref($request, $localeCode);
            $item['href'] = $href;
            $item['url'] = $href;
            $item['active'] = $localeCode === $currentLocale;
            $locations['shell.footer.main'][$index] = $item;
        }

        return $locations;
    }

    private function createLocaleHref(Request $request, string $localeCode): string
    {
        $query = $request->query->all();
        $query['locale'] = $localeCode;

        return $request->getPathInfo().'?'.http_build_query($query);
    }

    /**
     * Only populated component projections cross the App composition boundary.
     * Empty canonical buckets stay out so they cannot erase page-owned locations.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function extractLocations(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $locations = [];
        foreach ($value as $location => $blocks) {
            if (!\is_string($location) || '' === $location || !\is_array($blocks)) {
                continue;
            }

            $normalizedBlocks = [];
            foreach ($blocks as $block) {
                if (!\is_array($block)) {
                    continue;
                }

                /** @var array<string, mixed> $normalizedBlock */
                $normalizedBlock = $block;
                $normalizedBlocks[] = $normalizedBlock;
            }

            if ([] === $normalizedBlocks) {
                continue;
            }

            $locations[$location] = $normalizedBlocks;
        }

        return $locations;
    }

    /**
     * @param array<string, list<array<string, mixed>>> $left
     * @param array<string, list<array<string, mixed>>> $right
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function mergeLocations(array $left, array $right): array
    {
        foreach ($right as $location => $blocks) {
            $left[$location] = array_values(array_merge($left[$location] ?? [], $blocks));
        }

        return $left;
    }
}
