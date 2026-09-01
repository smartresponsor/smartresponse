<?php

declare(strict_types=1);

namespace App\Provider\Localization;

use App\Interfacing\Contract\Localization\InterfaceLocaleTemplateContext;
use App\Interfacing\ProviderInterface\Localization\InterfaceLocaleTemplateContextProviderInterface;
use App\Localizing\ServiceInterface\Locale\LocaleFallbackResolverInterface;
use App\Localizing\ServiceInterface\Locale\LocaleRegistryInterface;

final readonly class AppLocaleTemplateContextProvider implements InterfaceLocaleTemplateContextProviderInterface
{
    public function __construct(
        private LocaleRegistryInterface $localeRegistry,
        private LocaleFallbackResolverInterface $fallbackResolver,
        private AppLocaleTemplateSelectorProvider $selectorProvider,
    ) {
    }

    public function provide(string $currentLocaleCode): InterfaceLocaleTemplateContext
    {
        $currentLocaleCode = strtolower(trim($currentLocaleCode));

        return new InterfaceLocaleTemplateContext(
            $currentLocaleCode,
            $this->localeRegistry->getDefaultLocaleCode(),
            $this->fallbackResolver->resolveFallbackChain($currentLocaleCode),
            $this->selectorProvider->provide($currentLocaleCode),
        );
    }
}
