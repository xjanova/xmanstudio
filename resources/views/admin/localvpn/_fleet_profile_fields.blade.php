{{-- Fields of one game profile; $profile is null on the "add" form. Old input refills only the add form. --}}
@php $useOld = $profile === null; @endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">ชื่อเกม</label>
        <input type="text" name="name" maxlength="100" required value="{{ $useOld ? old('name') : $profile->name }}" class="{{ $input }} text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">slug</label>
        <input type="text" name="slug" maxlength="64" required placeholder="rov-sea" value="{{ $useOld ? old('slug') : $profile->slug }}" class="{{ $input }} text-sm font-mono">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">ภูมิภาค</label>
        <input type="text" name="region" maxlength="64" placeholder="SEA" value="{{ $useOld ? old('region') : $profile->region }}" class="{{ $input }} text-sm">
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">ลำดับ</label>
            <input type="number" name="sort" min="-100000" max="100000" value="{{ $useOld ? old('sort', 0) : $profile->sort }}" class="{{ $input }} text-sm">
        </div>
        <label class="flex items-end gap-2 text-sm text-gray-700 pb-2">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" name="enabled" value="1" {{ ($useOld ? old('enabled', '1') : $profile->enabled) ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
            เปิดใช้
        </label>
    </div>
</div>
<div>
    <label class="block text-xs font-medium text-gray-700 mb-1">CIDR (บรรทัดละหนึ่ง)</label>
    <textarea name="cidrs" rows="6" required placeholder="# ตัวอย่าง&#10;43.229.64.0/18" class="{{ $input }} font-mono text-xs">{{ $useOld ? old('cidrs') : $profile->cidrs }}</textarea>
</div>
