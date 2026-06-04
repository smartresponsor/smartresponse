<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 */

namespace App\Service\Dashboard;

use App\Contract\Ui\AppDashboardContract;
use App\Dto\Dashboard\AppDashboardPayload;
use App\Navigating\ServiceInterface\Navigation\NavigationRendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        private readonly NavigationRendererInterface $navigationRenderer,
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
                    'name' => 'q',
                    'label' => 'Search',
                    'type' => 'text',
                    'value' => $request->query->get('q'),
                    'placeholder' => 'Search application dashboard',
                    'options' => [],
                ],
                [
                    'name' => 'status',
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
                ['name' => 'title', 'label' => 'Dashboard item', 'type' => 'text', 'value' => null, 'placeholder' => null, 'helpText' => null, 'required' => false, 'validationState' => null, 'errorText' => null, 'options' => []],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'value' => null, 'placeholder' => null, 'helpText' => null, 'required' => false, 'validationState' => null, 'errorText' => null, 'options' => []],
            ],
            'formSections' => [],
            'headerActions' => [
                ['label' => 'Catalog', 'href' => '/catalog/', 'variant' => 'primary', 'operation' => 'index', 'enabled' => true, 'visibility' => 'visible'],
                ['label' => 'Vendors', 'href' => '/vendor/', 'variant' => 'default', 'operation' => 'index', 'enabled' => true, 'visibility' => 'visible'],
                ['label' => 'CRUD Explorer', 'href' => '/interfacing/crud/explorer', 'variant' => 'default', 'operation' => 'index', 'enabled' => true, 'visibility' => 'visible'],
            ],
            'paginationLabel' => 'App-owned dashboard sections rendered through the shared Interfacing shell',
        ];

        return new AppDashboardPayload($data);
    }

    public function __invoke(Request $request): Response
    {
        return $this->navigationRenderer->render(
            'dashboard',
            'index',
            $this->buildDashboard($request)->toArray(),
        );
    }
}
