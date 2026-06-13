<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 */

namespace App\Service\Dashboard;

use App\Contract\Ui\AppDashboardContract;
use App\Dto\Dashboard\AppDashboardPayload;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds the App-owned dashboard composition contract.
 *
 * The host application exposes dashboard composition as data. Viewing owns the
 * render decision boundary, while Interfacing provides the shell and templates
 * that the decision layer selects from.
 */
final class AppDashboardBuilderService implements AppDashboardContract
{
    public function __construct(
        private readonly object $interfaceLocationProjectionProvider,
    ) {
    }

    public function buildDashboard(Request $request): AppDashboardPayload
    {
        $locale = $request->query->get('contentLocale', $request->getLocale());

        $data = [
            'title' => 'Commerce Control Center',
            'component' => 'app-host',
            'integrationOwner' => 'app-host-contract',
            'renderingOwner' => 'interfacing',
            'primaryProvider' => 'ant-design-procomponents',
            'secondaryProvider' => 'primereact',
            'shellMode' => 'provider-page',
            'routeContext' => [
                'surfacePath' => 'dashboard',
                'resourcePath' => 'app-dashboard',
                'resourceLabel' => 'Commerce Control Center',
                'resourceCollectionLabel' => 'Application dashboard',
                'operation' => 'overview',
                'surface' => 'dashboard',
                'mode' => 'collection',
                'collectionHref' => '/',
            ],
            'columns' => [
                ['key' => 'title', 'label' => 'Workspace', 'type' => 'text', 'isCode' => false, 'isStatus' => false],
                ['key' => 'code', 'label' => 'Route', 'type' => 'text', 'isCode' => true, 'isStatus' => false],
                ['key' => 'owner', 'label' => 'Contract owner', 'type' => 'text', 'isCode' => false, 'isStatus' => false],
                ['key' => 'status', 'label' => 'Status', 'type' => 'text', 'isCode' => false, 'isStatus' => true],
                ['key' => 'locale', 'label' => 'Locale', 'type' => 'text', 'isCode' => false, 'isStatus' => false],
            ],
            'rows' => [
                [
                    'id' => 'dashboard-catalog',
                    'title' => 'Catalog workbench',
                    'code' => '/catalog/',
                    'owner' => 'Cataloging + Cruding contracts',
                    'status' => 'provider-ready',
                    'locale' => $locale,
                ],
                [
                    'id' => 'dashboard-vendor',
                    'title' => 'Vendor workbench',
                    'code' => '/vendor/',
                    'owner' => 'Vendoring + Cruding contracts',
                    'status' => 'provider-ready',
                    'locale' => $locale,
                ],
                [
                    'id' => 'dashboard-crud',
                    'title' => 'CRUD resource surface',
                    'code' => '/cruding/',
                    'owner' => 'Cruding resource contracts',
                    'status' => 'canonical-provider',
                    'locale' => $locale,
                ],
                [
                    'id' => 'dashboard-assets',
                    'title' => 'Interfacing shell assets',
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
                    'placeholder' => 'Search application dashboard',
                    'options' => [],
                ],
                [
                    'nameEntity' => 'status',
                    'label' => 'Status',
                    'type' => 'select',
                    'value' => $request->query->get('status'),
                    'placeholder' => 'Any status',
                    'options' => [
                        ['label' => 'Provider ready', 'value' => 'provider-ready'],
                        ['label' => 'Canonical provider', 'value' => 'canonical-provider'],
                        ['label' => 'Published', 'value' => 'published'],
                    ],
                ],
            ],
            'formFields' => [
                ['nameEntity' => 'title', 'label' => 'Dashboard item', 'type' => 'text', 'value' => null, 'placeholder' => null, 'helpText' => null, 'required' => false, 'validationState' => null, 'errorText' => null, 'options' => []],
                ['nameEntity' => 'status', 'label' => 'Status', 'type' => 'text', 'value' => null, 'placeholder' => null, 'helpText' => null, 'required' => false, 'validationState' => null, 'errorText' => null, 'options' => []],
            ],
            'formSections' => [],
            'headerActions' => [
                ['label' => 'Catalog', 'href' => '/catalog/', 'variant' => 'primary', 'operation' => 'index', 'enabled' => true, 'visibility' => 'visible'],
                ['label' => 'Vendors', 'href' => '/vendor/', 'variant' => 'default', 'operation' => 'index', 'enabled' => true, 'visibility' => 'visible'],
                ['label' => 'CRUD Explorer', 'href' => '/interfacing/crud/explorer', 'variant' => 'default', 'operation' => 'index', 'enabled' => true, 'visibility' => 'visible'],
            ],
            'paginationLabel' => 'App-owned dashboard sections rendered through the shared Interfacing shell',
            'interface' => $this->provideInterfacePayload($request),
        ];

        return new AppDashboardPayload($data);
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

    public function __invoke(Request $request): AppDashboardPayload
    {
        return $this->buildDashboard($request);
    }
}
