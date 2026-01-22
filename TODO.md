# Metal-X YouTube AI System - TODO & Work Summary

## 📋 สถานะปัจจุบัน

**Branch:** `claude/ai-youtube-metadata-dOn4u`
**Latest Commit:** `432dff5` - Production readiness documentation
**Overall Status:** ✅ ฟีเจอร์ครบ แต่ ⚠️ ยังไม่พร้อม Production (ต้องแก้ Security & Performance)
**Production Ready Score:** 6.5/10

---

## ✅ งานที่ทำเสร็จแล้ว

### 1. AI-Powered Metadata Generation (Commit: 866c9aa)
- ✅ `YouTubeMetadataAiService` - สร้าง metadata ภาษาไทยอัตโนมัติ
- ✅ `GenerateVideoMetadataJob` - Queue job สำหรับ async processing
- ✅ `MetalXAiController` - จัดการ AI metadata
- ✅ รองรับ 3 AI providers: OpenAI, Claude, Ollama
- ✅ Confidence scoring & approval workflow
- ✅ Auto-generate on video import
- ✅ Batch generation ได้
- ✅ UI Dashboard (`/admin/metal-x/ai`)

**ไฟล์ที่สร้าง:**
- `app/Services/YouTubeMetadataAiService.php`
- `app/Jobs/GenerateVideoMetadataJob.php`
- `app/Http/Controllers/Admin/MetalXAiController.php`
- `database/migrations/2026_01_22_160000_add_ai_metadata_to_metal_x_videos_table.php`
- `resources/views/admin/metal-x/ai-tools.blade.php`

**ไฟล์ที่แก้:**
- `app/Models/MetalXVideo.php`
- `app/Http/Controllers/Admin/MetalXVideoController.php`
- `routes/web.php`

---

### 2. AI-Powered YouTube Engagement System (Commit: 578a580)
- ✅ `YouTubeCommentService` - ดึง/จัดการคอมเมนต์
- ✅ `YouTubeEngagementAiService` - Sentiment analysis & reply generation
- ✅ `ProcessCommentEngagementJob` - Auto-reply & auto-like
- ✅ `SyncVideoCommentsJob` - Sync comments from YouTube
- ✅ `MetalXComment` model - เก็บคอมเมนต์พร้อม metadata
- ✅ Sentiment analysis (positive/negative/neutral/question)
- ✅ Auto-reply with creative responses
- ✅ Auto-like comments
- ✅ Batch processing
- ✅ UI Dashboard (`/admin/metal-x/engagement`)

**ไฟล์ที่สร้าง:**
- `app/Services/YouTubeCommentService.php`
- `app/Services/YouTubeEngagementAiService.php`
- `app/Jobs/ProcessCommentEngagementJob.php`
- `app/Jobs/SyncVideoCommentsJob.php`
- `app/Models/MetalXComment.php`
- `database/migrations/2026_01_22_170000_create_metal_x_comments_table.php`
- `resources/views/admin/metal-x/engagement.blade.php`

**ฟีเจอร์:**
- Caption/transcript fetching และ improvement
- Video content improvement (title, description, tags)
- Spam detection

---

### 3. Advanced Content Moderation & Blacklist (Commit: 2a30196)
- ✅ `MetalXBlacklist` model - จัดการบัญชีดำ
- ✅ `AutoModerateCommentJob` - Auto-moderate อัตโนมัติ
- ✅ AI violation detection (gambling, scam, inappropriate, harassment, spam, impersonation)
- ✅ Pattern-based quick detection (Thai + English gambling keywords)
- ✅ Auto-block repeat offenders
- ✅ Delete comment from YouTube
- ✅ Block channel and delete all their comments
- ✅ Blacklist management UI (`/admin/metal-x/engagement/blacklist`)

**ไฟล์ที่สร้าง:**
- `app/Models/MetalXBlacklist.php`
- `app/Jobs/AutoModerateCommentJob.php`
- `database/migrations/2026_01_22_180000_create_metal_x_blacklist_table.php`
- `resources/views/admin/metal-x/blacklist.blade.php`

