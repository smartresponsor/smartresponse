<?php

declare(strict_types=1);

$bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    SymfonyCasts\Bundle\VerifyEmail\SymfonyCastsVerifyEmailBundle::class => ['all' => true],
    SymfonyCasts\Bundle\ResetPassword\SymfonyCastsResetPasswordBundle::class => ['all' => true],
    Symfony\UX\TwigComponent\TwigComponentBundle::class => ['all' => true],
    Symfony\UX\StimulusBundle\StimulusBundle::class => ['all' => true],
    Symfony\UX\LiveComponent\LiveComponentBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class => ['dev' => true, 'test' => true],
    Scheb\TwoFactorBundle\SchebTwoFactorBundle::class => ['all' => true],
    Nelmio\ApiDocBundle\NelmioApiDocBundle::class => ['all' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle::class => ['all' => true],
    Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle::class => ['all' => true],
];

$componentBundleClasses = [
    App\Accessing\AccessingBundle::class,
    App\Adjudicating\AdjudicatingBundle::class,
    App\Administering\AdministeringBundle::class,
    App\Analysing\AnalysingBundle::class,
    App\Applicating\ApplicatingBundle::class,
    App\Attaching\AttachingBundle::class,
    App\Billing\BillingBundle::class,
    App\Carting\CartingBundle::class,
    App\Cataloging\CatalogingBundle::class,
    App\Commercializing\CommercializingBundle::class,
    App\Commissioning\CommissioningBundle::class,
    App\Configuring\ConfiguringBundle::class,
    App\Cruding\CrudingBundle::class,
    App\Currencing\CurrencingBundle::class,
    App\Discovering\DiscoveringBundle::class,
    App\Domaining\DomainingBundle::class,
    App\Exchanging\ExchangingBundle::class,
    App\Faceting\FacetingBundle::class,
    App\Facting\FactingBundle::class,
    App\Indexing\IndexingBundle::class,
    App\Interfacing\InterfacingBundle::class,
    App\Localizing\LocalizingBundle::class,
    App\Locating\LocatingBundle::class,
    App\Managing\ManagingBundle::class,
    App\Merchandising\MerchandisingBundle::class,
    App\Messaging\MessagingBundle::class,
    App\Navigating\NavigatingBundle::class,
    App\Objecting\ObjectingBundle::class,
    App\Observabiliting\ObservabilitingBundle::class,
    App\Ordering\OrderingBundle::class,
    App\Paging\PagingBundle::class,
    App\Paying\PayingBundle::class,
    App\Projecting\ProjectingBundle::class,
    App\Rolling\RollingBundle::class,
    App\Sdk\SdkBundle::class,
    App\Searching\SearchingBundle::class,
    App\Shipping\ShippingBundle::class,
    App\Subscripting\SubscriptingBundle::class,
    App\Tagging\TaggingBundle::class,
    App\Taxating\TaxatingBundle::class,
    App\Vendoring\VendoringBundle::class,
    App\Viewing\ViewingBundle::class,
];

foreach ($componentBundleClasses as $componentBundleClass) {
    if (!class_exists($componentBundleClass)) {
        continue;
    }

    $bundles[$componentBundleClass] = ['all' => true];
}

return $bundles;
