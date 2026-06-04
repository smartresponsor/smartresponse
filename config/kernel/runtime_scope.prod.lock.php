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
        App\Interfacing\InterfaceBundle::class,
        App\Viewing\ViewingBundle::class,
        App\Accessing\AccessingBundle::class,
    ],
    'disabledComponents' => [
        'administering',
        'analysing',
        'applicating',
        'attaching',
        'billing',
        'carting',
        'cataloging',
        'commissioning',
        'domaining',
        'exchanging',
        'indexing',
        'managing',
        'merchandising',
        'messaging',
        'navigating',
        'paging',
        'paying',
        'projecting',
        'rolling',
        'searching',
        'subscripting',
        'tagging',
        'taxating',
        'vendoring',
    ],
];
