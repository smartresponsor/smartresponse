<?php

declare(strict_types=1);

namespace App\Service\Twig;

use App\Vendoring\ServiceInterface\Profile\VendorPublicProfileSummaryProviderServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AppCurrentVendorTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly VendorPublicProfileSummaryProviderServiceInterface $vendorPublicProfileSummaryProvider,
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

    private function currentVendorSummary(): ?\App\Vendoring\Projection\Vendor\VendorPublicProfileSummary
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
