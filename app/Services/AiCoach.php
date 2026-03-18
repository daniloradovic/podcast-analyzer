<?php

namespace App\Services;

use Laravel\Ai\Ai;
use Laravel\Ai\AnonymousAgent;
use RuntimeException;
use Throwable;

class AiCoach
{
    public function analyse(string $title, string $description, array $checks): string
    {
        $issues = array_values(array_filter($checks, function (array $check): bool {
            return ($check['status'] ?? null) !== 'pass';
        }));

        $prompt = $this->buildPrompt($title, $description, $issues);

        try {
            $response = $this->generateTextResponse($prompt);

            return $this->extractResponseText($response);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('Failed to generate AI coaching summary: %s', $exception->getMessage()),
                previous: $exception,
            );
        }
    }

    private function buildPrompt(string $title, string $description, array $issues): string
    {
        $issuesBlock = empty($issues)
            ? "- None. All checks passed.\n"
            : implode("\n", array_map(function (array $issue): string {
                return sprintf(
                    '- [%s] %s: %s',
                    strtoupper((string) ($issue['status'] ?? 'unknown')),
                    (string) ($issue['name'] ?? 'Unnamed Check'),
                    (string) ($issue['message'] ?? 'No details provided.'),
                );
            }, $issues));

        return trim(<<<PROMPT
You are an expert podcast growth coach.
Provide a short, practical coaching summary in 4-6 bullet points.
Focus on concrete next actions and prioritise the most impactful fixes first.

Podcast title:
{$title}

Podcast description:
{$description}

Checks that need attention:
{$issuesBlock}
PROMPT);
    }

    private function generateTextResponse(string $prompt): mixed
    {
        if ($this->canUseLegacyAiTextCall()) {
            return Ai::text($prompt);
        }

        return AnonymousAgent::make(
            'You are an expert podcast growth coach. Keep responses concise and actionable.',
            [],
            [],
        )->prompt($prompt);
    }

    private function canUseLegacyAiTextCall(): bool
    {
        $root = Ai::getFacadeRoot();

        return is_object($root) && method_exists($root, 'text');
    }

    private function extractResponseText(mixed $response): string
    {
        if (is_object($response) && method_exists($response, 'text')) {
            return (string) $response->text();
        }

        if (is_object($response) && property_exists($response, 'text')) {
            return (string) $response->text;
        }

        throw new RuntimeException('AI response did not contain text output.');
    }
}
