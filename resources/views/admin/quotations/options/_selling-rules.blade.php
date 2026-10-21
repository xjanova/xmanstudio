{{--
    ตรรกะการเสนอขายของตัวเลือกหนึ่งรายการ

    หน้า /quote อ่านค่าพวกนี้ตอนประกอบรายการให้ลูกค้า — เปลี่ยนที่นี่แล้วมีผล
    ทันทีโดยไม่ต้อง deploy ถ้าปล่อยว่างทั้งหมด ตัวเลือกจะทำงานแบบเดิม คือ
    ขึ้นเป็นรายการให้ติ๊กเองเฉย ๆ

    ใช้ร่วมกันทั้งหน้า create และ edit — $option จะมีก็ต่อเมื่อเป็นหน้าแก้ไข
--}}
@php
    $o = $option ?? null;
    $curRequires = old('requires', $o?->requires ?? []);
    $curSuggested = old('suggested_for', $o?->suggested_for ?? []);
@endphp

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6">
    <div class="flex items-start gap-3 mb-5">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shrink-0 shadow-lg">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M12 2 9.2 8.6 2 9.2l5.5 4.7L5.8 21 12 17.3 18.2 21l-1.7-7.1L22 9.2l-7.2-.6z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">ตรรกะการเสนอขาย</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                กำหนดว่ารายการนี้ถูกเลือกให้อัตโนมัติเมื่อไร และเกี่ยวข้องกับรายการไหน —
                หน้าสั่งงานอ่านจากตรงนี้ ไม่ได้ฝังไว้ในโค้ด
            </p>
        </div>
    </div>

    <div class="space-y-5">

        {{-- ติ๊กไว้ให้เลย --}}
        <label class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 rounded-xl cursor-pointer hover:bg-blue-100/60 dark:hover:bg-blue-500/15 transition-colors">
            {{-- ช่องนี้ไม่ติ๊กจะไม่ถูกส่งมาเลย คอนโทรลเลอร์จึงอ่านด้วย boolean()
                 ไม่ใช่ดูว่ามีคีย์ไหม ไม่งั้นปลดออกไม่ได้ --}}
            <input type="checkbox" name="is_core" value="1" {{ old('is_core', $o?->is_core) ? 'checked' : '' }}
                   class="mt-0.5 w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 shrink-0">
            <span>
                <span class="block text-sm font-semibold text-gray-900 dark:text-white">ติ๊กไว้ให้เลยเมื่อเลือกบริการนี้ (แกนหลัก)</span>
                <span class="block text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                    ลูกค้าจะเห็นว่าถูกเลือกไว้แล้วและปลดออกไม่ได้ ใช้กับสิ่งที่ส่งมอบงานไม่ได้ถ้าไม่มี
                    เช่น การติดตั้งขึ้นเซิร์ฟเวอร์
                </span>
            </span>
        </label>

        {{-- เหตุผล --}}
        <div>
            <label for="reason_th" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                เหตุผลที่ต้องมี <span class="font-normal text-gray-500">แสดงใต้ชื่อรายการ</span>
            </label>
            <input type="text" id="reason_th" name="reason_th" maxlength="255"
                   value="{{ old('reason_th', $o?->reason_th) }}"
                   placeholder="เช่น ถ้าข้ามขั้นนี้ ทีมจะเขียนโค้ดจากการเดา แล้วแก้ทีหลังแพงกว่าเดิม"
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                เขียนเป็นเหตุผล ไม่ใช่คำโฆษณา — บรรทัดนี้คือสิ่งที่ทำให้ลูกค้าตัดสินใจติ๊กเองได้
            </p>
        </div>

        {{-- ระยะเวลา --}}
        <div>
            <label for="duration_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                ใช้เวลากี่วันทำการ <span class="font-normal text-gray-500">0 = ไม่นับรวม</span>
            </label>
            <input type="number" id="duration_days" name="duration_days" min="0" max="3650"
                   value="{{ old('duration_days', $o?->duration_days ?? 0) }}"
                   class="w-40 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                รวมกันทุกรายการที่ลูกค้าเลือก แล้วแสดงเป็น “ประเมินเสร็จราว N สัปดาห์”
            </p>
        </div>

        {{-- ต้องมีอะไรคู่กัน --}}
        <div>
            <label for="requires" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                เลือกอันนี้แล้วต้องมีอะไรด้วย
            </label>
            <select id="requires" name="requires[]" multiple size="8"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">
                @foreach ($allOptions as $groupName => $rows)
                    <optgroup label="{{ $groupName }}">
                        @foreach ($rows as $row)
                            <option value="{{ $row['key'] }}" @selected(in_array($row['key'], (array) $curRequires, true))>{{ $row['label'] }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                กด Ctrl (หรือ Cmd) ค้างเพื่อเลือกหลายรายการ · ลูกค้าติ๊กอันนี้ ระบบจะติ๊กรายการที่เลือกไว้ให้เอง
                พร้อมบอกว่าติ๊กเพราะอะไร เช่น “ตะกร้าสินค้า” ต้องมี “ระบบสมาชิก”
            </p>
        </div>

        {{-- แนะนำกับงานแบบไหน --}}
        <div>
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                แนะนำขึ้นมาก่อน เมื่อลูกค้าเลือกผลลัพธ์นี้
            </span>
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach ($outcomes as $key => $outcome)
                    <label class="flex items-center gap-2.5 px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <input type="checkbox" name="suggested_for[]" value="{{ $key }}"
                               @checked(in_array($key, (array) $curSuggested, true))
                               class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 shrink-0">
                        <span class="text-sm text-gray-800 dark:text-gray-200">{{ $outcome['th'] }}</span>
                    </label>
                @endforeach
            </div>
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                ไม่ติ๊กเลย = ยังเลือกได้อยู่ แต่ถูกพับไว้ใต้ “เพิ่มได้ถ้าต้องการ”
            </p>
        </div>
    </div>
</div>
