@props(['content' => ''])

@php
    $blocks = [];
    if ($content) {
        if (is_string($content)) {
            try {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $blocks = $decoded;
                } else {
                    // Plain text - wrap in text block
                    $blocks = [['type' => 'text', 'content' => $content, 'style' => []]];
                }
            } catch (\Exception $e) {
                $blocks = [['type' => 'text', 'content' => $content, 'style' => []]];
            }
        } elseif (is_array($content)) {
            $blocks = $content;
        }
    }
@endphp

@if(count($blocks) > 0)
<div class="page-builder-content space-y-4">
    @foreach($blocks as $block)
        @php
            $style = $block['style'] ?? [];
            $textClasses = collect([
                $style['bold'] ?? false ? 'font-bold' : '',
                $style['italic'] ?? false ? 'italic' : '',
                $style['underline'] ?? false ? 'underline' : '',
                isset($style['align']) ? 'text-' . $style['align'] : '',
            ])->filter()->join(' ');

            $inlineStyle = collect([
                isset($style['color']) ? 'color: ' . $style['color'] : '',
                isset($style['bgColor']) && $style['bgColor'] !== '#ffffff' ? 'background-color: ' . $style['bgColor'] : '',
                isset($style['padding']) ? 'padding: ' . $style['padding'] . 'px' : '',
            ])->filter()->join('; ');

            $containerStyle = isset($style['borderRadius']) ? 'border-radius: ' . $style['borderRadius'] . 'px' : '';
        @endphp

        <div class="block-item" @if($containerStyle) style="{{ $containerStyle }}" @endif>
            {{-- Heading Block --}}
            @if($block['type'] === 'heading')
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" @if($inlineStyle) style="{{ $inlineStyle }}" @endif>
                    <h2 class="text-2xl font-bold text-gray-900 {{ $textClasses }}">
                        {{ $block['content'] ?? '' }}
                    </h2>
                </div>

            {{-- Text Block --}}
            @elseif($block['type'] === 'text')
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" @if($inlineStyle) style="{{ $inlineStyle }}" @endif>
                    <p class="whitespace-pre-wrap text-gray-700 {{ $textClasses }}">{{ $block['content'] ?? '' }}</p>
                </div>

            {{-- List Block --}}
            @elseif($block['type'] === 'list')
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" @if($inlineStyle) style="{{ $inlineStyle }}" @endif>
                    <ul class="list-disc list-inside space-y-1 text-gray-700 {{ $textClasses }}">
                        @foreach(array_filter(explode("\n", $block['content'] ?? '')) as $item)
                            <li>{{ preg_replace('/^[-•*]\s*/', '', trim($item)) }}</li>
                        @endforeach
                    </ul>
                </div>

            {{-- Numbered List Block --}}
            @elseif($block['type'] === 'numbered-list')
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" @if($inlineStyle) style="{{ $inlineStyle }}" @endif>
                    <ol class="list-decimal list-inside space-y-1 text-gray-700 {{ $textClasses }}">
                        @foreach(array_filter(explode("\n", $block['content'] ?? '')) as $item)
                            <li>{{ preg_replace('/^\d+\.\s*/', '', trim($item)) }}</li>
                        @endforeach
                    </ol>
                </div>

            {{-- Code Block --}}
            @elseif($block['type'] === 'code')
                <div class="rounded-lg overflow-hidden shadow-sm border border-gray-200">
                    <div class="bg-gray-800 text-gray-100 px-4 py-2 flex justify-between items-center">
                        <span class="text-xs text-gray-400">{{ $block['language'] ?? 'code' }}</span>
                        <button onclick="navigator.clipboard.writeText(this.closest('.block-item').querySelector('code').textContent)"
                                class="text-xs text-gray-400 hover:text-white transition">
                            คัดลอก
                        </button>
                    </div>
                    <pre class="bg-gray-900 text-green-400 font-mono text-sm p-4 overflow-x-auto"><code>{{ $block['content'] ?? '' }}</code></pre>
                </div>

            {{-- Quote Block --}}
            @elseif($block['type'] === 'quote')
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 border-l-4 border-primary-500 bg-primary-50" @if($inlineStyle) style="{{ $inlineStyle }}" @endif>
                        <blockquote class="italic text-gray-700">
                            {{ $block['content'] ?? '' }}
                        </blockquote>
                    </div>
                </div>

            {{-- Divider Block --}}
            @elseif($block['type'] === 'divider')
                <div class="py-2">
                    <hr class="border-gray-300 {{ $style['type'] ?? 'border-solid' }}">
                </div>

            {{-- Spacer Block --}}
            @elseif($block['type'] === 'spacer')
                <div style="height: {{ $block['height'] ?? 40 }}px"></div>

            {{-- Image Block --}}
            @elseif($block['type'] === 'image')
                @if(!empty($block['src']))
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <img src="{{ $block['src'] }}"
                             alt="{{ $block['alt'] ?? 'Image' }}"
                             class="max-w-full h-auto rounded {{ ($style['align'] ?? '') === 'center' ? 'mx-auto' : (($style['align'] ?? '') === 'right' ? 'ml-auto' : '') }}">
                        @if(!empty($block['alt']))
                            <p class="text-sm text-gray-500 text-center mt-2">{{ $block['alt'] }}</p>
                        @endif
                    </div>
                @endif

            {{-- Alert Block --}}
            @elseif($block['type'] === 'alert')
                @php
                    $variant = $block['variant'] ?? 'info';
                    $alertClasses = match($variant) {
                        'success' => 'bg-green-50 border-l-4 border-green-500',
                        'warning' => 'bg-yellow-50 border-l-4 border-yellow-500',
                        'error' => 'bg-red-50 border-l-4 border-red-500',
                        default => 'bg-blue-50 border-l-4 border-blue-500',
                    };
                    $iconColor = match($variant) {
                        'success' => 'text-green-500',
                        'warning' => 'text-yellow-500',
                        'error' => 'text-red-500',
                        default => 'text-blue-500',
                    };
                @endphp
                <div class="rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 flex items-start space-x-3 {{ $alertClasses }}">
                        <div class="flex-shrink-0 mt-0.5">
                            @if($variant === 'info')
                                <svg class="w-5 h-5 {{ $iconColor }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($variant === 'success')
                                <svg class="w-5 h-5 {{ $iconColor }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($variant === 'warning')
                                <svg class="w-5 h-5 {{ $iconColor }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($variant === 'error')
                                <svg class="w-5 h-5 {{ $iconColor }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        <p class="text-sm text-gray-700">{{ $block['content'] ?? '' }}</p>
                    </div>
                </div>

            {{-- Button Block --}}
            @elseif($block['type'] === 'button')
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 {{ isset($style['align']) ? 'text-' . $style['align'] : '' }}">
                    @php
                        $buttonClasses = ($block['variant'] ?? '') === 'outline'
                            ? 'border-2 border-primary-600 text-primary-600 hover:bg-primary-50'
                            : 'bg-primary-600 text-white hover:bg-primary-700';
                    @endphp
                    <a href="{{ $block['url'] ?? '#' }}"
                       class="inline-block px-6 py-2 rounded-lg font-medium transition {{ $buttonClasses }}">
                        {{ $block['content'] ?? 'ปุ่ม' }}
                    </a>
                </div>

            {{-- Columns Block --}}
            @elseif($block['type'] === 'columns')
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="grid gap-4 grid-cols-{{ $block['columns'] ?? 2 }}">
                        @foreach($block['columnData'] ?? [] as $col)
                            <div class="p-3 bg-gray-50 rounded">
                                <p class="text-sm whitespace-pre-wrap text-gray-700">{{ $col['content'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>
@endif
