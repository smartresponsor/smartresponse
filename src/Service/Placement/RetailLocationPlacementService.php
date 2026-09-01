<?php

declare(strict_types=1);

namespace App\Service\Placement;

use App\Addressing\Contract\Message\AddressValidated;
use App\Addressing\EntityInterface\Record\AddressInterface;
use App\Addressing\Http\Factory\AddressInputFactory;
use App\Addressing\Http\Dto\AddressManageDto;
use App\Addressing\Service\Application\AddressValidatedApplierService;
use App\Addressing\Service\Application\AddressWriteService;
use App\Locating\Model\Location\AddressInput;
use App\Locating\Model\Location\AddressPipelineResult;
use App\Locating\ServiceInterface\Address\Location\AddressPipelineInterface;
use App\Retailing\Entity\Retail\RetailEntity;
use Doctrine\Persistence\ManagerRegistry;

final readonly class RetailLocationPlacementService
{
    public function __construct(
        private AddressInputFactory $addressInputFactory,
        private AddressWriteService $addressWriteService,
        private AddressValidatedApplierService $addressValidatedApplierService,
        private AddressPipelineInterface $addressPipeline,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * @param array{line1:string,line2?:?string,city:string,region?:?string,postalCode?:?string,countryCode:string} $input
     *
     * @return array{addressId:string,role:string}
     */
    public function place(RetailEntity $retail, string $ownerUuid, string $vendorId, array $input): array
    {
        $role = $this->addressRole($retail);
        $validation = $this->addressPipeline->process(new AddressInput('', [
            'street' => $input['line1'],
            'city' => $input['city'],
            'region' => $input['region'] ?? '',
            'postalCode' => $input['postalCode'] ?? '',
            'countryCode' => $input['countryCode'],
        ]));
        if (AddressPipelineResult::STATUS_VERIFIED !== $validation->status()) {
            throw new \DomainException('Listing placement requires a verified exact address.');
        }

        $ownerScope = 'task' === $retail->getKind()->value ? $ownerUuid : null;
        $vendorScope = 'goods' === $retail->getKind()->value ? $vendorId : null;
        $dto = new AddressManageDto();
        $dto->line1 = trim($input['line1']);
        $dto->line2 = $this->nullable($input['line2'] ?? null);
        $dto->city = trim($input['city']);
        $dto->region = $this->nullable($input['region'] ?? null);
        $dto->postalCode = $this->nullable($input['postalCode'] ?? null);
        $dto->countryCode = strtoupper(trim($input['countryCode']));
        $dto->ownerId = $ownerScope;
        $dto->vendorId = $vendorScope;

        $record = $this->addressInputFactory->fromManageDto($dto, [
            'sourceSystem' => 'placement',
            'sourceType' => 'manual',
            'sourceReference' => 'retail:'.$retail->getId().':'.$role,
        ]);
        $this->addressWriteService->create($record);
        $this->addressValidatedApplierService->apply(
            $record->id(),
            $this->validatedMessage($record, $validation),
            $ownerScope,
            $vendorScope,
        );

        $retail->setLocationProfile(['version' => 1, 'addressId' => $record->id(), 'role' => $role]);
        $manager = $this->managerRegistry->getManagerForClass(RetailEntity::class) ?? $this->managerRegistry->getManager();
        $manager->persist($retail);
        $manager->flush();

        return ['addressId' => $record->id(), 'role' => $role];
    }

    private function addressRole(RetailEntity $retail): string
    {
        $mode = $retail->getFulfillmentProfile()['mode'] ?? null;

        return match ($retail->getKind()->value) {
            'goods' => match ($mode) {
                'shipping' => 'ship-from',
                'pickup' => 'pickup-base',
                default => throw new \DomainException('Selected goods fulfillment does not require an exact address.'),
            },
            'task' => in_array($mode, ['onsite', 'hybrid'], true)
                ? 'job-site'
                : throw new \DomainException('Selected task fulfillment does not require an exact address.'),
            default => throw new \DomainException('Selected Retail fulfillment does not require an exact address.'),
        };
    }

    private function validatedMessage(AddressInterface $record, AddressPipelineResult $result): AddressValidated
    {
        $view = $result->address();
        if (AddressPipelineResult::STATUS_VERIFIED !== $result->status() || null === $view) {
            throw new \LogicException('Only verified Locating results may be applied to Addressing.');
        }

        $line1Norm = strtolower($view->street());
        $cityNorm = strtolower($view->city());
        $regionNorm = '' === trim($view->region()) ? null : strtolower($view->region());
        $postalCodeNorm = '' === trim($view->postalCode()) ? null : strtolower(str_replace(' ', '', $view->postalCode()));
        $normalizedSnapshot = [
            'line1Norm' => $line1Norm,
            'cityNorm' => $cityNorm,
            'regionNorm' => $regionNorm,
            'postalCodeNorm' => $postalCodeNorm,
        ];
        $encoded = json_encode($normalizedSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return AddressValidated::fromArray([
            ...$normalizedSnapshot,
            'validationProvider' => 'locating-core',
            'validatedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'dedupeKey' => $record->dedupeKey(),
            'raw' => ['status' => $result->status(), 'issues' => $result->issues()],
            'sourceSystem' => 'locating',
            'sourceType' => $record->sourceType(),
            'sourceReference' => $record->sourceReference(),
            'normalizationVersion' => 'locating-core-v1',
            'rawInput' => $record->rawInputSnapshot(),
            'normalizedSnapshot' => $normalizedSnapshot,
            'providerDigest' => 'sha256:'.hash('sha256', false === $encoded ? '' : $encoded),
            'governanceStatus' => $record->governanceStatus(),
            'revalidationPolicy' => $record->revalidationPolicy(),
            'lastValidationProvider' => 'locating-core',
            'lastValidationStatus' => 'validated',
        ]);
    }

    private function nullable(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return '' === $value ? null : $value;
    }
}
