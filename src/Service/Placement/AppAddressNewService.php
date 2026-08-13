<?php

declare(strict_types=1);

namespace App\Service\Placement;

use App\Accessing\Entity\AccessEntity;
use App\Addressing\Http\Dto\AddressInputFactory;
use App\Addressing\Http\Dto\AddressManageDto;
use App\Addressing\Service\Application\AddressWriteService;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Provider\Crud\CrudPageDefinitionProvider;
use App\Cruding\Service\Crud\Resource\CrudResourceContractFactory;
use App\Cruding\Value\Resource\CrudResourceContract;
use App\Dto\Placement\AppAddressPlacementFormData;
use App\Form\Placement\AppAddressPlacementType;
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
            $origin = $this->addressInputFactory->fromManageDto($this->originDto($data, $placement['vendorId']), [
                'sourceSystem' => 'placement',
                'sourceType' => 'checkout',
                'sourceReference' => $placement['orderId'].':origin',
            ]);
            $destination = $this->addressInputFactory->fromManageDto($this->destinationDto($data, $user->getObjectUuid()), [
                'sourceSystem' => 'placement',
                'sourceType' => 'checkout',
                'sourceReference' => $placement['orderId'].':destination',
            ]);

            $this->addressWriteService->create($origin);
            $this->addressWriteService->create($destination);
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

    private function redirect(string $crudPath): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate(
            'cruding_tokenized_catch_all',
            ['crudPath' => $crudPath],
        ));
    }
}
