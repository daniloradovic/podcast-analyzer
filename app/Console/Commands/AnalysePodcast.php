<?php

namespace App\Console\Commands;

use App\Services\AiCoach;
use App\Services\FeedChecker;
use App\Services\FeedFetcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

#[Signature('podcast:analyse {feed_url : The RSS feed URL to analyse}')]
#[Description('Analyse a podcast RSS feed and get an AI coaching summary')]
class AnalysePodcast extends Command
{
    public function handle(FeedFetcher $fetcher, FeedChecker $checker, AiCoach $coach): int
    {
        $url = (string) $this->argument('feed_url');

        try {
            $xml = $fetcher->fetch($url);
        } catch (Throwable $exception) {
            $this->error(sprintf('Failed to fetch feed: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        $title = trim((string) $xml->channel->title);
        $description = trim((string) $xml->channel->description);
        $checks = $checker->check($xml);

        $score = $this->calculateScore($checks);
        $scoreColor = $score >= 80 ? 'green' : ($score >= 50 ? 'yellow' : 'red');
        $passCount = count(array_filter($checks, fn (array $check): bool => ($check['status'] ?? null) === 'pass'));
        $warnCount = count(array_filter($checks, fn (array $check): bool => ($check['status'] ?? null) === 'warn'));
        $failCount = count(array_filter($checks, fn (array $check): bool => ($check['status'] ?? null) === 'fail'));

        $this->line(sprintf('<fg=%s>Podcast Health Score: %d/100</>', $scoreColor, $score));
        $this->line(sprintf('Checks: %d pass | %d warn | %d fail', $passCount, $warnCount, $failCount));
        $this->newLine();

        foreach ($checks as $check) {
            $status = (string) ($check['status'] ?? 'warn');
            $name = (string) ($check['name'] ?? 'Unnamed Check');
            $message = (string) ($check['message'] ?? 'No details provided.');

            [$icon, $color] = match ($status) {
                'pass' => ['✓', 'green'],
                'fail' => ['✗', 'red'],
                default => ['⚠', 'yellow'],
            };

            $this->line(sprintf('<fg=%s>%s %s: %s</>', $color, $icon, $name, $message));
        }

        $this->newLine();
        $this->line('<options=bold>AI Coach Summary</>');

        try {
            $coaching = $coach->analyse($title, $description, $checks);
            $this->line($coaching);
        } catch (RuntimeException $exception) {
            $this->error(sprintf('Unable to generate AI coaching summary: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Run a full 68-check podcast audit at https://podcheck.dev');

        return self::SUCCESS;
    }

    private function calculateScore(array $checks): int
    {
        $score = 100;

        foreach ($checks as $check) {
            $score -= match ($check['status'] ?? null) {
                'fail' => 15,
                'warn' => 5,
                default => 0,
            };
        }

        return max(0, $score);
    }
}
