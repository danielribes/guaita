<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\Snapshot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SnapshotTest extends TestCase
{
    #[Test]
    public function it_stores_content_and_computes_hash(): void
    {
        $snapshot = Snapshot::fromContent('<html>hello</html>');

        $this->assertSame('<html>hello</html>', $snapshot->content());
        $this->assertSame(hash('sha256', '<html>hello</html>'), $snapshot->contentHash()->toString());
    }

    #[Test]
    public function snapshots_with_same_content_are_equal(): void
    {
        $first = Snapshot::fromContent('<html>hello</html>');
        $second = Snapshot::fromContent('<html>hello</html>');

        $this->assertTrue($first->hasSameContentAs($second));
    }

    #[Test]
    public function snapshots_with_different_content_are_not_equal(): void
    {
        $first = Snapshot::fromContent('<html>hello</html>');
        $second = Snapshot::fromContent('<html>goodbye</html>');

        $this->assertFalse($first->hasSameContentAs($second));
    }
}
