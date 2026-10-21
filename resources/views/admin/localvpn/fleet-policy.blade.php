@extends($adminLayout ?? 'layouts.admin')

@section('title', 'LocalVPN - นโยบาย Fleet')
@section('page-title', 'LocalVPN - Relay Fleet: นโยบายและโปรไฟล์เกม')

@section('content')
@include('admin.localvpn._tabs')
@include('admin.localvpn._fleet_tabs')

@php
    $input = 'w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500';
    $tierLabels = ['free' => 'Free', 'trial' => 'Trial (ทดลอง)', 'pro' => 'Pro'];
    $tierErrors = $errors->getBag('default');
@endphp

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    {{-- Tier limits & budget --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">ขีดจำกัดตามระดับสมาชิก</h3>
        <p class="text-sm text-gray-500 mb-4">อยู่ในตั๋ว relay และรายชื่อ peer ของโหนด — โหนดบังคับใช้เอง · 0 = ไม่จำกัด</p>

        @if($tierErrors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                <ul class="list-disc list-inside">
                    @foreach($tierErrors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.localvpn.fleet.policy.update') }}" class="space-y-6">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 pr-3 text-gray-600 font-medium">ระดับ</th>
                            <th class="text-left py-2 px-2 text-gray-600 font-medium">Relay kbps</th>
                            <th class="text-left py-2 px-2 text-gray-600 font-medium">Relay MB/วัน</th>
                            <th class="text-center py-2 px-2 text-gray-600 font-medium">ลดปิง</th>
                            <th class="text-left py-2 pl-2 text-gray-600 font-medium">Exit MB/วัน</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($tierLabels as $tier => $label)
                            <tr>
                                <td class="py-2 pr-3 font-medium text-gray-900">{{ $label }}</td>
                                <td class="py-2 px-2">
                                    <input type="number" name="tiers[{{ $tier }}][relay_kbps]" min="0" max="10000000" required
                                           value="{{ old("tiers.$tier.relay_kbps", $policy['tiers'][$tier]['relay_kbps']) }}" class="{{ $input }} text-sm">
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" name="tiers[{{ $tier }}][relay_daily_mb]" min="0" max="10000000" required
                                           value="{{ old("tiers.$tier.relay_daily_mb", $policy['tiers'][$tier]['relay_daily_mb']) }}" class="{{ $input }} text-sm">
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <input type="hidden" name="tiers[{{ $tier }}][game]" value="0">
                                    <input type="checkbox" name="tiers[{{ $tier }}][game]" value="1"
                                           {{ old("tiers.$tier.game", $policy['tiers'][$tier]['game']) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                </td>
                                <td class="py-2 pl-2">
                                    <input type="number" name="tiers[{{ $tier }}][exit_daily_mb]" min="0" max="10000000" required
                                           value="{{ old("tiers.$tier.exit_daily_mb", $policy['tiers'][$tier]['exit_daily_mb']) }}" class="{{ $input }} text-sm">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-2 text-xs text-gray-500">VPN เต็มรูปแบบ (โหนด vpn) ใช้ได้เฉพาะ Pro เสมอ · ช่อง "ลดปิง" คุมสิทธิ์ใช้โหนด game</p>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h4 class="font-semibold text-gray-900 mb-1">งบประมาณ</h4>
                <p class="text-sm text-gray-500 mb-4">% ของปริมาณในแพ็กเกจของแต่ละโหนดในรอบบิลนี้ ที่โหนดเลิกรับผู้ใช้ใหม่ของแต่ละระดับ</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="monthly_thb">งบรวมต่อเดือน (บาท)</label>
                        <input type="number" step="0.01" min="0" name="budget[monthly_thb]" id="monthly_thb" required
                               value="{{ old('budget.monthly_thb', $policy['budget']['monthly_thb']) }}" class="{{ $input }}">
                        <p class="mt-1 text-xs text-gray-500">ค่าใช้จ่ายที่คาดการณ์ของทุกโหนดเกินงบนี้ = ปิด overage ทุกโหนด</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="free_cut_pct">ตัด Free ที่ (%)</label>
                        <input type="number" min="1" max="100" name="budget[free_cut_pct]" id="free_cut_pct" required
                               value="{{ old('budget.free_cut_pct', $policy['budget']['free_cut_pct']) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="trial_cut_pct">ตัด Trial ที่ (%)</label>
                        <input type="number" min="1" max="100" name="budget[trial_cut_pct]" id="trial_cut_pct" required
                               value="{{ old('budget.trial_cut_pct', $policy['budget']['trial_cut_pct']) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="pro_cut_pct">ตัดทุกคน (รวม Pro) ที่ (%)</label>
                        <input type="number" min="1" max="100" name="budget[pro_cut_pct]" id="pro_cut_pct" required
                               value="{{ old('budget.pro_cut_pct', $policy['budget']['pro_cut_pct']) }}" class="{{ $input }}">
                        <p class="mt-1 text-xs text-gray-500">ยกเว้นโหนดที่เปิด overage (เฉพาะ Pro)</p>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors shadow-sm">บันทึกนโยบาย</button>
            </div>
        </form>
    </div>

    {{-- Game profiles --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">โปรไฟล์เกม (ช่วง IP ของเซิร์ฟเวอร์เกม)</h3>
        <p class="text-sm text-gray-500 mb-4">แอปส่งเฉพาะช่วงเหล่านี้เข้า game exit และไฟร์วอลล์ของโหนดส่งต่อเฉพาะช่วงเหล่านี้ · IPv4 เท่านั้น, /8 หรือแคบกว่า, ห้ามช่วง private/สงวน · บรรทัดละหนึ่ง CIDR, ใส่ # หมายเหตุได้</p>

        @foreach(['profileCreate', 'profileUpdate'] as $bag)
            @if($errors->getBag($bag)->any())
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                    <ul class="list-disc list-inside">
                        @foreach($errors->getBag($bag)->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach

        <div class="space-y-3 mb-6">
            @forelse($profiles as $profile)
                <div class="border border-gray-200 rounded-xl p-4" x-data="{ editing: false }">
                    <div class="flex items-start justify-between gap-3" x-show="!editing">
                        <div>
                            <div class="font-medium text-gray-900">
                                {{ $profile->name }}
                                <span class="text-xs font-mono text-gray-400">{{ $profile->slug }}</span>
                                @unless($profile->enabled)
                                    <span class="ml-1 inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">ปิด</span>
                                @endunless
                            </div>
                            <div class="text-xs text-gray-500">{{ $profile->region ?: '—' }} · {{ count($profile->cidrList()) }} CIDR · ลำดับ {{ $profile->sort }}</div>
                        </div>
                        <div class="flex gap-3 whitespace-nowrap">
                            <button type="button" @click="editing = true" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">แก้ไข</button>
                            <form method="POST" action="{{ route('admin.localvpn.fleet.game-profiles.delete', $profile) }}" class="inline"
                                  onsubmit="return confirm('ลบโปรไฟล์เกม ' + @js($profile->name) + '? โหนด game จะหยุดส่งต่อช่วง IP ของเกมนี้ภายใน 5 นาที')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">ลบ</button>
                            </form>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.localvpn.fleet.game-profiles.update', $profile) }}" x-show="editing" x-cloak class="space-y-3">
                        @csrf
                        @method('PUT')
                        @include('admin.localvpn._fleet_profile_fields', ['profile' => $profile, 'input' => $input])
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700">บันทึก</button>
                            <button type="button" @click="editing = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">ยกเลิก</button>
                        </div>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500 text-center py-6 border border-dashed border-gray-200 rounded-xl">ยังไม่มีโปรไฟล์เกม — โหนด game จะไม่รับผู้ใช้จนกว่าจะเพิ่มอย่างน้อยหนึ่งโปรไฟล์</p>
            @endforelse
        </div>

        <div class="border-t border-gray-200 pt-4">
            <h4 class="font-semibold text-gray-900 mb-3">เพิ่มโปรไฟล์เกม</h4>
            <form method="POST" action="{{ route('admin.localvpn.fleet.game-profiles.store') }}" class="space-y-3">
                @csrf
                @include('admin.localvpn._fleet_profile_fields', ['profile' => null, 'input' => $input])
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700">เพิ่มโปรไฟล์</button>
            </form>
        </div>
    </div>
</div>
@endsection
