<?php

declare(strict_types=1);

return [
    'strict' => true,

    'enabledBundles' => [
        App\Accessing\AccessingBundle::class,
        App\Cruding\CrudingBundle::class,
        App\Interfacing\InterfaceBundle::class,
        App\Managing\ManagingBundle::class,
        App\Navigating\NavigatingBundle::class,
        App\Viewing\ViewingBundle::class,
        App\Searching\SearchingBundle::class,
        App\Rolling\RollingBundle::class,
        App\Applicating\ApplicatingBundle::class,
    ],

    'scope' => [
        'accessing',
        'cruding',
        'viewing',
        'interfacing',
        'managing',
        'navigating',
        'administering',
        'searching',
        'rolling',
        'applicating',
    ],

    'entity' => [
        'vendor',
        'attachment',
        'media',
        'product',
        'category',
        'search',
        'role',
        'application',
    ],

    'surface_token' => [
        'show',
        'index',
        'card',
        'table',
        'gallery',
        'compact',
        'full',
        'detail',
        'list',
    ],

    'packages' => [
        'accessing/access',
        'cruding/crud',
        'viewing/view',
        'interfacing/interface',
        'managing/manage',
        'navigating/navigation',
        'administering/admin',
        'searching/search',
        'rolling/role',
        'applicating/application',
    ],
];
