<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            <x-bi th="ลบบัญชี" en="Delete Account" />
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            <x-bi th="เมื่อลบบัญชีของคุณแล้ว ทรัพยากรและข้อมูลทั้งหมดจะถูกลบอย่างถาวร ก่อนลบบัญชี กรุณาดาวน์โหลดข้อมูลหรือสิ่งที่คุณต้องการเก็บไว้" en="Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain." />
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    ><x-bi th="ลบบัญชี" en="Delete Account" /></x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                <x-bi th="คุณแน่ใจหรือไม่ว่าต้องการลบบัญชีของคุณ?" en="Are you sure you want to delete your account?" />
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                <x-bi th="เมื่อลบบัญชีของคุณแล้ว ทรัพยากรและข้อมูลทั้งหมดจะถูกลบอย่างถาวร กรุณากรอกรหัสผ่านเพื่อยืนยันว่าคุณต้องการลบบัญชีอย่างถาวร" en="Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account." />
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ bi('common.password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ bi('common.password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    <x-bi k="common.cancel" />
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    <x-bi th="ลบบัญชี" en="Delete Account" />
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
