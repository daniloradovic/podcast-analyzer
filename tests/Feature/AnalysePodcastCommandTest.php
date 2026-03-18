<?php

namespace Tests\Feature;

use App\Services\AiCoach;
use App\Services\FeedChecker;
use App\Services\FeedFetcher;
use RuntimeException;
use SimpleXMLElement;
use Tests\TestCase;

class AnalysePodcastCommandTest extends TestCase
{
    public function test_it_analyses_a_feed_and_displays_score_checks_and_coaching_summary(): void
    {
        $xml = $this->makeFeedXml(
            title: 'Build Your SaaS Weekly',
            description: 'Practical lessons on growing a profitable bootstrapped SaaS business.',
        );

        $checks = [
            ['name' => 'Title Length', 'status' => 'pass', 'message' => 'Looks good.'],
            ['name' => 'Description', 'status' => 'warn', 'message' => 'Could be longer.'],
            ['name' => 'Artwork Present', 'status' => 'pass', 'message' => 'Artwork found.'],
            ['name' => 'Artwork Format', 'status' => 'warn', 'message' => 'Prefer .jpg or .png.'],
            ['name' => 'Episode Count', 'status' => 'fail', 'message' => 'No episodes found.'],
        ];

        $fetcher = $this->createMock(FeedFetcher::class);
        $fetcher->expects($this->once())
            ->method('fetch')
            ->with('https://example.com/feed.xml')
            ->willReturn($xml);
        $this->app->instance(FeedFetcher::class, $fetcher);

        $checker = $this->createMock(FeedChecker::class);
        $checker->expects($this->once())
            ->method('check')
            ->with($xml)
            ->willReturn($checks);
        $this->app->instance(FeedChecker::class, $checker);

        $coach = $this->createMock(AiCoach::class);
        $coach->expects($this->once())
            ->method('analyse')
            ->with(
                'Build Your SaaS Weekly',
                'Practical lessons on growing a profitable bootstrapped SaaS business.',
                $checks,
            )
            ->willReturn('- Prioritise publishing consistency this month.');
        $this->app->instance(AiCoach::class, $coach);

        $this->artisan('podcast:analyse', ['feed_url' => 'https://example.com/feed.xml'])
            ->expectsOutputToContain('Podcast Health Score: 75/100')
            ->expectsOutputToContain('Checks:')
            ->expectsOutputToContain('Title Length: Looks good.')
            ->expectsOutputToContain('Description: Could be longer.')
            ->expectsOutputToContain('Episode Count: No episodes found.')
            ->expectsOutputToContain('AI Coach Summary')
            ->expectsOutputToContain('Prioritise publishing consistency this month.')
            ->expectsOutputToContain('https://podcheck.dev')
            ->assertSuccessful();
    }

    public function test_it_returns_failure_when_feed_fetching_fails(): void
    {
        $fetcher = $this->createMock(FeedFetcher::class);
        $fetcher->expects($this->once())
            ->method('fetch')
            ->with('https://example.com/broken.xml')
            ->willThrowException(new RuntimeException('HTTP status 500.'));
        $this->app->instance(FeedFetcher::class, $fetcher);

        $this->artisan('podcast:analyse', ['feed_url' => 'https://example.com/broken.xml'])
            ->expectsOutputToContain('Failed to fetch feed: HTTP status 500.')
            ->assertFailed();
    }

    private function makeFeedXml(string $title, string $description): SimpleXMLElement
    {
        $xmlString = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">
    <channel>
        <title>{$title}</title>
        <description>{$description}</description>
    </channel>
</rss>
XML;

        $xml = simplexml_load_string($xmlString);
        $this->assertInstanceOf(SimpleXMLElement::class, $xml);

        return $xml;
    }
}
