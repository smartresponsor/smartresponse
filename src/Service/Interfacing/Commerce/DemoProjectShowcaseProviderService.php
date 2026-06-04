<?php

declare(strict_types=1);

namespace App\Service\Interfacing\Commerce;

use App\Interfacing\ProviderInterface\Commerce\InterfaceProjectShowcaseProviderInterface;

/**
 * Demo-backed storefront surface for Project routes.
 *
 * Projecting is a workspace/component capability. The customer-facing Project
 * page is a business storefront for intellectual products and project packages.
 * This provider keeps temporary project cards out of Twig until a real
 * Projecting/Cataloging data source owns these records.
 */
final readonly class DemoProjectShowcaseProviderService implements InterfaceProjectShowcaseProviderInterface
{
    public function provide(array $criteria = []): array
    {
        $query = isset($criteria['q']) && is_string($criteria['q']) ? trim($criteria['q']) : '';
        $section = isset($criteria['section']) && is_string($criteria['section']) ? trim($criteria['section']) : 'all';
        $cards = $this->filterCards($this->cards(), $query, $section);

        return [
            'id' => 'project.storefront',
            'title' => 'Projects',
            'eyebrow' => 'Intellectual product storefront',
            'summary' => 'A customer-facing project browsing page for knowledge products, implementation packages, and AI-assisted delivery bundles. Demo cards are provider-owned placeholders, not Twig hardcode.',
            'route' => '/project/',
            'canonicalRoutes' => ['/project/'],
            'query' => $query,
            'activeSection' => $section,
            'filters' => $this->filters(),
            'stats' => [
                ['label' => 'Projects', 'value' => (string) count($cards)],
                ['label' => 'Sections', 'value' => '4'],
                ['label' => 'Source', 'value' => 'Demo provider'],
            ],
            'heroActions' => [
                ['title' => 'Top projects', 'url' => '#top-projects', 'primary' => true],
                ['title' => 'Implementation packs', 'url' => '#implementation-projects', 'primary' => false],
            ],
            'sections' => $this->sections($cards),
            'cards' => $cards,
        ];
    }

    /** @return list<array{id:string,title:string,url:string}> */
    private function filters(): array
    {
        return [
            ['id' => 'all', 'title' => 'All projects', 'url' => '/project/'],
            ['id' => 'top', 'title' => 'Top projects', 'url' => '/project/?section=top'],
            ['id' => 'implementation', 'title' => 'Implementation packs', 'url' => '/project/?section=implementation'],
            ['id' => 'automation', 'title' => 'Automation projects', 'url' => '/project/?section=automation'],
            ['id' => 'strategy', 'title' => 'Strategy projects', 'url' => '/project/?section=strategy'],
        ];
    }

    /**
     * @param list<array<string, mixed>> $cards
     *
     * @return list<array<string, mixed>>
     */
    private function sections(array $cards): array
    {
        return array_values(array_filter([
            $this->section('top-projects', 'Top projects', 'High-value project packages prepared for customer-facing discovery.', $this->cardsByGroup($cards, 'top')),
            $this->section('implementation-projects', 'Implementation projects', 'Delivery-ready bundles for concrete commerce and platform work.', $this->cardsByGroup($cards, 'implementation')),
            $this->section('automation-projects', 'Automation projects', 'AI and workflow projects that behave like intellectual products.', $this->cardsByGroup($cards, 'automation')),
            $this->section('strategy-projects', 'Strategy projects', 'Advisory and planning packages for business and technical direction.', $this->cardsByGroup($cards, 'strategy')),
        ], static fn (array $section): bool => [] !== $section['cards']));
    }

    /**
     * @param list<array<string, mixed>> $cards
     *
     * @return list<array<string, mixed>>
     */
    private function cardsByGroup(array $cards, string $group): array
    {
        return array_values(array_filter($cards, static function (array $card) use ($group): bool {
            $groups = $card['merchandising'] ?? [];

            return is_array($groups) && in_array($group, $groups, true);
        }));
    }

    /**
     * @param list<array<string, mixed>> $cards
     *
     * @return list<array<string, mixed>>
     */
    private function filterCards(array $cards, string $query, string $section): array
    {
        return array_values(array_filter($cards, static function (array $card) use ($query, $section): bool {
            if ('all' !== $section) {
                $groups = $card['merchandising'] ?? [];
                if (!is_array($groups) || !in_array($section, $groups, true)) {
                    return false;
                }
            }

            if ('' === $query) {
                return true;
            }

            $haystack = strtolower(implode(' ', array_filter([
                $card['title'] ?? '',
                $card['eyebrow'] ?? '',
                $card['summary'] ?? '',
                $card['category'] ?? '',
                implode(' ', is_array($card['tags'] ?? null) ? $card['tags'] : []),
            ], static fn (mixed $value): bool => is_string($value) && '' !== $value)));

            return str_contains($haystack, strtolower($query));
        }));
    }

    /** @return array{id:string,title:string,summary:string,cards:list<array<string,mixed>>} */
    private function section(string $id, string $title, string $summary, array $cards): array
    {
        return ['id' => $id, 'title' => $title, 'summary' => $summary, 'cards' => $cards];
    }

    /** @return list<array<string, mixed>> */
    private function cards(): array
    {
        return [
            [
                'id' => 'project-commerce-launch',
                'title' => 'Commerce Launch Project',
                'eyebrow' => 'Top project',
                'category' => 'Commerce implementation',
                'summary' => 'A packaged project for launching catalog, product browsing, cart, order, payment, and shipment storefront flows.',
                'price' => '$2,400',
                'oldPrice' => null,
                'priceNote' => 'project package',
                'saving' => null,
                'status' => 'Top pick',
                'rating' => '4.9',
                'inventory' => 'Discovery slots',
                'accent' => 'Commerce',
                'visual' => 'Launch pack',
                'href' => '#project-commerce-launch',
                'tags' => ['Catalog', 'Product', 'Checkout'],
                'merchandising' => ['top', 'implementation'],
            ],
            [
                'id' => 'project-ai-operator',
                'title' => 'AI Operator Workflow',
                'eyebrow' => 'Automation project',
                'category' => 'AI automation',
                'summary' => 'Project-like intellectual product for operator tools, policy checks, prompts, audit traces, and controlled execution.',
                'price' => '$3,800',
                'oldPrice' => '$4,600',
                'priceNote' => 'automation bundle',
                'saving' => 'Pilot discount',
                'status' => 'Pilot',
                'rating' => '4.8',
                'inventory' => 'Limited capacity',
                'accent' => 'AI',
                'visual' => 'AI workflow',
                'href' => '#project-ai-operator',
                'tags' => ['AI', 'Tools', 'Audit'],
                'merchandising' => ['top', 'automation'],
            ],
            [
                'id' => 'project-storefront-system',
                'title' => 'Storefront System Design',
                'eyebrow' => 'Strategy project',
                'category' => 'Architecture',
                'summary' => 'A structured project for turning business surfaces into reusable Symfony-oriented screens and provider-backed templates.',
                'price' => '$1,250',
                'oldPrice' => null,
                'priceNote' => 'design package',
                'saving' => null,
                'status' => 'Available',
                'rating' => '4.7',
                'inventory' => 'Ready',
                'accent' => 'Strategy',
                'visual' => 'Design pack',
                'href' => '#project-storefront-system',
                'tags' => ['Symfony', 'UI', 'Storefront'],
                'merchandising' => ['strategy', 'implementation'],
            ],
            [
                'id' => 'project-responsoring-engine',
                'title' => 'Smart Responseing Engine',
                'eyebrow' => 'Intellectual product',
                'category' => 'Smart Responseing',
                'summary' => 'Template project for sponsor logic, offers, commissions, subscriptions, and marketplace-ready business flows.',
                'price' => '$5,200',
                'oldPrice' => null,
                'priceNote' => 'business system',
                'saving' => null,
                'status' => 'Concept',
                'rating' => '4.6',
                'inventory' => 'Template preview',
                'accent' => 'Business',
                'visual' => 'Engine',
                'href' => '#project-responsoring-engine',
                'tags' => ['Offers', 'Commissions', 'Subscriptions'],
                'merchandising' => ['top', 'strategy'],
            ],
            [
                'id' => 'project-integration-bridge',
                'title' => 'Integration Bridge Pack',
                'eyebrow' => 'Implementation project',
                'category' => 'Bridge integration',
                'summary' => 'Delivery package for connecting Accessing, Interfacing, provider surfaces, and storefront routes without leaking workspace internals.',
                'price' => '$2,950',
                'oldPrice' => '$3,400',
                'priceNote' => 'integration pack',
                'saving' => 'Save $450',
                'status' => 'Deal',
                'rating' => '4.8',
                'inventory' => '2 slots',
                'accent' => 'Bridge',
                'visual' => 'Bridge pack',
                'href' => '#project-integration-bridge',
                'tags' => ['Accessing', 'Interfacing', 'Bridge'],
                'merchandising' => ['implementation', 'automation'],
            ],
            [
                'id' => 'project-growth-playbook',
                'title' => 'Growth Playbook Project',
                'eyebrow' => 'Strategy project',
                'category' => 'Business playbook',
                'summary' => 'A customer-facing intellectual product for turning product, category, and project surfaces into commercial growth pages.',
                'price' => '$980',
                'oldPrice' => null,
                'priceNote' => 'playbook',
                'saving' => null,
                'status' => 'New',
                'rating' => '4.5',
                'inventory' => 'Available',
                'accent' => 'Growth',
                'visual' => 'Playbook',
                'href' => '#project-growth-playbook',
                'tags' => ['Growth', 'Content', 'Offer'],
                'merchandising' => ['strategy', 'automation'],
            ],
        ];
    }
}
