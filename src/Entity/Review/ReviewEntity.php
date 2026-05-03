<?php

declare(strict_types=1);

namespace App\Entity\Review;

use App\EntityTrait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'review')]
#[ORM\Index(name: 'idx_review_subject', columns: ['tenant_id', 'subject_type', 'subject_id'])]
#[ORM\Index(name: 'idx_review_author', columns: ['tenant_id', 'author_id'])]
#[ORM\UniqueConstraint(name: 'uniq_review_tenant_slug', columns: ['tenant_id', 'slug'])]
final class ReviewEntity
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $tenantId;

    #[ORM\Column(length: 180)]
    private string $slug;

    #[ORM\Column(name: 'subject_type', length: 64)]
    private string $subjectType;

    #[ORM\Column(name: 'subject_id', length: 64)]
    private string $subjectId;

    #[ORM\Column(name: 'subject_slug', length: 180, nullable: true)]
    private ?string $subjectSlug = null;

    #[ORM\Column(name: 'author_id', length: 64)]
    private string $authorId;

    #[ORM\Column(type: 'smallint')]
    private int $rating;

    #[ORM\Column(length: 160)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $comment;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $locale;

    #[ORM\Column(length: 32)]
    private string $status;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $visible = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $payload = null;

    public function __construct(
        string $tenantId,
        string $slug,
        string $subjectType,
        string $subjectId,
        string $authorId,
        int $rating,
        string $title,
        string $comment,
        ?string $locale = null,
        string $status = 'draft',
        bool $visible = true,
        ?string $subjectSlug = null,
        ?array $metadata = null,
        ?array $payload = null,
    ) {
        $this->tenantId = $this->normalizeTenantId($tenantId);
        $this->slug = $slug;
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
        $this->authorId = $authorId;
        $this->rating = max(0, min(5, $rating));
        $this->title = $title;
        $this->comment = $comment;
        $this->locale = $locale;
        $this->status = $status;
        $this->visible = $visible;
        $this->subjectSlug = $subjectSlug;
        $this->metadata = $metadata;
        $this->payload = $payload;
        $this->initializeTimestamps();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function subjectType(): string
    {
        return $this->subjectType;
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function subjectSlug(): ?string
    {
        return $this->subjectSlug;
    }

    public function authorId(): string
    {
        return $this->authorId;
    }

    public function rating(): int
    {
        return $this->rating;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function comment(): string
    {
        return $this->comment;
    }

    public function locale(): ?string
    {
        return $this->locale;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    /** @return array<string, mixed>|null */
    public function metadata(): ?array
    {
        return $this->metadata;
    }

    /** @return array<string, mixed>|null */
    public function payload(): ?array
    {
        return $this->payload;
    }
}
