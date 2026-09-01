<?php

declare(strict_types=1);

namespace App\Service\Placement;

use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Provider\Crud\CrudPageDefinitionProvider;
use App\Cruding\Service\Crud\Resource\CrudResourceContractFactory;
use App\Cruding\Value\Resource\CrudResourceContract;
use App\Dto\Placement\FulfillmentPlacementFormData;
use App\Form\Placement\FulfillmentPlacementType;
use App\Retailing\Entity\Retail\RetailEntity;
use App\Shipping\Service\Placement\ListingFulfillmentProfileNormalizer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class FulfillmentNewService
{
    private const SESSION_KEY = 'retail_placement';

    public function __construct(
        private FormFactoryInterface $formFactory,
        private CrudPageDefinitionProvider $pageDefinitionProvider,
        private CrudResourceContractFactory $contractFactory,
        private ListingFulfillmentProfileNormalizer $profileNormalizer,
        private ManagerRegistry $managerRegistry,
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

        $data = new FulfillmentPlacementFormData();
        $existing = $retail->getFulfillmentProfile() ?? [];
        $data->mode = is_string($existing['mode'] ?? null) ? $existing['mode'] : ('goods' === $retail->getKind()->value ? 'shipping' : 'onsite');
        $data->serviceArea = is_string($existing['serviceArea'] ?? null) ? $existing['serviceArea'] : null;
        $data->radiusKm = is_numeric($existing['radiusKm'] ?? null) ? (float) $existing['radiusKm'] : null;
        $data->weightKg = is_numeric($existing['weightKg'] ?? null) ? (float) $existing['weightKg'] : null;
        $data->priority = is_string($existing['priority'] ?? null) ? $existing['priority'] : ('goods' === $retail->getKind()->value ? 'STANDARD' : null);

        $form = $this->formFactory->create(FulfillmentPlacementType::class, $data);
        $form->handleRequest($context->request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $profile = $this->profileNormalizer->normalize($retail->getKind()->value, $data->toArray());
                $retail->setFulfillmentProfile($profile);
                $manager = $this->managerRegistry->getManagerForClass(RetailEntity::class) ?? $this->managerRegistry->getManager();
                $manager->persist($retail);
                $manager->flush();
                $placement['fulfillmentConfigured'] = true;
                $context->request->getSession()->set(self::SESSION_KEY, $placement);

                return $this->redirect($this->requiresExactAddress($retail, $profile) ? 'address/new' : 'pricing/new');
            } catch (\InvalidArgumentException $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }

        $formView = $form->createView();

        return $this->contractFactory->create(
            $this->pageDefinitionProvider->provideNew($context->crudContext, $data, $formView),
            $data,
            $formView,
        );
    }

    /** @return array{retailId: string, vendorId: string}|null */
    private function placement(CrudServiceContext $context): ?array
    {
        if (!$context->request->hasSession()) {
            return null;
        }
        $placement = $context->request->getSession()->get(self::SESSION_KEY);
        if (!is_array($placement)) {
            return null;
        }
        foreach (['retailId', 'vendorId'] as $key) {
            if (!isset($placement[$key]) || !is_scalar($placement[$key]) || '' === trim((string) $placement[$key])) {
                return null;
            }
        }

        return ['retailId' => trim((string) $placement['retailId']), 'vendorId' => trim((string) $placement['vendorId'])];
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

    /** @param array<string, mixed> $profile */
    private function requiresExactAddress(RetailEntity $retail, array $profile): bool
    {
        $mode = is_string($profile['mode'] ?? null) ? $profile['mode'] : '';

        return match ($retail->getKind()->value) {
            'goods' => in_array($mode, ['shipping', 'pickup'], true),
            'task' => in_array($mode, ['onsite', 'hybrid'], true),
            default => false,
        };
    }

    private function redirect(string $crudPath): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('cruding_tokenized_catch_all', ['crudPath' => $crudPath]));
    }
}
