<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\AppNotificationDispatchTick;
use App\Worker\Notification\AppNotificationDispatchWorker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AppNotificationDispatchTickHandler
{
    public function __construct(
        private AppNotificationDispatchWorker $worker,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(AppNotificationDispatchTick $message): void
    {
        $workerId = sprintf('app-notification-scheduler:%s:%d', gethostname() ?: 'host', getmypid());
        $result = $this->worker->runBatch($workerId, 100, 60);

        if ($result['claimed'] > 0 || $result['failed'] > 0) {
            $this->logger->info('app.notification.dispatch.tick', $result + ['worker' => $workerId]);
        }
    }
}
