@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แผนผังสายงาน Affiliate')

@section('content')
<div x-data="affiliateTree()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แผนผังสายงาน Affiliate</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                สมาชิกทั้งหมด {{ number_format($totalAffiliates) }} คน | ระดับบนสุด {{ number_format($totalRoots) }} คน
            </p>
        </div>
        <a href="{{ route('admin.affiliates.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            กลับรายการ
        </a>
    </div>

    <!-- Search -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <input type="text" x-model="searchQuery" placeholder="ค้นหาชื่อ, อีเมล, โค้ด..."
               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
        <div class="mt-2 flex items-center gap-3">
            <button @click="expandAll()" class="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                ขยายทั้งหมด
            </button>
            <button @click="collapseAll()" class="text-xs text-gray-600 hover:text-gray-700 dark:text-gray-400">
                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                ยุบทั้งหมด
            </button>
        </div>
    </div>

    <!-- Tree -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <template x-if="filteredTree.length === 0">
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p x-show="searchQuery">ไม่พบสมาชิกที่ตรงกับการค้นหา</p>
                <p x-show="!searchQuery">ยังไม่มีสมาชิก Affiliate</p>
            </div>
        </template>

        <template x-for="node in filteredTree" :key="node.id">
            <div>
                <div x-data="{ open: true }" class="mb-1">
                    <!-- Node -->
                    <div class="flex items-center gap-2 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-750 border border-transparent hover:border-gray-200 dark:hover:border-gray-600 transition group">
                        <!-- Expand/Collapse -->
                        <button x-show="node.children && node.children.length > 0"
                                @click="open = !open"
                                class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <div x-show="!node.children || node.children.length === 0" class="w-6"></div>

                        <!-- Avatar -->
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white"
                             :class="node.status === 'active' ? 'bg-green-500' : 'bg-red-400'"
                             x-text="node.name.charAt(0).toUpperCase()">
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="node.name"></span>
                                <code class="text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-gray-400" x-text="node.referral_code"></code>
                                <span class="text-xs px-1.5 py-0.5 rounded-full"
                                      :class="node.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'"
                                      x-text="node.status_label"></span>
                            </div>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="node.email"></span>
                                <span class="text-xs text-green-600" x-text="'฿' + Number(node.total_earned).toLocaleString()"></span>
                                <span class="text-xs text-gray-500" x-text="node.commission_rate + '%'"></span>
                                <span x-show="node.children_count > 0" class="text-xs text-indigo-600 dark:text-indigo-400" x-text="node.children_count + ' ลูกทีม'"></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="hidden group-hover:flex items-center gap-1">
                            <a :href="'/admin/affiliates/' + node.id" class="px-2 py-1 text-xs bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 rounded hover:bg-blue-100 transition">ดู</a>
                            <button @click="openMoveModal(node.id, node.name)"
                                    class="px-2 py-1 text-xs bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 rounded hover:bg-indigo-100 transition">ย้าย</button>
                            <template x-if="node.status === 'active'">
                                <form :action="'/admin/affiliates/' + node.id + '/suspend'" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-2 py-1 text-xs bg-red-50 text-red-700 dark:bg-red-900/50 dark:text-red-300 rounded hover:bg-red-100 transition"
                                            onclick="return confirm('ระงับ Affiliate นี้?')">บล๊อก</button>
                                </form>
                            </template>
                            <template x-if="node.status === 'suspended'">
                                <form :action="'/admin/affiliates/' + node.id + '/activate'" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-2 py-1 text-xs bg-green-50 text-green-700 dark:bg-green-900/50 dark:text-green-300 rounded hover:bg-green-100 transition">เปิดใช้</button>
                                </form>
                            </template>
                        </div>
                    </div>

                    <!-- Children (recursive) -->
                    <div x-show="open && node.children && node.children.length > 0" x-collapse class="ml-8 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
                        <template x-for="child in node.children" :key="child.id">
                            <div x-data="{ childOpen: true }" class="mb-1">
                                <!-- Child Node -->
                                <div class="flex items-center gap-2 p-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-750 transition group">
                                    <button x-show="child.children && child.children.length > 0"
                                            @click="childOpen = !childOpen"
                                            class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="w-3.5 h-3.5 transition-transform" :class="childOpen ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                    <div x-show="!child.children || child.children.length === 0" class="w-5"></div>

                                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white"
                                         :class="child.status === 'active' ? 'bg-green-400' : 'bg-red-300'"
                                         x-text="child.name.charAt(0).toUpperCase()"></div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate" x-text="child.name"></span>
                                            <code class="text-xs px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-500" x-text="child.referral_code"></code>
                                            <span class="text-xs px-1.5 py-0.5 rounded-full"
                                                  :class="child.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'"
                                                  x-text="child.status_label"></span>
                                        </div>
                                        <div class="flex items-center gap-3 mt-0.5">
                                            <span class="text-xs text-green-600" x-text="'฿' + Number(child.total_earned).toLocaleString()"></span>
                                            <span class="text-xs text-gray-500" x-text="child.commission_rate + '%'"></span>
                                            <span x-show="child.children_count > 0" class="text-xs text-indigo-500" x-text="child.children_count + ' ลูกทีม'"></span>
                                        </div>
                                    </div>

                                    <div class="hidden group-hover:flex items-center gap-1">
                                        <a :href="'/admin/affiliates/' + child.id" class="px-2 py-1 text-xs bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 rounded hover:bg-blue-100 transition">ดู</a>
                                        <button @click="openMoveModal(child.id, child.name)"
                                                class="px-2 py-1 text-xs bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 rounded hover:bg-indigo-100 transition">ย้าย</button>
                                    </div>
                                </div>

                                <!-- Grandchildren -->
                                <div x-show="childOpen && child.children && child.children.length > 0" x-collapse class="ml-7 pl-4 border-l-2 border-gray-100 dark:border-gray-700">
                                    <template x-for="gc in child.children" :key="gc.id">
                                        <div class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-750 transition group mb-1">
                                            <div class="w-5"></div>
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white"
                                                 :class="gc.status === 'active' ? 'bg-green-300' : 'bg-red-200'"
                                                 x-text="gc.name.charAt(0).toUpperCase()"></div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300" x-text="gc.name"></span>
                                                    <code class="text-xs px-1 bg-gray-100 dark:bg-gray-700 rounded text-gray-500" x-text="gc.referral_code"></code>
                                                </div>
                                                <span class="text-xs text-green-600" x-text="'฿' + Number(gc.total_earned).toLocaleString()"></span>
                                            </div>
                                            <a :href="'/admin/affiliates/' + gc.id" class="hidden group-hover:inline-block px-2 py-1 text-xs bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 rounded">ดู</a>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Move Modal -->
    <div x-show="showMoveModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" @click="showMoveModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 z-10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    ย้ายสายงาน <span x-text="moveAffiliateName" class="text-indigo-600 dark:text-indigo-400"></span>
                </h3>
                <form :action="'/admin/affiliates/' + moveAffiliateId + '/move'" method="POST">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลือก Upline ใหม่</label>
                    <select name="new_parent_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="">ไม่มี Upline (ระดับบนสุด)</option>
                        @foreach($allAffiliates as $aff)
                            <option value="{{ $aff->id }}">{{ $aff->user->name ?? 'N/A' }} ({{ $aff->referral_code }})</option>
                        @endforeach
                    </select>
                    <div class="flex gap-3 mt-6">
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">ย้าย</button>
                        <button type="button" @click="showMoveModal = false" class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function affiliateTree() {
    return {
        tree: @json($treeData),
        searchQuery: '',
        showMoveModal: false,
        moveAffiliateId: null,
        moveAffiliateName: '',

        get filteredTree() {
            if (!this.searchQuery.trim()) return this.tree;
            const q = this.searchQuery.toLowerCase();
            return this.tree.filter(node => this.nodeMatches(node, q));
        },

        nodeMatches(node, q) {
            if (node.name.toLowerCase().includes(q) || node.email.toLowerCase().includes(q) || node.referral_code.toLowerCase().includes(q)) return true;
            if (node.children) return node.children.some(c => this.nodeMatches(c, q));
            return false;
        },

        openMoveModal(id, name) {
            this.moveAffiliateId = id;
            this.moveAffiliateName = name;
            this.showMoveModal = true;
        },

        expandAll() {
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el.__x) el.__x.$data.open = true;
                if (el.__x) el.__x.$data.childOpen = true;
            });
        },

        collapseAll() {
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el.__x) el.__x.$data.open = false;
                if (el.__x) el.__x.$data.childOpen = false;
            });
        }
    }
}
</script>
@endpush
@endsection
