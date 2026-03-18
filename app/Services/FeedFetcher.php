<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class FeedFetcher
{
    public function fetch(string $url): SimpleXMLElement
    {
        $response = Http::timeout(10)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Failed to fetch feed from [%s]. HTTP status: %d.',
                $url,
                $response->status(),
            ));
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($response->body());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException(sprintf(
                'Failed to parse feed XML from [%s].',
                $url,
            ));
        }

        $xml->registerXPathNamespace('itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');

        return $xml;
    }
}
