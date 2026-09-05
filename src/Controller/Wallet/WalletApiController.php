<?php

declare(strict_types=1);

namespace App\Controller\Wallet;

use App\Vendoring\Entity\Vendor\VendorEntity;
use App\Vendoring\RepositoryInterface\Vendor\VendorRepositoryInterface;
use App\Walleting\Entity\PaymentInstrument;
use App\Walleting\Entity\Wallet;
use App\Walleting\Enum\PaymentInstrumentStatus;
use App\Walleting\Service\WalletingFacade;
use App\Withdrawing\Entity\Withdrawal;
use App\Withdrawing\Service\WithdrawalApplicationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class WalletApiController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly VendorRepositoryInterface $vendorRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly WalletingFacade $walleting,
        private readonly WithdrawalApplicationService $withdrawing,
    ) {
    }

    #[Route('/api/wallet/balance', name: 'api_wallet_balance', methods: ['GET'])]
    public function balance(): JsonResponse
    {
        $user = $this->security->getUser();
        if (null === $user || !method_exists($user, 'getId') || null === $user->getId()) {
            return $this->json(['code' => 'authentication_required'], 401);
        }

        $vendorId = $this->currentVendorId((int) $user->getId());
        if (null === $vendorId) {
            return $this->json(['code' => 'vendor_not_found'], 404);
        }

        $wallet = $this->entityManager->getRepository(Wallet::class)->findOneBy([
            'ownerType' => 'vendor',
            'ownerId' => (string) $vendorId,
        ]);
        if (!$wallet instanceof Wallet) {
            return $this->json(['data' => ['walletId' => null, 'currency' => []]]);
        }

        $balance = $this->walleting->walletBalance($wallet);
        $currency = array_map(static fn ($snapshot): array => [
            'code' => $snapshot->currency,
            'availableMinor' => $snapshot->availableMinor,
            'reservedMinor' => $snapshot->reservedMinor,
            'totalMinor' => $snapshot->totalMinor(),
        ], $balance->currencies);

        return $this->json(['data' => [
            'walletId' => $balance->walletId,
            'ownerType' => 'vendor',
            'ownerId' => (string) $vendorId,
            'currency' => $currency,
        ]]);
    }

    #[Route('/api/wallet/funding', name: 'api_wallet_funding', methods: ['GET'])]
    public function funding(Request $request): JsonResponse
    {
        return $this->operation($request, 'funding');
    }

    #[Route('/api/wallet/withdrawal', name: 'api_wallet_withdrawal', methods: ['GET'])]
    public function withdrawal(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (null === $user || !method_exists($user, 'getId') || null === $user->getId()) {
            return $this->json(['code' => 'authentication_required'], 401);
        }
        $vendorId = $this->currentVendorId((int) $user->getId());
        if (null === $vendorId) {
            return $this->json(['code' => 'vendor_not_found'], 404);
        }

        $limit = max(1, min(100, $request->query->getInt('limit', 50)));
        $item = $this->entityManager->getRepository(Withdrawal::class)->findBy(
            ['actorType' => 'vendor', 'actorId' => (string) $vendorId],
            ['id' => 'DESC'],
            $limit,
        );

        return $this->json(['data' => ['item' => array_map(static fn (Withdrawal $withdrawal): array => [
            'id' => $withdrawal->id()->toRfc4122(),
            'type' => 'withdrawal',
            'status' => $withdrawal->status()->value,
            'amountMinor' => $withdrawal->amountMinor(),
            'currency' => $withdrawal->currency(),
            'sourceType' => $withdrawal->sourceType(),
            'sourceId' => $withdrawal->sourceId(),
            'sourceReference' => $withdrawal->sourceReference(),
            'destinationReference' => $withdrawal->destinationReference(),
            'railReference' => $withdrawal->railReference(),
        ], $item)]]);
    }

    #[Route('/api/wallet/withdrawal/{id}', name: 'api_wallet_withdrawal_show', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function withdrawalShow(string $id): JsonResponse
    {
        $context = $this->currentWalletContext();
        if ($context instanceof JsonResponse) {
            return $context;
        }
        [$vendorId, $wallet] = $context;
        if (!$wallet instanceof Wallet) {
            return $this->json(['code' => 'wallet_not_found'], 404);
        }

        $withdrawal = $this->entityManager->find(Withdrawal::class, $id);
        if (!$withdrawal instanceof Withdrawal
            || 'vendor' !== $withdrawal->actorType()
            || (string) $vendorId !== $withdrawal->actorId()
            || 'wallet' !== $withdrawal->sourceType()
            || $wallet->id()->toRfc4122() !== $withdrawal->sourceId()) {
            return $this->json(['code' => 'withdrawal_not_found'], 404);
        }

        return $this->json(['data' => $this->withdrawalData($withdrawal)]);
    }

    #[Route('/api/wallet/withdrawal/destination', name: 'api_wallet_withdrawal_destination', methods: ['GET'])]
    public function withdrawalDestination(): JsonResponse
    {
        $context = $this->currentWalletContext();
        if ($context instanceof JsonResponse) {
            return $context;
        }
        [$vendorId, $wallet] = $context;
        if (!$wallet instanceof Wallet) {
            return $this->json(['data' => ['item' => []]]);
        }

        $item = $this->entityManager->getRepository(PaymentInstrument::class)->findBy([
            'wallet' => $wallet,
            'status' => PaymentInstrumentStatus::Active,
        ]);

        return $this->json(['data' => ['item' => array_map(static fn (PaymentInstrument $instrument): array => [
            'id' => $instrument->id()->toRfc4122(),
            'type' => $instrument->type()->value,
            'label' => $instrument->displayLabel(),
        ], $item)]]);
    }

    #[Route('/api/wallet/withdrawal/request', name: 'api_wallet_withdrawal_request', methods: ['POST'])]
    public function withdrawalRequest(Request $request): JsonResponse
    {
        $context = $this->currentWalletContext();
        if ($context instanceof JsonResponse) {
            return $context;
        }
        [$vendorId, $wallet] = $context;
        if (!$wallet instanceof Wallet) {
            return $this->json(['code' => 'wallet_not_found'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['code' => 'invalid_request', 'message' => 'Withdrawal request body must be valid JSON.'], 400);
        }
        $amountMinor = filter_var($payload['amountMinor'] ?? null, FILTER_VALIDATE_INT);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? '')));
        $instrumentId = trim((string) ($payload['paymentInstrumentId'] ?? ''));
        $idempotencyKey = trim((string) ($payload['idempotencyKey'] ?? ''));
        if (!is_int($amountMinor) || $amountMinor <= 0 || 1 !== preg_match('/^[A-Z]{3}$/', $currency) || '' === $instrumentId || '' === $idempotencyKey || strlen($idempotencyKey) > 128) {
            return $this->json(['code' => 'invalid_request', 'message' => 'Positive amountMinor, ISO currency, paymentInstrumentId, and idempotencyKey are required.'], 422);
        }

        $instrument = $this->entityManager->find(PaymentInstrument::class, $instrumentId);
        if (!$instrument instanceof PaymentInstrument || $instrument->wallet() !== $wallet || PaymentInstrumentStatus::Active !== $instrument->status()) {
            return $this->json(['code' => 'withdrawal_destination_not_found'], 404);
        }

        $balance = $this->walleting->walletBalance($wallet);
        $currencyBalance = null;
        foreach ($balance->currencies as $snapshot) {
            if ($snapshot->currency === $currency) {
                $currencyBalance = $snapshot;
                break;
            }
        }
        if (null === $currencyBalance || $currencyBalance->availableMinor < $amountMinor) {
            return $this->json(['code' => 'insufficient_wallet_balance'], 422);
        }

        try {
            $withdrawal = $this->withdrawing->request(
                'wallet',
                $wallet->id()->toRfc4122(),
                'vendor',
                (string) $vendorId,
                'payment-instrument:'.$instrument->id()->toRfc4122(),
                $amountMinor,
                $currency,
                $idempotencyKey,
            );
        } catch (\DomainException|\InvalidArgumentException $exception) {
            return $this->json(['code' => 'withdrawal_request_rejected', 'message' => $exception->getMessage()], 422);
        }

        return $this->json(['data' => $this->withdrawalData($withdrawal)], 201);
    }

    #[Route('/api/wallet/withdrawal/cancel/{id}', name: 'api_wallet_withdrawal_cancel', methods: ['POST'])]
    public function withdrawalCancel(string $id): JsonResponse
    {
        $context = $this->currentWalletContext();
        if ($context instanceof JsonResponse) {
            return $context;
        }
        [$vendorId, $wallet] = $context;
        if (!$wallet instanceof Wallet) {
            return $this->json(['code' => 'wallet_not_found'], 404);
        }

        $withdrawal = $this->entityManager->find(Withdrawal::class, $id);
        if (!$withdrawal instanceof Withdrawal
            || 'vendor' !== $withdrawal->actorType()
            || (string) $vendorId !== $withdrawal->actorId()
            || 'wallet' !== $withdrawal->sourceType()
            || $wallet->id()->toRfc4122() !== $withdrawal->sourceId()) {
            return $this->json(['code' => 'withdrawal_not_found'], 404);
        }

        try {
            $this->withdrawing->cancel($withdrawal);
        } catch (\DomainException|\LogicException $exception) {
            return $this->json(['code' => 'withdrawal_cancel_rejected', 'message' => $exception->getMessage()], 409);
        }

        return $this->json(['data' => $this->withdrawalData($withdrawal)]);
    }

    #[Route('/api/wallet/transaction', name: 'api_wallet_transaction', methods: ['GET'])]
    public function transaction(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (null === $user || !method_exists($user, 'getId') || null === $user->getId()) {
            return $this->json(['code' => 'authentication_required'], 401);
        }
        $vendorId = $this->currentVendorId((int) $user->getId());
        if (null === $vendorId) {
            return $this->json(['code' => 'vendor_not_found'], 404);
        }
        $wallet = $this->entityManager->getRepository(Wallet::class)->findOneBy([
            'ownerType' => 'vendor',
            'ownerId' => (string) $vendorId,
        ]);
        if (!$wallet instanceof Wallet) {
            return $this->json(['data' => ['item' => [], 'nextCursor' => null]]);
        }

        $limit = max(1, min(100, $request->query->getInt('limit', 50)));
        $cursor = trim((string) $request->query->get('cursor', ''));
        $page = $this->walleting->walletTransactions($wallet, $limit, '' === $cursor ? null : $cursor);

        return $this->json(['data' => [
            'item' => array_map(static fn ($item): array => [
                'transactionId' => $item->transactionId,
                'type' => $item->type,
                'amountMinor' => $item->amountMinor,
                'currency' => $item->currency,
                'postedAt' => $item->postedAt->format(DATE_ATOM),
            ], $page->items),
            'nextCursor' => $page->nextCursor,
        ]]);
    }

    /** @return array{0:int,1:?Wallet}|JsonResponse */
    private function currentWalletContext(): array|JsonResponse
    {
        $user = $this->security->getUser();
        if (null === $user || !method_exists($user, 'getId') || null === $user->getId()) {
            return $this->json(['code' => 'authentication_required'], 401);
        }
        $vendorId = $this->currentVendorId((int) $user->getId());
        if (null === $vendorId) {
            return $this->json(['code' => 'vendor_not_found'], 404);
        }
        $wallet = $this->entityManager->getRepository(Wallet::class)->findOneBy([
            'ownerType' => 'vendor',
            'ownerId' => (string) $vendorId,
        ]);

        return [$vendorId, $wallet instanceof Wallet ? $wallet : null];
    }

    /** @return array<string, mixed> */
    private function withdrawalData(Withdrawal $withdrawal): array
    {
        return [
            'id' => $withdrawal->id()->toRfc4122(),
            'type' => 'withdrawal',
            'status' => $withdrawal->status()->value,
            'amountMinor' => $withdrawal->amountMinor(),
            'currency' => $withdrawal->currency(),
            'sourceType' => $withdrawal->sourceType(),
            'sourceId' => $withdrawal->sourceId(),
            'sourceReference' => $withdrawal->sourceReference(),
            'destinationReference' => $withdrawal->destinationReference(),
            'railReference' => $withdrawal->railReference(),
        ];
    }

    private function currentVendorId(int $actorId): ?int
    {
        $vendor = $this->vendorRepository->findOneBy(['ownerUserId' => $actorId]);
        if (!$vendor instanceof VendorEntity) {
            return null;
        }

        $vendorId = $vendor->getId();

        return is_int($vendorId) ? $vendorId : null;
    }

    private function operation(Request $request, string $type): JsonResponse
    {
        $user = $this->security->getUser();
        if (null === $user || !method_exists($user, 'getId') || null === $user->getId()) {
            return $this->json(['code' => 'authentication_required'], 401);
        }
        $vendorId = $this->currentVendorId((int) $user->getId());
        if (null === $vendorId) {
            return $this->json(['code' => 'vendor_not_found'], 404);
        }
        $wallet = $this->entityManager->getRepository(Wallet::class)->findOneBy([
            'ownerType' => 'vendor',
            'ownerId' => (string) $vendorId,
        ]);
        if (!$wallet instanceof Wallet) {
            return $this->json(['data' => ['item' => []]]);
        }

        $limit = max(1, min(100, $request->query->getInt('limit', 50)));
        $item = 'funding' === $type
            ? $this->walleting->fundingByWallet($wallet, $limit)
            : $this->walleting->withdrawalByWallet($wallet, $limit);

        return $this->json(['data' => ['item' => array_map(static fn ($view): array => [
            'id' => $view->id,
            'type' => $view->type,
            'status' => $view->status,
            'amountMinor' => $view->amountMinor,
            'currency' => $view->currency,
            'transactionId' => $view->transactionId,
            'reversalTransactionId' => $view->reversalTransactionId,
        ], $item)]]);
    }
}
