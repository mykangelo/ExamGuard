/* home.js — scroll animations for the marketing homepage */
(function () {
    'use strict';

    /* ── tf-reveal: fade-up on scroll ──────────────────────────────── */
    const revealObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.10, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.tf-reveal').forEach(function (el) {
        revealObserver.observe(el);
    });

    /* ── Hero headline: word-by-word entrance ───────────────────────── */
    var headline = document.querySelector('.hero-headline');
    if (headline) {
        /* Preserve <br> tags by splitting on them first */
        var rawHtml  = headline.innerHTML;
        var parts    = rawHtml.split(/(<br\s*\/?>)/gi);
        var newHtml  = '';
        var wordIdx  = 0;

        parts.forEach(function (part) {
            if (/^<br/i.test(part)) {
                newHtml += part;
            } else {
                part.split(/\s+/).forEach(function (word, i, arr) {
                    if (!word) return;
                    var delay = 80 + wordIdx * 80;
                    newHtml += '<span class="hero-word" style="transition-delay:' + delay + 'ms;">' + word + '</span>';
                    if (i < arr.length - 1) newHtml += ' ';
                    wordIdx++;
                });
            }
        });

        headline.innerHTML        = newHtml;
        headline.style.opacity    = '1';
        headline.style.transform  = 'none';
        headline.classList.remove('tf-reveal');

        /* Trigger word animations on next frame */
        requestAnimationFrame(function () {
            headline.querySelectorAll('.hero-word').forEach(function (w) {
                w.classList.add('animate');
            });
        });
    }

    /* ── CTA pulse: stop animation after first interaction ─────────── */
    document.querySelectorAll('.cta-pulse').forEach(function (btn) {
        btn.addEventListener('mouseenter', function () {
            btn.style.animationPlayState = 'paused';
        });
        btn.addEventListener('mouseleave', function () {
            btn.style.animationPlayState = 'running';
        });
    });

    /* ── Mobile nav toggle (if present) ────────────────────────────── */
    var mobileToggle = document.getElementById('mobileMenuToggle');
    var mobileMenu   = document.getElementById('mobileMenu');
    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
        });
    }

})();