**ไฟล์ที่แก้:**
- `app/Services/YouTubeEngagementAiService.php` - เพิ่ม detectViolation(), quickDetectGambling()
- `app/Services/YouTubeCommentService.php` - เพิ่ม deleteComment(), blockAndDeleteChannel()
- `app/Http/Controllers/Admin/MetalXEngagementController.php` - เพิ่ม moderation endpoints
- `app/Models/MetalXComment.php` - เพิ่ม blacklist fields
- `resources/views/admin/metal-x/engagement.blade.php` - เพิ่มปุ่ม moderation
- `routes/web.php`

---

### 4. Production Readiness Documentation (Commit: 432dff5)
- ✅ `PRODUCTION_READINESS.md` - เอกสารครบถ้วน
- ✅ รายการปัญหาทั้งหมด (Critical, High, Medium, Low)
- ✅ Code examples สำหรับแก้ไข
- ✅ Timeline 3 สัปดาห์
- ✅ Checklist สำหรับ deployment

---

## 🚨 ปัญหาวิกฤตที่ต้องแก้ก่อน Production

### CRITICAL Issues (ต้องแก้ก่อนใช้งาน)

#### 1. ❌ XSS Vulnerability in Dynamic CSS Classes
**ไฟล์:** `resources/views/admin/metal-x/engagement.blade.php` หลายจุด
**ปัญหา:**
```blade
<span class="px-2 py-1 bg-{{ $color }}-100 text-{{ $color }}-700">
```
**วิธีแก้:**
```blade
@php
    $validColors = ['green', 'red', 'yellow', 'purple', 'gray', 'blue', 'pink', 'orange', 'indigo'];
    $color = in_array($color, $validColors) ? $color : 'gray';
@endphp
<span class="px-2 py-1 bg-{{ $color }}-100 text-{{ $color }}-700">
```
**หน้าที่ต้องแก้:**
- `resources/views/admin/metal-x/engagement.blade.php` (line 145, 360+)
- `resources/views/admin/metal-x/blacklist.blade.php` (line 68+)

---

#### 2. ❌ Prompt Injection - No Input Sanitization
**ไฟล์:** `app/Services/YouTubeMetadataAiService.php:107-145`
**ปัญหา:** ข้อมูลจาก database ถูกใส่ลง AI prompt โดยไม่ sanitize
**วิธีแก้:**

**สร้างไฟล์:** `app/Services/InputSanitizerService.php`
```php
<?php

namespace App\Services;

use Illuminate\Support\Str;

class InputSanitizerService
{
    /**
     * Sanitize text for AI prompts
     */
    public function sanitizeForPrompt(string $text, int $maxLength = 5000): string
    {
        // Remove HTML tags
        $text = strip_tags($text);

        // Remove special characters that could break prompts
        $text = preg_replace('/[^\p{L}\p{N}\s\-_.,!?()\[\]\'\"]/u', '', $text);

        // Remove multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);

        // Limit length
        $text = Str::limit($text, $maxLength, '...');

        return trim($text);
    }

    /**
     * Sanitize HTML content
     */
    public function sanitizeHtml(string $html): string
    {
        // Strip all tags except safe ones
        $allowed = '<p><br><strong><em><ul><ol><li>';
        return strip_tags($html, $allowed);
    }

    /**
     * Remove potentially malicious URLs
     */
    public function removeSuspiciousUrls(string $text): string
    {
        $suspiciousPatterns = [
            '/bit\.ly\/[\w-]+/i',
            '/tinyurl\.com\/[\w-]+/i',
            '/short\.link\/[\w-]+/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            $text = preg_replace($pattern, '[URL removed]', $text);
        }

        return $text;
    }
}
```

**แก้ไข:** `app/Services/YouTubeMetadataAiService.php`
```php
use App\Services\InputSanitizerService;

protected $sanitizer;

public function __construct()
{
    $this->loadAiSettings();
    $this->sanitizer = new InputSanitizerService();
}

protected function buildMetadataPrompt(MetalXVideo $video): string
{
    $channelName = Setting::get('metalx_channel_name', 'Metal-X');

    // Sanitize inputs
    $titleEn = $this->sanitizer->sanitizeForPrompt($video->title_en, 200);
    $descriptionEn = $this->sanitizer->sanitizeForPrompt($video->description_en, 2000);

    return <<<PROMPT
You are a professional YouTube content specialist for {$channelName}...

**English Title:** {$titleEn}
**English Description:** {$descriptionEn}
...
PROMPT;
}
```

