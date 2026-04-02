<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Domain\Url;
use App\Service\ContentFetcher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ContentFetcherTest extends TestCase
{
    #[Test]
    public function it_fetches_content_and_returns_a_snapshot(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('<html>hello</html>'));
        $fetcher = new ContentFetcher($httpClient);

        $snapshot = $fetcher->fetch(new Url('https://example.com'));

        $this->assertSame('<html>hello</html>', $snapshot->content());
    }

    #[Test]
    public function it_computes_the_hash_of_the_fetched_content(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('<html>hello</html>'));
        $fetcher = new ContentFetcher($httpClient);

        $snapshot = $fetcher->fetch(new Url('https://example.com'));

        $this->assertSame(hash('sha256', '<html>hello</html>'), $snapshot->contentHash()->toString());
    }

    #[Test]
    public function it_requests_the_correct_url(): void
    {
        $requestedUrls = [];
        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$requestedUrls): MockResponse {
            $requestedUrls[] = $url;
            return new MockResponse('<html>ok</html>');
        });

        $fetcher = new ContentFetcher($httpClient);
        $fetcher->fetch(new Url('https://example.com/page'));

        $this->assertSame(['https://example.com/page'], $requestedUrls);
    }
}
