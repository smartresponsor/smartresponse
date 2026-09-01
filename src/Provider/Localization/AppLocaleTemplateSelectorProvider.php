<?php

declare(strict_types=1);

namespace App\Provider\Localization;

use App\Interfacing\Contract\Localization\InterfaceLocaleTemplateSelectorOption;
use App\Interfacing\ProviderInterface\Localization\InterfaceLocaleTemplateSelectorProviderInterface;
use App\Localizing\ServiceInterface\Locale\LocaleCodeNameConverterInterface;
use App\Localizing\ServiceInterface\Locale\LocaleRegistryInterface;

final readonly class AppLocaleTemplateSelectorProvider implements InterfaceLocaleTemplateSelectorProviderInterface
{
    public function __construct(
        private LocaleRegistryInterface $localeRegistry,
        private LocaleCodeNameConverterInterface $codeNameConverter,
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