**ไฟล์ที่ต้องแก้:**
- `app/Services/YouTubeMetadataAiService.php` - ทุก method ที่สร้าง prompt
- `app/Services/YouTubeEngagementAiService.php` - ทุก method ที่สร้าง prompt

---

#### 3. ❌ API Keys Not Encrypted
**ไฟล์:** Database - `settings` table
**ปัญหา:** API keys เก็บแบบ plaintext
**วิธีแก้:**

เพิ่มใน `app/Models/Setting.php`:
```php
protected $casts = [
    'ai_openai_key' => 'encrypted',
    'ai_claude_key' => 'encrypted',
    'ai_ollama_url' => 'encrypted',
    'metalx_youtube_api_key' => 'encrypted',
    'metalx_youtube_access_token' => 'encrypted',
];
```

**⚠️ หมายเหตุ:** หลังแก้ไขต้อง re-save settings ทั้งหมดเพื่อ encrypt

---

#### 4. ❌ N+1 Query Problem
**ไฟล์:** `app/Services/YouTubeMetadataAiService.php:79-102`
**ปัญหา:**
```php
foreach ($videoIds as $videoId) {
    $video = MetalXVideo::find($videoId); // Query in loop!
}
```

**วิธีแก้:**
```php
public function generateBatchMetadata(array $videoIds): array
{
    $results = [];

    // FIX: Load all videos at once
    $videos = MetalXVideo::findMany($videoIds);

    foreach ($videos as $video) {
        $results[$video->id] = $this->generateMetadata($video);

        if ($results[$video->id]['success']) {
            $this->saveMetadata($video, $results[$video->id]['metadata'], $results[$video->id]['confidence']);
        }
    }

    return $results;
}
```

---

#### 5. ❌ No Rate Limiting
**ไฟล์:** `routes/web.php` และ `app/Providers/RouteServiceProvider.php`
**วิธีแก้:**

**ใน `app/Providers/RouteServiceProvider.php`:**
```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

protected function configureRateLimiting()
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // AI Operations rate limiting
    RateLimiter::for('ai-operations', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()->id);
    });

    // YouTube API operations
    RateLimiter::for('youtube-operations', function (Request $request) {
        return Limit::perMinute(20)->by($request->user()->id);
    });
}
```

**ใน `routes/web.php`:**
```php
// AI Tools
Route::prefix('ai')->name('ai.')->middleware(['throttle:ai-operations'])->group(function () {
    Route::get('/', [MetalXAiController::class, 'index'])->name('index');
    Route::post('/{video}/generate', [MetalXAiController::class, 'generateSingle'])->name('generate-single');
    Route::post('/generate-batch', [MetalXAiController::class, 'generateBatch'])->name('generate-batch');
    Route::post('/generate-all', [MetalXAiController::class, 'generateAll'])->name('generate-all');
    // ... rest
});

// Engagement
Route::prefix('engagement')->name('engagement.')->middleware(['throttle:youtube-operations'])->group(function () {
    // ...
});
```

---

#### 6. ❌ No Caching for Settings
**ไฟล์:** `app/Services/YouTubeMetadataAiService.php` และอื่นๆ
**ปัญหา:** Query settings จาก DB ทุกครั้ง
**วิธีแก้:**

```php
protected function loadAiSettings(): void
{
    $this->provider = Cache::remember('ai_provider', 3600, function () {
        return Setting::get('ai_provider', 'openai');
    });

    $this->temperature = (float) Cache::remember('ai_temperature', 3600, function () {
        return Setting::get('ai_temperature', 0.7);
    });

    $this->maxTokens = (int) Cache::remember('ai_max_tokens', 3600, function () {
        return Setting::get('ai_max_tokens', 2000);
    });

    // ... rest
}
```

**IMPORTANT:** ต้อง invalidate cache เมื่อ update settings!

**ใน Settings Controller:**
```php
public function update(Request $request)
{
    // Save settings...

    // Invalidate cache
    Cache::forget('ai_provider');
    Cache::forget('ai_temperature');
    Cache::forget('ai_max_tokens');
    // ... ทั้งหมด

    return back()->with('success', 'Settings updated');
}
```

---

#### 7. ❌ Missing Database Transactions
**ไฟล์:** `app/Services/YouTubeCommentService.php:364-424`
**ปัญหา:** `blockAndDeleteChannel()` update หลายที่โดยไม่มี transaction
**วิธีแก้:**

