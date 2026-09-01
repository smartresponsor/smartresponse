<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 */

namespace App\Service\Store;

use App\Dto\Store\AppStorePayload;
use App\ServiceInterface\Store\AppStoreBuilderServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Builds the App-owned store composition contract.
 *
 * The host application exposes store composition as data. Viewing owns the
 * render decision boundary, while Interfacing provides the shell and templates
 * that the decision layer selects from.
 */
#[AsController]
final class AppStoreBuilderService implements AppStoreBuilderServiceInterface
{
    public function __construct(
        #[Autowire(service: 'navigating.interface_location_projection_provider')]
        private readonly object $interfaceLocationProjectionProvider,
    ) {
    }

    public function buildStore(Request $request): AppStorePayload
    {
        $locale = $request->query->get('contentLocale', $request->getLocale());

        $data = [
            'title' => 'Smart Response Store',
            'component' => 'app-store',
            'integrationOwner' => 'app-host-contract',
            'renderingOwner' => 'interfacing',
            'primaryProvider' => 'ant-design-procomponents',
            'secondaryProvider' => 'primereact',
            'shellMode' => 'provider-page',
            'routeContext' => [
                'surfacePath' => 'store',
                'resourcePath' => 'store',
                'resourceLabel' => 'Smart Response Store',
                'resourceCollectionLabel' => 'Application store',
                'operation' => 'index',
                'surface' => 'store',
                'mode' => 'collection',
                'collectionHref' => '/',
            ],
            'columns' => [
                ['key' => 'title', 'label' => 'Store section', 'type' => 'text', 'isCode' => false, 'isStatus' => false],
                ['key' => 'code', 'label' => 'Route', 'type' => 'text', 'isCode' => true, 'isStatus' => false],
                ['key' => 'owner', 'label' => 'Contract owner', 'type' => 'text', 'isCode' => false, 'isStatus' => false],
                ['key' => 'status', 'label' => 'Status', 'type' => 'text', 'isCode' => false, 'isStatus' => true],
                ['key' => 'locale', 'label' => 'Locale', 'type' => 'text', 'isCode' => false, 'isStatus' => false],
            ],
            'rows' => [
                [
                    'id' => 'store-featured-catalog',
                    'title' => 'Featured catalog',
                    'code' => '/catalog/',
                    'owner' => 'Cataloging contracts',
                    'status' => 'store-ready',
                    'locale' => $locale,
                ],
                [
                    'id' => 'store-vendor-marketplace',
                    'title' => 'Vendor marketplace',
                    'code' => '/vendor/',
                    'owner' => 'Vendoring contracts',
                    'status' => 'store-ready',
                    'locale' => $locale,
                ],
                [
                    'id' => 'store-commerce-actions',
                    'title' => 'Commerce actions',
                    'code' => '/interfacing/crud/explorer',
                    'owner' => 'Cruding resource contracts',
                    'status' => 'provider-ready',
                    'locale' => $locale,
                ],
                [
                    'id' => 'store-interface-assets',
                    'title' => 'Store interface assets',
                    'code' => '/interfacing',
                    'owner' => 'App public publication + Interfacing source',
                    'status' => 'published',
                    'locale' => $locale,
                ],
            ],
            'filters' => [
                [
                    'nameEntity' => 'q',
                    'label' => 'Search',
                    'type' => 'text',
                    'value' => $request->query->get('q'),
                    'placeholder' => 'Search the store',
                    'options' => [],
                ],
                [
                    'nameEntity' => 'status',
                    'label' => 'Status',
                    'type' => 'select',
                    'value' => $request->query->get('status'),
                    'placeholder' => 'Any status',
                    'options' => [
                        ['label' => 'Store ready', 'value' => 'store-ready'],
                        ['label' => 'Provider ready', 'value' => 'provider-ready'],
                        ['label' => 'Published', 'value' => 'published'],
                    ],
                ],
            ],
            'formFields' => [
                ['nameEntity' => 'title', 'label' => 'Store item', 'type' => 'text', 'value' => null, 'placeholder' => null, 'helpText' => null, 'required' => false, 'validationState' => null, 'errorText' => null, 'options' => []],
                ['nameEntity' => 'status', 'label' => 'Status', 'type' => 'text', 'value' => null, 'placeholder' => null, 'helpText' => null, 'required' => false, 'validationState' => null, 'errorText' => null, 'options' => []],
            ],
            'formSections' => [],
            'headerActions' => [
                ['label' => 'Catalog', 'href' => '/catalog/', 'variant' => 'primary', 'operation' => 'index', 'enabled' => true, 'visibility' => 'visible'],
                ['label' => 'Vendors', 'href' => '/vendor/', 'variant' => 'default', 'operation' => 'index', 'enabled' => true, 'visibility' => 'visible'],
                ['label' => 'Store Explorer', 'href' => '/interfacing/crud/explorer', 'variant' => 'default', 'operation' => 'index', 'enabled' => true, 'visibility' => 'visible'],
            ],
            'paginationLabel' => 'App-owned store sections rendered through the shared Interfacing shell',
            'interface' => $this->provideInterfacePayload($request),
        ];

        return new AppStorePayload($data);
    }

    /**
     * @return array{locations: array<string, mixed>, active?: array<string, mixed>}
     */
    private function provideInterfacePayload(Request $request): array
    {
        if (!method_exists($this->interfaceLocationProjectionProvider, 'provideInterfacePayload')) {
            throw new \LogicException(sprintf('The service injected as navigating.interface_location_projection_provider must be App\\Navigating\\Service\\Navigation\\Provide\\NavigationInterfaceLocationProjectionProvider-compatible and expose provideInterfacePayload(Request): array; got %s.', $this->interfaceLocationProjectionProvider::class));
        }

        $payload = $this->interfaceLocationProjectionProvider->provideInterfacePayload($request);

        if (!\is_array($payload)) {
            throw new \LogicException('Navigation interface location projection provider must return an array payload.');
        }

        $locations = \is_array($payload['locations'] ?? null) ? $payload['locations'] : [];
        $active = \is_array($payload['active'] ?? null) ? $payload['active'] : [];

        return ['locations' => $locations, 'active' => $active];
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Request $request): array
    {
        $data = $this->buildStore($request)->toArray();
        $interface = \is_array($data['interface'] ?? null) ? $data['interface'] : ['locations' => []];
        unset($data['interface']);

        return [
            '_view' => [
                'surface' => 'store',
                'operation' => 'index',
                'intent' => 'home',
                'format' => 'auto',
                'component' => 'App',
            ],
            'interface' => $interface,
            'data' => $data,
            'meta' => ['title' => 'Smart Response Store'],
        ];
    }
}
