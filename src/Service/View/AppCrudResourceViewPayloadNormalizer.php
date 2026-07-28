<?php

declare(strict_types=1);

namespace App\Service\View;

use App\Cruding\Value\Resource\CrudResourceContract;
use App\Viewing\ServiceInterface\View\ViewPayloadNormalizerInterface;
use App\Viewing\Value\View\ViewPayload;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class AppCrudResourceViewPayloadNormalizer implements ViewPayloadNormalizerInterface
{
    public function __construct(
        private ViewPayloadNormalizerInterface $inner,
        private RequestStack $requestStack,
        private ?object $interfaceLocationProjectionProvider = null,
    ) {
    }

    public function supports(mixed $controllerResult): bool
    {
        return $controllerResult instanceof CrudResourceContract || $this->inner->supports($controllerResult);
    }

    public function normalize(mixed $controllerResult): ViewPayload
    {
        if (!$controllerResult instanceof CrudResourceContract) {
            return $this->inner->normalize($controllerResult);
        }

        $templateContext = $controllerResult->toTemplateContext();
        $fallbackData = $controllerResult->toFallbackData();
        $routeContext = $this->routeContextFrom($templateContext, $fallbackData);
        $meta = $this->metaFrom($templateContext, $fallbackData);
        $word = $this->stringFrom($templateContext['word'] ?? $fallbackData['word'] ?? null, 'crud');
        $view = $this->stringFrom($templateContext['view'] ?? $fallbackData['view'] ?? null, 'index');

        $locations = $this->mergeLocations(
            $this->locationsFrom($templateContext, $fallbackData),
            $this->navigatingLocations(),
        );
        $data = $this->withShellLocations($templateContext, $locations) + [
            'fallbackData' => $fallbackData,
            'routeContext' => $routeContext,
            'objectClass' => $controllerResult::class,
        ];

        return new ViewPayload(
            surface: $this->surfaceFromRouteContext($routeContext, $word),
            operation: $this->operationFromRouteContext($routeContext, $view),
            format: $this->stringFrom($templateContext['format'] ?? $fallbackData['format'] ?? null, 'auto'),
            intent: 'crud_resource',
            component: 'Cruding',
            locations: $locations,
            data: $data,
            meta: [
                'source' => 'app_cruding_view_bridge',
                'object_class' => $controllerResult::class,
            ] + $meta,
        );
    }

    /**
     * @param array<string, mixed> $templateContext
     * @param array<string, mixed> $fallbackData
     *
     * @return array<string, mixed>
     */
    private function routeContextFrom(array $templateContext, array $fallbackData): array
    {
        $templateWorkbench = \is_array($templateContext['workbench'] ?? null) ? $templateContext['workbench'] : [];
        $fallbackWorkbench = \is_array($fallbackData['workbench'] ?? null) ? $fallbackData['workbench'] : [];

        if (\is_array($templateWorkbench['routeContext'] ?? null)) {
            return $templateWorkbench['routeContext'];
        }

        if (\is_array($fallbackWorkbench['routeContext'] ?? null)) {
            return $fallbackWorkbench['routeContext'];
        }

        if (\is_array($templateContext['routeContext'] ?? null)) {
            return $templateContext['routeContext'];
        }

        return \is_array($fallbackData['routeContext'] ?? null) ? $fallbackData['routeContext'] : [];
    }

    /**
     * @param array<string, mixed> $templateContext
     * @param array<string, mixed> $fallbackData
     *
     * @return array<string, mixed>
     */
    private function locationsFrom(array $templateContext, array $fallbackData): array
    {
        if (\is_array($templateContext['interface'] ?? null) && \is_array($templateContext['interface']['locations'] ?? null)) {
            return $templateContext['interface']['locations'];
        }

        if (\is_array($fallbackData['interface'] ?? null) && \is_array($fallbackData['interface']['locations'] ?? null)) {
            return $fallbackData['interface']['locations'];
        }

        if (\is_array($templateContext['locations'] ?? null)) {
            return $templateContext['locations'];
        }

        return \is_array($fallbackData['locations'] ?? null) ? $fallbackData['locations'] : [];
    }

    /**
     * @param array<string, mixed>                      $templateContext
     * @param array<string, list<array<string, mixed>>> $locations
     *
     * @return array<string, mixed>
     */
    private function withShellLocations(array $templateContext, array $locations): array
    {
        $shell = \is_array($templateContext['shell'] ?? null) ? $templateContext['shell'] : [];
        $shell['locations'] = $locations;

        $interface = \is_array($templateContext['interface'] ?? null) ? $templateContext['interface'] : [];
        $interface['locations'] = $locations;

        return [
            'shell' => $shell,
            'interface' => $interface,
            'locations' => $locations,
        ] + $templateContext;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function navigatingLocations(): array
    {
        if (null === $this->interfaceLocationProjectionProvider) {
            return [];
        }

        if (!method_exists($this->interfaceLocationProjectionProvider, 'provideInterfacePayload')) {
            return [];
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return [];
        }

        $payload = $this->interfaceLocationProjectionProvider->provideInterfacePayload($request);
        if (!\is_array($payload)) {
            return [];
        }

        return $this->normalizeLocations($payload['locations'] ?? []);
    }

    /**
     * @param array<string, mixed>                      $left
     * @param array<string, list<array<string, mixed>>> $right
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function mergeLocations(array $left, array $right): array
    {
        $merged = $this->normalizeLocations($left);

        foreach ($right as $location => $blocks) {
            if ([] === $blocks) {
                continue;
            }

            $merged[$location] = array_values(array_merge($merged[$location] ?? [], $blocks));
        }

        return $merged;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function normalizeLocations(mixed $locations): array
    {
        if (!\is_array($locations)) {
            return [];
        }

        $normalized = [];
        foreach ($locations as $location => $blocks) {
            if (!\is_string($location) || '' === trim($location) || !\is_array($blocks)) {
                continue;
            }

            $normalizedBlocks = [];
            foreach ($blocks as $block) {
                if (\is_array($block)) {
                    $normalizedBlocks[] = $block;
                }
            }

            if ([] !== $normalizedBlocks) {
                $normalized[trim($location)] = $normalizedBlocks;
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $templateContext
     * @param array<string, mixed> $fallbackData
     *
     * @return array<string, mixed>
     */
    private function metaFrom(array $templateContext, array $fallbackData): array
    {
        if (\is_array($templateContext['meta'] ?? null)) {
            return $templateContext['meta'];
        }

        return \is_array($fallbackData['meta'] ?? null) ? $fallbackData['meta'] : [];
    }

    /**
     * @param array<string, mixed> $routeContext
     */
    private function surfaceFromRouteContext(array $routeContext, string $fallback): string
    {
        foreach (['viewPath', 'surfacePath', 'resourcePath', 'resource'] as $key) {
            if (\is_scalar($routeContext[$key] ?? null)) {
                $value = trim((string) $routeContext[$key]);
                if ('' !== $value) {
                    return $value;
                }
            }
        }

        return $fallback;
    }

    /**
     * @param array<string, mixed> $routeContext
     */
    private function operationFromRouteContext(array $routeContext, string $fallback): string
    {
        if (\is_scalar($routeContext['operation'] ?? null)) {
            $value = trim((string) $routeContext['operation']);
            if ('' !== $value) {
                return $value;
            }
        }

        return $fallback;
    }

    private function stringFrom(mixed $value, string $fallback): string
    {
        if (!\is_scalar($value)) {
            return $fallback;
        }

        $value = trim((string) $value);

        return '' !== $value ? $value : $fallback;
    }
}
