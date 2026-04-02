<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\Url;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    #[Test]
    public function it_returns_the_original_value(): void
    {
        $url = new Url('https://example.com');

        $this->assertSame('https://example.com', $url->toString());
    }

    #[Test]
    public function it_produces_a_sha256_hash(): void
    {
        $url = new Url('https://example.com');

        $this->assertSame(hash('sha256', 'https://example.com'), $url->hash());
    }

    #[Test]
    public function same_url_always_produces_same_hash(): void
    {
        $first = new Url('https://example.com');
        $second = new Url('https://example.com');

        $this->assertSame($first->hash(), $second->hash());
    }

    #[Test]
    public function different_urls_produce_different_hashes(): void
    {
        $first = new Url('https://example.com');
        $second = new Url('https://other.com');

        $this->assertNotSame($first->hash(), $second->hash());
    }
}
