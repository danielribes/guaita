<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\CheckUrlCommand;
use App\Domain\Snapshot;
use App\Domain\Url;
use App\Service\ContentFetcherInterface;
use App\Service\SnapshotStorage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckUrlCommandTest extends TestCase
{
    private string $temporaryDirectory;
    private SnapshotStorage $snapshotStorage;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/guaita_command_tests_' . uniqid();
        mkdir($this->temporaryDirectory);
        $this->snapshotStorage = new SnapshotStorage($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->temporaryDirectory);
    }

    #[Test]
    public function it_reports_first_check_when_no_previous_snapshot_exists(): void
    {
        $contentFetcher = $this->fetcherReturning('<html>hello</html>');
        $tester = $this->testerFor($contentFetcher);

        $tester->execute(['url' => 'https://example.com']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Primera consulta', $tester->getDisplay());
    }

    #[Test]
    public function it_reports_no_changes_when_content_is_identical(): void
    {
        $url = new Url('https://example.com');
        $this->snapshotStorage->store($url, Snapshot::fromContent('<html>hello</html>'));

        $contentFetcher = $this->fetcherReturning('<html>hello</html>');
        $tester = $this->testerFor($contentFetcher);

        $tester->execute(['url' => 'https://example.com']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Cap canvi detectat', $tester->getDisplay());
    }

    #[Test]
    public function it_shows_diff_when_content_has_changed(): void
    {
        $url = new Url('https://example.com');
        $this->snapshotStorage->store($url, Snapshot::fromContent("line one\nline two\n"));

        $contentFetcher = $this->fetcherReturning("line one\nline three\n");
        $tester = $this->testerFor($contentFetcher);

        $tester->execute(['url' => 'https://example.com']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('El contingut ha canviat', $tester->getDisplay());
        $this->assertStringContainsString('-line two', $tester->getDisplay());
        $this->assertStringContainsString('+line three', $tester->getDisplay());
    }

    #[Test]
    public function it_stores_the_new_snapshot_after_detecting_changes(): void
    {
        $url = new Url('https://example.com');
        $this->snapshotStorage->store($url, Snapshot::fromContent('<html>old</html>'));

        $contentFetcher = $this->fetcherReturning('<html>new</html>');
        $tester = $this->testerFor($contentFetcher);

        $tester->execute(['url' => 'https://example.com']);

        $latest = $this->snapshotStorage->findLatest($url);
        $this->assertSame('<html>new</html>', $latest?->content());
    }

    #[Test]
    public function it_returns_failure_when_the_url_cannot_be_fetched(): void
    {
        $contentFetcher = $this->createStub(ContentFetcherInterface::class);
        $contentFetcher->method('fetch')->willThrowException(new \RuntimeException('Connection refused'));

        $tester = $this->testerFor($contentFetcher);

        $tester->execute(['url' => 'https://example.com']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Connection refused', $tester->getDisplay());
    }

    private function fetcherReturning(string $content): ContentFetcherInterface
    {
        $fetcher = $this->createStub(ContentFetcherInterface::class);
        $fetcher->method('fetch')->willReturn(Snapshot::fromContent($content));

        return $fetcher;
    }

    private function testerFor(ContentFetcherInterface $contentFetcher): CommandTester
    {
        return new CommandTester(new CheckUrlCommand($contentFetcher, $this->snapshotStorage));
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
