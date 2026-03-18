<?php

namespace Tests\Feature;

use App\Services\FeedChecker;
use SimpleXMLElement;
use Tests\TestCase;

class FeedCheckerTest extends TestCase
{
    public function test_it_returns_all_five_check_results(): void
    {
        $checker = new FeedChecker();
        $results = $checker->check($this->makeFeedXml(
            title: 'This Podcast Title Is A Great Length For SEO',
            description: str_repeat('A', 60),
            artworkUrl: 'https://example.com/artwork.jpg',
            episodes: 2,
        ));

        $this->assertCount(5, $results);
        $this->assertSame(
            ['Title Length', 'Description', 'Artwork Present', 'Artwork Format', 'Episode Count'],
            array_column($results, 'name'),
        );
        $this->assertSame(['pass', 'pass', 'pass', 'pass', 'pass'], array_column($results, 'status'));
    }

    public function test_title_length_fails_when_empty(): void
    {
        $checker = new FeedChecker();
        $results = $checker->check($this->makeFeedXml(
            title: '   ',
            description: str_repeat('A', 60),
            artworkUrl: 'https://example.com/artwork.jpg',
            episodes: 1,
        ));

        $titleResult = $results[0];

        $this->assertSame('Title Length', $titleResult['name']);
        $this->assertSame('fail', $titleResult['status']);
    }

    public function test_description_fails_when_empty(): void
    {
        $checker = new FeedChecker();
        $results = $checker->check($this->makeFeedXml(
            title: 'This Podcast Title Is A Great Length For SEO',
            description: '',
            artworkUrl: 'https://example.com/artwork.jpg',
            episodes: 1,
        ));

        $descriptionResult = $results[1];

        $this->assertSame('Description', $descriptionResult['name']);
        $this->assertSame('fail', $descriptionResult['status']);
    }

    public function test_artwork_presence_fails_when_itunes_image_is_missing(): void
    {
        $checker = new FeedChecker();
        $results = $checker->check($this->makeFeedXml(
            title: 'This Podcast Title Is A Great Length For SEO',
            description: str_repeat('A', 60),
            artworkUrl: null,
            episodes: 1,
        ));

        $artworkPresentResult = $results[2];

        $this->assertSame('Artwork Present', $artworkPresentResult['name']);
        $this->assertSame('fail', $artworkPresentResult['status']);
    }

    public function test_artwork_format_warns_when_extension_is_not_jpg_or_png(): void
    {
        $checker = new FeedChecker();
        $results = $checker->check($this->makeFeedXml(
            title: 'This Podcast Title Is A Great Length For SEO',
            description: str_repeat('A', 60),
            artworkUrl: 'https://example.com/artwork.gif',
            episodes: 1,
        ));

        $artworkFormatResult = $results[3];

        $this->assertSame('Artwork Format', $artworkFormatResult['name']);
        $this->assertSame('warn', $artworkFormatResult['status']);
    }

    public function test_episode_count_fails_when_no_episodes_exist(): void
    {
        $checker = new FeedChecker();
        $results = $checker->check($this->makeFeedXml(
            title: 'This Podcast Title Is A Great Length For SEO',
            description: str_repeat('A', 60),
            artworkUrl: 'https://example.com/artwork.jpg',
            episodes: 0,
        ));

        $episodeCountResult = $results[4];

        $this->assertSame('Episode Count', $episodeCountResult['name']);
        $this->assertSame('fail', $episodeCountResult['status']);
    }

    private function makeFeedXml(string $title, string $description, ?string $artworkUrl, int $episodes): SimpleXMLElement
    {
        $artworkNode = $artworkUrl !== null
            ? sprintf('<itunes:image href="%s" />', $artworkUrl)
            : '';

        $items = '';
        for ($index = 1; $index <= $episodes; $index++) {
            $items .= sprintf('<item><title>Episode %d</title></item>', $index);
        }

        $xmlString = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">
    <channel>
        <title>{$title}</title>
        <description>{$description}</description>
        {$artworkNode}
        {$items}
    </channel>
</rss>
XML;

        $xml = simplexml_load_string($xmlString);
        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
        $xml->registerXPathNamespace('itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');

        return $xml;
    }
}
