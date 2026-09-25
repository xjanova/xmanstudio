{{--
    Stop 7 — voices: approved, featured reviews, received like transmissions
    from a pulsar (world/Pulsar.js). A deck that deals one card forward per
    step of scroll. Only included when there are reviews to show.
--}}
<section id="xu-reviews" class="xu-st" data-station="reviews" data-len="{{ max(1.4, min(6, $featuredReviews->count()) * 0.42) }}"
         data-label-th="เสียงจากผู้ใช้" data-label-en="Voices" aria-labelledby="xu-reviews-title">
    <div class="xu-panel xu-reviews">
        <header class="xu-head xu-head--left">
            <p class="xu-eyebrow xu-r" style="--i: 0;">
                <span class="xu-eyebrow__dot" aria-hidden="true"></span>
                รีวิวจากลูกค้า / Reviews
            </p>
            <h2 id="xu-reviews-title" class="xu-h2 xu-r" style="--i: 0.6;">
                เสียงจาก <span class="xu-grad">ผู้ใช้งานจริง</span>
            </h2>
            <p class="xu-signal xu-r" style="--i: 1.2;" aria-hidden="true">
                <span class="xu-signal__dot"></span> INCOMING TRANSMISSION · {{ str_pad((string) $featuredReviews->count(), 2, '0', STR_PAD_LEFT) }}
            </p>
        </header>

        <div class="xu-deck" data-xu-deck>
            @foreach($featuredReviews as $review)
                <article class="xu-card xu-review" style="--d: {{ $loop->index }};">
                    <p class="xu-review__meta" aria-hidden="true">
                        <span>SIGNAL {{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="xu-review__wave"><i></i><i></i><i></i><i></i><i></i></span>
                    </p>
                    <div class="xu-review__stars" aria-label="{{ $review->rating }} เต็ม 5 ดาว / {{ $review->rating }} out of 5 stars">
                        @for($s = 1; $s <= 5; $s++)
                            <svg viewBox="0 0 20 20" fill="{{ $s <= $review->rating ? 'currentColor' : 'none' }}"
                                 stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                                <path d="M10 2.5l2.3 4.9 5.2.7-3.8 3.7.9 5.3-4.6-2.5-4.6 2.5.9-5.3L2.5 8.1l5.2-.7z"/>
                            </svg>
                        @endfor
                    </div>
                    @if($review->title)
                        <h3 class="xu-review__title">{{ $review->title }}</h3>
                    @endif
                    <p class="xu-review__text">{{ Str::limit($review->comment, 190) }}</p>
                    <div class="xu-review__who">
                        @if($review->user?->avatar)
                            <img src="{{ $review->user->avatar_url }}" alt="" class="xu-review__avatar" loading="lazy" decoding="async">
                        @else
                            <span class="xu-review__avatar" aria-hidden="true">{{ mb_substr($review->user?->name ?? '?', 0, 1) }}</span>
                        @endif
                        <span>
                            <b>{{ $review->user?->name ?? 'ผู้ใช้' }}</b>
                            <small>{{ $review->reviewable_name }} &middot; {{ $review->created_at->diffForHumans() }}</small>
                        </span>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
