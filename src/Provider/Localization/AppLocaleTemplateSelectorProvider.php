<?php

declare(strict_types=1);

namespace App\Provider\Localization;

use App\Interfacing\Contract\Localization\InterfaceLocaleTemplateSelectorOption;
use App\Interfacing\ProviderInterface\Localization\InterfaceLocaleTemplateSelectorProviderInterface;
use App\Localizing\ServiceInterface\LocaleCodeNameConverterServiceInterface;
use App\Localizing\ServiceInterface\LocaleRegistryServiceInterface;

final readonly class AppLocaleTemplateSelectorProvider implements InterfaceLocaleTemplateSelectorProviderInterface
{
    public function __construct(
        private LocaleRegistryServiceInterface $localeRegistry,
        private LocaleCodeNameConverterServiceInterface $codeNameConverter,
    ) {
    }

    /** @return list<InterfaceLocaleTemplateSelectorOption> */
    public function provide(string $currentLocaleCode): array
    {
        $currentLocaleCode = strtolower(trim($currentLocaleCode));
        $default = $this->localeRegistry->getDefaultLocaleCode();
        $options = [];

        foreach ($this->localeRegistry->getAvailableLocaleCodes() as $code) {
            $options[] = new InterfaceLocaleTemplateSelectorOption(
                $code,
                $this->codeNameConverter->convertCodeToName($code),
                $this->codeNameConverter->convertCodeToName($code, $code),
                $code === $currentLocaleCode,
                $code === $default,
            );
        }

        return $options;
    }
}
