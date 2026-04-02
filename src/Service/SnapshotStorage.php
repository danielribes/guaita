<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\ContentHash;
use App\Domain\Snapshot;
use App\Domain\Url;

final class SnapshotStorage
{
    public function __construct(
        private readonly string $storageDirectory,
    ) {}

    public function findLatest(Url $url): ?Snapshot
    {
        $latestFilePath = $this->latestFilePath($url);

        if (!file_exists($latestFilePath)) {
            return null;
        }

        $rawHash = file_get_contents($latestFilePath);

        if ($rawHash === false) {
            return null;
        }

        $contentHash = ContentHash::fromStoredValue(trim($rawHash));
        $contentFilePath = $this->contentFilePath($url, $contentHash);

        if (!file_exists($contentFilePath)) {
            return null;
        }

        $rawContent = file_get_contents($contentFilePath);

        if ($rawContent === false) {
            return null;
        }

        return new Snapshot($rawContent, $contentHash);
    }

    public function store(Url $url, Snapshot $snapshot): void
    {
        $this->ensureDirectory($url);

        $contentFilePath = $this->contentFilePath($url, $snapshot->contentHash());
        file_put_contents($contentFilePath, $snapshot->content());
        file_put_contents($this->latestFilePath($url), $snapshot->contentHash()->toString());
    }

    private function ensureDirectory(Url $url): void
    {
        $directory = $this->urlDirectory($url);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    private function urlDirectory(Url $url): string
    {
        return $this->storageDirectory . '/' . $url->hash();
    }

    private function latestFilePath(Url $url): string
    {
        return $this->urlDirectory($url) . '/latest';
    }

    private function contentFilePath(Url $url, ContentHash $contentHash): string
    {
        return $this->urlDirectory($url) . '/' . $contentHash->toString() . '.html';
    }
}
