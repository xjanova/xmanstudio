{{-- ══════════ ประวัติการทำงานของเครื่อง ══════════ --}}
<div class="{{ $card }} p-6">
    <h2 class="{{ $h2 }}"><x-bi th="ประวัติการทำงานของเครื่อง" en="Server activity" /></h2>
    <p class="{{ $lead }}">
        <x-bi th="ทุกคำสั่งที่ส่งถึงเครื่อง ทั้งจากหน้านี้และจากระบบอัตโนมัติ — ใช้ตรวจว่าเกิดอะไรขึ้นเมื่อไร (15 รายการล่าสุด)"
              en="Every command sent to the server, from this page and from automation — handy to see what happened and when (latest 15)." />
    </p>

    @if(empty($actions))
        <p class="mt-4 rounded-lg bg-slate-100 dark:bg-slate-900/60 px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
            <x-bi th="ยังไม่มีประวัติ หรืออ่านข้อมูลไม่ได้ในขณะนี้" en="No history yet, or it couldn't be read just now." />
        </p>
    @else
        @include('customer.vps.tabs.partials.action-list', ['items' => $actions])
    @endif
</div>
