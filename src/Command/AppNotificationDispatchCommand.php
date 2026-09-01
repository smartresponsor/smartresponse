<?php

declare(strict_types=1);

namespace App\Command;

use App\Worker\Notification\AppNotificationDispatchWorker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:notification:dispatch', description: 'Claim Notifying push plans and deliver them through Delivering.')]
final class AppNotificationDispatchCommand extends Command
{
    public function __construct(private readonly AppNotificationDispatchWorker $worker)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of plans to claim.', '100')
            ->addOption('lease', null, InputOption::VALUE_REQUIRED, 'Claim lease duration in seconds.', '60')
            ->addOption('worker', null, InputOption::VALUE_REQUIRED, 'Explicit worker identity.', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(1, min(500, (int) $input->getOption('limit')));
        $leaseSeconds = max(10, min(3600, (int) $input->getOption('lease')));
        $workerId = trim((string) $input->getOption('worker'));
        if ('' === $workerId) {
            $workerId = sprintf('app-delivering-push:%s:%d', gethostname() ?: 'host', getmypid());
        }

        $result = $this->worker->runBatch($workerId, $limit, $leaseSeconds);
        $output->writeln(sprintf(
            'claimed=%d handedOff=%d failed=%d worker=%s',
            $result['claimed'],
            $result['handedOff'],
            $result['failed'],
            $workerId,
        ));

        foreach ($result['errors'] as $error) {
            $output->writeln(sprintf('<error>%s: %s</error>', '' === $error['id'] ? '(unknown)' : $error['id'], $error['error']));
        }

        return 0 === $result['failed'] ? Command::SUCCESS : Command::FAILURE;
    }
}
