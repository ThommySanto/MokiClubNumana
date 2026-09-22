(function () {
    var autoRedirectContainer = document.querySelector('[data-auto-redirect-url][data-auto-redirect-delay]');
    if (!autoRedirectContainer) {
        return;
    }

    var redirectUrl = autoRedirectContainer.getAttribute('data-auto-redirect-url');
    var redirectDelay = parseInt(autoRedirectContainer.getAttribute('data-auto-redirect-delay'), 10);

    if (!redirectUrl || Number.isNaN(redirectDelay) || redirectDelay < 0) {
        return;
    }

    setTimeout(function () {
        window.location.href = redirectUrl;
    }, redirectDelay);
})();
