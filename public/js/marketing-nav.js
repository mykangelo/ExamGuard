/* marketing-nav.js — mobile header menu for marketing pages */
(function () {
    'use strict';

    var btn = document.getElementById('mobileNavBtn');
    var nav = document.getElementById('mobileNav');
    if (!btn || !nav) return;

    function setOpen(isOpen) {
        nav.classList.toggle('hidden', !isOpen);
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    btn.addEventListener('click', function () {
        setOpen(nav.classList.contains('hidden'));
    });

    nav.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            setOpen(false);
        });
    });
})();