```php
use Illuminate\Support\Facades\DB;

public function blockAndDeleteChannel(
    MetalXComment $comment,
    string $reason,
    ?int $blockedBy = null
): array {
    return DB::transaction(function () use ($comment, $reason, $blockedBy) {
        if (!$comment->author_channel_id) {
            throw new Exception('Comment does not have author channel ID');
        }

        // Add to blacklist
        $blacklistEntry = MetalXBlacklist::addToBlacklist(
            $comment->author_channel_id,
            $comment->author_name,
            $reason,
            "Auto-blocked for: {$reason}",
            $blockedBy
        );

        // Find all comments from this author
        $allComments = MetalXComment::where('author_channel_id', $comment->author_channel_id)
            ->whereNull('deleted_at')
            ->get();

        $deleted = 0;
        $failed = 0;

        foreach ($allComments as $authorComment) {
            try {
                $authorComment->update([
                    'is_blacklisted_author' => true,
                    'violation_type' => $reason,
                ]);

                if ($this->deleteComment($authorComment)) {
                    $deleted++;
                } else {
                    $authorComment->update([
                        'deleted_at' => now(),
                        'is_hidden' => true,
                    ]);
                    $failed++;
                }
            } catch (Exception $e) {
                Log::error("Failed to delete comment {$authorComment->id}: " . $e->getMessage());
                $failed++;
            }
        }

        Log::info("Blocked channel {$comment->author_channel_id}. Deleted {$deleted} comments, {$failed} failed.");

        return [
            'blocked' => true,
            'blacklist_entry' => $blacklistEntry,
            'total_comments' => count($allComments),
            'deleted' => $deleted,
            'failed' => $failed,
        ];
    });
}
```

---

#### 8. ❌ Error Message Leakage
**ไฟล์:** ทุก Controller
**ปัญหา:**
```php
catch (\Exception $e) {
    return response()->json(['message' => $e->getMessage()], 500);
}
```

**วิธีแก้:**

**สร้าง:** `app/Exceptions/AIServiceException.php`
```php
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class AIServiceException extends Exception
{
    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'AI service is temporarily unavailable. Please try again later.',
            'error_code' => 'AI_SERVICE_UNAVAILABLE'
        ], 503);
    }
}
```

**สร้าง:** `app/Exceptions/YouTubeAPIException.php`
```php
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class YouTubeAPIException extends Exception
{
    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unable to connect to YouTube. Please try again later.',
            'error_code' => 'YOUTUBE_API_ERROR'
        ], 503);
    }
}
```

**แก้ไขใน Controllers:**
```php
use App\Exceptions\AIServiceException;
use App\Exceptions\YouTubeAPIException;

try {
    // ... operation
} catch (AIServiceException $e) {
    throw $e; // Let exception handler deal with it
} catch (YouTubeAPIException $e) {
    throw $e;
} catch (\Exception $e) {
    Log::error('Unexpected error', [
        'exception' => $e,
        'user' => auth()->id(),
        'request' => $request->all()
    ]);

    return response()->json([
        'success' => false,
        'message' => 'An unexpected error occurred. Please contact support if this persists.',
        'error_code' => 'INTERNAL_ERROR'
    ], 500);
}
```

---

## ⚠️ HIGH-PRIORITY Issues (ควรแก้)

### 1. ⚠️ Soft Delete Implementation
**ไฟล์:** `app/Models/MetalXComment.php`
**ปัญหา:** มี `deleted_at` แต่ไม่ใช้ `SoftDeletes` trait

**วิธีแก้:**
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class MetalXComment extends Model
{
    use SoftDeletes;

    // Remove deleted_at from $fillable and $casts
    // SoftDeletes trait handles it automatically
}
```

**Update queries:**
```php
// Show all including deleted
MetalXComment::withTrashed()->get();

// Only deleted
MetalXComment::onlyTrashed()->get();

// Restore
$comment->restore();

