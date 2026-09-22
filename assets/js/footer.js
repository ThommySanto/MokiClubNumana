document.addEventListener('DOMContentLoaded', function () {
    var alerts = document.querySelectorAll('.success-message, .error-message');
    alerts.forEach(function (alertBox) {
        setTimeout(function () {
            alertBox.classList.add('toast-hiding');
            setTimeout(function () {
                alertBox.remove();
            }, 500);
        }, 4000);
    });

});
