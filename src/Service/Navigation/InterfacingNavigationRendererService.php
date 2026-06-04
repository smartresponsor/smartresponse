<?php

declare(strict_types=1);

namespace App\Service\Navigation;

use App\Interfacing\ServiceInterface\Rendering\InterfaceRendererInterface;
use App\Navigating\ServiceInterface\Navigation\NavigationRendererInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class InterfacingNavigationRendererService implements NavigationRendererInterface
{
    public function __construct(
        private InterfaceRendererInterface $interfacingRenderer,
    ) {
    }

    public function supports(string $section, string $template): bool
    {
        return '' !== trim($section) && '' !== trim($template);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $section, string $template, array $data): Response
    {
        foreach ($this->buildTemplateCandidates($section, $template) as $templateName) {
            try {
                return call_user_func([$this->interfacingRenderer, 'render'], $templateName, $data);
            } catch (\Throwable) {
                // Try the next candidate before returning JSON fallback.
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @return list<string>
     */
    private function buildTemplateCandidates(string $section, string $template): array
    {
        $path = sprintf('%s/%s.%s', trim($section, '/'), trim($template, '/'), 'html.twig');

        return [
            $path,
            '@Interfacing/'.$path,
        ];
    }
}
