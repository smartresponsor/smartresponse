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
use App\Locating\Model\AddressInput;
use App\Locating\Model\AddressPipelineResult;
use App\Locating\ServiceInterface\AddressPipelineInterface;
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

        $user = $this->security->getUser();
        if (!$user instanceof AccessEntity) {
            throw new \DomainException('Address placement requires an authenticated owner.');
        }

        $data = new AppAddressPlacementFormData();
        $form = $this->formFactory->create(AppAddressPlacementType::class, $data);
        $form->handleRequest($context->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $originValidation = $this->addressPipeline->process(new AddressInput('', [
                'street' => $data->originLine1,
                'city' => $data->originCity,
                'region' => $data->originRegion ?? '',
                'postalCode' => $data->originPostalCode ?? '',
                'countryCode' => $data->originCountryCode,
            ]));
            $destinationValidation = $this->addressPipeline->process(new AddressInput('', [
                'street' => $data->destinationLine1,
                'city' => $data->destinationCity,
                'region' => $data->destinationRegion ?? '',
                'postalCode' => $data->destinationPostalCode ?? '',
                'countryCode' => $data->destinationCountryCode,
            ]));
            if (AddressPipelineResult::STATUS_VERIFIED !== $originValidation->status() || AddressPipelineResult::STATUS_VERIFIED !== $destinationValidation->status()) {
                throw new \DomainException('Shipment placement requires verified origin and destination addresses.');
            }

            $origin = $this->addressInputFactory->fromManageDto($this->originDto($data, $placement['vendorId']), [
                'sourceSystem' => 'placement',
                'sourceType' => 'manual',
                'sourceReference' => $placement['orderId'].':origin',
            ]);
            $destination = $this->addressInputFactory->fromManageDto($this->destinationDto($data, $user->getObjectUuid()), [
                'sourceSystem' => 'placement',
                'sourceType' => 'manual',
                'sourceReference' => $placement['orderId'].':destination',
            ]);

            $this->addressWriteService->create($origin);
            $this->addressWriteService->create($destination);
            $this->addressValidatedApplierService->apply(
                $origin->id(),
                $this->validatedMessage($origin, $originValidation),
                null,
                $placement['vendorId'],
            );
            $this->addressValidatedApplierService->apply(
                $destination->id(),
                $this->validatedMessage($destination, $destinationValidation),
                $user->getObjectUuid(),
                null,
            );
            $placement['originAddressId'] = $origin->id();
            $placement['destinationAddressId'] = $destination->id();
            $context->request->getSession()->set(self::SESSION_KEY, $placement);

            return $this->redirect('shipment/new');
        }

        $formView = $form->createView();

        return $this->contractFactory->create(
            $this->pageDefinitionProvider->provideNew($context->crudContext, $data, $formView),
            $data,
            $formView,
        );
    }

    /** @return array{orderId: string, vendorId: string}|null */
    private function placement(CrudServiceContext $context): ?array
    {
        if (!$context->request->hasSession()) {
            return null;
        }

        $placement = $context->request->getSession()->get(self::SESSION_KEY);
        if (!is_array($placement)) {
            return null;
        }

        $orderId = $placement['orderId'] ?? null;
        $vendorId = $placement['vendorId'] ?? null;
        if (!is_scalar($orderId) || !is_scalar($vendorId)) {
            return null;
        }

        $orderId = trim((string) $orderId);
        $vendorId = trim((string) $vendorId);

        return '' !== $orderId && '' !== $vendorId ? ['orderId' => $orderId, 'vendorId' => $vendorId] : null;
    }

    private function originDto(AppAddressPlacementFormData $data, string $vendorId): AddressManageDto
    {
        $dto = new AddressManageDto();
        $dto->line1 = $data->originLine1;
        $dto->line2 = $data->originLine2;
        $dto->city = $data->originCity;
        $dto->region = $data->originRegion;
        $dto->postalCode = $data->originPostalCode;
        $dto->countryCode = $data->originCountryCode;
        $dto->vendorId = $vendorId;

        return $dto;
    }

    private function destinationDto(AppAddressPlacementFormData $data, string $ownerId): AddressManageDto
    {
        $dto = new AddressManageDto();
        $dto->line1 = $data->destinationLine1;
        $dto->line2 = $data->destinationLine2;
        $dto->city = $data->destinationCity;
        $dto->region = $data->destinationRegion;
        $dto->postalCode = $data->destinationPostalCode;
        $dto->countryCode = $data->destinationCountryCode;
        $dto->ownerId = $ownerId;

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
