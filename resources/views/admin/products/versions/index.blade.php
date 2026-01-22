@extends('layouts.admin')

@section('title', 'จัดการเวอร์ชัน - ' . $product->name)
@section('page-title', 'จัดการเวอร์ชัน: ' . $product->name)

@section('content')
<!-- Breadcrumb -->
<div class="mb-6">
    <a href="{{ route('admin.products.index') }}" class="text-primary-600 hover:underline flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        กลับไปรายการผลิตภัณฑ์
    </a>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">จำนวนเวอร์ชัน</p>
                <p class="text-2xl font-bold text-gray-900">{{ $product->versions->count() }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">เวอร์ชันล่าสุด</p>
                <p class="text-2xl font-bold text-gray-900">{{ $product->latestVersion()?->version ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">ดาวน์โหลด</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($downloadStats) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full {{ $product->githubSetting ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600' }}">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.341-3.369-1.341-.454-1.155-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">GitHub</p>
                <p class="text-xl font-bold text-gray-900">{{ $product->githubSetting ? 'เชื่อมต่อแล้ว' : 'ยังไม่ตั้งค่า' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- GitHub Settings -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.604-3.369-1.341-3.369-1.341-.454-1.155-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z"/>
                </svg>
                GitHub Settings
            </h3>
        </div>
        <form action="{{ route('admin.products.versions.github', $product) }}" method="POST" class="p-6">
            @csrf
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Owner</label>
                        <input type="text" name="github_owner" value="{{ $product->githubSetting?->github_owner ?? 'xjanova' }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                               placeholder="username or org">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Repository</label>
                        <input type="text" name="github_repo" value="{{ $product->githubSetting?->github_repo ?? '' }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                               placeholder="repo-name">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Personal Access Token</label>
                    <input type="password" name="github_token" value="{{ $product->githubSetting ? '********' : '' }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="ghp_xxxxxxxxxxxx">
                    <p class="text-xs text-gray-500 mt-1">สร้างที่: GitHub → Settings → Developer settings → Personal access tokens</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asset Pattern</label>
                    <input type="text" name="asset_pattern" value="{{ $product->githubSetting?->asset_pattern ?? '*.exe' }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="*.exe, *.zip, *.msi">
                    <p class="text-xs text-gray-500 mt-1">Pattern สำหรับเลือกไฟล์จาก Release assets</p>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="github_active" value="1"
                           {{ $product->githubSetting?->is_active ?? true ? 'checked' : '' }}
                           class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <label for="github_active" class="ml-2 text-sm text-gray-700">เปิดใช้งาน</label>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">
                        บันทึก Settings
                    </button>
                    @if($product->githubSetting)
                        <button type="button" onclick="testConnection()"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            ทดสอบการเชื่อมต่อ
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">การดำเนินการ</h3>
        </div>
        <div class="p-6 space-y-4">
            @if($product->githubSetting)
                <!-- Sync from GitHub -->
                <form action="{{ route('admin.products.versions.sync', $product) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Sync จาก GitHub Release ล่าสุด
                    </button>
                </form>
            @endif

            <!-- Manual Create Version -->
            <button type="button" onclick="showCreateVersionModal()"
                    class="w-full flex items-center justify-center px-4 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                สร้างเวอร์ชันใหม่ (Manual)
            </button>

            <!-- View Download Logs -->
            <a href="{{ route('admin.products.versions.logs', $product) }}"
               class="w-full flex items-center justify-center px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                ดู Download Logs
            </a>
        </div>
    </div>
</div>

<!-- Versions Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">รายการเวอร์ชัน</h3>
    </div>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เวอร์ชัน</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ไฟล์</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ขนาด</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sync เมื่อ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($product->versions as $version)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-lg font-semibold text-gray-900">v{{ $version->version }}</span>
                        @if($version->is_active)
                            <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                ล่าสุด
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $version->download_filename ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $version->file_size_formatted }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $version->synced_at?->format('d/m/Y H:i') ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $version->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $version->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($version->changelog)
                            <button type="button" onclick="showChangelog('{{ addslashes($version->version) }}', `{{ addslashes($version->changelog) }}`)"
                                    class="text-blue-600 hover:underline mr-3">Changelog</button>
                        @endif
                        <form action="{{ route('admin.products.versions.toggle', [$product, $version]) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="{{ $version->is_active ? 'text-yellow-600' : 'text-green-600' }} hover:underline mr-3">
                                {{ $version->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.products.versions.destroy', [$product, $version]) }}" method="POST" class="inline"
                              onsubmit="return confirm('ยืนยันการลบเวอร์ชันนี้?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">ลบ</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        ยังไม่มีเวอร์ชัน - Sync จาก GitHub หรือสร้างใหม่
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Create Version Modal -->
<div id="createVersionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">สร้างเวอร์ชันใหม่</h3>
        </div>
        <form action="{{ route('admin.products.versions.create', $product) }}" method="POST" class="p-6">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เวอร์ชัน</label>
                    <input type="text" name="version" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="1.0.0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Download URL (optional)</label>
                    <input type="url" name="download_url"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="https://...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filename (optional)</label>
                    <input type="text" name="download_filename"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                           placeholder="app-setup.exe">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Changelog</label>
                    <textarea name="changelog" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                              placeholder="รายละเอียดการอัปเดต..."></textarea>
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    สร้างเวอร์ชัน
                </button>
                <button type="button" onclick="hideCreateVersionModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Changelog Modal -->
<div id="changelogModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900" id="changelogTitle">Changelog</h3>
            <button type="button" onclick="hideChangelog()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <pre id="changelogContent" class="whitespace-pre-wrap text-sm text-gray-700 bg-gray-50 p-4 rounded-lg"></pre>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showCreateVersionModal() {
    document.getElementById('createVersionModal').classList.remove('hidden');
    document.getElementById('createVersionModal').classList.add('flex');
}

function hideCreateVersionModal() {
    document.getElementById('createVersionModal').classList.add('hidden');
    document.getElementById('createVersionModal').classList.remove('flex');
}

function showChangelog(version, content) {
    document.getElementById('changelogTitle').textContent = 'Changelog v' + version;
    document.getElementById('changelogContent').textContent = content;
    document.getElementById('changelogModal').classList.remove('hidden');
    document.getElementById('changelogModal').classList.add('flex');
}

function hideChangelog() {
    document.getElementById('changelogModal').classList.add('hidden');
    document.getElementById('changelogModal').classList.remove('flex');
}

async function testConnection() {
    try {
        const response = await fetch('{{ route("admin.products.versions.test", $product) }}');
        const data = await response.json();

        if (data.success) {
            alert('เชื่อมต่อสำเร็จ!\n\nRepository: ' + data.repo_name + '\nPrivate: ' + (data.is_private ? 'Yes' : 'No'));
        } else {
            alert('เชื่อมต่อล้มเหลว: ' + data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Close modals when clicking outside
document.getElementById('createVersionModal').addEventListener('click', function(e) {
    if (e.target === this) hideCreateVersionModal();
});
document.getElementById('changelogModal').addEventListener('click', function(e) {
    if (e.target === this) hideChangelog();
});
</script>
@endpush
@endsection
