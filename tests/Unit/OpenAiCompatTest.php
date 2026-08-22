<?php

namespace Tests\Unit;

use App\Support\OpenAiCompat;
use PHPUnit\Framework\TestCase;

/**
 * Guards the parameter split that took the public AI assistant down once:
 * GPT-5 rejects `max_tokens` outright and rejects any temperature but 1.
 */
class OpenAiCompatTest extends TestCase
{
    public static function reasoningModels(): array
    {
        return [
            ['gpt-5.6-luna'],
            ['gpt-5.6-sol'],
            ['gpt-5.5'],
            ['o1-mini'],
            ['o3'],
        ];
    }

    public static function classicModels(): array
    {
        return [
            ['gpt-4o-mini'],
            ['gpt-4o'],
            ['gpt-4.1-mini'],
            ['llama-3.3-70b-versatile'],
        ];
    }

    /**
     * @dataProvider reasoningModels
     */
    public function test_reasoning_models_get_only_the_completion_token_budget(string $model): void
    {
        $params = OpenAiCompat::tuningParams($model, 1000, 0.7);

        $this->assertArrayNotHasKey('max_tokens', $params);
        $this->assertArrayNotHasKey('temperature', $params);
        $this->assertArrayHasKey('max_completion_tokens', $params);
    }

    /**
     * @dataProvider classicModels
     */
    public function test_classic_models_keep_the_original_pair(string $model): void
    {
        $this->assertSame(
            ['max_tokens' => 1000, 'temperature' => 0.7],
            OpenAiCompat::tuningParams($model, 1000, 0.7),
        );
    }

    public function test_a_reasoning_budget_is_raised_to_leave_room_for_reasoning_tokens(): void
    {
        // A 1000-token budget can be spent entirely on hidden reasoning and
        // return an empty message, so the floor keeps room for the answer.
        $this->assertSame(2000, OpenAiCompat::tuningParams('gpt-5.6-luna', 1000, 1.0)['max_completion_tokens']);
    }

    public function test_a_budget_above_the_floor_is_respected(): void
    {
        $this->assertSame(8000, OpenAiCompat::tuningParams('gpt-5.6-luna', 8000, 1.0)['max_completion_tokens']);
    }
}
