@props(['name' => 'content', 'value' => '', 'placeholder' => 'ลากบล็อกมาวางที่นี่เพื่อเริ่มสร้างเนื้อหา...'])

<div x-data="pageBuilder(@js($value))"
     x-init="init()"
     class="page-builder border border-gray-300 rounded-xl overflow-hidden bg-white shadow-lg">

    <!-- Hidden input to store JSON data -->
    <input type="hidden" name="{{ $name }}" :value="JSON.stringify(blocks)">

    <!-- Top Toolbar -->
    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-4 py-2 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
            <span class="font-semibold text-sm">Page Builder</span>
        </div>
        <div class="flex items-center space-x-2">
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
            <button type="button" @click="previewMode = !previewMode"
                    class="px-3 py-1 text-xs rounded transition"
                    :class="previewMode ? 'bg-primary-600 text-white' : 'bg-gray-700 hover:bg-gray-600'">
                <span x-text="previewMode ? 'แก้ไข' : 'ดูตัวอย่าง'"></span>
            </button>
            <button type="button" @click="clearAll()" class="p-1.5 rounded hover:bg-red-600 transition" title="ล้างทั้งหมด">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="flex" style="min-height: 400px;">
        <!-- Blocks Sidebar -->
        <div class="w-64 bg-gray-50 border-r border-gray-200 p-3 overflow-y-auto" x-show="!previewMode">
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
    </div>
</div>

@push('scripts')
<script>
function pageBuilder(initialValue) {
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
                    if (typeof initialValue === 'string') {
                        this.blocks = JSON.parse(initialValue);
                    } else {
                        this.blocks = initialValue;
                    }
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
            }

            return block;
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
        }
    };
}
</script>
@endpush
