<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Carting\Entity\Cart;
use App\Carting\RepositoryInterface\CartRepositoryInterface;
use App\Carting\Service\Cart\CartCheckoutPreparationService;
use App\Carting\Service\Cart\CartMutationService;
use App\Carting\Service\Cart\CartSummaryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartHostController extends AbstractController
{
    private const TOKEN_HEADER = 'X-Cart-Token';
    private const TOKEN_COOKIE = 'sr_cart_token';

    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CartMutationService $mutationService,
        private readonly CartSummaryService $summaryService,
        private readonly CartCheckoutPreparationService $checkoutPreparationService,
    ) {
    }

    #[Route('/cart', name: 'cart.page.root', methods: ['GET'])]
    #[Route('/cart/page', name: 'cart.page', methods: ['GET'])]
    #[Route('/cart/index', name: 'cart.index', methods: ['GET'])]
    public function page(): Response
    {
        return $this->render('cart/page.html.twig');
    }

    #[Route('/api/cart/current', name: 'api.cart.current', methods: ['GET'])]
    public function current(Request $request): JsonResponse
    {
        [$cart, $created] = $this->resolveCart($request);

        return $this->cartResponse($cart, $request, $created);
    }

    #[Route('/api/cart/item', name: 'api.cart.item.add', methods: ['POST'])]
    public function addItem(Request $request): JsonResponse
    {
        [$cart, $created] = $this->resolveCart($request);
        $payload = $request->toArray();
        $offerReference = trim((string) ($payload['offerReference'] ?? ''));
        $quantity = (int) ($payload['quantity'] ?? 1);

        if ('' === $offerReference || $quantity < 1) {
            return new JsonResponse(['code' => 'invalid_cart_item', 'message' => 'offerReference and positive quantity are required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $this->mutationService->addItem($cart, $offerReference, $quantity);
        } catch (\LogicException $exception) {
            return new JsonResponse(['code' => 'cart_offer_unavailable', 'message' => $exception->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->cartResponse($cart, $request, $created, $result->changed ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Route('/api/cart/item/{id}', name: 'api.cart.item.update', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function updateItem(Request $request, int $id): JsonResponse
    {
        [$cart] = $this->resolveCart($request);
        $payload = $request->toArray();
        $result = $this->mutationService->updateItemQuantity($cart, $id, (int) ($payload['quantity'] ?? 0));

        return $this->cartResponse($cart, $request, false, $result->changed ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/cart/item/{id}', name: 'api.cart.item.remove', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    public function removeItem(Request $request, int $id): JsonResponse
    {
        [$cart] = $this->resolveCart($request);
        $result = $this->mutationService->removeItem($cart, $id);

        return $this->cartResponse($cart, $request, false, $result->changed ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/cart/checkout-handoff', name: 'api.cart.checkout_handoff', methods: ['POST'])]
    public function checkoutHandoff(Request $request): JsonResponse
    {
        [$cart] = $this->resolveCart($request);

        try {
            $handoff = $this->checkoutPreparationService->prepare($cart);
        } catch (\LogicException $exception) {
            return new JsonResponse(['code' => 'cart_checkout_blocked', 'message' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        $response = new JsonResponse([
            'cartId' => null === $cart->getId() ? null : (string) $cart->getId(),
            'cartToken' => $cart->getCartToken(),
            'handoffId' => $handoff->getHandoffReference(),
            'handoffReference' => $handoff->getHandoffReference(),
            'checkoutUrl' => null,
            'status' => 'prepared',
            'expiresAt' => $cart->getExpiresAt()?->format(DATE_ATOM),
        ]);
        $this->attachToken($response, $request, $cart);

        return $response;
    }

    /** @return array{Cart, bool} */
    private function resolveCart(Request $request): array
    {
        $token = trim((string) $request->headers->get(self::TOKEN_HEADER, ''));
        if ('' === $token) {
            $token = trim((string) $request->cookies->get(self::TOKEN_COOKIE, ''));
        }

        if ('' !== $token) {
            $cart = $this->cartRepository->findActiveByToken($token);
            if ($cart instanceof Cart) {
                return [$cart, false];
            }
        }

        return [$this->mutationService->create('USD'), true];
    }

    private function cartResponse(Cart $cart, Request $request, bool $created, int $status = Response::HTTP_OK): JsonResponse
    {
        $summary = $this->summaryService->summarize($cart)->toArray();
        $payload = $summary + [
            'cartId' => null === $cart->getId() ? null : (string) $cart->getId(),
            'ownerReference' => $cart->getOwnerReference(),
            'status' => $cart->getStatus()->value,
            'expiresAt' => $cart->getExpiresAt()?->format(DATE_ATOM),
            'updatedAt' => $cart->getUpdatedAt()->format(DATE_ATOM),
        ];
        $response = new JsonResponse($payload, $created && Response::HTTP_OK === $status ? Response::HTTP_CREATED : $status);
        $this->attachToken($response, $request, $cart);

        return $response;
    }

    private function attachToken(Response $response, Request $request, Cart $cart): void
    {
        $response->headers->set(self::TOKEN_HEADER, $cart->getCartToken());
        $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie(
            self::TOKEN_COOKIE,
            $cart->getCartToken(),
            0,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            \Symfony\Component\HttpFoundation\Cookie::SAMESITE_LAX,
        ));
    }
}