// Force delete
$comment->forceDelete();
```

---

### 2. ⚠️ Input Validation Rules
**สร้างไฟล์:** `app/Rules/NoScriptTags.php`
```php
<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class NoScriptTags implements Rule
{
    public function passes($attribute, $value): bool
    {
        return !preg_match('/<script|<iframe|javascript:|on\w+=/i', $value);
    }

    public function message(): string
    {
        return 'The :attribute contains forbidden content.';
    }
}
```

**สร้างไฟล์:** `app/Rules/NoMaliciousUrls.php`
```php
<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class NoMaliciousUrls implements Rule
{
    public function passes($attribute, $value): bool
    {
        $suspiciousPatterns = [
            '/bit\.ly|tinyurl|short\.link/i',
            '/\.exe|\.bat|\.cmd|\.sh/i',
            '/data:text\/html/i',
            '/javascript:/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return 'The :attribute contains suspicious URLs.';
    }
}
```

**Update Controllers:**
```php
use App\Rules\NoScriptTags;
use App\Rules\NoMaliciousUrls;

public function postReply(Request $request, MetalXComment $comment)
{
    $request->validate([
        'reply_text' => [
            'required',
            'string',
            'max:10000',
            new NoScriptTags,
            new NoMaliciousUrls,
        ],
    ]);
    // ...
}

public function batchProcess(Request $request)
{
    $request->validate([
        'comment_ids' => 'required|array|max:100', // Limit batch size!
        'comment_ids.*' => 'exists:metal_x_comments,id',
        'action' => 'required|in:reply,like,spam,attention',
    ]);
    // ...
}
```

---

### 3. ⚠️ Circuit Breaker Pattern
**สร้างไฟล์:** `app/Services/CircuitBreaker.php`
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    const FAILURE_THRESHOLD = 5;
    const TIMEOUT = 300; // 5 minutes

    public static function recordFailure(string $service): void
    {
        $key = "circuit_breaker_{$service}_failures";
        $failures = Cache::get($key, 0);

        Cache::put($key, $failures + 1, self::TIMEOUT);

        if ($failures + 1 >= self::FAILURE_THRESHOLD) {
            Cache::put("circuit_breaker_{$service}_open", true, self::TIMEOUT);
        }
    }

    public static function isOpen(string $service): bool
    {
        return Cache::get("circuit_breaker_{$service}_open", false);
    }

    public static function recordSuccess(string $service): void
    {
        Cache::forget("circuit_breaker_{$service}_failures");
        Cache::forget("circuit_breaker_{$service}_open");
    }

    public static function reset(string $service): void
    {
        self::recordSuccess($service);
    }
}
```

**Update AI Services:**
```php
use App\Services\CircuitBreaker;
use App\Exceptions\AIServiceException;

protected function callAi(string $prompt): string
{
    if (CircuitBreaker::isOpen('ai_service')) {
        throw new AIServiceException('AI service is temporarily unavailable (circuit breaker open)');
    }

    try {
        $response = $this->callOpenAi($prompt);
        CircuitBreaker::recordSuccess('ai_service');
        return $response;
    } catch (Exception $e) {
        CircuitBreaker::recordFailure('ai_service');
        throw new AIServiceException('AI service error: ' . $e->getMessage());
    }
}
```

---

### 4. ⚠️ Database Composite Indexes
**สร้าง migration:** `database/migrations/2026_01_22_190000_add_composite_indexes.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metal_x_comments', function (Blueprint $table) {
            // For filtering by video + sentiment + reply status
            $table->index(
                ['video_id', 'sentiment', 'ai_replied'],
                'video_sentiment_replied_idx'
            );

            // For blacklist checks
            $table->index(
                ['author_channel_id', 'deleted_at'],
                'author_deleted_idx'
            );

            // For pending engagement
            $table->index(
                ['ai_replied', 'is_spam', 'is_hidden', 'can_reply'],
                'engagement_status_idx'
            );

            // For finding comments from same author
            $table->index(['author_channel_id', 'published_at'], 'author_time_idx');
        });

        Schema::table('metal_x_videos', function (Blueprint $table) {
            // For finding videos needing AI metadata
            $table->index(
                ['ai_generated', 'ai_approved', 'is_active'],
                'ai_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('metal_x_comments', function (Blueprint $table) {
            $table->dropIndex('video_sentiment_replied_idx');
            $table->dropIndex('author_deleted_idx');
            $table->dropIndex('engagement_status_idx');
            $table->dropIndex('author_time_idx');
        });

        Schema::table('metal_x_videos', function (Blueprint $table) {
            $table->dropIndex('ai_status_idx');
        });
    }
};
```

---

### 5. ⚠️ Configuration File
**สร้างไฟล์:** `config/metalx.php`
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'providers' => ['openai', 'claude', 'ollama'],
        'default_provider' => env('AI_PROVIDER', 'openai'),
        'temperature' => (float) env('AI_TEMPERATURE', 0.7),
        'max_tokens' => (int) env('AI_MAX_TOKENS', 2000),
        'timeout' => (int) env('AI_TIMEOUT', 60),
        'auto_reply_min_confidence' => (int) env('AI_AUTO_REPLY_MIN_CONFIDENCE', 75),
        'retry_attempts' => (int) env('AI_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | YouTube Configuration
    |--------------------------------------------------------------------------
    */
    'youtube' => [
        'api_key' => env('METALX_YOUTUBE_API_KEY'),
        'access_token' => env('METALX_YOUTUBE_ACCESS_TOKEN'),
        'channel_id' => env('METALX_CHANNEL_ID'),
        'auto_engagement' => env('METALX_AUTO_ENGAGEMENT', false),
        'comments_per_sync' => (int) env('METALX_COMMENTS_PER_SYNC', 50),
        'max_batch_size' => (int) env('METALX_MAX_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Moderation Configuration
    |--------------------------------------------------------------------------
    */
    'moderation' => [
        'auto_block_repeat_offenders' => env('METALX_AUTO_BLOCK_REPEAT', true),
        'min_violations_for_block' => (int) env('METALX_MIN_VIOLATIONS', 2),
        'gambling_detection_strict' => env('METALX_STRICT_GAMBLING', true),
        'auto_moderate_enabled' => env('METALX_AUTO_MODERATE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'settings_ttl' => (int) env('METALX_CACHE_TTL', 3600), // 1 hour
        'ai_response_ttl' => (int) env('METALX_AI_CACHE_TTL', 86400), // 24 hours
    ],
];
```

**Update `.env.example`:**
```env
# AI Configuration
AI_PROVIDER=openai
AI_OPENAI_KEY=
AI_CLAUDE_KEY=
AI_OLLAMA_URL=http://localhost:11434
AI_TEMPERATURE=0.7
AI_MAX_TOKENS=2000
AI_TIMEOUT=60
AI_AUTO_REPLY_MIN_CONFIDENCE=75
AI_RETRY_ATTEMPTS=3

# YouTube Configuration
METALX_YOUTUBE_API_KEY=
METALX_YOUTUBE_ACCESS_TOKEN=
METALX_CHANNEL_ID=
METALX_AUTO_ENGAGEMENT=false
METALX_COMMENTS_PER_SYNC=50
METALX_MAX_BATCH_SIZE=100

# Moderation Configuration
METALX_AUTO_BLOCK_REPEAT=true
METALX_MIN_VIOLATIONS=2
METALX_STRICT_GAMBLING=true
METALX_AUTO_MODERATE=false

# Cache Configuration
METALX_CACHE_TTL=3600
METALX_AI_CACHE_TTL=86400
```

---

## 🔧 MEDIUM-PRIORITY Improvements

### 1. UI/UX Improvements

**สร้าง:** `resources/js/components/LoadingButton.js`
```javascript
export class LoadingButton {
    constructor(button) {
        this.button = button;
        this.originalText = button.textContent;
        this.originalHtml = button.innerHTML;
    }

    start() {
        this.button.disabled = true;
        this.button.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Processing...</span>
        `;
    }

    stop() {
        this.button.disabled = false;
        this.button.innerHTML = this.originalHtml;
    }
}

// Usage:
// const btn = new LoadingButton(document.querySelector('#myButton'));
// btn.start();
// await doSomething();
// btn.stop();
```

**สร้าง:** `resources/js/components/Toast.js`
```javascript
export class Toast {
    static show(message, type = 'success', duration = 3000) {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        const icons = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        };

        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${colors[type]} text-white flex items-center gap-2 transition-all`;
        toast.innerHTML = `
            <span class="text-xl">${icons[type]}</span>
            <span>${message}</span>
        `;

        document.body.appendChild(toast);

        // Slide in
        setTimeout(() => toast.classList.add('translate-x-0'), 10);

        // Slide out and remove
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
}

// Usage:
// Toast.show('Operation successful!', 'success');
// Toast.show('Error occurred', 'error');
```

**Update views เพื่อใช้ Toast แทน alert():**
```javascript
// Replace all alert() with Toast.show()
// alert('Success!') → Toast.show('Success!', 'success')
// alert('Error: ' + msg) → Toast.show('Error: ' + msg, 'error')
```

---

### 2. Health Check Endpoint

**สร้างไฟล์:** `app/Http/Controllers/HealthController.php`
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = !in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkCache(): bool
    {
        try {
            Cache::put('health_check', true, 10);
            return Cache::get('health_check') === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkQueue(): bool
    {
        // Check if queue workers are running
        // This is a simple check - implement based on your queue driver
        return true;
    }
}
```

**เพิ่มใน `routes/web.php`:**
```php
Route::get('/health', [HealthController::class, 'check'])->name('health');
```

---

## ❌ MISSING FEATURES

### 1. Testing (CRITICAL)
**ยังไม่มีเลย - ต้องสร้าง!**

**สร้าง Unit Tests:**
- `tests/Unit/Services/YouTubeMetadataAiServiceTest.php`
- `tests/Unit/Services/YouTubeEngagementAiServiceTest.php`
- `tests/Unit/Services/YouTubeCommentServiceTest.php`
- `tests/Unit/Models/MetalXVideoTest.php`
- `tests/Unit/Models/MetalXCommentTest.php`

**สร้าง Feature Tests:**
- `tests/Feature/MetalXAiControllerTest.php`
- `tests/Feature/MetalXEngagementControllerTest.php`
- `tests/Feature/CommentModerationTest.php`

**Target: 50% coverage minimum ก่อน production**

---

### 2. Monitoring & Logging
- [ ] Error tracking (Sentry, Bugsnag)
- [ ] Performance monitoring (APM)
- [ ] AI cost tracking
- [ ] Queue job monitoring
- [ ] Custom metrics dashboard

---

### 3. Documentation
- [ ] API documentation (Swagger/OpenAPI)
- [ ] Developer guide
- [ ] Admin user manual
- [ ] Deployment guide
- [ ] Troubleshooting guide

---

## 📝 TODO LIST - ลำดับความสำคัญ

### IMMEDIATE (ต้องทำก่อนใช้งาน)
- [ ] แก้ XSS vulnerabilities (engagement.blade.php, blacklist.blade.php)
- [ ] สร้าง InputSanitizerService และใช้ใน AI services
- [ ] เพิ่ม encrypted casts ใน Setting model
- [ ] แก้ N+1 query ใน generateBatchMetadata()
- [ ] เพิ่ม rate limiting ใน routes
- [ ] เพิ่ม caching สำหรับ settings
- [ ] เพิ่ม DB transaction ใน blockAndDeleteChannel()
- [ ] สร้าง custom exceptions และแก้ error messages

### SHORT-TERM (1-2 สัปดาห์)
- [ ] Implement SoftDeletes properly
- [ ] สร้าง validation rules (NoScriptTags, NoMaliciousUrls)
- [ ] สร้าง CircuitBreaker service
- [ ] สร้าง composite indexes migration
- [ ] สร้าง config/metalx.php
- [ ] Update .env.example
- [ ] สร้าง LoadingButton และ Toast components
- [ ] Replace alert() ด้วย Toast
- [ ] สร้าง HealthController

### MEDIUM-TERM (2-4 สัปดาห์)
- [ ] เขียน unit tests (target 50% coverage)
- [ ] เขียน feature tests
- [ ] Setup error tracking (Sentry)
- [ ] Setup APM monitoring
- [ ] เขียน API documentation
- [ ] เขียน user manual
- [ ] Setup automated backups
- [ ] Create admin activity logging

### NICE-TO-HAVE
- [ ] A/B testing สำหรับ AI content
- [ ] Custom ML model สำหรับ violation detection
- [ ] Multi-language support expansion
- [ ] Advanced analytics dashboard
- [ ] Email notifications
- [ ] Export/Import features

---

## 📚 เอกสารอ้างอิง

1. **PRODUCTION_READINESS.md** - Production readiness assessment (อ่านก่อน!)
2. **Code Files:**
   - Services: `app/Services/YouTube*.php`
   - Controllers: `app/Http/Controllers/Admin/MetalX*.php`
   - Models: `app/Models/MetalX*.php`
   - Jobs: `app/Jobs/*CommentJob.php`
   - Views: `resources/views/admin/metal-x/*.blade.php`
3. **Migrations:** `database/migrations/*metal_x*.php`
4. **Routes:** `routes/web.php` (metal-x prefix)

---

## 🎯 Workflow สำหรับคนทำงานต่อ

### ขั้นตอนการเริ่มงาน:
1. อ่าน `PRODUCTION_READINESS.md` ให้ละเอียด
2. อ่านไฟล์นี้ (`TODO.md`) ทั้งหมด
3. Checkout branch: `claude/ai-youtube-metadata-dOn4u`
4. รัน `git log --oneline` ดู history
5. เริ่มจาก IMMEDIATE tasks ก่อน

### วิธีทดสอบ:
```bash
# ยังไม่มี migrations run
# ถ้ามี dependencies:
composer install
npm install

# ถ้ามี database:
php artisan migrate

# ตรวจสอบ syntax
composer run-script check-syntax  # ถ้ามี script

# ดู routes
php artisan route:list | grep metal-x

# ดู jobs
php artisan queue:work --once
```

### วิธี commit:
```bash
# ตัวอย่าง commit message
git commit -m "fix: add XSS protection in engagement view

- Whitelist color values before rendering
- Prevents malicious class injection
- Affects engagement.blade.php line 145

Ref: PRODUCTION_READINESS.md #1"
```

---

## 💡 Tips สำหรับคนทำงานต่อ

1. **อ่าน PRODUCTION_READINESS.md ก่อน** - มี code examples ครบ
2. **ทำทีละ issue** - อย่ารีบ fix หมดพร้อมกัน
3. **Test หลังแก้** - แม้ยังไม่มี automated tests
4. **Commit บ่อยๆ** - แต่ละ fix แยก commit
5. **Log everything** - ใช้ `Log::info()` เยอะๆ เพื่อ debug
6. **ระวัง Breaking Changes** - database migrations ต้องระวังพิเศษ
7. **Backup ก่อนทำ** - database, env files
8. **Documentation** - อัพเดท docs ทุกครั้งที่เปลี่ยนแปลง

---

## ⚡ Quick Commands

```bash
# View current branch
git branch

# Check uncommitted changes
git status

# View last 10 commits
git log --oneline -10

# Search for specific code
grep -r "functionName" app/

# Find files
find app/ -name "*Comment*"

# Check Laravel routes
php artisan route:list | grep metal-x

# View queue jobs
php artisan queue:failed

# Clear cache (if needed)
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## 📞 คำถามที่พบบ่อย

**Q: ทำจากไหนดี?**
A: เริ่มจาก IMMEDIATE tasks ใน TODO LIST

**Q: มี tests ไหม?**
A: ยังไม่มี - นี่คือ priority สูง

**Q: Production ready หรือยัง?**
A: ยัง - คะแนน 6.5/10 ต้องแก้ CRITICAL issues ก่อน

**Q: ใช้เวลานานแค่ไหน?**
A: ประมาณ 2-3 สัปดาห์ถ้าทำ full-time

**Q: มี breaking changes ไหม?**
A: จะมีถ้าเพิ่ม encrypted casts - ต้อง re-save settings

**Q: Rollback ได้ไหม?**
A: ได้ - git revert แต่ database อาจมีปัญหา

---

## ✅ Checklist ก่อน Merge

- [ ] CRITICAL issues ทั้งหมดแก้แล้ว
- [ ] Tests ผ่าน (เมื่อมี tests)
- [ ] Code review แล้ว
- [ ] Documentation อัพเดทแล้ว
- [ ] .env.example อัพเดทแล้ว
- [ ] Migrations tested
- [ ] No console errors ใน browser
- [ ] Performance acceptable
- [ ] Security scan passed

---

## 📌 สรุป

**สถานะ:** ✅ ระบบใช้งานได้ แต่ ⚠️ ยังไม่ปลอดภัยพอสำหรับ production

**ต้องทำ:** แก้ CRITICAL issues 8 ข้อก่อน พร้อมเขียน tests

**เป้าหมาย:** Production-ready ใน 2-3 สัปดาห์

**Contact:** ดูรายละเอียดใน `PRODUCTION_READINESS.md`

---

**Last Updated:** 2026-01-22
**Document Version:** 1.0
**Branch:** claude/ai-youtube-metadata-dOn4u
**Author:** Claude (AI Assistant)
