<?php

namespace App\Services;

use App\Models\MetalXComment;
use App\Models\MetalXVideo;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeEngagementAiService
{
    protected $provider;

    protected $model;

    protected $apiKey;

    protected $temperature;

    protected $maxTokens;

    protected $sanitizer;

    public function __construct(InputSanitizerService $sanitizer)
    {
        $this->sanitizer = $sanitizer;
        $this->loadAiSettings();
    }

    /**
     * Load AI settings from database.
     */
    protected function loadAiSettings(): void
    {
        $this->provider = Setting::get('ai_provider', 'openai');
        $this->temperature = (float) Setting::get('ai_temperature', 0.7);
        $this->maxTokens = (int) Setting::get('ai_max_tokens', 1000);

        switch ($this->provider) {
            case 'openai':
                $this->apiKey = Setting::get('openai_api_key') ?: Setting::get('ai_openai_key');
                $this->model = Setting::get('openai_model', 'gpt-4o-mini');
                break;
            case 'claude':
                $this->apiKey = Setting::get('claude_api_key') ?: Setting::get('ai_claude_key');
                $this->model = Setting::get('claude_model', 'claude-3-haiku-20240307');
                break;
            case 'gemini':
                $this->apiKey = Setting::get('gemini_api_key') ?: Setting::get('ai_gemini_key');
                $this->model = Setting::get('gemini_model', 'gemini-2.0-flash');
                break;
            case 'ollama':
                $this->model = Setting::get('ollama_model', 'llama3.2');
                break;
        }
    }

    /**
     * Analyze sentiment of a comment.
     * Sanitized to prevent prompt injection attacks.
     */
    public function analyzeSentiment(MetalXComment $comment): array
    {
        // Sanitize user-provided content to prevent prompt injection
        $commentText = $this->sanitizer->sanitizeForPrompt($comment->text ?? '', 1000);
        $videoTitle = $this->sanitizer->sanitizeForPrompt($comment->video->title_en ?? '', 200);

        $prompt = <<<PROMPT
Analyze the sentiment and intent of this YouTube comment:

Comment: "{$commentText}"
Video: "{$videoTitle}"

Respond with JSON only:
{
  "sentiment": "positive|negative|neutral|question",
  "sentiment_score": 0-100,
  "is_spam": true|false,
  "requires_attention": true|false,
  "reason": "brief explanation"
}

Guidelines:
- positive: Praise, appreciation, positive feedback
- negative: Criticism, complaints, negative feedback
- neutral: General comments, observations
- question: Questions that need answers
- is_spam: Promotional, irrelevant, or spam content
- requires_attention: Needs human review or urgent response
PROMPT;

        try {
            $response = $this->callAi($prompt);
            $result = $this->parseJsonResponse($response);

            // Update comment with analysis
            $comment->update([
                'sentiment' => $result['sentiment'],
                'sentiment_score' => $result['sentiment_score'],
                'is_spam' => $result['is_spam'] ?? false,
                'requires_attention' => $result['requires_attention'] ?? false,
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error("Failed to analyze sentiment for comment {$comment->id}: " . $e->getMessage());

            return [
                'sentiment' => 'neutral',
                'sentiment_score' => 50,
                'is_spam' => false,
                'requires_attention' => false,
                'reason' => 'Analysis failed',
            ];
        }
    }

    /**
     * Detect gambling, unsafe content, and violations.
     * Sanitized to prevent prompt injection attacks.
     */
    public function detectViolation(MetalXComment $comment): array
    {
        // Sanitize user-provided content to prevent prompt injection
        $commentText = $this->sanitizer->sanitizeForPrompt($comment->text ?? '', 1000);
        $authorName = $this->sanitizer->sanitizeForPrompt($comment->author_name ?? '', 100);
        $videoTitle = $this->sanitizer->sanitizeForPrompt($comment->video->title_en ?? '', 200);

        $prompt = <<<PROMPT
Analyze this YouTube comment for policy violations and unsafe content:

Comment: "{$commentText}"
Author: {$authorName}
Video: "{$videoTitle}"

Detect the following violations:
1. **Gambling**: Casino, betting, gambling sites, poker, slots, sports betting
2. **Scam/Fraud**: Phishing, fake giveaways, "get rich quick", pyramid schemes
3. **Inappropriate**: Adult content, sexual content, violence, drugs
4. **Harassment**: Hate speech, bullying, threats, offensive language
5. **Spam**: Irrelevant links, promotional spam, repeated messages
6. **Impersonation**: Pretending to be someone else

Respond with JSON only:
{
  "is_violation": true|false,
  "violation_type": "gambling|scam|inappropriate|harassment|spam|impersonation|none",
  "severity": "low|medium|high|critical",
  "confidence": 0-100,
  "should_delete": true|false,
  "should_block": true|false,
  "reasoning": "why this is a violation"
}

Guidelines:
- high/critical severity: Immediate delete and block
- medium severity: Delete, block if repeat offender
- low severity: Warning, monitor
- Gambling keywords: พนัน, เดิมพัน, casino, bet, สล็อต, บาคาร่า, แทงบอล, etc.
- Be strict with gambling and scam content
- Consider context (Thai and English)
PROMPT;

        try {
            $response = $this->callAi($prompt);
            $result = $this->parseJsonResponse($response);

            // Update comment if violation detected
            if ($result['is_violation']) {
                $comment->update([
                    'violation_type' => $result['violation_type'],
                    'requires_attention' => true,
                    'is_spam' => in_array($result['violation_type'], ['spam', 'scam']),
                ]);
            }

            return $result;
        } catch (Exception $e) {
            Log::error("Failed to detect violation for comment {$comment->id}: " . $e->getMessage());

            return [
                'is_violation' => false,
                'violation_type' => 'none',
                'severity' => 'low',
                'confidence' => 0,
                'should_delete' => false,
                'should_block' => false,
                'reasoning' => 'Detection failed',
            ];
        }
    }

    /**
     * Quick pattern-based detection for common violations.
     */
    public function quickDetectGambling(string $text): bool
    {
        $gamblingPatterns = [
            // Thai keywords
            '/พนัน/ui',
            '/เดิมพัน/ui',
            '/สล็อต/ui',
            '/บาคาร่า/ui',
            '/แทงบอล/ui',
            '/คาสิโน/ui',
            '/เว็บพนัน/ui',
            '/แทงหวย/ui',
            '/รับเครดิต/ui',
            '/ฝาก.*ถอน/ui',
            '/โปรโมชั่น.*เครดิต/ui',

            // English keywords
            '/\bcasino\b/i',
            '/\bbetting\b/i',
            '/\bgambling\b/i',
            '/\bslots?\b/i',
            '/\bpoker\b/i',
            '/\bbaccarat\b/i',
            '/\broulette\b/i',
            '/\bsports ?bet/i',
            '/\b(?:bet|odds|wager).*(?:now|here|site)\b/i',
            '/\bdeposit.*bonus\b/i',
            '/\bfree.*credit\b/i',
            '/\bwin.*money\b/i',

            // URLs
            '/\b(?:bet|casino|poker|slots?)(?:365|777|888)\b/i',
            '/\.bet\b/i',
            '/\.casino\b/i',
        ];

        foreach ($gamblingPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate creative reply for a comment.
     * Sanitized to prevent prompt injection attacks.
     */
    public function generateReply(MetalXComment $comment): array
    {
        $video = $comment->video;

        // Sanitize all user-provided content
        $channelName = $this->sanitizer->sanitizeForPrompt(Setting::get('metalx_channel_name', 'Metal-X'), 100);
        $channelDescription = $this->sanitizer->sanitizeForPrompt(Setting::get('metalx_channel_description', 'A metal fabrication company'), 200);
        $videoTitle = $this->sanitizer->sanitizeForPrompt($video->title_en ?? '', 200);
        $videoDescription = $this->sanitizer->sanitizeForPrompt($video->description_en ?? '', 500);
        $authorName = $this->sanitizer->sanitizeForPrompt($comment->author_name ?? '', 100);
        $commentText = $this->sanitizer->sanitizeForPrompt($comment->text ?? '', 1000);
        $sentiment = in_array($comment->sentiment, ['positive', 'negative', 'neutral', 'question']) ? $comment->sentiment : 'neutral';

        $prompt = <<<PROMPT
You are the social media manager for {$channelName}, {$channelDescription}.

Generate a creative, engaging reply to this YouTube comment:

Video Title: "{$videoTitle}"
Video Description: "{$videoDescription}"
Comment by {$authorName}: "{$commentText}"
Sentiment: {$sentiment}

Guidelines for the reply:
1. Be friendly, professional, and authentic
2. Show appreciation for positive comments
3. Address questions with helpful information
4. Handle negative feedback constructively
5. Keep it concise (1-3 sentences)
6. Use appropriate emoji occasionally (don't overdo it)
7. Maintain Metal-X's brand voice: professional, innovative, customer-focused
8. If it's a question, provide helpful information or direct them to resources
9. For Thai users, you can use some Thai phrases to connect better

Respond with JSON only:
{
  "reply_text": "your reply here",
  "confidence_score": 0-100,
  "should_reply": true|false,
  "reasoning": "why this reply is appropriate"
}
PROMPT;

        try {
            $response = $this->callAi($prompt);
            $result = $this->parseJsonResponse($response);

            return [
                'success' => true,
                'reply_text' => $result['reply_text'],
                'confidence_score' => $result['confidence_score'],
                'should_reply' => $result['should_reply'] ?? true,
                'reasoning' => $result['reasoning'] ?? '',
            ];
        } catch (Exception $e) {
            Log::error("Failed to generate reply for comment {$comment->id}: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Improve video title and description.
     * Sanitized to prevent prompt injection attacks.
     */
    public function improveVideoContent(MetalXVideo $video): array
    {
        // Sanitize all user-provided content
        $channelName = $this->sanitizer->sanitizeForPrompt(Setting::get('metalx_channel_name', 'Metal-X'), 100);
        $titleEn = $this->sanitizer->sanitizeForPrompt($video->title_en ?? '', 200);
        $descriptionEn = $this->sanitizer->sanitizeForPrompt($video->description_en ?? '', 2000);
        $tags = $this->sanitizer->sanitizeForPrompt($video->tags ?? '', 500);
        $viewCount = (int) ($video->view_count ?? 0);
        $likeCount = (int) ($video->like_count ?? 0);

        $prompt = <<<PROMPT
You are a YouTube SEO specialist for {$channelName}, a metal fabrication and engineering company.

Current video content:
Title (EN): "{$titleEn}"
Description (EN): "{$descriptionEn}"
Tags: {$tags}
Views: {$viewCount}
Likes: {$likeCount}

Improve this content for better engagement and SEO:

1. Create a more compelling, click-worthy title (keep it under 60 characters)
2. Write an improved description with:
   - Engaging hook in first 2 lines
   - Detailed content description
   - Relevant keywords naturally integrated
   - Call-to-action
   - Timestamps if applicable
3. Suggest 15-20 relevant tags for better discoverability

Guidelines:
- Maintain accuracy and authenticity
- Use power words and emotional triggers appropriately
- Optimize for YouTube search and suggested videos
- Appeal to both Thai and international audiences
- Focus on Metal-X's expertise and value proposition

Respond with JSON only:
{
  "improved_title_en": "improved title",
  "improved_description_en": "improved description",
  "suggested_tags": ["tag1", "tag2", ...],
  "improvement_reasoning": "why these changes help",
  "estimated_impact": "low|medium|high"
}
PROMPT;

        try {
            $response = $this->callAi($prompt);
            $result = $this->parseJsonResponse($response);

            return [
                'success' => true,
                'improvements' => $result,
            ];
        } catch (Exception $e) {
            Log::error("Failed to improve content for video {$video->id}: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Improve captions/transcript.
     */
    public function improveCaptions(string $originalCaptions, MetalXVideo $video): array
    {
        $prompt = <<<PROMPT
You are a content editor specializing in video captions and subtitles.

Original captions/transcript:
{$originalCaptions}

Video: "{$video->title_en}"

Improve these captions by:
1. Fixing grammar and spelling errors
2. Improving readability and flow
3. Adding proper punctuation
4. Breaking into appropriate segments
5. Maintaining timing and context
6. Making it more engaging and clear

Respond with JSON only:
{
  "improved_captions": "improved caption text",
  "changes_made": ["list of key improvements"],
  "confidence_score": 0-100
}
PROMPT;

        try {
            $response = $this->callAi($prompt);
            $result = $this->parseJsonResponse($response);

            return [
                'success' => true,
                'improved_captions' => $result['improved_captions'],
                'changes_made' => $result['changes_made'],
                'confidence_score' => $result['confidence_score'],
            ];
        } catch (Exception $e) {
            Log::error("Failed to improve captions for video {$video->id}: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Batch analyze comments for a video.
     */
    public function analyzeVideoComments(MetalXVideo $video): array
    {
        $comments = $video->comments()
            ->topLevel()
            ->whereNull('sentiment')
            ->limit(50)
            ->get();

        $results = [];
        foreach ($comments as $comment) {
            $results[] = $this->analyzeSentiment($comment);
        }

        return $results;
    }

    /**
     * Determine if comment should be liked.
     */
    public function shouldLikeComment(MetalXComment $comment): bool
    {
        // Auto-like positive comments and questions
        if ($comment->sentiment === 'positive' || $comment->sentiment === 'question') {
            return true;
        }

        // Don't like spam or negative comments
        if ($comment->is_spam || $comment->sentiment === 'negative') {
            return false;
        }

        // For neutral comments, like if they have some engagement
        if ($comment->sentiment === 'neutral' && $comment->like_count >= 5) {
            return true;
        }

        return false;
    }

    /**
     * Call AI API.
     */
    protected function callAi(string $prompt): string
    {
        switch ($this->provider) {
            case 'openai':
                return $this->callOpenAi($prompt);
            case 'claude':
                return $this->callClaude($prompt);
            case 'gemini':
                return $this->callGemini($prompt);
            case 'ollama':
                return $this->callOllama($prompt);
            default:
                throw new Exception("Unsupported AI provider: {$this->provider}");
        }
    }

    /**
     * Call OpenAI API.
     */
    protected function callOpenAi(string $prompt): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional YouTube engagement specialist. Always respond with valid JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ]);

        if (! $response->successful()) {
            throw new Exception('OpenAI API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content');
    }

    /**
     * Call Claude API.
     */
    protected function callClaude(string $prompt): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new Exception('Claude API error: ' . $response->body());
        }

        return $response->json('content.0.text');
    }

    /**
     * Call Google Gemini API.
     */
    protected function callGemini(string $prompt): string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(60)->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [['text' => $prompt]],
                ],
            ],
            'systemInstruction' => [
                'parts' => [['text' => 'You are a professional YouTube engagement specialist. Always respond with valid JSON only.']],
            ],
            'generationConfig' => [
                'temperature' => $this->temperature,
                'maxOutputTokens' => $this->maxTokens,
            ],
        ]);

        if (! $response->successful()) {
            throw new Exception('Gemini API error: ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text');
    }

    /**
     * Call Ollama API.
     */
    protected function callOllama(string $prompt): string
    {
        $ollamaUrl = Setting::get('ollama_host', 'http://localhost:11434');

        $response = Http::timeout(120)->post("{$ollamaUrl}/api/generate", [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => $this->temperature,
            ],
        ]);

        if (! $response->successful()) {
            throw new Exception('Ollama API error: ' . $response->body());
        }

        return $response->json('response');
    }

    /**
     * Parse JSON response from AI.
     */
    protected function parseJsonResponse(string $response): array
    {
        // Remove markdown code blocks if present
        $response = preg_replace('/```json\s*|\s*```/', '', $response);
        $response = trim($response);

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from AI: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Check if AI is configured.
     */
    public function isConfigured(): bool
    {
        switch ($this->provider) {
            case 'openai':
            case 'claude':
            case 'gemini':
                return ! empty($this->apiKey) && ! empty($this->model);
            case 'ollama':
                return ! empty($this->model);
            default:
                return false;
        }
    }
}
