<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\ContentHash;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContentHashTest extends TestCase
{
    #[Test]
    public function it_creates_a_sha256_hash_from_content(): void
    {
        $hash = ContentHash::fromContent('hello world');

        $this->assertSame(hash('sha256', 'hello world'), $hash->toString());
    }

    #[Test]
    public function it_creates_a_hash_from_a_stored_value(): void
    {
        $value = 'abc123def456';
        $hash = ContentHash::fromStoredValue($value);

        $this->assertSame($value, $hash->toString());
    }

    #[Test]
    public function same_content_produces_equal_hashes(): void
    {
        $first = ContentHash::fromContent('hello world');
        $second = ContentHash::fromContent('hello world');

        $this->assertTrue($first->equals($second));
    }

    #[Test]
    public function different_content_produces_unequal_hashes(): void
    {
        $first = ContentHash::fromContent('hello world');
        $second = ContentHash::fromContent('goodbye world');

        $this->assertFalse($first->equals($second));
    }

    #[Test]
    public function from_content_and_from_stored_value_are_equal_for_same_hash(): void
    {
        $rawHash = hash('sha256', 'hello world');
        $fromContent = ContentHash::fromContent('hello world');
        $fromStored = ContentHash::fromStoredValue($rawHash);

        $this->assertTrue($fromContent->equals($fromStored));
    }
}
