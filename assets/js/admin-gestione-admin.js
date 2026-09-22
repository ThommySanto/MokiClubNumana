(function () {
    var deleteLinks = document.querySelectorAll('.gestione-admin-delete-action[data-confirm-message]');

    deleteLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
            var message = link.getAttribute('data-confirm-message') || 'Confermi questa operazione?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
})();
