<?php

declare(strict_types=1);

namespace App\Service\Shipping;

use App\Accessing\Entity\AccessEntity;
use App\Addressing\EntityInterface\Record\AddressInterface;
use App\Addressing\Service\Application\AddressReadService;
use App\Shipping\ServiceInterface\Shipping\ShipmentServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class ShipmentAddressService implements ShipmentServiceInterface
{
    public function __construct(
        private ShipmentServiceInterface $inner,
        private AddressReadService $addressReadService,
        private Security $security,
    ) {
    }

    public function create(string $tenantId, string $vendorId, string $orderId, array $payload): array
    {
        $originAddressId = $this->addressId($payload, 'originAddressId');
        $destinationAddressId = $this->addressId($payload, 'destinationAddressId');
        $ownerId = $this->ownerId();

        $origin = $this->addressReadService->get($originAddressId, null, $vendorId);
        if (!$origin instanceof AddressInterface) {
            throw new \DomainException('Shipment origin address is not available in the current vendor scope.');
        }

        $destination = $this->addressReadService->get($destinationAddressId, $ownerId, null);
        if (!$destination instanceof AddressInterface) {
            throw new \DomainException('Shipment destination address is not available in the current owner scope.');
        }

        $payload['originAddressId'] = $origin->id();
        $payload['destinationAddressId'] = $destination->id();
        $payload['from'] = $this->providerSnapshot($origin);
        $payload['to'] = $this->providerSnapshot($destination);
        $payload['_addressSnapshots'] = [
            'origin' => $this->immutableSnapshot($origin),
            'destination' => $this->immutableSnapshot($destination),
        ];

        return $this->inner->create($tenantId, $vendorId, $orderId, $payload);
    }

    public function get(string $shipmentId): array
    {
        return $this->inner->get($shipmentId);
    }

    public function dispatch(string $shipmentId): array
    {
        return $this->inner->dispatch($shipmentId);
    }

    public function createLabel(string $shipmentId): array
    {
        return $this->inner->createLabel($shipmentId);
    }

    public function voidLabel(string $shipmentId, string $reason): array
    {
        return $this->inner->voidLabel($shipmentId, $reason);
    }

    public function markDelivered(string $shipmentId, array $payload): array
    {
        return $this->inner->markDelivered($shipmentId, $payload);
    }

    public function markFailed(string $shipmentId, string $reason): array
    {
        return $this->inner->markFailed($shipmentId, $reason);
    }

    public function createReturnLabel(string $shipmentId, array $payload): array
    {
        return $this->inner->createReturnLabel($shipmentId, $payload);
    }

    public function track(string $shipmentId): array
    {
        return $this->inner->track($shipmentId);
    }

    public function customerStatus(string $shipmentId): array
    {
        return $this->inner->customerStatus($shipmentId);
    }

    public function publicTracking(string $trackingNumber): array
    {
        return $this->inner->publicTracking($trackingNumber);
    }

    public function addSupportNote(string $shipmentId, array $payload): array
    {
        return $this->inner->addSupportNote($shipmentId, $payload);
    }

    public function escalateSupport(string $shipmentId, array $payload): array
    {
        return $this->inner->escalateSupport($shipmentId, $payload);
    }

    public function resendNotification(string $shipmentId, string $channel, string $kind): array
    {
        return $this->inner->resendNotification($shipmentId, $channel, $kind);
    }

    /** @param array<string, mixed> $payload */
    private function addressId(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;
        if (!is_string($value)) {
            throw new \DomainException(sprintf('Shipment %s is required.', $key));
        }

        $value = strtoupper(trim($value));
        if (1 !== preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value)) {
            throw new \DomainException(sprintf('Shipment %s is invalid.', $key));
        }

        return $value;
    }

    private function ownerId(): string
    {
        $user = $this->security->getUser();
        if (!$user instanceof AccessEntity) {
            throw new \DomainException('Shipment destination address requires an authenticated owner.');
        }

        return $user->getObjectUuid();
    }

    /** @return array<string, mixed> */
    private function providerSnapshot(AddressInterface $address): array
    {
        return [
            'line1' => $address->line1(),
            'line2' => $address->line2(),
            'city' => $address->city(),
            'state' => $address->region(),
            'postalCode' => $address->postalCode(),
            'country' => strtoupper($address->countryCode()),
        ];
    }

    /** @return array<string, mixed> */
    private function immutableSnapshot(AddressInterface $address): array
    {
        return $this->providerSnapshot($address) + [
            'addressId' => $address->id(),
            'validationStatus' => $address->validationStatus(),
            'validationProvider' => $address->validationProvider(),
            'validatedAt' => $address->validatedAt(),
            'latitude' => $address->latitude(),
            'longitude' => $address->longitude(),
            'geohash' => $address->geohash(),
        ];
    }
}
