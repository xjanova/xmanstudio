<?php

namespace App\Support;

/**
 * Payload compatibility for the OpenAI chat-completions API.
 *
 * The GPT-5 and o-series families changed two request parameters: `max_tokens`
 * was replaced by `max_completion_tokens`, and `temperature` now only accepts
 * its default value of 1. Sending the classic pair to one of those models
 * returns HTTP 400, so callers must build their tuning params through here
 * instead of hard-coding `max_tokens` + `temperature`.
 *
 * Older models (gpt-4o, gpt-4.1, …) still take the classic pair, and Groq's
 * OpenAI-compatible endpoint accepts it too, so both shapes stay supported.
 */
class OpenAiCompat
{
    /**
     * Smallest completion budget granted to a reasoning model.
     *
     * `max_completion_tokens` covers reasoning tokens on top of the visible
     * answer, so a budget sized for an older model can be spent entirely on
     * reasoning and come back with an empty message.
     */
    protected const REASONING_TOKEN_FLOOR = 2000;

    /**
     * Whether the model belongs to the GPT-5 / o-series reasoning families.
     */
    public static function isReasoningModel(string $model): bool
    {
        return (bool) preg_match('/^(gpt-5|o[1-9])/i', trim($model));
    }

    /**
     * Build the token-limit and temperature keys the given model accepts.
     *
     * @return array<string, float|int>
     */
    public static function tuningParams(string $model, int $maxTokens, float $temperature): array
    {
        if (static::isReasoningModel($model)) {
            return ['max_completion_tokens' => max($maxTokens, static::REASONING_TOKEN_FLOOR)];
        }

        return [
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];
    }
}
