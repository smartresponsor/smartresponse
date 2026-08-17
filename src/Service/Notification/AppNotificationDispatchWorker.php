<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Delivering\Message\DeliveringSendPush;
use App\Notifying\Service\NotificationDispatchPlanService;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class AppNotificationDispatchWorker
{
    public function __construct(
        private NotificationDispatchPlanService $dispatchPlanService,
        private MessageBusInterface $messageBus,
    ) {
    }

    /** @return array{claimed:int, handedOff:int, failed:int, errors:list<array{id:string,error:string}>} */
    public function runBatch(string $workerId, int $limit = 100, int $leaseSeconds = 60): array
    {
        $claimed = $this->dispatchPlanService->claim($workerId, $limit, $leaseSeconds);
        $result = [
            'claimed' => count($claimed),
            'handedOff' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($claimed as $plan) {
            $planId = trim((string) ($plan['id'] ?? ''));
            $claimLeaseId = trim((string) ($plan['claimLeaseId'] ?? ''));
            if ('' === $planId || '' === $claimLeaseId) {
                ++$result['failed'];
                $result['errors'][] = ['id' => $planId, 'error' => 'Claim payload is missing id or claimLeaseId.'];
                continue;
            }

            $enqueued = false;
            try {
                $delivery = $plan['delivery'] ?? null;
                if (!is_array($delivery)) {
                    throw new \RuntimeException('Claimed push plan does not contain a physical delivery payload.');
                }

                $message = new DeliveringSendPush(
                    platform: (string) ($delivery['provider'] ?? ''),
                    tokenHash: (string) ($delivery['tokenHash'] ?? ''),
                    appKey: (string) ($delivery['appKey'] ?? ''),
                    title: (string) ($delivery['title'] ?? ''),
                    body: (string) ($delivery['body'] ?? ''),
                    actionUrl: isset($delivery['actionUrl']) ? (string) $delivery['actionUrl'] : null,
                    payload: is_array($delivery['payload'] ?? null) ? $delivery['payload'] : [],
                    correlationId: (string) ($delivery['correlationId'] ?? $planId),
                    idempotencyKey: 'notification-dispatch:'.$planId,
                );

                $this->messageBus->dispatch($message);
                $enqueued = true;
                $this->dispatchPlanService->markHandedOff([$planId], $workerId, $claimLeaseId, $workerId);
                ++$result['handedOff'];
            } catch (\Throwable $exception) {
                $reason = $this->failureReason($exception);
                if (!$enqueued) {
                    try {
                        $this->dispatchPlanService->markFailed([$planId], $reason, $workerId, $claimLeaseId, $workerId);
                    } catch (\Throwable $transitionException) {
                        $reason .= '; transition: '.$this->failureReason($transitionException);
                    }
                } else {
                    $reason = 'Delivering enqueue succeeded but Notifying handoff transition failed: '.$reason;
                }

                ++$result['failed'];
                $result['errors'][] = ['id' => $planId, 'error' => $reason];
            }
        }

        return $result;
    }

    private function failureReason(\Throwable $exception): string
    {
        $message = trim($exception->getMessage());
        if ('' === $message) {
            $message = $exception::class;
        }

        return mb_substr($message, 0, 1000);
    }
}
