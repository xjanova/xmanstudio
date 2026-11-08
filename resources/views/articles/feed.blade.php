{!! '<' . '?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>บทความไอทีน่ารู้ — XMAN Studio</title>
    <link>{{ route('articles.index') }}</link>
    <atom:link href="{{ route('articles.feed') }}" rel="self" type="application/rss+xml" />
    <description>เรื่องไอทีที่คนไทยกำลังค้นหา อธิบายให้เข้าใจง่ายโดยทีมงาน XMAN Studio</description>
    <language>th</language>
    @if($articles->isNotEmpty())
    <lastBuildDate>{{ $articles->first()->published_at->toRfc2822String() }}</lastBuildDate>
    @endif
    @foreach($articles as $a)
    <item>
        <title>{{ $a->title }}</title>
        <link>{{ $a->url() }}</link>
        <guid isPermaLink="true">{{ $a->url() }}</guid>
        <pubDate>{{ $a->published_at->toRfc2822String() }}</pubDate>
        <category>{{ $a->categoryLabel() }}</category>
        <description>{{ $a->excerpt }}</description>
        @php $ext = strtolower(pathinfo((string) ($a->cover_image ?: 'cover.png'), PATHINFO_EXTENSION)); @endphp
        <enclosure url="{{ $a->coverUrl() }}" type="image/{{ $ext === 'jpg' ? 'jpeg' : ($ext ?: 'png') }}" length="0" />
    </item>
    @endforeach
</channel>
</rss>
