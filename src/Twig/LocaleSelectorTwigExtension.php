<?php

declare(strict_types=1);

namespace App\Twig;

use App\Localizing\ServiceInterface\Template\LocaleTemplateSelectorProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LocaleSelectorTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LocaleTemplateSelectorProviderInterface $localeTemplateSelectorProvider,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('locale_selector', [$this, 'localeSelector']),
        ];
    }

    /**
     * @return array{
     *     currentLocale: string,
     *     action: string,
     *     query: array<string, string>,
     *     options: list<array{code: string, name: string, nativeName: string, current: bool, default: bool}>
     * }
     */
    public function localeSelector(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $currentLocale = null !== $request ? (string) $request->getLocale() : 'en';
        $action = null !== $request ? $request->getPathInfo() : '/';
        $query = [];

        if (null !== $request) {
            foreach ($request->query->all() as $key => $value) {
                if ('locale' === $key) {
                    continue;
                }

                if (!is_scalar($value)) {
                    continue;
                }

                $query[(string) $key] = (string) $value;
            }
        }

        return [
            'currentLocale' => $currentLocale,
            'action' => $action,
            'query' => $query,
            'options' => array_map(
                static fn ($option): array => [
                    'code' => $option->code,
                    'name' => $option->name,
                    'nativeName' => $option->nativeName,
                    'current' => $option->current,
                    'default' => $option->default,
                ],
                $this->localeTemplateSelectorProvider->provide($currentLocale),
            ),
        ];
    }
}
