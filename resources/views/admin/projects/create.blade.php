@extends($adminLayout ?? 'layouts.admin')

@section('title', 'สร้างโครงการใหม่')
@section('page-title', 'สร้างโครงการใหม่')

@section('content')
<form action="{{ route('admin.projects.store') }}" method="POST" x-data="projectForm()">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลโครงการ</h3>

                @if($quotation)
                    <input type="hidden" name="quotation_id" value="{{ $quotation->id }}">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-blue-700">สร้างจากใบเสนอราคา #{{ $quotation->quote_number }}</span>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อโครงการ <span class="text-red-500">*</span></label>
                        <input type="text" name="project_name" value="{{ old('project_name', $quotation->service_name ?? '') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        @error('project_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ลูกค้า <span class="text-red-500">*</span></label>
                        <select name="user_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            <option value="">เลือกลูกค้า</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id', $quotation->user_id ?? '') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ประเภทโครงการ <span class="text-red-500">*</span></label>
                        <select name="project_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            @foreach(\App\Models\ProjectOrder::TYPE_LABELS as $value => $label)
                                <option value="{{ $value }}" {{ old('project_type', $quotation->service_type ?? '') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดโครงการ</label>
                        <textarea name="project_description" rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('project_description', $quotation->project_description ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">กำหนดการ</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">วันเริ่มโครงการ</label>
                        <input type="date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">กำหนดส่งมอบ</label>
                        <input type="date" name="expected_end_date" value="{{ old('expected_end_date') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">ฟีเจอร์/ไมล์สโตน</h3>
                    <button type="button" @click="addFeature()"
                            class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
                        + เพิ่มฟีเจอร์
                    </button>
                </div>

                <div class="space-y-3" x-show="features.length > 0">
                    <template x-for="(feature, index) in features" :key="index">
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <input type="text" :name="'features['+index+'][name]'" x-model="feature.name"
                                       placeholder="ชื่อฟีเจอร์" required
                                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                <input type="text" :name="'features['+index+'][description]'" x-model="feature.description"
                                       placeholder="รายละเอียด (optional)"
                                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                <input type="date" :name="'features['+index+'][due_date]'" x-model="feature.due_date"
                                       placeholder="กำหนดส่ง"
                                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <button type="button" @click="removeFeature(index)"
                                    class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div x-show="features.length === 0" class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <p>ยังไม่มีฟีเจอร์</p>
                    <button type="button" @click="addFeature()" class="mt-2 text-primary-600 hover:underline">+ เพิ่มฟีเจอร์แรก</button>
                </div>
            </div>

            <!-- Team Members -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">ทีมงาน</h3>
                    <button type="button" @click="addMember()"
                            class="text-sm px-3 py-1 bg-green-100 text-green-700 rounded-lg hover:bg-green-200">
                        + เพิ่มสมาชิก
                    </button>
                </div>

                <div class="space-y-3" x-show="members.length > 0">
                    <template x-for="(member, index) in members" :key="index">
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-3">
                                <input type="text" :name="'members['+index+'][name]'" x-model="member.name"
                                       placeholder="ชื่อ" required
                                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                <select :name="'members['+index+'][role]'" x-model="member.role" required
                                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">เลือกตำแหน่ง</option>
                                    <option value="project_manager">Project Manager</option>
                                    <option value="developer">Developer</option>
                                    <option value="designer">Designer</option>
                                    <option value="tester">Tester</option>
                                    <option value="analyst">Analyst</option>
                                    <option value="support">Support</option>
                                </select>
                                <input type="email" :name="'members['+index+'][email]'" x-model="member.email"
                                       placeholder="อีเมล (optional)"
                                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" :name="'members['+index+'][is_lead]'" :id="'is_lead_'+index"
                                           x-model="member.is_lead" value="1"
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <label :for="'is_lead_'+index" class="text-sm text-gray-600">หัวหน้าโครงการ</label>
                                </div>
                            </div>
                            <button type="button" @click="removeMember(index)"
                                    class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div x-show="members.length === 0" class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p>ยังไม่มีสมาชิกในทีม</p>
                    <button type="button" @click="addMember()" class="mt-2 text-primary-600 hover:underline">+ เพิ่มสมาชิกคนแรก</button>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Pricing -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ราคา</h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ราคารวม (บาท)</label>
                    <input type="number" name="total_price" value="{{ old('total_price', $quotation->grand_total ?? 0) }}" min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">บันทึกภายใน</h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">บันทึกสำหรับแอดมิน</label>
                    <textarea name="admin_notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('admin_notes') }}</textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <button type="submit" class="w-full px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold">
                    สร้างโครงการ
                </button>
                <a href="{{ route('admin.projects.index') }}" class="block text-center mt-3 text-gray-600 hover:text-gray-900">
                    ยกเลิก
                </a>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
function projectForm() {
    return {
        features: @json(old('features', $quotation->service_options ?? [])),
        members: @json(old('members', [])),

        addFeature() {
            this.features.push({ name: '', description: '', due_date: '' });
        },

        removeFeature(index) {
            this.features.splice(index, 1);
        },

        addMember() {
            this.members.push({ name: '', role: '', email: '', phone: '', is_lead: false });
        },

        removeMember(index) {
            this.members.splice(index, 1);
        }
    }
}
</script>
@endpush
@endsection
