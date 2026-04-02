<?php

declare(strict_types=1);

namespace App\Domain;

final class Snapshot
{
    public function __construct(
        private readonly string $content,
        private readonly ContentHash $contentHash,
    ) {}

    public static function fromContent(string $content): self
    {
        return new self($content, ContentHash::fromContent($content));
    }

    public function hasSameContentAs(Snapshot $other): bool
    {
        return $this->contentHash->equals($other->contentHash);
    }

    public function contentHash(): ContentHash
    {
        return $this->contentHash;
    }

    public function content(): string
    {
        return $this->content;
    }
}
