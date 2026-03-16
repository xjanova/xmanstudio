@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่า Metal-X')
@section('page-title', 'ตั้งค่า Metal-X Channel')

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.metal-x.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Channel Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูล Channel</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ Channel <span class="text-red-500">*</span></label>
                    <input type="text" name="channel_name" value="{{ old('channel_name', $settings['channel_name']) }}" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                    @error('channel_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL Channel <span class="text-red-500">*</span></label>
                    <input type="url" name="channel_url" value="{{ old('channel_url', $settings['channel_url']) }}" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="https://www.youtube.com/@...">
                    @error('channel_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รายละเอียด Channel</label>
                    <textarea name="channel_description" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">{{ old('channel_description', $settings['channel_description']) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Images --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รูปภาพ</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โลโก้ Channel</label>
                    @if($settings['channel_logo'])
                        <div class="mb-3">
                            <img src="{{ Storage::url($settings['channel_logo']) }}" alt="Channel Logo" class="w-24 h-24 rounded-lg object-cover">
                        </div>
                    @endif
                    <input type="file" name="channel_logo" accept="image/*" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-indigo-50 dark:file:bg-indigo-900 file:text-indigo-700 dark:file:text-indigo-200">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ไม่เกิน 2MB</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แบนเนอร์ Channel</label>
                    @if($settings['channel_banner'])
                        <div class="mb-3">
                            <img src="{{ Storage::url($settings['channel_banner']) }}" alt="Channel Banner" class="w-full h-24 rounded-lg object-cover">
                        </div>
                    @endif
                    <input type="file" name="channel_banner" accept="image/*" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-indigo-50 dark:file:bg-indigo-900 file:text-indigo-700 dark:file:text-indigo-200">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ไม่เกิน 2MB</p>
                </div>
            </div>
        </div>

        {{-- YouTube API --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                YouTube API
            </h3>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <p class="text-sm text-blue-700 dark:text-blue-300">ตั้งค่า YouTube API เพื่อ sync วิดีโอ, เชื่อมต่อช่อง และอัปโหลดวิดีโอ สร้าง Credentials ที่ <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="underline font-medium">Google Cloud Console</a></p>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">YouTube Data API Key</label>
                    <input type="password" name="youtube_api_key" value="{{ old('youtube_api_key', $settings['youtube_api_key'] ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="AIza...">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">สำหรับอ่านข้อมูลวิดีโอ/ช่อง (read-only)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Channel ID</label>
                    <input type="text" name="channel_id" value="{{ old('channel_id', $settings['channel_id'] ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="UCxxxxxxxxxxxxxxxxxx">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">OAuth Client ID</label>
                        <input type="password" name="youtube_client_id" value="{{ old('youtube_client_id', $settings['youtube_client_id'] ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="xxxx.apps.googleusercontent.com">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">สำหรับเชื่อมต่อช่อง (OAuth2) และอัปโหลดวิดีโอ</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">OAuth Client Secret</label>
                        <input type="password" name="youtube_client_secret" value="{{ old('youtube_client_secret', $settings['youtube_client_secret'] ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="GOCSPX-...">
                    </div>
                </div>
            </div>
        </div>

        {{-- Suno AI Music --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                Suno AI Music
            </h3>
            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4 mb-4">
                <p class="text-sm text-purple-700 dark:text-purple-300">Suno AI ใช้สร้างเพลงอัตโนมัติสำหรับคลิปวิดีโอ สมัครใช้งานที่ <a href="https://suno.com" target="_blank" class="underline font-medium">suno.com</a></p>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Suno API Key</label>
                    <input type="password" name="suno_api_key" value="{{ old('suno_api_key', $settings['suno_api_key'] ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="your-suno-api-key">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Suno API Base URL</label>
                    <input type="url" name="suno_base_url" value="{{ old('suno_base_url', $settings['suno_base_url'] ?? 'https://apibox.erweima.ai') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ค่าเริ่มต้น: https://apibox.erweima.ai (เปลี่ยนได้ถ้าใช้ API provider อื่น)</p>
                </div>
            </div>
        </div>

        {{-- AI Provider --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                AI Provider (สร้างข้อมูลวิดีโอ / ตอบคอมเม้นต์)
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">AI Provider หลัก</label>
                    <select name="metalx_ai_provider" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="groq" {{ old('metalx_ai_provider', $settings['metalx_ai_provider']) === 'groq' ? 'selected' : '' }}>Groq (เร็วมาก, ฟรี)</option>
                        <option value="openai" {{ old('metalx_ai_provider', $settings['metalx_ai_provider']) === 'openai' ? 'selected' : '' }}>OpenAI (GPT-4o-mini)</option>
                        <option value="claude" {{ old('metalx_ai_provider', $settings['metalx_ai_provider']) === 'claude' ? 'selected' : '' }}>Claude (Anthropic)</option>
                        <option value="gemini" {{ old('metalx_ai_provider', $settings['metalx_ai_provider']) === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                        <option value="ollama" {{ old('metalx_ai_provider', $settings['metalx_ai_provider']) === 'ollama' ? 'selected' : '' }}>Ollama (Local)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Groq API Key</label>
                    <input type="password" name="groq_api_key" value="{{ old('groq_api_key', $settings['groq_api_key'] ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="gsk_...">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">สมัครฟรีที่ <a href="https://console.groq.com" target="_blank" class="underline text-indigo-600 dark:text-indigo-400">console.groq.com</a></p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">OpenAI API Key</label>
                        <input type="password" name="metalx_openai_key" value="{{ old('metalx_openai_key', $settings['metalx_openai_key'] ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="sk-...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Claude API Key</label>
                        <input type="password" name="metalx_claude_key" value="{{ old('metalx_claude_key', $settings['metalx_claude_key'] ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="sk-ant-...">
                    </div>
                </div>
            </div>
        </div>

        {{-- FFmpeg --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                FFmpeg (เรนเดอร์วิดีโอ)
            </h3>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
                <p class="text-sm text-green-700 dark:text-green-300">FFmpeg ใช้สร้างวิดีโอ visualizer จากรูปภาพ + เพลง ต้องติดตั้งบน server ก่อน</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">FFmpeg Binary Path</label>
                    <input type="text" name="ffmpeg_binary" value="{{ old('ffmpeg_binary', $settings['ffmpeg_binary'] ?? 'ffmpeg') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="ffmpeg">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เช่น: ffmpeg หรือ /usr/bin/ffmpeg หรือ C:/ffmpeg/bin/ffmpeg.exe</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">FFprobe Binary Path</label>
                    <input type="text" name="ffprobe_binary" value="{{ old('ffprobe_binary', $settings['ffprobe_binary'] ?? 'ffprobe') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="ffprobe">
                </div>
            </div>
        </div>

        {{-- Video Defaults --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                ค่าเริ่มต้นวิดีโอ
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความละเอียดวิดีโอ</label>
                    <select name="metalx_video_resolution" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="1920x1080" {{ old('metalx_video_resolution', $settings['metalx_video_resolution']) === '1920x1080' ? 'selected' : '' }}>1080p (1920x1080)</option>
                        <option value="1280x720" {{ old('metalx_video_resolution', $settings['metalx_video_resolution']) === '1280x720' ? 'selected' : '' }}>720p (1280x720)</option>
                        <option value="3840x2160" {{ old('metalx_video_resolution', $settings['metalx_video_resolution']) === '3840x2160' ? 'selected' : '' }}>4K (3840x2160)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะเผยแพร่เริ่มต้น</label>
                    <select name="metalx_default_privacy" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="private" {{ old('metalx_default_privacy', $settings['metalx_default_privacy']) === 'private' ? 'selected' : '' }}>ส่วนตัว</option>
                        <option value="unlisted" {{ old('metalx_default_privacy', $settings['metalx_default_privacy']) === 'unlisted' ? 'selected' : '' }}>ไม่แสดงในรายการ</option>
                        <option value="public" {{ old('metalx_default_privacy', $settings['metalx_default_privacy']) === 'public' ? 'selected' : '' }}>สาธารณะ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โปรโมทสูงสุด/วิดีโอ/วัน</label>
                    <input type="number" name="metalx_promo_max_per_video_per_day" value="{{ old('metalx_promo_max_per_video_per_day', $settings['metalx_promo_max_per_video_per_day'] ?? 2) }}" min="1" max="20"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.metal-x.analytics') }}" class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">ยกเลิก</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">บันทึกการตั้งค่า</button>
        </div>
    </form>
</div>
@endsection
