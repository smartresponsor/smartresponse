<?php

declare(strict_types=1);

namespace App\Command;

use App\Navigating\ServiceInterface\Navigation\Provide\NavigationDatabaseConfigProvideServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:walleting:navigation-debug')]
final class WalletingNavigationDebugCommand extends Command
{
    public function __construct(private readonly NavigationDatabaseConfigProvideServiceInterface $provider)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->provider->provideConfig();

        foreach (['mobile_bottom_primary', 'left_middle_business', 'right_toolbar_quick'] as $groupKey) {
            $items = $config['shell_groups'][$groupKey]['items'] ?? [];
            $firstKey = array_key_first($items);
            if (null === $firstKey) {
                continue;
            }

            $output->writeln(sprintf(
                '%s:%s=%s',
                $groupKey,
                $firstKey,
                json_encode($items[$firstKey], JSON_THROW_ON_ERROR),
            ));
        }

        return Command::SUCCESS;
    }
}
