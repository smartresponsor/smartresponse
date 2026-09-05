<?php

declare(strict_types=1);

namespace App\Service\Placement\Retail;

use App\Accessing\Entity\AccessEntity;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Provider\Crud\CrudPageDefinitionProvider;
use App\Cruding\Service\Crud\Resource\CrudResourceContractFactory;
use App\Cruding\Value\Resource\CrudResourceContract;
use App\Dto\Placement\Retail\RetailPlacementAddressFormData;
use App\Form\Placement\Retail\RetailPlacementAddressType;
use App\Retailing\Entity\Retail\RetailEntity;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class RetailPlacementAddressNewService
{
    private const SESSION_KEY = 'retail_placement';

    public function __construct(
        private FormFactoryInterface $formFactory,
        private CrudPageDefinitionProvider $pageDefinitionProvider,
        private CrudResourceContractFactory $contractFactory,
        private RetailPlacementLocationService $locationPlacementService,
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

        $data = new RetailPlacementAddressFormData();
        $form = $this->formFactory->create(RetailPlacementAddressType::class, $data);
        $form->handleRequest($context->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->locationPlacementService->place(
                $retail,
                $user->getObjectUuid(),
                $placement['vendorId'],
                [
                    'line1' => $data->line1,
                    'line2' => $data->line2,
                    'city' => $data->city,
                    'region' => $data->region,
                    'postalCode' => $data->postalCode,
                    'countryCode' => $data->countryCode,
                ],
            );
            $placement['listingAddressId'] = $result['addressId'];
            $placement['addressRole'] = $result['role'];
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

    private function redirect(string $crudPath): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate(
            'cruding_tokenized_catch_all',
            ['crudPath' => $crudPath],
        ));
    }
}
