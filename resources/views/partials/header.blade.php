<header class="border-b border-white/10 bg-slate-950/50 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <a href="/" class="eg-brand flex items-center gap-3 text-lg font-bold"><span>EG</span> ExamGuard</a>
        <nav class="flex flex-wrap items-center gap-4 text-sm text-slate-300">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" @if(!empty($link['logout'])) data-logout @endif class="hover:text-white">{{ $link['label'] }}</a>
            @endforeach
        </nav>
    </div>
</header>
