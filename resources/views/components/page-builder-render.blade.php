@props(['content' => '', 'theme' => 'light'])

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

    // Theme-based classes
    $isDark = $theme === 'dark';
    $cardBg = $isDark ? 'bg-white/5 backdrop-blur-sm border-white/10' : 'bg-white border-gray-200';
    $textColor = $isDark ? 'text-gray-100' : 'text-gray-700';
    $headingColor = $isDark ? 'text-white' : 'text-gray-900';
    $mutedColor = $isDark ? 'text-gray-400' : 'text-gray-600';
    $dividerColor = $isDark ? 'border-gray-700' : 'border-gray-300';
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
                @php
                    $level = $block['level'] ?? 'h2';
                    $headingSize = match($level) {
                        'h1' => 'text-4xl',
                        'h2' => 'text-2xl',
                        'h3' => 'text-xl',
                        'h4' => 'text-lg',
                        default => 'text-2xl',
                    };
                @endphp
                <{{ $level }} class="{{ $headingSize }} font-bold {{ $headingColor }} {{ $textClasses }}" @if($inlineStyle) style="{{ $inlineStyle }}" @endif>
                    {{ $block['content'] ?? '' }}
                </{{ $level }}>

            {{-- Text Block --}}
            @elseif($block['type'] === 'text')
                <p class="whitespace-pre-wrap {{ $textColor }} leading-relaxed {{ $textClasses }}" @if($inlineStyle) style="{{ $inlineStyle }}" @endif>
                    {{ $block['content'] ?? '' }}
                </p>

            {{-- List Block (new format with items array) --}}
            @elseif($block['type'] === 'list')
                @php
                    $listStyle = $block['style'] ?? 'check';
                    $items = $block['items'] ?? [];
                    // Fallback to old format
                    if (empty($items) && isset($block['content'])) {
                        $items = array_filter(explode("\n", $block['content']));
                    }
                @endphp
                <ul class="space-y-2 {{ $textColor }}">
                    @foreach($items as $item)
                        <li class="flex items-start gap-3">
                            @if($listStyle === 'check')
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center mt-0.5">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            @else
                                <span class="flex-shrink-0 w-2 h-2 rounded-full bg-primary-500 mt-2"></span>
                            @endif
                            <span>{{ is_string($item) ? preg_replace('/^[-•*]\s*/', '', trim($item)) : ($item['text'] ?? '') }}</span>
                        </li>
                    @endforeach
                </ul>

            {{-- Numbered List Block --}}
            @elseif($block['type'] === 'numbered-list')
                <ol class="list-decimal list-inside space-y-1 {{ $textColor }} {{ $textClasses }}">
                    @foreach(array_filter(explode("\n", $block['content'] ?? '')) as $item)
                        <li>{{ preg_replace('/^\d+\.\s*/', '', trim($item)) }}</li>
                    @endforeach
                </ol>

            {{-- Code Block --}}
            @elseif($block['type'] === 'code')
                <div class="rounded-lg overflow-hidden {{ $isDark ? 'border border-white/10' : 'border border-gray-200 shadow-sm' }}">
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
                <div class="p-4 border-l-4 border-primary-500 {{ $isDark ? 'bg-primary-500/10' : 'bg-primary-50' }} rounded-r-lg">
                    <blockquote class="italic {{ $textColor }}">
                        {{ $block['content'] ?? '' }}
                    </blockquote>
                </div>

            {{-- Divider Block --}}
            @elseif($block['type'] === 'divider')
                <div class="py-4">
                    <hr class="{{ $dividerColor }} {{ $style['type'] ?? 'border-solid' }}">
                </div>

            {{-- Spacer Block --}}
            @elseif($block['type'] === 'spacer')
                @php
                    $size = $block['size'] ?? 'md';
                    $height = match($size) {
                        'sm' => '16px',
                        'md' => '32px',
                        'lg' => '48px',
                        'xl' => '64px',
                        default => $block['height'] ?? '32px',
                    };
                @endphp
                <div style="height: {{ $height }}"></div>

            {{-- Image Block --}}
            @elseif($block['type'] === 'image')
                @if(!empty($block['src']))
                    <div class="rounded-lg overflow-hidden {{ $isDark ? 'border border-white/10' : 'border border-gray-200 shadow-sm' }}">
                        <img src="{{ $block['src'] }}"
                             alt="{{ $block['alt'] ?? 'Image' }}"
                             class="max-w-full h-auto {{ ($style['align'] ?? '') === 'center' ? 'mx-auto' : (($style['align'] ?? '') === 'right' ? 'ml-auto' : '') }}">
                        @if(!empty($block['alt']))
                            <p class="text-sm {{ $mutedColor }} text-center py-2">{{ $block['alt'] }}</p>
                        @endif
                    </div>
                @endif

            {{-- Alert Block --}}
            @elseif($block['type'] === 'alert')
                @php
                    $variant = $block['variant'] ?? 'info';
                    $alertClasses = match($variant) {
                        'success' => $isDark ? 'bg-green-500/10 border-l-4 border-green-500' : 'bg-green-50 border-l-4 border-green-500',
                        'warning' => $isDark ? 'bg-yellow-500/10 border-l-4 border-yellow-500' : 'bg-yellow-50 border-l-4 border-yellow-500',
                        'error' => $isDark ? 'bg-red-500/10 border-l-4 border-red-500' : 'bg-red-50 border-l-4 border-red-500',
                        default => $isDark ? 'bg-blue-500/10 border-l-4 border-blue-500' : 'bg-blue-50 border-l-4 border-blue-500',
                    };
                @endphp
                <div class="p-4 rounded-r-lg {{ $alertClasses }}">
                    <p class="{{ $textColor }}">{{ $block['content'] ?? '' }}</p>
                </div>

            {{-- Button Block --}}
            @elseif($block['type'] === 'button')
                <div class="{{ isset($style['align']) ? 'text-' . $style['align'] : '' }}">
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

            {{-- Icon Box Block --}}
            @elseif($block['type'] === 'icon-box')
                @php
                    $iconColor = $block['iconColor'] ?? '#7c3aed';
                    $iconName = $block['icon'] ?? 'star';
                @endphp
                <div class="p-5 rounded-xl border {{ $cardBg }} hover:border-primary-500/50 transition-all">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center"
                             style="background-color: {{ $iconColor }}20; color: {{ $iconColor }}">
                            @include('components.page-builder-icons', ['icon' => $iconName])
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold {{ $headingColor }} text-lg">{{ $block['title'] ?? $block['content'] ?? 'หัวข้อ' }}</h4>
                            @if(!empty($block['description']))
                                <p class="{{ $mutedColor }} mt-2 leading-relaxed">{{ $block['description'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>

            {{-- Feature Card Block --}}
            @elseif($block['type'] === 'feature-card')
                @php
                    $fIconColor = $block['iconColor'] ?? '#059669';
                    $fBgColor = $block['bgColor'] ?? $block['style']['bgColor'] ?? ($isDark ? 'rgba(16,185,129,0.1)' : '#f0fdf4');
                    $fIconName = $block['icon'] ?? 'check-circle';
                @endphp
                <div class="rounded-xl p-5 border border-transparent hover:border-primary-500/30 transition-all"
                     style="background-color: {{ $isDark ? $fIconColor . '15' : $fBgColor }}">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center {{ $isDark ? 'bg-white/10' : 'bg-white shadow-sm' }}"
                             style="color: {{ $fIconColor }}">
                            @include('components.page-builder-icons', ['icon' => $fIconName])
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold {{ $headingColor }} text-lg">{{ $block['title'] ?? 'ฟีเจอร์' }}</h4>
                            <p class="{{ $mutedColor }} mt-2 leading-relaxed">{{ $block['description'] ?? $block['content'] ?? '' }}</p>
                        </div>
                    </div>
                </div>

            {{-- Columns Block --}}
            @elseif($block['type'] === 'columns')
                <div class="grid gap-4 grid-cols-{{ $block['columns'] ?? 2 }}">
                    @foreach($block['columnData'] ?? [] as $col)
                        <div class="p-4 rounded-lg {{ $isDark ? 'bg-white/5' : 'bg-gray-50' }}">
                            <p class="text-sm whitespace-pre-wrap {{ $textColor }}">{{ $col['content'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
@endif
