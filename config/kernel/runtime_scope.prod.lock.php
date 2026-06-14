<?php

declare(strict_types=1);

return [
    'schema' => 'app.kernel.runtime_scope.v1',
    'scope' => 'prod-composer-current',
    'source' => 'materialized from the current composer.prod.json production inventory',
    'sourceComposerFile' => 'composer.prod.json',
    'strict' => true,
    'enabledBundles' => [
        App\Cruding\CrudingBundle::class,
        App\Viewing\ViewingBundle::class,
        App\Accessing\AccessingBundle::class,
        App\Interfacing\InterfacingBundle::class,
        App\Navigating\NavigatingBundle::class,
        App\Vendoring\VendoringBundle::class,
        App\Rolling\RollingBundle::class,
    ],
    'disabledComponents' => [
        'analysing',
        'attaching',
        'billing',
        'carting',
        'cataloging',
        'commissioning',
        'domaining',
        'exchanging',
        'indexing',
        'merchandising',
        'messaging',
        'paging',
        'paying',
        'projecting',
        'subscripting',
        'taxating',
    ],
];
