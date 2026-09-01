<?php

declare(strict_types=1);

namespace App\Service\Placement;

use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Provider\Crud\CrudPageDefinitionProvider;
use App\Cruding\Service\Crud\Resource\CrudResourceContractFactory;
use App\Cruding\Value\Resource\CrudResourceContract;
use App\Dto\Placement\RetailReviewFormData;
use App\Form\Placement\RetailReviewType;
use App\Retailing\Entity\Retail\RetailEntity;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class RetailReviewService
{
    private const SESSION_KEY = 'retail_placement';

    public function __construct(
        private FormFactoryInterface $formFactory,
        private CrudPageDefinitionProvider $pageDefinitionProvider,
        private CrudResourceContractFactory $contractFactory,
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
        if (null === $retail->getFulfillmentProfile()) {
            return $this->redirect('fulfillment/new');
        }
        if ($this->requiresExactAddress($retail) && null === $retail->getLocationProfile()) {
            return $this->redirect('address/new');
        }
        if (null === $retail->getPricingProfile()) {
            return $this->redirect('pricing/new');
        }

        $data = new RetailReviewFormData();
        $data->title = $retail->getTitle();
        $data->kind = $retail->getKind()->label();
        $data->catalog = $retail->getCatalogCode() ?? '';
        $data->typePath = $retail->getTypePath() ?? '';
        $data->location = null === $retail->getLocationProfile() ? 'Not required' : $this->encoded($retail->getLocationProfile());
        $data->fulfillment = $this->encoded($retail->getFulfillmentProfile());
        $data->pricing = $this->encoded($retail->getPricingProfile());

        $form = $this->formFactory->create(RetailReviewType::class, $data);
        $form->handleRequest($context->request);
        if ($form->isSubmitted() && $form->isValid() && $data->publish) {
            $retail->publish();
            $manager = $this->managerRegistry->getManagerForClass(RetailEntity::class) ?? $this->managerRegistry->getManager();
            $manager->persist($retail);
            $manager->flush();
            $context->request->getSession()->remove(self::SESSION_KEY);

            return $this->redirect('retail/'.$retail->getId());
        }

        $formView = $form->createView();

        return $this->contractFactory->create(
            $this->pageDefinitionProvider->provideNew($context->crudContext, $data, $formView),
            $data,
            $formView,
        );
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

    /** @param array<string, mixed> $value */
    private function encoded(array $value): string
    {
        $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return false === $encoded ? '{}' : $encoded;
    }

    private function redirect(string $crudPath): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('cruding_tokenized_catch_all', ['crudPath' => $crudPath]));
    }
}
