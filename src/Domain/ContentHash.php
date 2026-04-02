<?php

declare(strict_types=1);

namespace App\Domain;

final class ContentHash
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function fromContent(string $content): self
    {
        return new self(hash('sha256', $content));
    }

    public static function fromStoredValue(string $value): self
    {
        return new self($value);
    }

    public function equals(ContentHash $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
