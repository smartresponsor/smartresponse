<?php

declare(strict_types=1);

namespace App\Service\Navigation;

use App\Interfacing\ServiceInterface\Presentation\InterfacingRendererInterface;
use App\Navigating\ServiceInterface\Navigation\NavigationSurfaceRendererInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class InterfacingNavigationSurfaceRendererService implements NavigationSurfaceRendererInterface
{
    public function __construct(
        private InterfacingRendererInterface $interfacingRenderer,
    ) {
    }

    public function supports(string $surface, string $template): bool
    {
        return '' !== trim($surface) && '' !== trim($template);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $surface, string $template, array $data): Response
    {
        foreach ($this->buildTemplateCandidates($surface, $template) as $templateName) {
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
    private function buildTemplateCandidates(string $surface, string $template): array
    {
        $path = sprintf('%s/%s.%s', trim($surface, '/'), trim($template, '/'), 'html.twig');

        return [
            $path,
            '@Interfacing/'.$path,
        ];
    }
}
