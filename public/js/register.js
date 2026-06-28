/* register.js — registration form logic */
(function () {
    'use strict';

    var roleProf      = document.getElementById('roleProf');
    var roleStud      = document.getElementById('roleStud');
    var selectedRole  = document.getElementById('selectedRole');
    var form          = document.getElementById('registerForm');
    var submitBtn     = document.getElementById('registerBtn');
    var submitText    = document.getElementById('registerBtnText');
    var spinner       = document.getElementById('registerSpinner');
    var errorBox      = document.getElementById('registerError');
    var errorText     = document.getElementById('registerErrorText');
    var successBox    = document.getElementById('registerSuccess');

    /* ── Role selection ──────────────────────────────────────────── */
    [roleProf, roleStud].forEach(function (btn) {
        if (!btn) return;
        btn.addEventListener('click', function () {
            [roleProf, roleStud].forEach(function (b) { b.classList.remove('selected'); });
            btn.classList.add('selected');
            selectedRole.value = btn.dataset.role;
        });
    });

    /* ── Password visibility toggle ─────────────────────────────── */
    var togglePassword = document.getElementById('togglePassword');
    var passwordInput  = document.getElementById('passwordInput');
    var eyeIcon        = document.getElementById('eyeIcon');
    var eyeOffIcon     = document.getElementById('eyeOffIcon');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            var isText = passwordInput.type === 'text';
            passwordInput.type = isText ? 'password' : 'text';
            eyeIcon.classList.toggle('hidden', !isText);
            eyeOffIcon.classList.toggle('hidden', isText);
        });
    }

    /* ── Show / hide error ───────────────────────────────────────── */
    function showError(msg) {
        errorText.textContent = msg;
        errorBox.classList.remove('hidden');
        successBox.classList.add('hidden');
    }
    function hideError() {
        errorBox.classList.add('hidden');
    }

    /* ── Form submit ─────────────────────────────────────────────── */
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            hideError();

            var name     = document.getElementById('nameInput').value.trim();
            var email    = document.getElementById('emailInput').value.trim();
            var password = document.getElementById('passwordInput').value;
            var confirm  = document.getElementById('passwordConfirmInput').value;
            var role     = selectedRole.value;

            if (!role)             return showError('Please select a role — Professor or Student.');
            if (!name)             return showError('Full name is required.');
            if (!email)            return showError('Email address is required.');
            if (password.length < 8) return showError('Password must be at least 8 characters.');
            if (password !== confirm) return showError('Passwords do not match.');

            /* Loading state */
            submitBtn.disabled  = true;
            submitText.textContent = 'Creating account…';
            spinner.classList.remove('hidden');

            try {
                var result = await ExamGuardApi.register(name, email, password, confirm, role);

                if (result.success) {
                    successBox.classList.remove('hidden');
                    setTimeout(function () {
                        window.location.href = result.user.role === 'professor'
                            ? '/professor/dashboard'
                            : '/student/dashboard';
                    }, 1200);
                } else {
                    showError(result.message || 'Registration failed. Please try again.');
                }
            } catch (err) {
                showError('Something went wrong. Please try again.');
            } finally {
                submitBtn.disabled     = false;
                submitText.textContent = 'Create account';
                spinner.classList.add('hidden');
            }
        });
    }

})();
