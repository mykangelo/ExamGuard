(function () {
    'use strict';

    var form = document.getElementById('forgotForm');
    var emailInput = document.getElementById('emailInput');
    var emailMsg = document.getElementById('emailMsg');
    var submitBtn = document.getElementById('submitBtn');
    var spinner = document.getElementById('submitSpinner');
    var formPanel = document.getElementById('formPanel');
    var successPanel = document.getElementById('successPanel');
    var successMessage = document.getElementById('successMessage');
    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function fieldError(input, msgEl, text) {
        input.classList.add('field-error');
        msgEl.querySelector('span').textContent = text;
        msgEl.classList.remove('hidden');
    }

    function fieldOk(input, msgEl) {
        input.classList.remove('field-error');
        msgEl.classList.add('hidden');
    }

    function validateEmail() {
        var val = emailInput.value.trim();
        if (!val) {
            fieldError(emailInput, emailMsg, 'Email address is required.');
            return false;
        }
        if (!emailRe.test(val)) {
            fieldError(emailInput, emailMsg, 'Please enter a valid email address.');
            return false;
        }
        fieldOk(emailInput, emailMsg);
        return true;
    }

    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!validateEmail()) return;

            submitBtn.disabled = true;
            spinner.classList.remove('hidden');

            try {
                var hp = document.getElementById('hp_forgot');
                var result = await ExamGuardApi.forgotPassword(
                    emailInput.value.trim(),
                    hp ? hp.value : ''
                );
                successMessage.textContent = result.message || 'If an account exists for that email, we sent password reset instructions.';
                formPanel.classList.add('hidden');
                successPanel.classList.remove('hidden');
            } catch (err) {
                fieldError(emailInput, emailMsg, err.message || 'Unable to send reset email. Please try again.');
            } finally {
                submitBtn.disabled = false;
                spinner.classList.add('hidden');
            }
        });
    }
})();
