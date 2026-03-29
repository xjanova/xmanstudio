<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AiCrawlSetting extends Model
{
    protected $fillable = [
        'enabled',
        'logging_enabled',
        'block_training_bots',
        'allow_assistant_bots',
        'allow_search_bots',
        'custom_bot_rules',
        'blocked_paths',
        'llms_txt_enabled',
        'llms_txt_content',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'logging_enabled' => 'boolean',
        'block_training_bots' => 'boolean',
        'allow_assistant_bots' => 'boolean',
        'allow_search_bots' => 'boolean',
        'custom_bot_rules' => 'array',
        'blocked_paths' => 'array',
        'llms_txt_enabled' => 'boolean',
    ];

    public static function getInstance(): self
    {
        return Cache::remember('ai_crawl_settings', 60, function () {
            $setting = self::first();

            if (! $setting) {
                $setting = self::create([
                    'enabled' => true,
                    'logging_enabled' => true,
                    'block_training_bots' => true,
                    'allow_assistant_bots' => true,
                    'allow_search_bots' => true,
                    'blocked_paths' => ['/admin/', '/customer/', '/api/', '/downloads/'],
                    'llms_txt_enabled' => true,
                ]);
            }

            return $setting;
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('ai_crawl_settings'));
    }

    /**
     * AI Bot definitions with categories.
     */
    public static function getKnownBots(): array
    {
        return [
            // Training bots - scrape for model training
            'GPTBot' => ['pattern' => 'GPTBot', 'category' => 'training', 'owner' => 'OpenAI'],
            'Google-Extended' => ['pattern' => 'Google-Extended', 'category' => 'training', 'owner' => 'Google'],
            'CCBot' => ['pattern' => 'CCBot', 'category' => 'training', 'owner' => 'Common Crawl'],
            'anthropic-ai' => ['pattern' => 'anthropic-ai', 'category' => 'training', 'owner' => 'Anthropic'],
            'cohere-ai' => ['pattern' => 'cohere-ai', 'category' => 'training', 'owner' => 'Cohere'],
            'Bytespider' => ['pattern' => 'Bytespider', 'category' => 'training', 'owner' => 'ByteDance'],
            'Meta-ExternalAgent' => ['pattern' => 'Meta-ExternalAgent', 'category' => 'training', 'owner' => 'Meta'],
            'Diffbot' => ['pattern' => 'Diffbot', 'category' => 'training', 'owner' => 'Diffbot'],
            'Omgilibot' => ['pattern' => 'Omgilibot', 'category' => 'training', 'owner' => 'Omgili'],
            'FacebookBot' => ['pattern' => 'FacebookBot', 'category' => 'training', 'owner' => 'Meta'],

            // Assistant bots - used in real-time AI assistants
            'ChatGPT-User' => ['pattern' => 'ChatGPT-User', 'category' => 'assistant', 'owner' => 'OpenAI'],
            'ClaudeBot' => ['pattern' => 'ClaudeBot', 'category' => 'assistant', 'owner' => 'Anthropic'],
            'PerplexityBot' => ['pattern' => 'PerplexityBot', 'category' => 'assistant', 'owner' => 'Perplexity'],
            'YouBot' => ['pattern' => 'YouBot', 'category' => 'assistant', 'owner' => 'You.com'],
            'Applebot-Extended' => ['pattern' => 'Applebot-Extended', 'category' => 'assistant', 'owner' => 'Apple'],

            // Search/AI Search bots
            'Googlebot' => ['pattern' => 'Googlebot', 'category' => 'search', 'owner' => 'Google'],
            'Bingbot' => ['pattern' => 'bingbot', 'category' => 'search', 'owner' => 'Microsoft'],
        ];
    }

    /**
     * Check if a bot should be blocked based on current settings.
     */
    public function shouldBlockBot(string $botName, string $category, string $path): bool
    {
        if (! $this->enabled) {
            return false;
        }

        // Check custom bot rules first
        if ($this->custom_bot_rules) {
            foreach ($this->custom_bot_rules as $rule) {
                if (($rule['bot_name'] ?? '') === $botName) {
                    return ($rule['action'] ?? 'allow') === 'block';
                }
            }
        }

        // Check blocked paths
        if ($this->blocked_paths) {
            foreach ($this->blocked_paths as $blockedPath) {
                if (str_starts_with($path, $blockedPath)) {
                    return true;
                }
            }
        }

        // Check category rules
        return match ($category) {
            'training' => $this->block_training_bots,
            'assistant' => ! $this->allow_assistant_bots,
            'search' => ! $this->allow_search_bots,
            default => false,
        };
    }

    /**
     * Generate robots.txt rules for AI bots.
     */
    public function generateRobotsTxtRules(): string
    {
        $lines = [];
        $bots = self::getKnownBots();

        // Group bots by whether they should be blocked
        foreach ($bots as $name => $info) {
            $category = $info['category'];
            $shouldBlock = match ($category) {
                'training' => $this->block_training_bots,
                'assistant' => ! $this->allow_assistant_bots,
                'search' => ! $this->allow_search_bots,
                default => false,
            };

            // Check custom rules override
            if ($this->custom_bot_rules) {
                foreach ($this->custom_bot_rules as $rule) {
                    if (($rule['bot_name'] ?? '') === $name) {
                        $shouldBlock = ($rule['action'] ?? 'allow') === 'block';
                        break;
                    }
                }
            }

            if ($shouldBlock) {
                $lines[] = "User-agent: {$name}";
                $lines[] = 'Disallow: /';
                $lines[] = '';
            } else {
                $lines[] = "User-agent: {$name}";
                if ($this->blocked_paths) {
                    foreach ($this->blocked_paths as $path) {
                        $lines[] = "Disallow: {$path}";
                    }
                }
                $lines[] = 'Allow: /';
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }
}
