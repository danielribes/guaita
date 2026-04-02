<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Snapshot;
use App\Domain\Url;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ContentFetcher implements ContentFetcherInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {}

    public function fetch(Url $url): Snapshot
    {
        $response = $this->httpClient->request('GET', $url->toString());

        return Snapshot::fromContent($response->getContent());
    }
}
