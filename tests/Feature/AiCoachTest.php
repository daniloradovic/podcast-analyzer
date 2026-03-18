<?php

namespace Tests\Feature;

use App\Services\AiCoach;
use Laravel\Ai\Ai;
use RuntimeException;
use Tests\TestCase;

class AiCoachTest extends TestCase
{
    public function test_it_filters_to_non_passing_checks_when_building_the_prompt(): void
    {
        $coach = new AiCoach();

        $checks = [
            ['name' => 'Title Length', 'status' => 'pass', 'message' => 'Good'],
            ['name' => 'Description', 'status' => 'warn', 'message' => 'Too short'],
            ['name' => 'Artwork Present', 'status' => 'fail', 'message' => 'Missing'],
        ];

        Ai::shouldReceive('text')
            ->once()
            ->withArgs(function (string $prompt): bool {
                $this->assertStringContainsString('Podcast title:', $prompt);
                $this->assertStringContainsString('My Podcast', $prompt);
                $this->assertStringContainsString('Podcast description:', $prompt);
                $this->assertStringContainsString('A show about startup lessons.', $prompt);
                $this->assertStringContainsString('[WARN] Description: Too short', $prompt);
                $this->assertStringContainsString('[FAIL] Artwork Present: Missing', $prompt);
                $this->assertStringNotContainsString('[PASS] Title Length: Good', $prompt);

                return true;
            })
            ->andReturn(new class
            {
                public function text(): string
                {
                    return 'AI coaching output';
                }
            });

        $result = $coach->analyse('My Podcast', 'A show about startup lessons.', $checks);

        $this->assertSame('AI coaching output', $result);
    }

    public function test_it_wraps_ai_failures_with_a_descriptive_exception(): void
    {
        $coach = new AiCoach();

        Ai::shouldReceive('text')
            ->once()
            ->andThrow(new RuntimeException('Provider timeout'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate AI coaching summary: Provider timeout');

        $coach->analyse('My Podcast', 'A show about startup lessons.', [
            ['name' => 'Description', 'status' => 'warn', 'message' => 'Too short'],
        ]);
    }
}
