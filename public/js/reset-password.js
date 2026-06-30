(function () {
    'use strict';

    var form = document.getElementById('resetForm');
    var tokenInput = document.getElementById('tokenInput');
    var emailInput = document.getElementById('emailInput');
    var passInput = document.getElementById('passwordInput');
    var passConfirmInput = document.getElementById('passwordConfirmInput');
    var togglePass = document.getElementById('togglePassword');
    var eyeIcon = document.getElementById('eyeIcon');
    var eyeOffIcon = document.getElementById('eyeOffIcon');
    var passwordMsg = document.getElementById('passwordMsg');
    var passwordConfirmMsg = document.getElementById('passwordConfirmMsg');
    var submitBtn = document.getElementById('submitBtn');
    var spinner = document.getElementById('submitSpinner');
    var formPanel = document.getElementById('formPanel');
    var successPanel = document.getElementById('successPanel');
    var invalidPanel = document.getElementById('invalidPanel');

    var token = (tokenInput && tokenInput.value) || '';
    var email = (emailInput && emailInput.value) || '';

    if (!token || !email) {
        if (formPanel) formPanel.classList.add('hidden');
        if (invalidPanel) invalidPanel.classList.remove('hidden');
    }

    if (togglePass && passInput) {
        togglePass.addEventListener('click', function () {
            var show = passInput.type === 'password';
            passInput.type = show ? 'text' : 'password';
            eyeIcon.classList.toggle('hidden', show);
            eyeOffIcon.classList.toggle('hidden', !show);
        });
    }

    function fieldError(input, msgEl, text) {
        input.classList.add('field-error');
        msgEl.querySelector('span').textContent = text;
        msgEl.classList.remove('hidden');
    }

    function fieldOk(input, msgEl) {
        input.classList.remove('field-error');
        msgEl.classList.add('hidden');
    }

    function validatePassword() {
        if (!passInput.value || passInput.value.length < 8) {
            fieldError(passInput, passwordMsg, 'Password must be at least 8 characters.');
            return false;
        }
        fieldOk(passInput, passwordMsg);
        return true;
    }

    function validateConfirm() {
        if (!passConfirmInput.value) {
            fieldError(passConfirmInput, passwordConfirmMsg, 'Please confirm your password.');
            return false;
        }
        if (passConfirmInput.value !== passInput.value) {
            fieldError(passConfirmInput, passwordConfirmMsg, 'Passwords do not match.');
            return false;
        }
        fieldOk(passConfirmInput, passwordConfirmMsg);
        return true;
    }

    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            var passOk = validatePassword();
            var confirmOk = validateConfirm();
            if (!passOk || !confirmOk) return;

            submitBtn.disabled = true;
            spinner.classList.remove('hidden');

            try {
                await ExamGuardApi.resetPassword({
                    token: token,
                    email: emailInput.value.trim(),
                    password: passInput.value,
                    password_confirmation: passConfirmInput.value,
                });
                formPanel.classList.add('hidden');
                successPanel.classList.remove('hidden');
            } catch (err) {
                if (err.status === 422 && /invalid|expired/i.test(err.message || '')) {
                    formPanel.classList.add('hidden');
                    invalidPanel.classList.remove('hidden');
                    return;
                }
                fieldError(passInput, passwordMsg, err.message || 'Unable to reset password. Please try again.');
            } finally {
                submitBtn.disabled = false;
                spinner.classList.add('hidden');
            }
        });
    }
})();
