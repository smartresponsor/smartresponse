<?php

declare(strict_types=1);

namespace App\Service\Placement;

use App\Accessing\Entity\AccessEntity;
use App\Addressing\Contract\Message\AddressValidated;
use App\Addressing\EntityInterface\Record\AddressInterface;
use App\Addressing\Http\Dto\AddressInputFactory;
use App\Addressing\Http\Dto\AddressManageDto;
use App\Addressing\Service\Application\AddressValidatedApplierService;
use App\Addressing\Service\Application\AddressWriteService;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Provider\Crud\CrudPageDefinitionProvider;
use App\Cruding\Service\Crud\Resource\CrudResourceContractFactory;
use App\Cruding\Value\Resource\CrudResourceContract;
use App\Dto\Placement\AppAddressPlacementFormData;
use App\Form\Placement\AppAddressPlacementType;
use App\Locating\Model\Location\AddressInput;
use App\Locating\Model\Location\AddressPipelineResult;
use App\Locating\ServiceInterface\Address\Location\AddressPipelineInterface;
use App\Retailing\Entity\Retail\RetailEntity;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AppAddressNewService
{
    private const SESSION_KEY = 'retail_placement';

    public function __construct(
        private FormFactoryInterface $formFactory,
        private CrudPageDefinitionProvider $pageDefinitionProvider,
        private CrudResourceContractFactory $contractFactory,
        private AddressInputFactory $addressInputFactory,
        private AddressWriteService $addressWriteService,
        private AddressValidatedApplierService $addressValidatedApplierService,
        private AddressPipelineInterface $addressPipeline,
        private ManagerRegistry $managerRegistry,
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function get(CrudServiceContext $context): CrudResourceContract|RedirectResponse
    {
        return $this->handle($context);
    }

    public function post(CrudServiceContext $context): CrudResourceContract|RedirectResponse
    {
        return $this->handle($context);
    }

    private function handle(CrudServiceContext $context): CrudResourceContract|RedirectResponse
    {
        $placement = $this->placement($context);
        if (null === $placement) {
            return $this->redirect('retail/new');
        }

        $retail = $this->retail($placement['retailId'], $placement['vendorId']);
        if (null === $retail) {
            return $this->redirect('retail/new');
        }

        $user = $this->security->getUser();
        if (!$user instanceof AccessEntity) {
            throw new \DomainException('Address placement requires an authenticated owner.');
        }

        $data = new AppAddressPlacementFormData();
        $form = $this->formFactory->create(AppAddressPlacementType::class, $data);
        $form->handleRequest($context->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $validation = $this->addressPipeline->process(new AddressInput('', [
                'street' => $data->line1,
                'city' => $data->city,
                'region' => $data->region ?? '',
                'postalCode' => $data->postalCode ?? '',
                'countryCode' => $data->countryCode,
            ]));
            if (AddressPipelineResult::STATUS_VERIFIED !== $validation->status()) {
                throw new \DomainException('Listing placement requires a verified exact address.');
            }

            $role = $this->addressRole($retail);
            $ownerId = 'task' === $retail->getKind()->value ? $user->getObjectUuid() : null;
            $vendorId = 'goods' === $retail->getKind()->value ? $placement['vendorId'] : null;
            $record = $this->addressInputFactory->fromManageDto(
                $this->listingDto($data, $ownerId, $vendorId),
                [
                    'sourceSystem' => 'placement',
                    'sourceType' => 'manual',
                    'sourceReference' => $placement['placementReference'].':'.$role,
                ],
            );

            $this->addressWriteService->create($record);
            $this->addressValidatedApplierService->apply(
                $record->id(),
                $this->validatedMessage($record, $validation),
                $ownerId,
                $vendorId,
            );
            $retail->setLocationProfile([
                'version' => 1,
                'addressId' => $record->id(),
                'role' => $role,
            ]);
            $manager = $this->managerRegistry->getManagerForClass(RetailEntity::class) ?? $this->managerRegistry->getManager();
            $manager->persist($retail);
            $manager->flush();
            $placement['listingAddressId'] = $record->id();
            $placement['addressRole'] = $role;
            $context->request->getSession()->set(self::SESSION_KEY, $placement);

            return $this->redirect('pricing/new');
        }

        $formView = $form->createView();

        return $this->contractFactory->create(
            $this->pageDefinitionProvider->provideNew($context->crudContext, $data, $formView),
            $data,
            $formView,
        );
    }

    /** @return array{retailId: string, placementReference: string, vendorId: string}|null */
    private function placement(CrudServiceContext $context): ?array
    {
        if (!$context->request->hasSession()) {
            return null;
        }

        $placement = $context->request->getSession()->get(self::SESSION_KEY);
        if (!is_array($placement) || true !== ($placement['fulfillmentConfigured'] ?? false)) {
            return null;
        }

        $retailId = $placement['retailId'] ?? null;
        $vendorId = $placement['vendorId'] ?? null;
        $placementReference = $placement['placementReference'] ?? null;
        if (!is_scalar($retailId) || !is_scalar($vendorId) || !is_scalar($placementReference)) {
            return null;
        }

        $retailId = trim((string) $retailId);
        $vendorId = trim((string) $vendorId);
        $placementReference = trim((string) $placementReference);

        return '' !== $retailId && '' !== $vendorId && '' !== $placementReference
            ? ['retailId' => $retailId, 'placementReference' => $placementReference, 'vendorId' => $vendorId]
            : null;
    }

    private function retail(string $retailId, string $vendorId): ?RetailEntity
    {
        if (!ctype_digit($retailId)) {
            return null;
        }
        $manager = $this->managerRegistry->getManagerForClass(RetailEntity::class) ?? $this->managerRegistry->getManager();
        $retail = $manager->getRepository(RetailEntity::class)->find((int) $retailId);

        return $retail instanceof RetailEntity && $retail->getOwner() === $vendorId ? $retail : null;
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

    private function listingDto(
        AppAddressPlacementFormData $data,
        ?string $ownerId,
        ?string $vendorId,
    ): AddressManageDto {
        $dto = new AddressManageDto();
        $dto->line1 = $data->line1;
        $dto->line2 = $data->line2;
        $dto->city = $data->city;
        $dto->region = $data->region;
        $dto->postalCode = $data->postalCode;
        $dto->countryCode = $data->countryCode;
        $dto->ownerId = $ownerId;
        $dto->vendorId = $vendorId;

        return $dto;
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

    private function redirect(string $crudPath): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate(
            'cruding_tokenized_catch_all',
            ['crudPath' => $crudPath],
        ));
    }
}
