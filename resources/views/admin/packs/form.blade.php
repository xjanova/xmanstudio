@extends($adminLayout ?? 'layouts.admin')

@section('title', $pack->exists ? 'แก้ไขชุด' : 'เพิ่มชุด')
@section('page-title', $pack->exists ? 'แก้ไขชุด' : 'เพิ่มชุด')

@section('content')
@php($version = $pack->exists ? $pack->product?->latestVersion() : null)

<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-fuchsia-600 via-purple-600 to-indigo-600 p-6 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="relative flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">
                {{ $pack->exists ? $pack->product?->name : 'เพิ่มชุดใหม่' }}
            </h1>
            <p class="mt-1 text-white/80 text-sm">
                ชุดหนึ่งชุดคือสินค้าหนึ่งชิ้น — ราคา คำสั่งซื้อ และไลเซนส์ใช้ระบบเดิมทั้งหมด
            </p>
        </div>
        <a href="{{ route('admin.packs.index') }}"
           class="px-4 py-2 bg-white/20 text-white rounded-xl hover:bg-white/30 text-sm font-medium">
            กลับ
        </a>
    </div>
</div>

@if($errors->any())
    <div class="mb-6 rounded-xl bg-red-50 border border-red-200 p-4">
        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
        <form method="POST"
              action="{{ $pack->exists ? route('admin.packs.update', $pack) : route('admin.packs.store') }}"
              enctype="multipart/form-data"
              x-data="{ kind: '{{ old('kind', $pack->kind ?? 'character') }}' }">
            @csrf
            @if($pack->exists) @method('PUT') @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        รหัสชุด (pack id)
                    </label>
                    <input type="text" name="pack_id" required
                           value="{{ old('pack_id', $pack->pack_id) }}"
                           class="w-full rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-600 font-mono"
                           placeholder="nana-office">
                    {{-- ตัวนี้ต้องตรงกับ id ใน pack.json เป๊ะ ๆ ไม่งั้นแอปโหลดมาแล้ว
                         จับคู่กับสิ่งที่ซื้อไม่ได้ · ตอนอัปโหลดไฟล์มีด่านเช็คให้อีกชั้น --}}
                    <p class="mt-1 text-xs text-gray-500">
                        ต้องตรงกับ <code>id</code> ใน <code>pack.json</code> ที่อยู่ในไฟล์ zip · ใช้ได้เฉพาะ a-z 0-9 . _ -
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (ไทย)</label>
                    <input type="text" name="name_th" required
                           value="{{ old('name_th', $pack->product?->name) }}"
                           class="w-full rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (อังกฤษ)</label>
                    <input type="text" name="name_en"
                           value="{{ old('name_en', $pack->name_en) }}"
                           class="w-full rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-600">
                    <p class="mt-1 text-xs text-gray-500">ไม่ใส่ = ใช้ชื่อไทย</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชนิด</label>
                    <select name="kind" x-model="kind"
                            class="w-full rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-600">
                        <option value="character">ตัวละคร — เลขาคนใหม่ทั้งตัว</option>
                        <option value="outfit">ชุดแต่งตัว — ของตัวละครที่มีอยู่</option>
                        <option value="prop">ของประดับ — โมเดล 3D บนเวที</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคา (บาท)</label>
                    <input type="number" name="price" step="1" min="0" required
                           value="{{ old('price', $pack->product?->price ?? 0) }}"
                           class="w-full rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-600">
                    <p class="mt-1 text-xs text-gray-500">0 = แจกฟรี (โหลดได้โดยไม่ต้องซื้อ)</p>
                </div>

                <div class="sm:col-span-2" x-show="kind === 'outfit'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ต้องมีตัวละครนี้ก่อน
                    </label>
                    <select name="requires"
                            class="w-full rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-600">
                        <option value="">— ไม่ต้องมีอะไรก่อน —</option>
                        @foreach($characters as $character)
                            <option value="{{ $character->pack_id }}"
                                {{ old('requires', $pack->requires) === $character->pack_id ? 'selected' : '' }}>
                                {{ $character->product?->name }} ({{ $character->pack_id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="3"
                              class="w-full rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-600">{{ old('description', $pack->product?->description) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ภาพตัวอย่าง</label>
                    <input type="file" name="preview" accept="image/*"
                           class="w-full text-sm text-gray-600 dark:text-gray-300">
                    @if($pack->preview_path)
                        <img src="{{ asset($pack->preview_path) }}" alt=""
                             class="mt-3 w-40 rounded-xl object-cover">
                    @endif
                </div>

                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $pack->is_active ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300">
                        <span class="text-sm text-gray-700 dark:text-gray-300">เปิดขาย</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2.5 bg-purple-600 text-white rounded-xl hover:bg-purple-700 font-medium">
                    {{ $pack->exists ? 'บันทึก' : 'สร้างชุด' }}
                </button>
            </div>
        </form>
    </div>

    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">ไฟล์ชุด (.zip)</h2>
            <p class="text-xs text-gray-500 mb-4">
                ระบบจะอ่าน <code>pack.json</code> ในไฟล์ แล้วเทียบ id กับรหัสชุดให้ก่อนบันทึก
            </p>

            @if(! $pack->exists)
                <p class="text-sm text-gray-500">สร้างชุดก่อน แล้วค่อยอัปโหลดไฟล์</p>
            @else
                @if($version && $version->storage_path)
                    <dl class="mb-4 text-sm space-y-1">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">เวอร์ชัน</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $version->version }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">ขนาด</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $version->file_size_formatted }}</dd>
                        </div>
                    </dl>
                    <p class="text-[11px] text-gray-400 font-mono break-all mb-4">
                        sha256 {{ $version->sha256 }}
                    </p>
                @else
                    <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800">
                        ยังไม่มีไฟล์ — ชุดนี้ขึ้นร้านได้ แต่กดโหลดแล้วแอปจะไม่ได้อะไร
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.packs.file', $pack) }}"
                      enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="file" accept=".zip" required
                           class="w-full text-sm text-gray-600 dark:text-gray-300">
                    <input type="text" name="version" placeholder="เวอร์ชัน (ไม่ใส่ = ตั้งให้เอง)"
                           class="w-full rounded-xl border-gray-300 dark:bg-gray-900 dark:border-gray-600 text-sm">
                    <button type="submit"
                            class="w-full px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 text-sm font-medium">
                        อัปโหลด
                    </button>
                </form>
            @endif
        </div>

        @if($pack->exists)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">ลบชุด</h2>
                <p class="text-xs text-gray-500 mb-4">
                    ลบไม่ได้ถ้ามีคนซื้อไปแล้ว — ใช้ปิดขายแทน
                </p>
                <form method="POST" action="{{ route('admin.packs.destroy', $pack) }}"
                      onsubmit="return confirm('ลบชุดนี้และสินค้าที่ผูกอยู่?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-xl hover:bg-red-100 text-sm font-medium">
                        ลบ
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
