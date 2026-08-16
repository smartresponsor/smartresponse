<?php

declare(strict_types=1);

use App\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

$kernel = new Kernel('dev', false);
$kernel->boot();
$provider = $kernel->getContainer()->get('navigating.walleting_database_config_debug');
$config = $provider->provideConfig();

foreach (['mobile_bottom_primary', 'left_middle_business', 'right_toolbar_quick'] as $groupKey) {
    $items = $config['shell_groups'][$groupKey]['items'] ?? [];
    $firstKey = array_key_first($items);
    if (null === $firstKey) {
        continue;
    }
    echo $groupKey, ':', $firstKey, '=', json_encode($items[$firstKey], JSON_THROW_ON_ERROR), PHP_EOL;
}
