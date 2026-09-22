/**
 * nav-mobile.js — Moki Club Numana
 * Hamburger menu mobile: apre/chiude il drawer di navigazione.
 * Nessuna dipendenza esterna.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var toggle  = document.getElementById('nav-hamburger');
        var drawer  = document.getElementById('nav-drawer');
        var overlay = document.getElementById('nav-overlay');

        if (!toggle || !drawer || !overlay) return;

        function openMenu() {
            toggle.classList.add('is-open');
            drawer.classList.add('is-open');
            overlay.classList.add('is-visible');
            document.body.classList.add('nav-open');
            toggle.setAttribute('aria-expanded', 'true');
        }

        function closeMenu() {
            toggle.classList.remove('is-open');
            drawer.classList.remove('is-open');
            overlay.classList.remove('is-visible');
            document.body.classList.remove('nav-open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            if (drawer.classList.contains('is-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        // Chiude cliccando sull'overlay
        overlay.addEventListener('click', closeMenu);

        // Chiude premendo Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMenu();
        });

        // Chiude su resize verso desktop (evita menu aperto su rotazione)
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) closeMenu();
        });
    });
})();
