<?php

declare(strict_types=1);

namespace App\Service\View;

use App\Attaching\Repository\Attachment\AttachmentLinkRepository;
use App\Attaching\Repository\Attachment\AttachmentRepository;
use App\Cruding\Value\Resource\CrudResourceContract;
use App\Service\Context\AppContextTreeProjectionResolver;
use App\Vendoring\ServiceInterface\Profile\VendorPublicProfileSummaryProviderServiceInterface;
use App\Viewing\ServiceInterface\View\ViewPayloadNormalizerInterface;
use App\Viewing\Value\View\ViewPayload;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class AppCrudResourceViewPayloadNormalizer implements ViewPayloadNormalizerInterface
{
    public function __construct(
        private ViewPayloadNormalizerInterface $inner,
        private RequestStack $requestStack,
        private AppContextTreeProjectionResolver $contextTreeProjectionResolver,
        private ?object $interfaceLocationProjectionProvider,
        private VendorPublicProfileSummaryProviderServiceInterface $vendorPublicProfileSummaryProvider,
        private AttachmentLinkRepository $attachmentLinkRepository,
        private AttachmentRepository $attachmentRepository,
        private Security $security,
    ) {
    }

    public function supports(mixed $controllerResult): bool
    {
        return $controllerResult instanceof CrudResourceContract || $this->inner->supports($controllerResult);
    }

    public function normalize(mixed $controllerResult): ViewPayload
    {
        if (!$controllerResult instanceof CrudResourceContract) {
            return $this->inner->normalize($controllerResult);
        }

        $request = $this->requestStack->getCurrentRequest();
        $contractStartedAt = hrtime(true);
        $templateContext = $controllerResult->toTemplateContext();
        $fallbackData = $controllerResult->toFallbackData();
        $contractMs = (hrtime(true) - $contractStartedAt) / 1_000_000;
        $routeContext = $this->routeContextFrom($templateContext, $fallbackData);
        $meta = $this->metaFrom($templateContext, $fallbackData);
        $word = $this->stringFrom($templateContext['word'] ?? $fallbackData['word'] ?? null, 'crud');
        $view = $this->stringFrom($templateContext['view'] ?? $fallbackData['view'] ?? null, 'index');

        $navigationStartedAt = hrtime(true);
        $locations = $this->mergeLocations(
            $this->locationsFrom($templateContext, $fallbackData),
            $this->navigatingLocations(),
        );
        $navigationMs = (hrtime(true) - $navigationStartedAt) / 1_000_000;

        $bodyBlockData = $locations['body'][0]['data'] ?? [];
        $bodyLocation = \is_array($bodyBlockData) ? $bodyBlockData : [];
        $rows = \is_array($bodyLocation['rows'] ?? null) ? $bodyLocation['rows'] : [];
        $rows = $this->withVendorPageProfileMedia($rows, $routeContext);
        $rows = $this->withAttachmentIndexLinks($rows, $routeContext);
        if (isset($locations['body'][0]['data']) && \is_array($locations['body'][0]['data'])) {
            $locations['body'][0]['data']['rows'] = $rows;
            if (\is_array($locations['body'][0]['data']['workbench'] ?? null)) {
                $locations['body'][0]['data']['workbench']['rows'] = $rows;
                $locations['body'][0]['data']['workbench']['paginationLabel'] = sprintf('%d attachment records', count($rows));
            }
        }
        if ([] !== $rows && $request instanceof Request) {
            $contextTreeNodes = $this->contextTreeProjectionResolver->resolve($locations, $rows, $routeContext, $request);
            if ([] !== $contextTreeNodes) {
                $locations['body'][0]['data']['contextTreeNodes'] = $contextTreeNodes;
            }
        }

        $data = $this->withShellLocations($templateContext, $locations) + [
            'fallbackData' => $fallbackData,
            'routeContext' => $routeContext,
            'objectClass' => $controllerResult::class,
        ];

        if ($request instanceof Request) {
            $request->attributes->set('_app_crud_contract_ms', number_format($contractMs, 2, '.', ''));
            $request->attributes->set('_app_crud_navigation_ms', number_format($navigationMs, 2, '.', ''));
        }

        return new ViewPayload(
            surface: $this->surfaceFromRouteContext($routeContext, $word),
            operation: $this->operationFromRouteContext($routeContext, $view),
            format: $this->stringFrom($templateContext['format'] ?? $fallbackData['format'] ?? null, 'auto'),
            intent: 'crud_resource',
            component: 'Cruding',
            locations: $locations,
            data: $data,
            meta: [
                'source' => 'app_cruding_view_bridge',
                'object_class' => $controllerResult::class,
            ] + $meta,
        );
    }

    private function withNavigatingLocations(ViewPayload $payload): ViewPayload
    {
        $locations = $this->mergeLocations($payload->locations, $this->navigatingLocations());
        $data = $payload->data;
        $interface = \is_array($data['interface'] ?? null) ? $data['interface'] : [];
        $interface['locations'] = $locations;
        $shell = \is_array($data['shell'] ?? null) ? $data['shell'] : [];
        $shell['locations'] = $locations;
        $data['interface'] = $interface;
        $data['shell'] = $shell;
        $data['locations'] = $locations;

        return new ViewPayload(
            surface: $payload->surface,
            operation: $payload->operation,
            format: $payload->format,
            intent: $payload->intent,
            component: $payload->component,
            locations: $locations,
            data: $data,
            meta: $payload->meta,
            debug: $payload->debug,
        );
    }

    /**
     * @param array<string, mixed> $templateContext
     * @param array<string, mixed> $fallbackData
     *
     * @return array<string, mixed>
     */
    private function routeContextFrom(array $templateContext, array $fallbackData): array
    {
        $templateWorkbench = \is_array($templateContext['workbench'] ?? null) ? $templateContext['workbench'] : [];
        $fallbackWorkbench = \is_array($fallbackData['workbench'] ?? null) ? $fallbackData['workbench'] : [];

        if (\is_array($templateWorkbench['routeContext'] ?? null)) {
            return $templateWorkbench['routeContext'];
        }

        if (\is_array($fallbackWorkbench['routeContext'] ?? null)) {
            return $fallbackWorkbench['routeContext'];
        }

        if (\is_array($templateContext['routeContext'] ?? null)) {
            return $templateContext['routeContext'];
        }

        return \is_array($fallbackData['routeContext'] ?? null) ? $fallbackData['routeContext'] : [];
    }

    /**
     * @param array<string, mixed> $templateContext
     * @param array<string, mixed> $fallbackData
     *
     * @return array<string, mixed>
     */
    private function locationsFrom(array $templateContext, array $fallbackData): array
    {
        if (\is_array($templateContext['interface'] ?? null) && \is_array($templateContext['interface']['locations'] ?? null)) {
            return $templateContext['interface']['locations'];
        }

        if (\is_array($fallbackData['interface'] ?? null) && \is_array($fallbackData['interface']['locations'] ?? null)) {
            return $fallbackData['interface']['locations'];
        }

        if (\is_array($templateContext['locations'] ?? null)) {
            return $templateContext['locations'];
        }

        return \is_array($fallbackData['locations'] ?? null) ? $fallbackData['locations'] : [];
    }

    /**
     * @param array<string, mixed>                      $templateContext
     * @param array<string, list<array<string, mixed>>> $locations
     *
     * @return array<string, mixed>
     */
    private function withShellLocations(array $templateContext, array $locations): array
    {
        $shell = \is_array($templateContext['shell'] ?? null) ? $templateContext['shell'] : [];
        $shell['locations'] = $locations;

        $interface = \is_array($templateContext['interface'] ?? null) ? $templateContext['interface'] : [];
        $interface['locations'] = $locations;

        return [
            'shell' => $shell,
            'interface' => $interface,
            'locations' => $locations,
        ] + $templateContext;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function navigatingLocations(): array
    {
        if (null === $this->interfaceLocationProjectionProvider) {
            return [];
        }

        if (!method_exists($this->interfaceLocationProjectionProvider, 'provideInterfacePayload')) {
            return [];
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return [];
        }

        $payload = $this->interfaceLocationProjectionProvider->provideInterfacePayload($request);
        if (!\is_array($payload)) {
            return [];
        }

        return $this->normalizeLocations($payload['locations'] ?? []);
    }

    /**
     * @param array<string, mixed>                      $left
     * @param array<string, list<array<string, mixed>>> $right
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function mergeLocations(array $left, array $right): array
    {
        $merged = $this->normalizeLocations($left);

        foreach ($right as $location => $blocks) {
            if ([] === $blocks) {
                continue;
            }

            $merged[$location] = array_values(array_merge($merged[$location] ?? [], $blocks));
        }

        return $merged;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function normalizeLocations(mixed $locations): array
    {
        if (!\is_array($locations)) {
            return [];
        }

        $normalized = [];
        foreach ($locations as $location => $blocks) {
            if (!\is_string($location) || '' === trim($location) || !\is_array($blocks)) {
                continue;
            }

            $normalizedBlocks = [];
            foreach ($blocks as $block) {
                if (\is_array($block)) {
                    $normalizedBlocks[] = $block;
                }
            }

            if ([] !== $normalizedBlocks) {
                $normalized[trim($location)] = $normalizedBlocks;
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $templateContext
     * @param array<string, mixed> $fallbackData
     *
     * @return array<string, mixed>
     */
    private function metaFrom(array $templateContext, array $fallbackData): array
    {
        if (\is_array($templateContext['meta'] ?? null)) {
            return $templateContext['meta'];
        }

        return \is_array($fallbackData['meta'] ?? null) ? $fallbackData['meta'] : [];
    }

    /**
     * @param array<string, mixed> $routeContext
     */
    private function surfaceFromRouteContext(array $routeContext, string $fallback): string
    {
        foreach (['viewPath', 'surfacePath', 'resourcePath', 'resource'] as $key) {
            if (\is_scalar($routeContext[$key] ?? null)) {
                $value = trim((string) $routeContext[$key]);
                if ('' !== $value) {
                    return $value;
                }
            }
        }

        return $fallback;
    }

    /**
     * @param array<string, mixed> $routeContext
     */
    private function operationFromRouteContext(array $routeContext, string $fallback): string
    {
        if (\is_scalar($routeContext['operation'] ?? null)) {
            $value = trim((string) $routeContext['operation']);
            if ('' !== $value) {
                return $value;
            }
        }

        return $fallback;
    }

    /**
     * @param list<mixed>          $rows
     * @param array<string, mixed> $routeContext
     *
     * @return list<mixed>
     */
    private function withVendorPageProfileMedia(array $rows, array $routeContext): array
    {
        $resourcePath = $this->stringFrom($routeContext['resourcePath'] ?? $routeContext['resource'] ?? null, '');
        $operation = $this->stringFrom($routeContext['operation'] ?? null, '');

        if ('vendor' !== $resourcePath || 'page' !== $operation || [] === $rows || !\is_array($rows[0] ?? null)) {
            return $rows;
        }

        $vendorId = $rows[0]['id'] ?? null;
        if (!\is_int($vendorId) && !(\is_string($vendorId) && ctype_digit($vendorId))) {
            return $rows;
        }

        $summary = $this->vendorPublicProfileSummaryProvider->provideForVendorId((int) $vendorId);
        if (null === $summary) {
            return $rows;
        }

        $rows[0]['publicProfile'] = $summary->toArray();
        $rows[0]['avatarPath'] = $summary->avatar->url;
        $rows[0]['coverPath'] = $summary->cover->url;
        $rows[0]['avatarAttachmentId'] = $summary->avatar->attachmentId;
        $rows[0]['coverAttachmentId'] = $summary->cover->attachmentId;

        return $rows;
    }

    /**
     * @param list<mixed>          $rows
     * @param array<string, mixed> $routeContext
     *
     * @return list<mixed>
     */
    private function withAttachmentIndexLinks(array $rows, array $routeContext): array
    {
        $resourcePath = $this->stringFrom($routeContext['resourcePath'] ?? $routeContext['resource'] ?? null, '');
        $operation = $this->stringFrom($routeContext['operation'] ?? null, '');

        if ('attachment' !== $resourcePath || !\in_array($operation, ['index', 'page'], true)) {
            return $rows;
        }

        $attachmentIds = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $attachmentId = $row['id'] ?? null;
            if (\is_int($attachmentId) || (\is_string($attachmentId) && ctype_digit($attachmentId))) {
                $attachmentIds[] = (int) $attachmentId;
            }
        }

        $attachmentsById = [];
        foreach ($this->attachmentRepository->findByIds($attachmentIds) as $attachment) {
            $attachmentsById[$attachment->getId()] = $attachment;
        }

        $myVendorId = $this->currentMyAttachmentVendorId();
        $isMyAttachmentIndex = $this->isMyAttachmentIndexRequest();
        if ($isMyAttachmentIndex && null === $myVendorId) {
            return [];
        }

        $linksByAttachmentId = [];
        foreach ($this->attachmentLinkRepository->findByAttachmentIds($attachmentIds) as $link) {
            if ($isMyAttachmentIndex && ('vendor' !== $link->getOwnerType() || (string) $myVendorId !== $link->getOwnerId())) {
                continue;
            }

            $attachmentId = $link->getAttachment()->getId();
            $linksByAttachmentId[$attachmentId][] = [
                'id' => $link->getId(),
                'ownerType' => $link->getOwnerType(),
                'ownerId' => $link->getOwnerId(),
                'context' => $link->getContext(),
                'slot' => $link->getSlot(),
                'position' => $link->getPosition(),
                'isPrimary' => $link->isPrimary(),
            ];
        }

        foreach ($rows as $index => $row) {
            if (!\is_array($row)) {
                continue;
            }

            $attachmentId = $row['id'] ?? null;
            if (!\is_int($attachmentId) && !(\is_string($attachmentId) && ctype_digit($attachmentId))) {
                continue;
            }

            $attachmentId = (int) $attachmentId;
            if ($isMyAttachmentIndex && !isset($linksByAttachmentId[$attachmentId])) {
                unset($rows[$index]);
                continue;
            }

            $attachment = $attachmentsById[$attachmentId] ?? null;
            if (null !== $attachment) {
                $rows[$index]['type'] = $attachment->getType()->value;
                $rows[$index]['mediaKind'] = $attachment->getMediaKind()?->value;
                $rows[$index]['documentKind'] = $attachment->getDocumentKind()?->value;
                $rows[$index]['originalName'] = $attachment->getOriginalName();
                $rows[$index]['title'] = $attachment->getTitle() ?? $attachment->getOriginalName();
                $rows[$index]['mimeType'] = $attachment->getMimeType();
                $rows[$index]['extension'] = $attachment->getExtension();
                $rows[$index]['size'] = $attachment->getSize();
                $rows[$index]['width'] = $attachment->getWidth();
                $rows[$index]['height'] = $attachment->getHeight();
                $rows[$index]['durationMs'] = $attachment->getDurationMs();
                $rows[$index]['pageCount'] = $attachment->getPageCount();
                $rows[$index]['altText'] = $attachment->getAltText();
                $rows[$index]['createdAt'] = $attachment->getCreatedAt()->format(DATE_ATOM);
            }

            $links = $linksByAttachmentId[$attachmentId] ?? [];
            $rows[$index]['attachmentLinks'] = $links;
            $rows[$index]['downloadUrl'] = sprintf('/attachment/%d/download', $attachmentId);
            $rows[$index]['categorySlots'] = array_values(array_unique(array_filter(array_map(
                static fn (array $link): ?string => \is_string($link['slot'] ?? null) ? $link['slot'] : null,
                $links,
            ))));
        }

        return array_values($rows);
    }

    private function isMyAttachmentIndexRequest(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request instanceof Request && '/attachment/page' === rtrim($request->getPathInfo(), '/');
    }

    private function currentMyAttachmentVendorId(): ?int
    {
        if (!$this->isMyAttachmentIndexRequest()) {
            return null;
        }

        $user = $this->security->getUser();
        if (!\is_object($user) || !method_exists($user, 'getId')) {
            return null;
        }

        $actorId = $user->getId();
        if (!\is_int($actorId) || $actorId <= 0) {
            return null;
        }

        return $this->vendorPublicProfileSummaryProvider->provideForCurrentActor($actorId)?->vendorId;
    }

    private function stringFrom(mixed $value, string $fallback): string
    {
        if (!\is_scalar($value)) {
            return $fallback;
        }

        $value = trim((string) $value);

        return '' !== $value ? $value : $fallback;
    }
}
