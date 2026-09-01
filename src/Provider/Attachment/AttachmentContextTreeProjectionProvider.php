<?php

declare(strict_types=1);

namespace App\Provider\Attachment;

use App\Attaching\Enum\Attachment\AttachmentDocumentKind;
use App\Attaching\Enum\Attachment\AttachmentMediaKind;
use App\Attaching\Enum\Attachment\AttachmentType;
use App\ProviderInterface\Context\AppContextTreeProjectionProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('app.context_tree_projection_provider')]
final readonly class AttachmentContextTreeProjectionProvider implements AppContextTreeProjectionProviderInterface
{
    public function key(): string
    {
        return 'attachment.tree';
    }

    public function project(array $rows, array $routeContext, Request $request): array
    {
        return [
            [
                'label' => 'Media',
                'expanded' => true,
                'children' => [
                    $this->leaf('Avatars', [
                        'type' => AttachmentType::Media->value,
                        'mediaKind' => AttachmentMediaKind::Image->value,
                        'context' => 'profile',
                        'slot' => 'avatar',
                    ]),
                    $this->leaf('Cover images', [
                        'type' => AttachmentType::Media->value,
                        'mediaKind' => AttachmentMediaKind::Image->value,
                        'context' => 'profile',
                        'slot' => 'cover',
                    ]),
                    $this->leaf('Images', [
                        'type' => AttachmentType::Media->value,
                        'mediaKind' => AttachmentMediaKind::Image->value,
                    ]),
                    $this->leaf('Video', [
                        'type' => AttachmentType::Media->value,
                        'mediaKind' => AttachmentMediaKind::Video->value,
                    ]),
                    $this->leaf('Audio', [
                        'type' => AttachmentType::Media->value,
                        'mediaKind' => AttachmentMediaKind::Audio->value,
                    ]),
                    $this->leaf('Other media', [
                        'type' => AttachmentType::Media->value,
                        'mediaKind' => AttachmentMediaKind::Other->value,
                    ]),
                ],
            ],
            [
                'label' => 'Documents',
                'expanded' => true,
                'children' => [
                    $this->leaf('Verifications', [
                        'type' => AttachmentType::Document->value,
                        'context' => 'verification',
                    ]),
                    $this->leaf('Personal identity', [
                        'type' => AttachmentType::Document->value,
                        'context' => 'verification',
                        'slot' => 'personal_identity',
                    ]),
                    $this->leaf('PDF', [
                        'type' => AttachmentType::Document->value,
                        'documentKind' => AttachmentDocumentKind::Pdf->value,
                    ]),
                    $this->leaf('Text documents', [
                        'type' => AttachmentType::Document->value,
                        'documentKind' => AttachmentDocumentKind::Text->value,
                    ]),
                    $this->leaf('Spreadsheets', [
                        'type' => AttachmentType::Document->value,
                        'documentKind' => AttachmentDocumentKind::Spreadsheet->value,
                    ]),
                    $this->leaf('Presentations', [
                        'type' => AttachmentType::Document->value,
                        'documentKind' => AttachmentDocumentKind::Presentation->value,
                    ]),
                    $this->leaf('Archives', [
                        'type' => AttachmentType::Document->value,
                        'documentKind' => AttachmentDocumentKind::Archive->value,
                    ]),
                    $this->leaf('Other documents', [
                        'type' => AttachmentType::Document->value,
                        'documentKind' => AttachmentDocumentKind::Other->value,
                    ]),
                ],
            ],
        ];
    }

    /**
     * @param array<string, string> $query
     *
     * @return array{label: string, href: string, children: list<never>}
     */
    private function leaf(string $label, array $query): array
    {
        return [
            'label' => $label,
            'href' => '/attachment/index?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986),
            'children' => [],
        ];
    }
}
