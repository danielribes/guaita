<?php

declare(strict_types=1);

namespace App\Domain;

final class Url
{
    public function __construct(
        private readonly string $value,
    ) {}

    public function toString(): string
    {
        return $this->value;
    }

    public function hash(): string
    {
        return hash('sha256', $this->value);
    }
}
