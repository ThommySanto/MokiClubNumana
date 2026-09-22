(function () {
    var passwordToggle = document.getElementById('passwordToggle');
    var loginForm = document.getElementById('loginForm');

    if (passwordToggle) {
        passwordToggle.addEventListener('click', function () {
            var passwordInput = document.getElementById('password');
            var eyeOpen = this.querySelector('.eye-open');
            var eyeClosed = this.querySelector('.eye-closed');

            if (!passwordInput || !eyeOpen || !eyeClosed) {
                return;
            }

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
                this.classList.add('show-password');
            } else {
                passwordInput.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
                this.classList.remove('show-password');
            }
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function () {
            var loginBtn = this.querySelector('.login-btn');
            if (!loginBtn) {
                return;
            }
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = '<div class="neu-spinner"></div>';
        });
    }
})();
