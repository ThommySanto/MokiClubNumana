/**
 * cookie-banner.js — Moki Club Numana
 * File: assets/js/cookie-banner.js
 *
 * Gestisce il banner cookie: mostra/nasconde, salva la preferenza
 * nel cookie "moki_cookie_consent" (durata 12 mesi).
 * Nessuna dipendenza esterna.
 */

(function () {
    'use strict';

    var COOKIE_NAME    = 'moki_cookie_consent';
    var COOKIE_DAYS    = 365;
    var banner         = document.getElementById('cookie-banner');
    var btnAccept      = document.getElementById('cookie-accept');
    var btnReject      = document.getElementById('cookie-reject');

    // ── Utility ──────────────────────────────────────────────

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
    }

    function getCookie(name) {
        var nameEQ = name + '=';
        var parts  = document.cookie.split(';');
        for (var i = 0; i < parts.length; i++) {
            var part = parts[i].trim();
            if (part.indexOf(nameEQ) === 0) {
                return decodeURIComponent(part.substring(nameEQ.length));
            }
        }
        return null;
    }

    // ── Mostra / nascondi banner ──────────────────────────────

    function hideBanner() {
        if (!banner) return;
        banner.classList.remove('cookie-banner-visible');
    }

    function showBanner() {
        if (!banner) return;
        // Piccolo ritardo per far completare il rendering della pagina
        setTimeout(function () {
            banner.classList.add('cookie-banner-visible');
        }, 600);
    }

    // ── Gestione consenso ────────────────────────────────────

    function handleAccept() {
        setCookie(COOKIE_NAME, 'all', COOKIE_DAYS);
        hideBanner();
    }

    function handleReject() {
        setCookie(COOKIE_NAME, 'necessary', COOKIE_DAYS);
        hideBanner();
    }

    // ── Init ─────────────────────────────────────────────────

    function init() {
        if (!banner) return;

        var consent = getCookie(COOKIE_NAME);

        // Se l'utente non ha ancora espresso preferenza, mostra il banner
        if (!consent) {
            showBanner();
        }

        if (btnAccept) {
            btnAccept.addEventListener('click', handleAccept);
        }

        if (btnReject) {
            btnReject.addEventListener('click', handleReject);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
