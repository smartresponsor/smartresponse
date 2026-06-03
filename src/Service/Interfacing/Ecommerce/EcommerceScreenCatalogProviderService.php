<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Ecommerce;

use App\Interfacing\Contract\View\CrudResourceLinkSetInterface;
use App\Interfacing\Contract\View\EcommerceComponentSummary;
use App\Interfacing\Contract\View\EcommerceScreenEntry;
use App\Interfacing\ServiceInterface\Crud\CrudResourceExplorerProviderInterface;
use App\Interfacing\ServiceInterface\Ecommerce\EcommerceScreenCatalogProviderInterface;

final readonly class EcommerceScreenCatalogProviderService implements EcommerceScreenCatalogProviderInterface
{
    public function __construct(private CrudResourceExplorerProviderInterface $crudResourceExplorerProvider)
    {
    }

    public function provide(): array
    {
        static $cache = null;
        if (null !== $cache) {
            return $cache;
        }

        $entries = $this->platformEntries();

        foreach ($this->crudResourceExplorerProvider->provide() as $resource) {
            if (!$resource instanceof CrudResourceLinkSetInterface) {
                continue;
            }

            foreach ($this->crudOperationEntries($resource) as $entry) {
                $entries[] = $entry;
            }
        }

        usort(
            $entries,
            static fn (EcommerceScreenEntry $left, EcommerceScreenEntry $right): int => [
                self::zoneOrder($left->zone()),
                $left->zone(),
                $left->component(),
                self::operationOrder($left->operation()),
                $left->label(),
                $left->id(),
            ] <=> [
                self::zoneOrder($right->zone()),
                $right->zone(),
                $right->component(),
                self::operationOrder($right->operation()),
                $right->label(),
                $right->id(),
            ],
        );

        return $cache = $entries;
    }

    public function groupedByZone(): array
    {
        static $cache = null;
        if (null !== $cache) {
            return $cache;
        }

        $grouped = [];
        foreach ($this->provide() as $entry) {
            $grouped[$entry->zone()][] = $entry;
        }

        return $cache = $grouped;
    }

    public function statusCounts(): array
    {
        static $cache = null;
        if (null !== $cache) {
            return $cache;
        }

        $counts = [
            'connected' => 0,
            'canonical' => 0,
            'planned' => 0,
        ];

        foreach ($this->provide() as $entry) {
            $counts[$entry->status()] = ($counts[$entry->status()] ?? 0) + 1;
        }

        return $cache = $counts;
    }

    public function componentSummaryByZone(): array
    {
        static $cache = null;
        if (null !== $cache) {
            return $cache;
        }

        $summary = [];
        foreach ($this->provide() as $entry) {
            $key = $entry->zone().'|'.$entry->component();
            $summary[$key] ??= [
                'zone' => $entry->zone(),
                'component' => $entry->component(),
                'total' => 0,
                'connected' => 0,
                'canonical' => 0,
                'planned' => 0,
            ];

            ++$summary[$key]['total'];
            ++$summary[$key][$entry->status()];
        }

        $grouped = [];
        foreach ($summary as $row) {
            $grouped[$row['zone']][] = new EcommerceComponentSummary(
                component: $row['component'],
                zone: $row['zone'],
                total: $row['total'],
                connected: $row['connected'],
                canonical: $row['canonical'],
                planned: $row['planned'],
            );
        }

        foreach ($grouped as &$components) {
            usort(
                $components,
                static fn (EcommerceComponentSummary $left, EcommerceComponentSummary $right): int => [
                    self::zoneOrder($left->zone()),
                    $left->component(),
                ] <=> [
                    self::zoneOrder($right->zone()),
                    $right->component(),
                ],
            );
        }
        unset($components);

        uksort($grouped, static fn (string $left, string $right): int => self::zoneOrder($left) <=> self::zoneOrder($right));

        return $cache = $grouped;
    }

    /** @return list<EcommerceScreenEntry> */
    private function platformEntries(): array
    {
        return [
            new EcommerceScreenEntry('platform.workspace', 'Platform', 'Interfacing', 'Workspace dashboard', 'index', '/interfacing', 'connected', 'screen', 'Canonical shell dashboard; no business demo data.'),
            new EcommerceScreenEntry('platform.admin-launchpad', 'Platform', 'Interfacing', 'Admin launchpad', 'index', '/interfacing/launchpad', 'connected', 'screen', 'Fast operator entry point for e-commerce zones, CRUD operations and component status.'),
            new EcommerceScreenEntry('platform.screen-directory', 'Platform', 'Interfacing', 'Screen directory', 'index', '/interfacing/screens', 'connected', 'screen', 'Full screen/action directory grouped by e-commerce operating zone.'),
            new EcommerceScreenEntry('platform.operation-workbench', 'Platform', 'Interfacing', 'Operation workbench', 'index', '/interfacing/operations', 'connected', 'screen', 'admin-provider-like command surface generated from the canonical CRUD registry.'),
            new EcommerceScreenEntry('platform.admin-tables', 'Platform', 'Interfacing', 'Admin tables', 'index', '/interfacing/tables', 'connected', 'screen', 'Table-first admin surface for index-like CRUD browsing without Interfacing-owned business rows.'),
            new EcommerceScreenEntry('platform.crud-frames', 'Platform', 'Interfacing', 'CRUD form frames', 'index', '/interfacing/forms', 'connected', 'screen', 'Shell-native New/Edit/Delete operation frames without Interfacing-owned business records.'),
            new EcommerceScreenEntry('platform.crud-affordances', 'Platform', 'Interfacing', 'CRUD affordances', 'index', '/interfacing/affordances', 'connected', 'screen', 'Shell-native filter, search, sort, pagination and bulk-action affordances without Interfacing-owned records.'),
            new EcommerceScreenEntry('platform.crud-readiness', 'Platform', 'Interfacing', 'CRUD readiness', 'index', '/interfacing/readiness', 'connected', 'screen', 'Compact readiness checklist across table, form, operation and affordance surfaces.'),
            new EcommerceScreenEntry('platform.component-obligations', 'Platform', 'Interfacing', 'Component obligations', 'index', '/interfacing/obligations', 'connected', 'screen', 'Owning-component checklist for fixtures, contracts, runtime bridges and evidence without Interfacing-owned business rows.'),
            new EcommerceScreenEntry('platform.runtime-bridges', 'Platform', 'Interfacing', 'Runtime bridges', 'index', '/interfacing/bridges', 'connected', 'screen', 'Runtime bridge catalog for route/controller/query/command/policy/evidence handoff from owning components.'),
            new EcommerceScreenEntry('platform.promotion-gates', 'Platform', 'Interfacing', 'Promotion gates', 'index', '/interfacing/promotions', 'connected', 'screen', 'Promotion discipline for planned/canonical/connected component status changes without Interfacing-owned demo rows.'),
            new EcommerceScreenEntry('platform.evidence-registry', 'Platform', 'Interfacing', 'Evidence registry', 'index', '/interfacing/evidence', 'connected', 'screen', 'Evidence checklist for route/data/operation/policy/audit proof supplied by owning components.'),
            new EcommerceScreenEntry('platform.contract-registry', 'Platform', 'Interfacing', 'Contract registry', 'index', '/interfacing/contracts', 'connected', 'screen', 'Interface contract registry for screen, data, operation, policy and evidence obligations.'),
            new EcommerceScreenEntry('platform.field-schema-registry', 'Platform', 'Interfacing', 'Field schema registry', 'index', '/interfacing/schemas', 'connected', 'screen', 'Field, identifier, validation and relationship schema obligations for CRUD tables and forms.'),
            new EcommerceScreenEntry('platform.surface-audit', 'Platform', 'Interfacing', 'Surface audit', 'index', '/interfacing/surface', 'connected', 'screen', 'Transparency report for shell coverage, CRUD route grammar and data ownership boundaries.'),
            new EcommerceScreenEntry('platform.component-roadmap', 'Platform', 'Interfacing', 'Component roadmap', 'index', '/interfacing/components', 'connected', 'screen', 'Full Smart Response component roadmap and e-commerce screen obligations.'),
            new EcommerceScreenEntry('platform.crud-explorer', 'Platform', 'Interfacing', 'CRUD Explorer', 'index', '/interfacing/crud/explorer', 'connected', 'screen', 'Canonical index of CRUD route grammar across Smart Response.'),
            new EcommerceScreenEntry('platform.doctor', 'Platform', 'Interfacing', 'Doctor', 'index', '/interfacing/interfacing-doctor', 'connected', 'screen', 'Operator diagnostics surface.'),
            new EcommerceScreenEntry('platform.health', 'Platform', 'Interfacing', 'Health', 'index', '/interfacing/health', 'connected', 'screen', 'Health and smoke surface.'),
            new EcommerceScreenEntry('messaging.notifications.inbox', 'Messaging', 'Messaging', 'Notifications inbox', 'index', '/interfacing/message.notifications.inbox', 'connected', 'screen', 'Connected screen; records must come from Messaging, not Interfacing.'),
            new EcommerceScreenEntry('messaging.rooms.collection', 'Messaging', 'Messaging', 'Rooms collection', 'index', '/interfacing/message.rooms.collection', 'connected', 'screen', 'Connected screen; Interfacing owns only rendering contract.'),
            new EcommerceScreenEntry('messaging.search.results', 'Messaging', 'Messaging', 'Search results', 'index', '/interfacing/message.search.results', 'connected', 'screen', 'Connected screen; Interfacing owns only rendering contract.'),
            new EcommerceScreenEntry('billing.meter.screen', 'Billing and paying', 'Billing', 'Billing meter', 'index', '/interfacing/billing/meter', 'connected', 'screen', 'Connected workbench screen.'),
            new EcommerceScreenEntry('ordering.summary.screen', 'Ordering', 'Ordering', 'Order summary', 'index', '/interfacing/order/summary', 'connected', 'screen', 'Connected workbench screen.'),
        ];
    }

    /** @return list<EcommerceScreenEntry> */
    private function crudOperationEntries(CrudResourceLinkSetInterface $resource): array
    {
        $zone = $this->zoneForComponent($resource->component());
        $baseId = 'crud.'.$resource->id();
        $label = $resource->label();
        $component = $resource->component();
        $status = $resource->status();

        return [
            new EcommerceScreenEntry($baseId.'.index', $zone, $component, $label, 'index', $resource->indexUrl(), $status, 'crud', $resource->note()),
            new EcommerceScreenEntry($baseId.'.new', $zone, $component, $label, 'new', $resource->newUrl(), $status, 'crud', 'New form route follows the canonical CRUD bridge.'),
            new EcommerceScreenEntry($baseId.'.show', $zone, $component, $label, 'show', $resource->showSampleUrl(), $status, 'crud-sample', 'Sample identifier is intentional; real id/slug comes from the owning component.'),
            new EcommerceScreenEntry($baseId.'.edit', $zone, $component, $label, 'edit', $resource->editSampleUrl(), $status, 'crud-sample', 'Sample identifier is intentional; real id/slug comes from the owning component.'),
            new EcommerceScreenEntry($baseId.'.delete', $zone, $component, $label, 'delete', $resource->deleteSampleUrl(), $status, 'crud-sample', 'Sample identifier is intentional; real id/slug comes from the owning component.'),
        ];
    }

    private function zoneForComponent(string $component): string
    {
        return match ($component) {
            'Accessing' => 'Access',
            'Cataloging', 'Faceting', 'Tagging', 'Search', 'Indexing', 'Discovering' => 'Catalog and discovery',
            'Commercializing', 'Retailing' => 'Commercial and retail',
            'Ordering', 'Consuming' => 'Ordering',
            'Billing', 'Paying', 'Currencing', 'Exchanging', 'Subscripting', 'Commissioning' => 'Billing and paying',
            'Taxating', 'Complying', 'Governancing', 'Adjudicating', 'Evaluating', 'Facting' => 'Tax and governance',
            'Shipping', 'Addressing', 'Locating' => 'Fulfillment and location',
            'Messaging' => 'Messaging',
            'Documenting', 'Attaching' => 'Documents and attachments',
            'Bridging', 'Harvesting', 'Applicating', 'Runtiming', 'Rolling', 'Projecting', 'Analysing', 'Anchoring' => 'Platform operations',
            default => 'Supporting components',
        };
    }

    private static function zoneOrder(string $zone): int
    {
        return match ($zone) {
            'Platform' => 10,
            'Access' => 20,
            'Catalog and discovery' => 30,
            'Commercial and retail' => 40,
            'Ordering' => 50,
            'Billing and paying' => 60,
            'Tax and governance' => 70,
            'Fulfillment and location' => 80,
            'Messaging' => 90,
            'Documents and attachments' => 100,
            'Platform operations' => 110,
            default => 900,
        };
    }

    private static function operationOrder(string $operation): int
    {
        return match ($operation) {
            'index' => 10,
            'new' => 20,
            'show' => 30,
            'edit' => 40,
            'delete' => 50,
            default => 900,
        };
    }
}
