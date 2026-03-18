<?php

namespace App\Services;

use SimpleXMLElement;

class FeedChecker
{
    public function check(SimpleXMLElement $xml): array
    {
        return [
            $this->checkTitleLength($xml),
            $this->checkDescription($xml),
            $this->checkArtworkPresent($xml),
            $this->checkArtworkFormat($xml),
            $this->checkEpisodeCount($xml),
        ];
    }

    private function checkTitleLength(SimpleXMLElement $xml): array
    {
        $title = trim((string) $xml->channel->title);
        $length = mb_strlen($title);

        if ($length === 0) {
            return [
                'name' => 'Title Length',
                'status' => 'fail',
                'message' => 'Podcast title is empty.',
            ];
        }

        if ($length >= 30 && $length <= 60) {
            return [
                'name' => 'Title Length',
                'status' => 'pass',
                'message' => sprintf('Title length looks good (%d characters).', $length),
            ];
        }

        return [
            'name' => 'Title Length',
            'status' => 'warn',
            'message' => sprintf('Title length is %d characters; recommended range is 30-60.', $length),
        ];
    }

    private function checkDescription(SimpleXMLElement $xml): array
    {
        $description = trim((string) $xml->channel->description);
        $length = mb_strlen($description);

        if ($length === 0) {
            return [
                'name' => 'Description',
                'status' => 'fail',
                'message' => 'Podcast description is empty.',
            ];
        }

        if ($length < 50) {
            return [
                'name' => 'Description',
                'status' => 'warn',
                'message' => sprintf('Description is short (%d characters); aim for at least 50.', $length),
            ];
        }

        return [
            'name' => 'Description',
            'status' => 'pass',
            'message' => sprintf('Description length is solid (%d characters).', $length),
        ];
    }

    private function checkArtworkPresent(SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $images = $xml->xpath('//itunes:image');

        if (! is_array($images) || count($images) === 0) {
            return [
                'name' => 'Artwork Present',
                'status' => 'fail',
                'message' => 'No itunes:image artwork was found.',
            ];
        }

        return [
            'name' => 'Artwork Present',
            'status' => 'pass',
            'message' => 'Artwork is present.',
        ];
    }

    private function checkArtworkFormat(SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $images = $xml->xpath('//itunes:image');
        $imageUrl = '';

        if (is_array($images) && isset($images[0])) {
            $imageUrl = trim((string) $images[0]->attributes()->href);
        }

        $isSupported = $imageUrl !== '' && (bool) preg_match('/\.(jpg|png)$/i', $imageUrl);

        if ($isSupported) {
            return [
                'name' => 'Artwork Format',
                'status' => 'pass',
                'message' => sprintf('Artwork format looks valid (%s).', $imageUrl),
            ];
        }

        return [
            'name' => 'Artwork Format',
            'status' => 'warn',
            'message' => 'Artwork URL should end with .jpg or .png.',
        ];
    }

    private function checkEpisodeCount(SimpleXMLElement $xml): array
    {
        $episodeCount = isset($xml->channel->item) ? count($xml->channel->item) : 0;

        if ($episodeCount === 0) {
            return [
                'name' => 'Episode Count',
                'status' => 'fail',
                'message' => 'No episodes found in the feed.',
            ];
        }

        return [
            'name' => 'Episode Count',
            'status' => 'pass',
            'message' => sprintf('Feed has %d episode(s).', $episodeCount),
        ];
    }
}
