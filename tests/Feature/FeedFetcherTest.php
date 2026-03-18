<?php

namespace Tests\Feature;

use App\Services\FeedFetcher;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class FeedFetcherTest extends TestCase
{
    public function test_it_fetches_and_parses_a_valid_feed(): void
    {
        Http::fake([
            'https://example.com/feed.xml' => Http::response($this->validFeedXml(), 200),
        ]);

        $fetcher = new FeedFetcher();

        $xml = $fetcher->fetch('https://example.com/feed.xml');

        $this->assertSame('My Podcast Show', (string) $xml->channel->title);
        $this->assertSame('A show about shipping.', (string) $xml->channel->description);
    }

    public function test_it_registers_the_itunes_namespace_on_the_xml_object(): void
    {
        Http::fake([
            'https://example.com/feed.xml' => Http::response($this->validFeedXml(), 200),
        ]);

        $fetcher = new FeedFetcher();
        $xml = $fetcher->fetch('https://example.com/feed.xml');

        $itunesImages = $xml->xpath('//itunes:image');

        $this->assertIsArray($itunesImages);
        $this->assertCount(1, $itunesImages);
        $this->assertSame(
            'https://example.com/artwork.jpg',
            (string) $itunesImages[0]->attributes()->href,
        );
    }

    public function test_it_throws_when_the_http_response_is_unsuccessful(): void
    {
        Http::fake([
            'https://example.com/feed.xml' => Http::response('Not Found', 404),
        ]);

        $fetcher = new FeedFetcher();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch feed from [https://example.com/feed.xml]. HTTP status: 404.');

        $fetcher->fetch('https://example.com/feed.xml');
    }

    public function test_it_throws_when_xml_parsing_fails(): void
    {
        Http::fake([
            'https://example.com/feed.xml' => Http::response('<rss><channel><title>Oops</channel></rss>', 200),
        ]);

        $fetcher = new FeedFetcher();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse feed XML from [https://example.com/feed.xml].');

        $fetcher->fetch('https://example.com/feed.xml');
    }

    private function validFeedXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">
    <channel>
        <title>My Podcast Show</title>
        <description>A show about shipping.</description>
        <itunes:image href="https://example.com/artwork.jpg" />
    </channel>
</rss>
XML;
    }
}
