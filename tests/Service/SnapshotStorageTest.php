<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Domain\Snapshot;
use App\Domain\Url;
use App\Service\SnapshotStorage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SnapshotStorageTest extends TestCase
{
    private string $temporaryDirectory;
    private SnapshotStorage $storage;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/guaita_tests_' . uniqid();
        mkdir($this->temporaryDirectory);
        $this->storage = new SnapshotStorage($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->temporaryDirectory);
    }

    #[Test]
    public function it_returns_null_when_no_snapshot_exists_for_a_url(): void
    {
        $result = $this->storage->findLatest(new Url('https://example.com'));

        $this->assertNull($result);
    }

    #[Test]
    public function it_stores_and_retrieves_a_snapshot(): void
    {
        $url = new Url('https://example.com');
        $snapshot = Snapshot::fromContent('<html>hello</html>');

        $this->storage->store($url, $snapshot);
        $retrieved = $this->storage->findLatest($url);

        $this->assertNotNull($retrieved);
        $this->assertSame('<html>hello</html>', $retrieved->content());
    }

    #[Test]
    public function it_retrieves_the_latest_snapshot_after_multiple_stores(): void
    {
        $url = new Url('https://example.com');

        $this->storage->store($url, Snapshot::fromContent('<html>version one</html>'));
        $this->storage->store($url, Snapshot::fromContent('<html>version two</html>'));

        $latest = $this->storage->findLatest($url);

        $this->assertNotNull($latest);
        $this->assertSame('<html>version two</html>', $latest->content());
    }

    #[Test]
    public function it_stores_snapshots_for_different_urls_independently(): void
    {
        $firstUrl = new Url('https://first.com');
        $secondUrl = new Url('https://second.com');

        $this->storage->store($firstUrl, Snapshot::fromContent('<html>first</html>'));
        $this->storage->store($secondUrl, Snapshot::fromContent('<html>second</html>'));

        $this->assertSame('<html>first</html>', $this->storage->findLatest($firstUrl)?->content());
        $this->assertSame('<html>second</html>', $this->storage->findLatest($secondUrl)?->content());
    }

    #[Test]
    public function it_creates_the_storage_directory_if_it_does_not_exist(): void
    {
        $nestedDirectory = $this->temporaryDirectory . '/nested/path';
        $storage = new SnapshotStorage($nestedDirectory);

        $storage->store(new Url('https://example.com'), Snapshot::fromContent('<html>hi</html>'));

        $this->assertDirectoryExists($nestedDirectory);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
