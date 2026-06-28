/* tour.js — sidebar scroll-spy and animations for the Tour page */
(function () {
    'use strict';

    /* ── tf-reveal: fade-up on scroll ──────────────────────────── */
    var revealObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.tf-reveal').forEach(function (el) {
        revealObserver.observe(el);
    });

    /* ── Sidebar scroll-spy ─────────────────────────────────────── */
    var sidebarLinks = document.querySelectorAll('.tour-sidebar-link');
    var sections     = document.querySelectorAll('.tour-section');

    if (sections.length && sidebarLinks.length) {
        var spyObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var id = entry.target.id;
                    sidebarLinks.forEach(function (link) {
                        var active = link.getAttribute('href') === '#' + id;
                        link.classList.toggle('is-active', active);
                    });
                }
            });
        }, { threshold: 0.3, rootMargin: '-10% 0px -60% 0px' });

        sections.forEach(function (sec) { spyObserver.observe(sec); });
    }

    /* ── Smooth scroll for sidebar links ───────────────────────── */
    sidebarLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            var href = link.getAttribute('href');
            if (href && href.startsWith('#')) {
                e.preventDefault();
                var target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    /* ── Mobile tab links ───────────────────────────────────────── */
    var tabLinks = document.querySelectorAll('.tour-tab-link');
    tabLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            var href = link.getAttribute('href');
            if (href && href.startsWith('#')) {
                e.preventDefault();
                var target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

})();
