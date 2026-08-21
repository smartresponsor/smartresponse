<?php

declare(strict_types=1);

namespace App\Service\Placement;

use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Provider\Crud\CrudPageDefinitionProvider;
use App\Cruding\Service\Crud\Resource\CrudResourceContractFactory;
use App\Cruding\Value\Resource\CrudResourceContract;
use App\Dto\Placement\AppPricingPlacementFormData;
use App\Form\Placement\AppPricingPlacementType;
use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Service\Pricing\RetailPricingProfileNormalizer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AppPricingNewService
{
    private const SESSION_KEY = 'retail_placement';

    public function __construct(
        private FormFactoryInterface $formFactory,
        private CrudPageDefinitionProvider $pageDefinitionProvider,
        private CrudResourceContractFactory $contractFactory,
        private RetailPricingProfileNormalizer $profileNormalizer,
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
        if (null === $retail || null === $retail->getFulfillmentProfile()) {
            return $this->redirect('fulfillment/new');
        }
        if ($this->requiresExactAddress($retail) && null === $retail->getLocationProfile()) {
            return $this->redirect('address/new');
        }

        $data = new AppPricingPlacementFormData();
        $existing = $retail->getPricingProfile() ?? [];
        $data->model = is_string($existing['model'] ?? null) ? $existing['model'] : $this->defaultModel($retail);
        $data->amountMinor = is_numeric($existing['amountMinor'] ?? null) ? (int) $existing['amountMinor'] : $retail->getAmountMinor();
        $data->maximumAmountMinor = is_numeric($existing['maximumAmountMinor'] ?? null) ? (int) $existing['maximumAmountMinor'] : null;
        $data->currency = is_string($existing['currency'] ?? null) ? $existing['currency'] : $retail->getCurrency();

        $form = $this->formFactory->create(AppPricingPlacementType::class, $data);
        $form->handleRequest($context->request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $profile = $this->profileNormalizer->normalize($retail->getKind()->value, $data->toArray());
                $retail->setPricingProfile($profile);
                if (isset($profile['amountMinor']) && is_int($profile['amountMinor'])) {
                    $retail->setAmountMinor($profile['amountMinor']);
                }
                $retail->setCurrency((string) $profile['currency']);
                $manager = $this->managerRegistry->getManagerForClass(RetailEntity::class) ?? $this->managerRegistry->getManager();
                $manager->persist($retail);
                $manager->flush();
                $placement['pricingConfigured'] = true;
                $placement['amountMinor'] = $retail->getAmountMinor();
                $placement['currency'] = $retail->getCurrency();
                $context->request->getSession()->set(self::SESSION_KEY, $placement);

                return $this->redirect('retail/review');
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

    private function defaultModel(RetailEntity $retail): string
    {
        return match ($retail->getKind()->value) {
            'service', 'goods' => 'fixed',
            'task', 'project' => 'budget',
            default => 'fixed',
        };
    }

    private function requiresExactAddress(RetailEntity $retail): bool
    {
        $mode = $retail->getFulfillmentProfile()['mode'] ?? null;

        return match ($retail->getKind()->value) {
            'goods' => in_array($mode, ['shipping', 'pickup'], true),
            'task' => in_array($mode, ['onsite', 'hybrid'], true),
            default => false,
        };
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

    private function redirect(string $crudPath): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('cruding_tokenized_catch_all', ['crudPath' => $crudPath]));
    }
}
