<?php

declare(strict_types=1);

namespace App\Controller\Paying;

use App\Service\Withdrawing\WithdrawalSettlementEventService;
use App\Service\Withdrawing\WithdrawalSettlementService;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

final class StripeConnectPayoutWebhookController extends AbstractController
{
    public function __construct(
        private readonly WithdrawalSettlementService $settlement,
        private readonly WithdrawalSettlementEventService $settlementEvents,
        private readonly KernelInterface $kernel,
        private readonly string $webhookSecret,
    ) {
    }

    #[Route('/api/paying/stripe/connect/webhook', name: 'api_paying_stripe_connect_webhook', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        if ('' === trim($this->webhookSecret)) {
            return $this->json(['code' => 'stripe_connect_webhook_not_configured'], 503);
        }

        $signature = trim((string) $request->headers->get('Stripe-Signature', ''));
        if ('' === $signature) {
            return $this->json(['code' => 'stripe_signature_required'], 400);
        }

        try {
            $event = Webhook::constructEvent($request->getContent(), $signature, $this->webhookSecret);
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            return $this->json(['code' => 'stripe_signature_invalid'], 400);
        }

        $eventId = trim((string) $event->id);
        if ('' === $eventId) {
            return $this->json(['code' => 'stripe_event_identity_invalid'], 400);
        }

        $isPayoutSettlement = in_array($event->type, ['payout.paid', 'payout.failed'], true);
        $payout = $event->data->object;
        $railReference = null;
        if ($isPayoutSettlement) {
            $accountReference = trim((string) ($event->account ?? ''));
            $payoutReference = trim((string) ($payout->id ?? ''));
            if (!str_starts_with($accountReference, 'acct_') || !str_starts_with($payoutReference, 'po_')) {
                return $this->json(['code' => 'stripe_connect_payout_identity_invalid'], 400);
            }
            $railReference = 'stripe-payout:'.$accountReference.':'.$payoutReference;
        }

        try {
            [$settlementEvent, $shouldProcess] = $this->settlementEvents->receive(
                'stripe',
                $eventId,
                $event->type,
                $railReference,
                hash('sha256', $request->getContent()),
            );
        } catch (\DomainException) {
            return $this->json(['code' => 'stripe_event_integrity_violation'], 409);
        }

        if (!$shouldProcess) {
            return $this->json(['data' => ['accepted' => true, 'duplicate' => true]]);
        }

        if ('prod' === $this->kernel->getEnvironment() && true !== $event->livemode) {
            $this->settlementEvents->ignored($settlementEvent);

            return $this->json(['data' => ['accepted' => true, 'ignored' => 'test_event_in_production']], 202);
        }

        if (!$isPayoutSettlement || null === $railReference) {
            $this->settlementEvents->ignored($settlementEvent);

            return $this->json(['data' => ['accepted' => true, 'ignored' => $event->type]], 202);
        }

        $failureCode = null;
        $failureMessage = null;
        try {
            if ('payout.paid' === $event->type) {
                $withdrawal = $this->settlement->succeed($railReference);
            } else {
                $failureCode = trim((string) ($payout->failure_code ?? 'stripe_payout_failed'));
                $failureMessage = trim((string) ($payout->failure_message ?? $failureCode));
                $withdrawal = $this->settlement->fail($railReference, $failureMessage);
            }
            $this->settlementEvents->processed($settlementEvent, $failureCode, $failureMessage);
        } catch (\Throwable $throwable) {
            $this->settlementEvents->failed($settlementEvent, 'settlement_failed', $throwable->getMessage());

            return $this->json(['code' => 'stripe_payout_settlement_failed'], 500);
        }

        return $this->json(['data' => [
            'accepted' => true,
            'withdrawalId' => $withdrawal->id()->toRfc4122(),
            'status' => $withdrawal->status()->value,
        ]]);
    }
}
