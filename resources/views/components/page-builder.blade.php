@props(['name' => 'content', 'value' => '', 'placeholder' => 'ลากบล็อกมาวางที่นี่เพื่อเริ่มสร้างเนื้อหา...', 'fullWidth' => false])

<div x-data="pageBuilder(@js($value), @js($name))"
     x-init="init()"
     :class="{ 'fixed inset-0 z-50 flex flex-col': isFullscreen }"
     class="page-builder overflow-hidden bg-white {{ $fullWidth ? '' : 'border border-gray-300 rounded-xl shadow-lg' }}"
     @if($fullWidth) style="min-height: 500px;" @endif>

    <!-- Hidden input to store JSON data -->
    <input type="hidden" name="{{ $name }}" :value="JSON.stringify(blocks)">

    <!-- Top Toolbar -->
    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-4 py-2 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
            <span class="font-semibold text-sm">Page Builder</span>
            <span x-show="isFullscreen" class="text-xs text-gray-400 ml-2">(กด ESC เพื่อออก)</span>
        </div>
        <div class="flex items-center space-x-2">
            <!-- AI Auto-Format Button -->
            <button type="button" @click="showAiFormatModal = true"
                    class="px-3 py-1 text-xs rounded bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 transition flex items-center space-x-1"
                    title="AI จัดรูปแบบอัตโนมัติ">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <span>AI จัดรูปแบบ</span>
            </button>

            <div class="w-px h-4 bg-gray-600 mx-1"></div>

            <button type="button" @click="undo()" :disabled="historyIndex <= 0"
                    class="p-1.5 rounded hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition" title="Undo">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
            </button>
            <button type="button" @click="redo()" :disabled="historyIndex >= history.length - 1"
                    class="p-1.5 rounded hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition" title="Redo">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/>
                </svg>
            </button>
            <div class="w-px h-4 bg-gray-600 mx-1"></div>

            <!-- Live Preview Toggle -->
            <button type="button" @click="showLivePreview = !showLivePreview"
                    class="px-3 py-1 text-xs rounded transition flex items-center space-x-1"
                    :class="showLivePreview ? 'bg-green-600 text-white' : 'bg-gray-700 hover:bg-gray-600'"
                    title="แสดงตัวอย่างแบบ Real-time">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span x-text="showLivePreview ? 'ซ่อนตัวอย่าง' : 'ดูตัวอย่าง'"></span>
            </button>

            <button type="button" @click="previewMode = !previewMode"
                    class="px-3 py-1 text-xs rounded transition"
                    :class="previewMode ? 'bg-primary-600 text-white' : 'bg-gray-700 hover:bg-gray-600'">
                <span x-text="previewMode ? 'แก้ไข' : 'Preview Only'"></span>
            </button>

            <div class="w-px h-4 bg-gray-600 mx-1"></div>

            <!-- Fullscreen Toggle -->
            <button type="button" @click="toggleFullscreen()"
                    class="p-1.5 rounded hover:bg-gray-700 transition"
                    :title="isFullscreen ? 'ออกจากโหมดเต็มหน้าจอ' : 'โหมดเต็มหน้าจอ'">
                <svg x-show="!isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
                <svg x-show="isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <button type="button" @click="clearAll()" class="p-1.5 rounded hover:bg-red-600 transition" title="ล้างทั้งหมด">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="flex" :class="{ 'flex-1': isFullscreen }" :style="isFullscreen ? '' : 'min-height: 400px;'">
        <!-- Blocks Sidebar -->
        <div class="w-64 bg-gray-50 border-r border-gray-200 p-3 overflow-y-auto" x-show="!previewMode"
             :class="{ 'w-56': isFullscreen && showLivePreview }">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">บล็อกพื้นฐาน</div>

            <div class="grid grid-cols-2 gap-2 mb-4">
                <template x-for="block in availableBlocks.basic" :key="block.type">
                    <div draggable="true"
                         @dragstart="dragStart($event, block)"
                         @dragend="dragEnd($event)"
                         class="bg-white border border-gray-200 rounded-lg p-3 cursor-move hover:border-primary-400 hover:shadow-md transition-all group">
                        <div class="text-center">
                            <div class="w-8 h-8 mx-auto mb-1 flex items-center justify-center text-gray-400 group-hover:text-primary-500 transition" x-html="block.icon"></div>
                            <div class="text-xs text-gray-600 group-hover:text-gray-900" x-text="block.label"></div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">บล็อกขั้นสูง</div>

            <div class="grid grid-cols-2 gap-2 mb-4">
                <template x-for="block in availableBlocks.advanced" :key="block.type">
                    <div draggable="true"
                         @dragstart="dragStart($event, block)"
                         @dragend="dragEnd($event)"
                         class="bg-white border border-gray-200 rounded-lg p-3 cursor-move hover:border-primary-400 hover:shadow-md transition-all group">
                        <div class="text-center">
                            <div class="w-8 h-8 mx-auto mb-1 flex items-center justify-center text-gray-400 group-hover:text-primary-500 transition" x-html="block.icon"></div>
                            <div class="text-xs text-gray-600 group-hover:text-gray-900" x-text="block.label"></div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">เลย์เอาท์</div>

            <div class="grid grid-cols-2 gap-2">
                <template x-for="block in availableBlocks.layout" :key="block.type">
                    <div draggable="true"
                         @dragstart="dragStart($event, block)"
                         @dragend="dragEnd($event)"
                         class="bg-white border border-gray-200 rounded-lg p-3 cursor-move hover:border-primary-400 hover:shadow-md transition-all group">
                        <div class="text-center">
                            <div class="w-8 h-8 mx-auto mb-1 flex items-center justify-center text-gray-400 group-hover:text-primary-500 transition" x-html="block.icon"></div>
                            <div class="text-xs text-gray-600 group-hover:text-gray-900" x-text="block.label"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Canvas Area -->
        <div class="flex-1 flex flex-col">
            <!-- Format Toolbar (shown when editing text) -->
            <div x-show="selectedBlock && !previewMode && isTextBlock(selectedBlock)"
                 x-transition
                 class="bg-gray-100 border-b border-gray-200 px-3 py-2 flex items-center space-x-1 flex-wrap gap-1">

                <!-- Text Style -->
                <select @change="updateBlockStyle('tag', $event.target.value)"
                        :value="selectedBlock?.style?.tag || 'p'"
                        class="text-sm border border-gray-300 rounded px-2 py-1 bg-white">
                    <option value="p">ย่อหน้า</option>
                    <option value="h1">หัวข้อ 1</option>
                    <option value="h2">หัวข้อ 2</option>
                    <option value="h3">หัวข้อ 3</option>
                    <option value="h4">หัวข้อ 4</option>
                </select>

                <div class="w-px h-6 bg-gray-300 mx-1"></div>

                <!-- Bold -->
                <button type="button" @click="toggleStyle('bold')"
                        :class="selectedBlock?.style?.bold ? 'bg-primary-100 text-primary-700' : 'hover:bg-gray-200'"
                        class="p-1.5 rounded transition" title="ตัวหนา">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/><path d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/>
                    </svg>
                </button>

                <!-- Italic -->
                <button type="button" @click="toggleStyle('italic')"
                        :class="selectedBlock?.style?.italic ? 'bg-primary-100 text-primary-700' : 'hover:bg-gray-200'"
                        class="p-1.5 rounded transition" title="ตัวเอียง">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 4h4m2 0l-6 16m-2 0h4"/>
                    </svg>
                </button>

                <!-- Underline -->
                <button type="button" @click="toggleStyle('underline')"
                        :class="selectedBlock?.style?.underline ? 'bg-primary-100 text-primary-700' : 'hover:bg-gray-200'"
                        class="p-1.5 rounded transition" title="ขีดเส้นใต้">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 8v4a5 5 0 0010 0V8M5 20h14"/>
                    </svg>
                </button>

                <div class="w-px h-6 bg-gray-300 mx-1"></div>

                <!-- Text Align -->
                <button type="button" @click="updateBlockStyle('align', 'left')"
                        :class="(selectedBlock?.style?.align || 'left') === 'left' ? 'bg-primary-100 text-primary-700' : 'hover:bg-gray-200'"
                        class="p-1.5 rounded transition" title="ชิดซ้าย">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M3 12h12M3 18h18"/>
                    </svg>
                </button>
                <button type="button" @click="updateBlockStyle('align', 'center')"
                        :class="selectedBlock?.style?.align === 'center' ? 'bg-primary-100 text-primary-700' : 'hover:bg-gray-200'"
                        class="p-1.5 rounded transition" title="กึ่งกลาง">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M6 12h12M3 18h18"/>
                    </svg>
                </button>
                <button type="button" @click="updateBlockStyle('align', 'right')"
                        :class="selectedBlock?.style?.align === 'right' ? 'bg-primary-100 text-primary-700' : 'hover:bg-gray-200'"
                        class="p-1.5 rounded transition" title="ชิดขวา">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M9 12h12M3 18h18"/>
                    </svg>
                </button>

                <div class="w-px h-6 bg-gray-300 mx-1"></div>

                <!-- Text Color -->
                <div class="relative">
                    <input type="color"
                           :value="selectedBlock?.style?.color || '#374151'"
                           @input="updateBlockStyle('color', $event.target.value)"
                           class="w-8 h-8 rounded cursor-pointer border border-gray-300"
                           title="สีตัวอักษร">
                </div>

                <!-- Background Color -->
                <div class="relative">
                    <input type="color"
                           :value="selectedBlock?.style?.bgColor || '#ffffff'"
                           @input="updateBlockStyle('bgColor', $event.target.value)"
                           class="w-8 h-8 rounded cursor-pointer border border-gray-300"
                           title="สีพื้นหลัง">
                </div>
            </div>

            <!-- Drop Zone / Canvas -->
            <div class="flex-1 p-4 overflow-y-auto bg-gray-50"
                 :class="{ 'bg-gray-100': previewMode }"
                 @dragover.prevent="dragOver($event)"
                 @drop="drop($event, -1)">

                <!-- Empty State -->
                <div x-show="blocks.length === 0 && !previewMode"
                     class="h-full flex flex-col items-center justify-center text-gray-400 border-2 border-dashed border-gray-300 rounded-xl">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    <p class="text-lg font-medium">{{ $placeholder }}</p>
                    <p class="text-sm mt-1">ลากบล็อกจากแถบด้านซ้ายมาวางที่นี่</p>
                </div>

                <!-- Blocks -->
                <div class="space-y-2">
                    <template x-for="(block, index) in blocks" :key="block.id">
                        <div>
                            <!-- Drop indicator above -->
                            <div class="h-1 rounded transition-all duration-200"
                                 :class="dropIndex === index ? 'bg-primary-400 my-2' : ''"
                                 @dragover.prevent="dropIndex = index"
                                 @dragleave="dropIndex = -1"
                                 @drop="drop($event, index)">
                            </div>

                            <!-- Block -->
                            <div class="group relative"
                                 :class="{
                                     'ring-2 ring-primary-500 ring-offset-2': selectedBlock?.id === block.id && !previewMode,
                                     'hover:ring-1 hover:ring-gray-300': selectedBlock?.id !== block.id && !previewMode
                                 }"
                                 @click="selectBlock(block)"
                                 draggable="true"
                                 @dragstart="dragStartExisting($event, block, index)"
                                 @dragend="dragEnd($event)">

                                <!-- Block Controls -->
                                <div x-show="!previewMode"
                                     class="absolute -left-10 top-0 flex flex-col space-y-1 opacity-0 group-hover:opacity-100 transition">
                                    <button type="button" @click.stop="moveBlock(index, -1)"
                                            :disabled="index === 0"
                                            class="p-1 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-50 disabled:opacity-30">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                        </svg>
                                    </button>
                                    <button type="button" @click.stop="moveBlock(index, 1)"
                                            :disabled="index === blocks.length - 1"
                                            class="p-1 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-50 disabled:opacity-30">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Delete Button -->
                                <button x-show="!previewMode"
                                        type="button"
                                        @click.stop="deleteBlock(index)"
                                        class="absolute -right-2 -top-2 p-1 bg-red-500 text-white rounded-full shadow-lg opacity-0 group-hover:opacity-100 hover:bg-red-600 transition z-10">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>

                                <!-- Duplicate Button -->
                                <button x-show="!previewMode"
                                        type="button"
                                        @click.stop="duplicateBlock(index)"
                                        class="absolute -right-2 top-6 p-1 bg-primary-500 text-white rounded-full shadow-lg opacity-0 group-hover:opacity-100 hover:bg-primary-600 transition z-10">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>

                                <!-- Block Content -->
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
                                     :style="getBlockContainerStyle(block)">

                                    <!-- Heading Block -->
                                    <template x-if="block.type === 'heading'">
                                        <div class="p-4" :style="getBlockStyle(block)">
                                            <div x-show="!previewMode && selectedBlock?.id === block.id">
                                                <input type="text"
                                                       x-model="block.content"
                                                       @input="saveHistory()"
                                                       class="w-full text-2xl font-bold border-0 p-0 focus:ring-0 bg-transparent"
                                                       :class="getTextClasses(block)"
                                                       placeholder="หัวข้อ...">
                                            </div>
                                            <div x-show="previewMode || selectedBlock?.id !== block.id"
                                                 class="text-2xl font-bold"
                                                 :class="getTextClasses(block)"
                                                 x-text="block.content || 'หัวข้อ...'">
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Text Block -->
                                    <template x-if="block.type === 'text'">
                                        <div class="p-4" :style="getBlockStyle(block)">
                                            <div x-show="!previewMode && selectedBlock?.id === block.id">
                                                <textarea x-model="block.content"
                                                          @input="saveHistory()"
                                                          rows="3"
                                                          class="w-full border-0 p-0 focus:ring-0 bg-transparent resize-none"
                                                          :class="getTextClasses(block)"
                                                          placeholder="พิมพ์ข้อความที่นี่..."></textarea>
                                            </div>
                                            <div x-show="previewMode || selectedBlock?.id !== block.id"
                                                 class="whitespace-pre-wrap"
                                                 :class="getTextClasses(block)"
                                                 x-text="block.content || 'พิมพ์ข้อความที่นี่...'">
                                            </div>
                                        </div>
                                    </template>

                                    <!-- List Block -->
                                    <template x-if="block.type === 'list'">
                                        <div class="p-4" :style="getBlockStyle(block)">
                                            <div x-show="!previewMode && selectedBlock?.id === block.id">
                                                <textarea x-model="block.content"
                                                          @input="saveHistory()"
                                                          rows="4"
                                                          class="w-full border-0 p-0 focus:ring-0 bg-transparent resize-none font-mono text-sm"
                                                          placeholder="- รายการ 1&#10;- รายการ 2&#10;- รายการ 3"></textarea>
                                            </div>
                                            <ul x-show="previewMode || selectedBlock?.id !== block.id"
                                                class="list-disc list-inside space-y-1" :class="getTextClasses(block)">
                                                <template x-for="item in (block.content || '').split('\n').filter(i => i.trim())" :key="item">
                                                    <li x-text="item.replace(/^[-•*]\s*/, '')"></li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>

                                    <!-- Numbered List Block -->
                                    <template x-if="block.type === 'numbered-list'">
                                        <div class="p-4" :style="getBlockStyle(block)">
                                            <div x-show="!previewMode && selectedBlock?.id === block.id">
                                                <textarea x-model="block.content"
                                                          @input="saveHistory()"
                                                          rows="4"
                                                          class="w-full border-0 p-0 focus:ring-0 bg-transparent resize-none font-mono text-sm"
                                                          placeholder="1. รายการ 1&#10;2. รายการ 2&#10;3. รายการ 3"></textarea>
                                            </div>
                                            <ol x-show="previewMode || selectedBlock?.id !== block.id"
                                                class="list-decimal list-inside space-y-1" :class="getTextClasses(block)">
                                                <template x-for="item in (block.content || '').split('\n').filter(i => i.trim())" :key="item">
                                                    <li x-text="item.replace(/^\d+\.\s*/, '')"></li>
                                                </template>
                                            </ol>
                                        </div>
                                    </template>

                                    <!-- Code Block -->
                                    <template x-if="block.type === 'code'">
                                        <div>
                                            <div class="bg-gray-800 text-gray-100 px-4 py-2 flex justify-between items-center">
                                                <input type="text"
                                                       x-model="block.language"
                                                       @input="saveHistory()"
                                                       class="bg-transparent border-0 text-xs text-gray-400 w-24 focus:ring-0"
                                                       placeholder="language">
                                                <button type="button" @click="copyCode(block)" class="text-xs text-gray-400 hover:text-white">
                                                    คัดลอก
                                                </button>
                                            </div>
                                            <div x-show="!previewMode && selectedBlock?.id === block.id">
                                                <textarea x-model="block.content"
                                                          @input="saveHistory()"
                                                          rows="6"
                                                          class="w-full bg-gray-900 text-green-400 font-mono text-sm p-4 border-0 focus:ring-0 resize-none"
                                                          placeholder="// พิมพ์โค้ดที่นี่..."></textarea>
                                            </div>
                                            <pre x-show="previewMode || selectedBlock?.id !== block.id"
                                                 class="bg-gray-900 text-green-400 font-mono text-sm p-4 overflow-x-auto"><code x-text="block.content || '// พิมพ์โค้ดที่นี่...'"></code></pre>
                                        </div>
                                    </template>

                                    <!-- Quote Block -->
                                    <template x-if="block.type === 'quote'">
                                        <div class="p-4 border-l-4 border-primary-500 bg-primary-50" :style="getBlockStyle(block)">
                                            <div x-show="!previewMode && selectedBlock?.id === block.id">
                                                <textarea x-model="block.content"
                                                          @input="saveHistory()"
                                                          rows="2"
                                                          class="w-full border-0 p-0 focus:ring-0 bg-transparent resize-none italic"
                                                          placeholder="ข้อความอ้างอิง..."></textarea>
                                            </div>
                                            <blockquote x-show="previewMode || selectedBlock?.id !== block.id"
                                                        class="italic text-gray-700"
                                                        x-text="block.content || 'ข้อความอ้างอิง...'">
                                            </blockquote>
                                        </div>
                                    </template>

                                    <!-- Divider Block -->
                                    <template x-if="block.type === 'divider'">
                                        <div class="py-4 px-4">
                                            <hr class="border-gray-300" :class="block.style?.type || 'border-solid'">
                                        </div>
                                    </template>

                                    <!-- Spacer Block -->
                                    <template x-if="block.type === 'spacer'">
                                        <div :style="'height: ' + (block.height || 40) + 'px'"
                                             class="flex items-center justify-center"
                                             :class="{ 'border border-dashed border-gray-300': !previewMode }">
                                            <span x-show="!previewMode" class="text-xs text-gray-400" x-text="(block.height || 40) + 'px'"></span>
                                        </div>
                                    </template>

                                    <!-- Image Block -->
                                    <template x-if="block.type === 'image'">
                                        <div class="p-4">
                                            <div x-show="!block.src && !previewMode"
                                                 class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                                                <input type="text"
                                                       x-model="block.src"
                                                       @input="saveHistory()"
                                                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm mb-2"
                                                       placeholder="วาง URL รูปภาพ...">
                                                <p class="text-xs text-gray-400">หรือวาง URL รูปภาพจากภายนอก</p>
                                            </div>
                                            <div x-show="block.src">
                                                <img :src="block.src"
                                                     :alt="block.alt || 'Image'"
                                                     class="max-w-full h-auto rounded"
                                                     :class="block.style?.align === 'center' ? 'mx-auto' : (block.style?.align === 'right' ? 'ml-auto' : '')">
                                                <input x-show="!previewMode && selectedBlock?.id === block.id"
                                                       type="text"
                                                       x-model="block.alt"
                                                       @input="saveHistory()"
                                                       class="w-full mt-2 border border-gray-300 rounded px-3 py-1 text-sm"
                                                       placeholder="คำอธิบายรูปภาพ...">
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Alert/Notice Block -->
                                    <template x-if="block.type === 'alert'">
                                        <div class="p-4 flex items-start space-x-3"
                                             :class="{
                                                 'bg-blue-50 border-l-4 border-blue-500': block.variant === 'info',
                                                 'bg-green-50 border-l-4 border-green-500': block.variant === 'success',
                                                 'bg-yellow-50 border-l-4 border-yellow-500': block.variant === 'warning',
                                                 'bg-red-50 border-l-4 border-red-500': block.variant === 'error'
                                             }">
                                            <div class="flex-shrink-0 mt-0.5">
                                                <template x-if="block.variant === 'info'">
                                                    <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                    </svg>
                                                </template>
                                                <template x-if="block.variant === 'success'">
                                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                </template>
                                                <template x-if="block.variant === 'warning'">
                                                    <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                </template>
                                                <template x-if="block.variant === 'error'">
                                                    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                    </svg>
                                                </template>
                                            </div>
                                            <div class="flex-1">
                                                <div x-show="!previewMode && selectedBlock?.id === block.id" class="space-y-2">
                                                    <select x-model="block.variant" @change="saveHistory()"
                                                            class="text-xs border border-gray-300 rounded px-2 py-1">
                                                        <option value="info">ข้อมูล</option>
                                                        <option value="success">สำเร็จ</option>
                                                        <option value="warning">คำเตือน</option>
                                                        <option value="error">ข้อผิดพลาด</option>
                                                    </select>
                                                    <textarea x-model="block.content"
                                                              @input="saveHistory()"
                                                              rows="2"
                                                              class="w-full border border-gray-300 rounded px-3 py-2 text-sm resize-none"
                                                              placeholder="ข้อความ..."></textarea>
                                                </div>
                                                <p x-show="previewMode || selectedBlock?.id !== block.id"
                                                   class="text-sm"
                                                   x-text="block.content || 'ข้อความ...'"></p>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Button Block -->
                                    <template x-if="block.type === 'button'">
                                        <div class="p-4" :class="'text-' + (block.style?.align || 'left')">
                                            <div x-show="!previewMode && selectedBlock?.id === block.id" class="space-y-2">
                                                <input type="text"
                                                       x-model="block.content"
                                                       @input="saveHistory()"
                                                       class="w-full border border-gray-300 rounded px-3 py-2"
                                                       placeholder="ข้อความปุ่ม">
                                                <input type="text"
                                                       x-model="block.url"
                                                       @input="saveHistory()"
                                                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                                       placeholder="URL">
                                            </div>
                                            <a x-show="previewMode || selectedBlock?.id !== block.id"
                                               :href="block.url || '#'"
                                               class="inline-block px-6 py-2 rounded-lg font-medium transition"
                                               :class="{
                                                   'bg-primary-600 text-white hover:bg-primary-700': block.variant !== 'outline',
                                                   'border-2 border-primary-600 text-primary-600 hover:bg-primary-50': block.variant === 'outline'
                                               }"
                                               x-text="block.content || 'ปุ่ม'">
                                            </a>
                                        </div>
                                    </template>

                                    <!-- Columns Block -->
                                    <template x-if="block.type === 'columns'">
                                        <div class="p-4">
                                            <div class="grid gap-4"
                                                 :class="'grid-cols-' + (block.columns || 2)">
                                                <template x-for="(col, colIndex) in (block.columnData || [{}, {}])" :key="colIndex">
                                                    <div class="border border-dashed border-gray-300 rounded-lg p-3 min-h-[100px]"
                                                         :class="{ 'border-primary-400': !previewMode }">
                                                        <textarea x-show="!previewMode"
                                                                  x-model="block.columnData[colIndex].content"
                                                                  @input="saveHistory()"
                                                                  rows="3"
                                                                  class="w-full border-0 p-0 focus:ring-0 bg-transparent resize-none text-sm"
                                                                  :placeholder="'คอลัมน์ ' + (colIndex + 1)"></textarea>
                                                        <p x-show="previewMode"
                                                           class="text-sm whitespace-pre-wrap"
                                                           x-text="col.content || ''"></p>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Icon Box Block -->
                                    <template x-if="block.type === 'icon-box'">
                                        <div class="p-4">
                                            <div class="flex items-start space-x-4">
                                                <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center"
                                                     :style="'background-color: ' + (block.iconColor || '#7c3aed') + '15'">
                                                    <div class="text-2xl" :style="'color: ' + (block.iconColor || '#7c3aed')"
                                                         x-html="getIconSvg(block.icon)"></div>
                                                </div>
                                                <div class="flex-1">
                                                    <div x-show="!previewMode && selectedBlock?.id === block.id" class="space-y-2">
                                                        <div class="flex items-center space-x-2">
                                                            <select x-model="block.icon" @change="saveHistory()"
                                                                    class="text-xs border border-gray-300 rounded px-2 py-1">
                                                                <option value="star">⭐ ดาว</option>
                                                                <option value="check-circle">✓ เครื่องหมายถูก</option>
                                                                <option value="heart">❤️ หัวใจ</option>
                                                                <option value="lightning">⚡ สายฟ้า</option>
                                                                <option value="shield">🛡️ โล่</option>
                                                                <option value="rocket">🚀 จรวด</option>
                                                                <option value="globe">🌐 โลก</option>
                                                                <option value="code">💻 โค้ด</option>
                                                                <option value="cog">⚙️ เฟือง</option>
                                                                <option value="chart">📊 กราฟ</option>
                                                                <option value="users">👥 ผู้ใช้</option>
                                                                <option value="clock">🕐 นาฬิกา</option>
                                                                <option value="sparkles">✨ ประกาย</option>
                                                                <option value="fire">🔥 ไฟ</option>
                                                                <option value="trophy">🏆 ถ้วย</option>
                                                            </select>
                                                            <input type="color"
                                                                   x-model="block.iconColor"
                                                                   @input="saveHistory()"
                                                                   class="w-8 h-8 rounded cursor-pointer border border-gray-300"
                                                                   title="สีไอคอน">
                                                        </div>
                                                        <input type="text"
                                                               x-model="block.content"
                                                               @input="saveHistory()"
                                                               class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm font-semibold"
                                                               placeholder="หัวข้อ">
                                                        <textarea x-model="block.description"
                                                                  @input="saveHistory()"
                                                                  rows="2"
                                                                  class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm resize-none"
                                                                  placeholder="รายละเอียด"></textarea>
                                                    </div>
                                                    <div x-show="previewMode || selectedBlock?.id !== block.id">
                                                        <h4 class="font-semibold text-gray-900" x-text="block.content || 'หัวข้อ'"></h4>
                                                        <p class="text-sm text-gray-600 mt-1" x-text="block.description || 'รายละเอียด'"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Feature Card Block -->
                                    <template x-if="block.type === 'feature-card'">
                                        <div class="p-4 rounded-lg" :style="'background-color: ' + (block.style?.bgColor || '#f0fdf4')">
                                            <div class="flex items-start space-x-3">
                                                <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center bg-white shadow-sm"
                                                     :style="'color: ' + (block.iconColor || '#059669')">
                                                    <div class="text-xl" x-html="getIconSvg(block.icon)"></div>
                                                </div>
                                                <div class="flex-1">
                                                    <div x-show="!previewMode && selectedBlock?.id === block.id" class="space-y-2">
                                                        <div class="flex items-center space-x-2">
                                                            <select x-model="block.icon" @change="saveHistory()"
                                                                    class="text-xs border border-gray-300 rounded px-2 py-1 bg-white">
                                                                <option value="check-circle">✓ เครื่องหมายถูก</option>
                                                                <option value="star">⭐ ดาว</option>
                                                                <option value="heart">❤️ หัวใจ</option>
                                                                <option value="lightning">⚡ สายฟ้า</option>
                                                                <option value="shield">🛡️ โล่</option>
                                                                <option value="rocket">🚀 จรวด</option>
                                                                <option value="globe">🌐 โลก</option>
                                                                <option value="code">💻 โค้ด</option>
                                                                <option value="cog">⚙️ เฟือง</option>
                                                                <option value="chart">📊 กราฟ</option>
                                                                <option value="users">👥 ผู้ใช้</option>
                                                                <option value="clock">🕐 นาฬิกา</option>
                                                                <option value="sparkles">✨ ประกาย</option>
                                                                <option value="fire">🔥 ไฟ</option>
                                                                <option value="trophy">🏆 ถ้วย</option>
                                                            </select>
                                                            <input type="color"
                                                                   x-model="block.iconColor"
                                                                   @input="saveHistory()"
                                                                   class="w-8 h-8 rounded cursor-pointer border border-gray-300"
                                                                   title="สีไอคอน">
                                                            <input type="color"
                                                                   x-model="block.style.bgColor"
                                                                   @input="saveHistory()"
                                                                   class="w-8 h-8 rounded cursor-pointer border border-gray-300"
                                                                   title="สีพื้นหลัง">
                                                        </div>
                                                        <input type="text"
                                                               x-model="block.title"
                                                               @input="saveHistory()"
                                                               class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm font-semibold bg-white"
                                                               placeholder="ชื่อฟีเจอร์">
                                                        <textarea x-model="block.content"
                                                                  @input="saveHistory()"
                                                                  rows="2"
                                                                  class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm resize-none bg-white"
                                                                  placeholder="รายละเอียดของฟีเจอร์"></textarea>
                                                    </div>
                                                    <div x-show="previewMode || selectedBlock?.id !== block.id">
                                                        <h4 class="font-semibold text-gray-900" x-text="block.title || 'ฟีเจอร์'"></h4>
                                                        <p class="text-sm text-gray-600 mt-1" x-text="block.content || 'รายละเอียด'"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Final drop zone -->
                    <div class="h-8 rounded transition-all duration-200"
                         :class="dropIndex === blocks.length ? 'bg-primary-400 my-2' : ''"
                         @dragover.prevent="dropIndex = blocks.length"
                         @dragleave="dropIndex = -1"
                         @drop="drop($event, blocks.length)">
                    </div>
                </div>
            </div>
        </div>

        <!-- Properties Panel -->
        <div x-show="selectedBlock && !previewMode"
             x-transition
             class="w-64 bg-white border-l border-gray-200 p-4 overflow-y-auto">
            <div class="text-sm font-semibold text-gray-700 mb-4">คุณสมบัติ</div>

            <div class="space-y-4">
                <!-- Spacer Height -->
                <template x-if="selectedBlock?.type === 'spacer'">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ความสูง (px)</label>
                        <input type="range"
                               x-model="selectedBlock.height"
                               @input="updateSelectedBlock()"
                               min="10" max="200" step="10"
                               class="w-full">
                        <div class="text-center text-xs text-gray-400" x-text="(selectedBlock.height || 40) + 'px'"></div>
                    </div>
                </template>

                <!-- Columns Count -->
                <template x-if="selectedBlock?.type === 'columns'">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">จำนวนคอลัมน์</label>
                        <select x-model="selectedBlock.columns"
                                @change="updateColumnsCount()"
                                class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                            <option value="2">2 คอลัมน์</option>
                            <option value="3">3 คอลัมน์</option>
                            <option value="4">4 คอลัมน์</option>
                        </select>
                    </div>
                </template>

                <!-- Divider Style -->
                <template x-if="selectedBlock?.type === 'divider'">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">รูปแบบเส้น</label>
                        <select x-model="selectedBlock.style.type"
                                @change="saveHistory()"
                                class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                            <option value="border-solid">เส้นทึบ</option>
                            <option value="border-dashed">เส้นประ</option>
                            <option value="border-dotted">จุด</option>
                        </select>
                    </div>
                </template>

                <!-- Padding -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">ระยะขอบใน</label>
                    <select x-model="selectedBlock.style.padding"
                            @change="saveHistory()"
                            class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                        <option value="0">ไม่มี</option>
                        <option value="8">เล็ก</option>
                        <option value="16">ปกติ</option>
                        <option value="24">ใหญ่</option>
                        <option value="32">ใหญ่มาก</option>
                    </select>
                </div>

                <!-- Border Radius -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">มุมโค้ง</label>
                    <select x-model="selectedBlock.style.borderRadius"
                            @change="saveHistory()"
                            class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                        <option value="0">ไม่มี</option>
                        <option value="4">เล็ก</option>
                        <option value="8">ปกติ</option>
                        <option value="16">ใหญ่</option>
                        <option value="9999">เต็มที่</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Live Preview Panel -->
        <div x-show="showLivePreview && !previewMode"
             x-transition
             class="bg-gray-100 border-l border-gray-200 overflow-y-auto"
             :class="isFullscreen ? 'w-1/3' : 'w-80'">
            <div class="bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between sticky top-0 z-10">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">ตัวอย่างแบบ Real-time</span>
                </div>
                <button type="button" @click="showLivePreview = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 min-h-[200px]">
                    <template x-if="blocks.length === 0">
                        <div class="text-center text-gray-400 py-8">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <p class="text-sm">ยังไม่มีเนื้อหา</p>
                        </div>
                    </template>
                    <div class="space-y-3" x-html="renderPreview()"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Format Modal -->
    <div x-show="showAiFormatModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50"
         @click.self="showAiFormatModal = false"
         @keydown.escape.window="showAiFormatModal = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="transform scale-95 opacity-0"
             x-transition:enter-end="transform scale-100 opacity-100">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold">AI จัดรูปแบบอัตโนมัติ</h3>
                            <p class="text-xs text-purple-200">วางข้อความแล้ว AI จะจัดรูปแบบให้สวยงามทันที</p>
                        </div>
                    </div>
                    <button type="button" @click="showAiFormatModal = false" class="text-white hover:text-purple-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-4">
                <!-- Template Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">เลือกรูปแบบการจัดเรียง</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="aiTemplate = 'features'"
                                :class="aiTemplate === 'features' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-gray-200 hover:border-gray-300'"
                                class="border-2 rounded-lg p-3 text-center transition">
                            <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-xs font-medium">คุณสมบัติ</span>
                        </button>
                        <button type="button" @click="aiTemplate = 'steps'"
                                :class="aiTemplate === 'steps' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-gray-200 hover:border-gray-300'"
                                class="border-2 rounded-lg p-3 text-center transition">
                            <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                            </svg>
                            <span class="text-xs font-medium">ขั้นตอน</span>
                        </button>
                        <button type="button" @click="aiTemplate = 'description'"
                                :class="aiTemplate === 'description' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-gray-200 hover:border-gray-300'"
                                class="border-2 rounded-lg p-3 text-center transition">
                            <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h12"/>
                            </svg>
                            <span class="text-xs font-medium">รายละเอียด</span>
                        </button>
                    </div>
                </div>

                <!-- Text Input -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วางข้อความที่ต้องการจัดรูปแบบ</label>
                    <textarea x-model="aiInputText"
                              rows="8"
                              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-purple-500 focus:border-purple-500"
                              placeholder="วางข้อความที่นี่...&#10;&#10;ตัวอย่าง:&#10;- ออกแบบเว็บไซต์&#10;- พัฒนาระบบหลังบ้าน&#10;- ติดตั้งและทดสอบ"></textarea>
                </div>

                <!-- Preview -->
                <div x-show="aiInputText.trim().length > 0">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ตัวอย่างผลลัพธ์</label>
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 max-h-48 overflow-y-auto">
                        <div class="space-y-2" x-html="previewAiFormat()"></div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <button type="button" @click="showAiFormatModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                    ยกเลิก
                </button>
                <button type="button" @click="applyAiFormat()"
                        :disabled="aiInputText.trim().length === 0"
                        class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>นำไปใช้</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function pageBuilder(initialValue, fieldName) {
    return {
        blocks: [],
        selectedBlock: null,
        previewMode: false,
        dropIndex: -1,
        draggingBlock: null,
        draggingIndex: -1,
        history: [],
        historyIndex: -1,
        maxHistory: 50,
        // New features
        isFullscreen: false,
        showLivePreview: false,
        showAiFormatModal: false,
        aiInputText: '',
        aiTemplate: 'features',
        fieldName: fieldName || 'content',

        availableBlocks: {
            basic: [
                { type: 'heading', label: 'หัวข้อ', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>' },
                { type: 'text', label: 'ข้อความ', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h12"/></svg>' },
                { type: 'list', label: 'รายการ', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>' },
                { type: 'numbered-list', label: 'ลำดับ', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>' },
            ],
            advanced: [
                { type: 'code', label: 'โค้ด', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>' },
                { type: 'quote', label: 'อ้างอิง', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>' },
                { type: 'image', label: 'รูปภาพ', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' },
                { type: 'icon-box', label: 'ไอคอน', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
                { type: 'feature-card', label: 'การ์ดฟีเจอร์', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>' },
                { type: 'alert', label: 'แจ้งเตือน', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
                { type: 'button', label: 'ปุ่ม', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>' },
            ],
            layout: [
                { type: 'divider', label: 'เส้นแบ่ง', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>' },
                { type: 'spacer', label: 'ช่องว่าง', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>' },
                { type: 'columns', label: 'คอลัมน์', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>' },
            ]
        },

        init() {
            // Parse initial value
            if (initialValue) {
                try {
                    let parsedBlocks;
                    if (typeof initialValue === 'string') {
                        parsedBlocks = JSON.parse(initialValue);
                    } else {
                        parsedBlocks = initialValue;
                    }
                    // Ensure all blocks have IDs
                    this.blocks = parsedBlocks.map(block => {
                        if (!block.id) {
                            block.id = this.generateId();
                        }
                        // Ensure style object exists
                        if (!block.style) {
                            block.style = {};
                        }
                        return block;
                    });
                } catch (e) {
                    // If not valid JSON, treat as plain text
                    if (initialValue.trim()) {
                        this.blocks = [{
                            id: this.generateId(),
                            type: 'text',
                            content: initialValue,
                            style: {}
                        }];
                    }
                }
            }
            this.saveHistory();
        },

        generateId() {
            return 'block_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        createBlock(blockDef) {
            const block = {
                id: this.generateId(),
                type: blockDef.type,
                content: '',
                style: {}
            };

            // Set defaults based on type
            switch (blockDef.type) {
                case 'alert':
                    block.variant = 'info';
                    break;
                case 'spacer':
                    block.height = 40;
                    break;
                case 'columns':
                    block.columns = 2;
                    block.columnData = [{content: ''}, {content: ''}];
                    break;
                case 'divider':
                    block.style = { type: 'border-solid' };
                    break;
                case 'code':
                    block.language = 'javascript';
                    break;
                case 'icon-box':
                    block.icon = 'star';
                    block.iconColor = '#7c3aed';
                    block.content = 'หัวข้อ';
                    block.description = 'รายละเอียด';
                    break;
                case 'feature-card':
                    block.icon = 'check-circle';
                    block.iconColor = '#059669';
                    block.title = 'ฟีเจอร์';
                    block.content = 'รายละเอียดของฟีเจอร์นี้';
                    block.style = { bgColor: '#f0fdf4' };
                    break;
            }

            return block;
        },

        getIconSvg(iconName) {
            const icons = {
                'star': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>',
                'check-circle': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
                'heart': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/></svg>',
                'lightning': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>',
                'shield': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
                'rocket': '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
                'globe': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"/></svg>',
                'code': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>',
                'cog': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>',
                'chart': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>',
                'users': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>',
                'clock': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>',
                'sparkles': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/></svg>',
                'fire': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/></svg>',
                'trophy': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 5a3 3 0 015-2.236A3 3 0 0114.83 6H16a2 2 0 110 4h-1.17a3 3 0 01-5.66 0H9.83a3 3 0 01-5.66 0H3a2 2 0 110-4h1.17C4.06 5.687 4 5.35 4 5a1 1 0 011-1zm4 7v4h2v-4h3a2 2 0 012 2v4H4v-4a2 2 0 012-2h3z" clip-rule="evenodd"/></svg>'
            };
            return icons[iconName] || icons['star'];
        },

        dragStart(event, block) {
            this.draggingBlock = block;
            this.draggingIndex = -1;
            event.dataTransfer.effectAllowed = 'copy';
            event.target.classList.add('opacity-50');
        },

        dragStartExisting(event, block, index) {
            this.draggingBlock = block;
            this.draggingIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.target.classList.add('opacity-50');
        },

        dragEnd(event) {
            event.target.classList.remove('opacity-50');
            this.draggingBlock = null;
            this.draggingIndex = -1;
            this.dropIndex = -1;
        },

        dragOver(event) {
            event.preventDefault();
        },

        drop(event, targetIndex) {
            event.preventDefault();

            if (this.draggingIndex >= 0) {
                // Moving existing block
                const block = this.blocks[this.draggingIndex];
                this.blocks.splice(this.draggingIndex, 1);
                const newIndex = targetIndex > this.draggingIndex ? targetIndex - 1 : targetIndex;
                this.blocks.splice(newIndex, 0, block);
            } else if (this.draggingBlock) {
                // Adding new block
                const newBlock = this.createBlock(this.draggingBlock);
                this.blocks.splice(targetIndex, 0, newBlock);
                this.selectedBlock = newBlock;
            }

            this.saveHistory();
            this.dropIndex = -1;
        },

        selectBlock(block) {
            if (!this.previewMode) {
                this.selectedBlock = block;
            }
        },

        deleteBlock(index) {
            if (this.selectedBlock?.id === this.blocks[index].id) {
                this.selectedBlock = null;
            }
            this.blocks.splice(index, 1);
            this.saveHistory();
        },

        duplicateBlock(index) {
            const original = this.blocks[index];
            const copy = JSON.parse(JSON.stringify(original));
            copy.id = this.generateId();
            this.blocks.splice(index + 1, 0, copy);
            this.selectedBlock = copy;
            this.saveHistory();
        },

        moveBlock(index, direction) {
            const newIndex = index + direction;
            if (newIndex >= 0 && newIndex < this.blocks.length) {
                const block = this.blocks[index];
                this.blocks.splice(index, 1);
                this.blocks.splice(newIndex, 0, block);
                this.saveHistory();
            }
        },

        updateBlockStyle(property, value) {
            if (this.selectedBlock) {
                if (!this.selectedBlock.style) {
                    this.selectedBlock.style = {};
                }
                this.selectedBlock.style[property] = value;
                this.saveHistory();
            }
        },

        toggleStyle(property) {
            if (this.selectedBlock) {
                if (!this.selectedBlock.style) {
                    this.selectedBlock.style = {};
                }
                this.selectedBlock.style[property] = !this.selectedBlock.style[property];
                this.saveHistory();
            }
        },

        updateSelectedBlock() {
            this.saveHistory();
        },

        updateColumnsCount() {
            if (this.selectedBlock?.type === 'columns') {
                const count = parseInt(this.selectedBlock.columns) || 2;
                while (this.selectedBlock.columnData.length < count) {
                    this.selectedBlock.columnData.push({content: ''});
                }
                while (this.selectedBlock.columnData.length > count) {
                    this.selectedBlock.columnData.pop();
                }
                this.saveHistory();
            }
        },

        isTextBlock(block) {
            return ['heading', 'text', 'quote'].includes(block?.type);
        },

        getTextClasses(block) {
            const classes = [];
            const style = block.style || {};

            if (style.bold) classes.push('font-bold');
            if (style.italic) classes.push('italic');
            if (style.underline) classes.push('underline');
            if (style.align) classes.push('text-' + style.align);

            return classes.join(' ');
        },

        getBlockStyle(block) {
            const style = block.style || {};
            const css = {};

            if (style.color) css.color = style.color;
            if (style.bgColor && style.bgColor !== '#ffffff') css.backgroundColor = style.bgColor;
            if (style.padding) css.padding = style.padding + 'px';

            return css;
        },

        getBlockContainerStyle(block) {
            const style = block.style || {};
            const css = {};

            if (style.borderRadius) css.borderRadius = style.borderRadius + 'px';

            return css;
        },

        copyCode(block) {
            navigator.clipboard.writeText(block.content || '');
        },

        clearAll() {
            if (confirm('ต้องการล้างเนื้อหาทั้งหมดหรือไม่?')) {
                this.blocks = [];
                this.selectedBlock = null;
                this.saveHistory();
            }
        },

        saveHistory() {
            // Remove future history if we're in the middle
            if (this.historyIndex < this.history.length - 1) {
                this.history = this.history.slice(0, this.historyIndex + 1);
            }

            // Add current state
            this.history.push(JSON.stringify(this.blocks));

            // Limit history size
            if (this.history.length > this.maxHistory) {
                this.history.shift();
            }

            this.historyIndex = this.history.length - 1;
        },

        undo() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                this.blocks = JSON.parse(this.history[this.historyIndex]);
                this.selectedBlock = null;
            }
        },

        redo() {
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                this.blocks = JSON.parse(this.history[this.historyIndex]);
                this.selectedBlock = null;
            }
        },

        // Fullscreen functionality
        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen;
            if (this.isFullscreen) {
                document.body.style.overflow = 'hidden';
                // Add ESC key listener
                this._escHandler = (e) => {
                    if (e.key === 'Escape') {
                        this.isFullscreen = false;
                        document.body.style.overflow = '';
                    }
                };
                document.addEventListener('keydown', this._escHandler);
            } else {
                document.body.style.overflow = '';
                if (this._escHandler) {
                    document.removeEventListener('keydown', this._escHandler);
                }
            }
        },

        // Live preview rendering
        renderPreview() {
            if (this.blocks.length === 0) return '';

            return this.blocks.map(block => this.renderBlockPreview(block)).join('');
        },

        renderBlockPreview(block) {
            const style = block.style || {};
            let textClasses = [];
            if (style.bold) textClasses.push('font-bold');
            if (style.italic) textClasses.push('italic');
            if (style.underline) textClasses.push('underline');
            if (style.align) textClasses.push('text-' + style.align);

            let inlineStyle = [];
            if (style.color) inlineStyle.push('color: ' + style.color);
            if (style.bgColor && style.bgColor !== '#ffffff') inlineStyle.push('background-color: ' + style.bgColor);
            if (style.padding) inlineStyle.push('padding: ' + style.padding + 'px');
            if (style.borderRadius) inlineStyle.push('border-radius: ' + style.borderRadius + 'px');

            const styleAttr = inlineStyle.length ? `style="${inlineStyle.join('; ')}"` : '';
            const classAttr = textClasses.length ? textClasses.join(' ') : '';

            switch (block.type) {
                case 'heading':
                    return `<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 mb-2" ${styleAttr}>
                        <h2 class="text-xl font-bold text-gray-900 ${classAttr}">${this.escapeHtml(block.content || 'หัวข้อ')}</h2>
                    </div>`;
                case 'text':
                    return `<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 mb-2" ${styleAttr}>
                        <p class="whitespace-pre-wrap text-gray-700 text-sm ${classAttr}">${this.escapeHtml(block.content || 'ข้อความ')}</p>
                    </div>`;
                case 'list':
                    const listItems = (block.content || '').split('\n').filter(i => i.trim()).map(item =>
                        `<li class="text-sm">${this.escapeHtml(item.replace(/^[-•*]\s*/, ''))}</li>`
                    ).join('');
                    return `<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 mb-2" ${styleAttr}>
                        <ul class="list-disc list-inside space-y-1 text-gray-700 ${classAttr}">${listItems}</ul>
                    </div>`;
                case 'numbered-list':
                    const numberedItems = (block.content || '').split('\n').filter(i => i.trim()).map(item =>
                        `<li class="text-sm">${this.escapeHtml(item.replace(/^\d+\.\s*/, ''))}</li>`
                    ).join('');
                    return `<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 mb-2" ${styleAttr}>
                        <ol class="list-decimal list-inside space-y-1 text-gray-700 ${classAttr}">${numberedItems}</ol>
                    </div>`;
                case 'quote':
                    return `<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-2">
                        <div class="p-3 border-l-4 border-primary-500 bg-primary-50" ${styleAttr}>
                            <blockquote class="italic text-gray-700 text-sm">${this.escapeHtml(block.content || 'ข้อความอ้างอิง')}</blockquote>
                        </div>
                    </div>`;
                case 'alert':
                    const variant = block.variant || 'info';
                    const alertColors = {
                        info: 'bg-blue-50 border-l-4 border-blue-500',
                        success: 'bg-green-50 border-l-4 border-green-500',
                        warning: 'bg-yellow-50 border-l-4 border-yellow-500',
                        error: 'bg-red-50 border-l-4 border-red-500'
                    };
                    return `<div class="rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-2">
                        <div class="p-3 ${alertColors[variant]}">
                            <p class="text-sm text-gray-700">${this.escapeHtml(block.content || 'ข้อความแจ้งเตือน')}</p>
                        </div>
                    </div>`;
                case 'divider':
                    return `<div class="py-2 mb-2"><hr class="border-gray-300 ${style.type || 'border-solid'}"></div>`;
                case 'spacer':
                    return `<div style="height: ${block.height || 40}px" class="mb-2"></div>`;
                case 'icon-box':
                    const iconColor = block.iconColor || '#7c3aed';
                    return `<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 mb-2">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: ${iconColor}15; color: ${iconColor}">
                                ${this.getIconSvg(block.icon)}
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 text-sm">${this.escapeHtml(block.content || 'หัวข้อ')}</h4>
                                <p class="text-xs text-gray-600 mt-1">${this.escapeHtml(block.description || 'รายละเอียด')}</p>
                            </div>
                        </div>
                    </div>`;
                case 'feature-card':
                    const fIconColor = block.iconColor || '#059669';
                    const bgColor = block.style?.bgColor || '#f0fdf4';
                    return `<div class="rounded-lg shadow-sm border border-gray-200 p-3 mb-2" style="background-color: ${bgColor}">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-white shadow-sm" style="color: ${fIconColor}">
                                ${this.getIconSvg(block.icon)}
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 text-sm">${this.escapeHtml(block.title || 'ฟีเจอร์')}</h4>
                                <p class="text-xs text-gray-600 mt-1">${this.escapeHtml(block.content || 'รายละเอียด')}</p>
                            </div>
                        </div>
                    </div>`;
                case 'image':
                    if (!block.src) return `<div class="bg-gray-100 rounded-lg p-4 text-center text-gray-400 text-sm mb-2">รูปภาพ (ยังไม่มี URL)</div>`;
                    return `<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-2 mb-2">
                        <img src="${block.src}" alt="${this.escapeHtml(block.alt || '')}" class="max-w-full h-auto rounded" style="max-height: 150px; object-fit: contain;">
                    </div>`;
                default:
                    return '';
            }
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        // AI Format functionality
        previewAiFormat() {
            const text = this.aiInputText.trim();
            if (!text) return '';

            const blocks = this.parseTextToBlocks(text, this.aiTemplate);
            return blocks.map(block => this.renderBlockPreview(block)).join('');
        },

        parseTextToBlocks(text, template) {
            const lines = text.split('\n').filter(line => line.trim());
            const blocks = [];

            if (template === 'features') {
                // Features template: heading + bullet list
                blocks.push({
                    id: this.generateId(),
                    type: 'heading',
                    content: '✨ คุณสมบัติเด่น',
                    style: { color: '#7c3aed' }
                });
                blocks.push({
                    id: this.generateId(),
                    type: 'list',
                    content: lines.map(line => line.replace(/^[-•*\d.)\s]+/, '').trim()).join('\n'),
                    style: {}
                });
            } else if (template === 'steps') {
                // Steps template: heading + numbered list with alert
                blocks.push({
                    id: this.generateId(),
                    type: 'heading',
                    content: '📋 ขั้นตอนการทำงาน',
                    style: { color: '#059669' }
                });
                blocks.push({
                    id: this.generateId(),
                    type: 'numbered-list',
                    content: lines.map(line => line.replace(/^[-•*\d.)\s]+/, '').trim()).join('\n'),
                    style: {}
                });
                blocks.push({
                    id: this.generateId(),
                    type: 'alert',
                    content: 'เราทำงานอย่างมืออาชีพในทุกขั้นตอน',
                    variant: 'success',
                    style: {}
                });
            } else if (template === 'description') {
                // Description template: auto-detect structure
                let currentParagraph = [];

                lines.forEach((line, index) => {
                    const trimmedLine = line.trim();

                    // Check if it's a header (short, no punctuation at end)
                    if (trimmedLine.length < 40 && !trimmedLine.match(/[.,:;!?]$/)) {
                        // Save any pending paragraph
                        if (currentParagraph.length > 0) {
                            blocks.push({
                                id: this.generateId(),
                                type: 'text',
                                content: currentParagraph.join('\n'),
                                style: {}
                            });
                            currentParagraph = [];
                        }

                        // Add as heading
                        blocks.push({
                            id: this.generateId(),
                            type: 'heading',
                            content: trimmedLine,
                            style: {}
                        });
                    }
                    // Check if it's a list item
                    else if (trimmedLine.match(/^[-•*\d.)]/) ) {
                        // Save any pending paragraph
                        if (currentParagraph.length > 0) {
                            blocks.push({
                                id: this.generateId(),
                                type: 'text',
                                content: currentParagraph.join('\n'),
                                style: {}
                            });
                            currentParagraph = [];
                        }

                        // Collect consecutive list items
                        const listItems = [trimmedLine.replace(/^[-•*\d.)\s]+/, '').trim()];
                        while (index + 1 < lines.length && lines[index + 1].trim().match(/^[-•*\d.)]/)) {
                            index++;
                            listItems.push(lines[index].trim().replace(/^[-•*\d.)\s]+/, '').trim());
                        }

                        blocks.push({
                            id: this.generateId(),
                            type: 'list',
                            content: listItems.join('\n'),
                            style: {}
                        });
                    }
                    else {
                        currentParagraph.push(trimmedLine);
                    }
                });

                // Save any remaining paragraph
                if (currentParagraph.length > 0) {
                    blocks.push({
                        id: this.generateId(),
                        type: 'text',
                        content: currentParagraph.join('\n'),
                        style: {}
                    });
                }
            }

            return blocks;
        },

        applyAiFormat() {
            const text = this.aiInputText.trim();
            if (!text) return;

            const newBlocks = this.parseTextToBlocks(text, this.aiTemplate);
            this.blocks = [...this.blocks, ...newBlocks];
            this.saveHistory();

            // Close modal and reset
            this.showAiFormatModal = false;
            this.aiInputText = '';

            // Select the first new block
            if (newBlocks.length > 0) {
                this.selectedBlock = newBlocks[0];
            }
        }
    };
}
</script>
@endpush
