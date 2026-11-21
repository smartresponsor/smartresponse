<?php

declare(strict_types=1);

namespace App\Twig;

use App\Vendoring\ProviderInterface\Profile\VendorPublicProfileSummaryProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class VendorCurrentExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly VendorPublicProfileSummaryProviderInterface $vendorPublicProfileSummaryProvider,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_vendor_avatar_url', $this->currentVendorAvatarUrl(...)),
            new TwigFunction('current_vendor_name', $this->currentVendorName(...)),
        ];
    }

    public function currentVendorAvatarUrl(): ?string
    {
        return $this->currentVendorSummary()?->avatar->url;
    }

    public function currentVendorName(): ?string
    {
        $summary = $this->currentVendorSummary();
        if (null === $summary) {
            return null;
        }

        $personalName = trim(implode(' ', array_filter([
            $summary->firstTitle,
            $summary->lastTitle,
        ], static fn (?string $part): bool => null !== $part && '' !== trim($part))));

        if ('' !== $personalName) {
            return $personalName;
        }

        return '' !== trim($summary->publicName) ? trim($summary->publicName) : null;
    }

    private function currentVendorSummary(): ?\App\Vendoring\Projection\VendorPublicProfileSummary
    {
        $user = $this->security->getUser();
        if (!\is_object($user) || !method_exists($user, 'getId')) {
            return null;
        }

        $actorId = $user->getId();
        if (!\is_int($actorId) || $actorId <= 0) {
            return null;
        }

        return $this->vendorPublicProfileSummaryProvider->provideForCurrentActor($actorId);
    }
}
