<?php

declare(strict_types=1);

namespace App\Service\InterfaceLocation;

use App\Navigating\ServiceInterface\Navigation\Provide\NavigationTemplateDataProvideServiceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Host application composition boundary for neutral Interfacing locations.
 *
 * App may compose UI projections from installed runtime components, but the
 * components stay decoupled from each other: Viewing does not know Navigating,
 * Interfacing does not know Navigating, and Navigating does not know Viewing.
 */
final readonly class AppInterfaceLocationComposeService
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

        return $locations;
    }

    /**
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
