<footer class="bg-[#0f1e3d] px-6 pb-8 pt-16 text-slate-400">
    <div class="mx-auto max-w-6xl">
        <div class="mb-12 grid gap-8 md:grid-cols-4">
            <div class="space-y-3 md:col-span-1">
                <a href="/" class="flex items-center gap-2.5 text-sm font-bold text-white">
                    <img src="/images/logo.png" alt="ExamGuard" class="h-8 w-8 object-contain drop-shadow-[0_0_6px_rgba(59,130,246,0.5)]">
                    ExamGuard
                </a>
                <p class="text-[14px] leading-relaxed text-slate-400">Controlled online exam monitoring for academic integrity.</p>
            </div>
            @foreach([
                ['Product', [
                    ['Home',              '/'],
                    ['Take a Tour',       '/tour'],
                    ['Features',          '/tour#features'],
                    ['ExamGuard Monitor', '/#proctoring'],
                    ['Pricing',           '/pricing'],
                    ['API',               '/tour#features'],
                ]],
                ['Support', [
                    ['FAQ',              '/faq'],
                    ['Contact Us',       '/contact'],
                    ['Getting Started',  '/tour'],
                    ['Documentation',    '/tour'],
                    ['System Status',    '#'],
                ]],
                ['Legal', [
                    ['Privacy Policy',   '#'],
                    ['Terms of Service', '#'],
                    ['Cookie Policy',    '#'],
                    ['GDPR Compliance',  '#'],
                    ['Data Protection',  '#'],
                ]],
            ] as [$col, $links])
            <div class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-300">{{ $col }}</p>
                <ul class="space-y-2.5">
                    @foreach($links as [$label, $href])
                    <li><a href="{{ $href }}" class="text-[14px] text-slate-400 transition hover:text-white">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
        <div class="flex flex-col items-center gap-3 border-t border-white/[0.08] pt-6 text-[13px] text-slate-600 sm:flex-row sm:justify-between">
            <span>© {{ date('Y') }} ExamGuard. All rights reserved.</span>
            <div class="flex gap-5">
                <a href="#" class="transition hover:text-slate-400">Privacy Policy</a>
                <a href="#" class="transition hover:text-slate-400">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>
