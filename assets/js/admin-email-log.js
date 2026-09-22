(function () {
    var clearForm = document.querySelector('.email-log-clear-form[data-confirm-message]');
    if (!clearForm) {
        return;
    }

    clearForm.addEventListener('submit', function (event) {
        var message = clearForm.getAttribute('data-confirm-message') || 'Confermi questa operazione?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
})();
