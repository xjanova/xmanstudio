{{--
    Free-vs-PRO table. groups: [[group label, [[feature, proOnly], …]], …]
--}}
@props(['groups' => [], 'freeLabel' => 'ฟรี', 'proLabel' => 'PRO', 'note' => null])
<div {{ $attributes->merge(['class' => 'max-w-5xl mx-auto xp-card overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="xp-table w-full text-sm">
            <thead>
                <tr class="text-left bg-white/[.03]">
                    <th class="px-4 sm:px-5 py-4 font-bold text-white"><x-bi th="ฟีเจอร์" en="Feature" /></th>
                    <th class="px-2 sm:px-5 py-4 text-center w-16 sm:w-28"><x-xp.badge tier="free" :label="$freeLabel" /></th>
                    <th class="px-2 sm:px-5 py-4 text-center w-16 sm:w-28"><x-xp.badge tier="pro" :label="$proLabel" /></th>
                </tr>
            </thead>
            <tbody>
                @foreach($groups as [$groupName, $rows])
                    <tr><td colspan="3" class="px-4 sm:px-5 pt-5 pb-2 xp-eyebrow" style="letter-spacing: .14em">{{ $groupName }}</td></tr>
                    @foreach($rows as [$name, $proOnly])
                        <tr>
                            <td class="px-4 sm:px-5 py-3 text-gray-200">{{ $name }}</td>
                            <td class="px-2 sm:px-5 py-3 text-center">
                                @if($proOnly)<span class="text-gray-600" aria-label="ไม่มี">—</span>@else<span class="font-black" style="color: var(--xp-a)" aria-label="มี">✓</span>@endif
                            </td>
                            <td class="px-2 sm:px-5 py-3 text-center"><span class="text-amber-300 font-black" aria-label="มี">✓</span></td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    @if($note)<p class="px-5 py-4 text-xs text-gray-500 border-t border-white/5">{{ $note }}</p>@endif
</div>
