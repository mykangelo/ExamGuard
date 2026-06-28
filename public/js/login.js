/* login.js */
(function () {
    'use strict';

    /* ── Show verified success banner if redirected from email link ── */
    if (new URLSearchParams(window.location.search).get('verified') === '1') {
        document.getElementById('verifiedBanner').classList.remove('hidden');
    }

    var form         = document.getElementById('loginForm');
    var emailInput   = document.getElementById('emailInput');
    var passInput    = document.getElementById('passwordInput');
    var togglePass   = document.getElementById('togglePassword');
    var eyeIcon      = document.getElementById('eyeIcon');
    var eyeOffIcon   = document.getElementById('eyeOffIcon');
    var loginBtn     = document.getElementById('loginBtn');
    var loginText    = document.getElementById('loginBtnText');
    var loginLock    = document.getElementById('loginLockIcon');
    var spinner      = document.getElementById('loginSpinner');
    var emailMsg     = document.getElementById('emailMsg');
    var passwordMsg  = document.getElementById('passwordMsg');
    var formPanel    = document.getElementById('formPanel');
    var verifyPanel  = document.getElementById('verifyPanel');
    var verifyEmail  = document.getElementById('verifyEmail');
    var resendBtn    = document.getElementById('resendBtn');
    var resentBanner = document.getElementById('resentBanner');
    var backToLogin  = document.getElementById('backToLogin');

    /* ── Password visibility toggle ───────────────────────────────── */
    if (togglePass) {
        togglePass.addEventListener('click', function () {
            var show = passInput.type === 'password';
            passInput.type = show ? 'text' : 'password';
            eyeIcon.classList.toggle('hidden', show);
            eyeOffIcon.classList.toggle('hidden', !show);
        });
    }

    /* ── Field helpers ─────────────────────────────────────────────── */
    function fieldError(input, msgEl, text) {
        input.classList.add('field-error');
        input.classList.remove('field-ok', 'field-warn');
        msgEl.querySelector('span').textContent = text;
        msgEl.classList.remove('hidden', 'ok', 'warn');
        msgEl.classList.add('err');
    }
    function fieldOk(input, msgEl) {
        input.classList.remove('field-error', 'field-warn');
        input.classList.add('field-ok');
        msgEl.classList.add('hidden');
    }
    function fieldReset(input, msgEl) {
        input.classList.remove('field-error', 'field-ok', 'field-warn');
        msgEl.classList.add('hidden');
        msgEl.classList.remove('err', 'ok', 'warn');
    }
    function shake(el) {
        el.classList.remove('shake');
        void el.offsetWidth;
        el.classList.add('shake');
        el.addEventListener('animationend', function () { el.classList.remove('shake'); }, { once: true });
    }

    /* ── Validation rules ──────────────────────────────────────────── */
    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function validateEmail() {
        var val = emailInput.value.trim();
        if (!val) { fieldError(emailInput, emailMsg, 'Email address is required.'); return false; }
        if (!emailRe.test(val)) { fieldError(emailInput, emailMsg, 'Please enter a valid email address.'); return false; }
        fieldOk(emailInput, emailMsg);
        return true;
    }
    function validatePassword() {
        if (!passInput.value) { fieldError(passInput, passwordMsg, 'Password is required.'); return false; }
        fieldOk(passInput, passwordMsg);
        return true;
    }

    /* ── Live feedback (blur) ──────────────────────────────────────── */
    emailInput.addEventListener('blur', validateEmail);
    passInput.addEventListener('blur', function () {
        if (!credentialLocked()) validatePassword();
    });

    /* ── Clear state on input ──────────────────────────────────────── */
    emailInput.addEventListener('input', function () {
        fieldReset(emailInput, emailMsg);
        applyCredentialLock();
    });
    passInput.addEventListener('input', function () {
        if (!passwordMsg.classList.contains('warn')) fieldReset(passInput, passwordMsg);
        applyCredentialLock();
    });

    /* ── Credential-lock (same failed combo) ──────────────────────── */
    var failedEmail    = null;
    var failedPassword = null;

    function credentialLocked() {
        return failedEmail !== null
            && emailInput.value.trim() === failedEmail
            && passInput.value         === failedPassword;
    }

    function markFailedCredentials(email, password) {
        failedEmail    = email;
        failedPassword = password;
        applyCredentialLock();
    }

    function clearFailedCredentials() {
        failedEmail    = null;
        failedPassword = null;
    }

    function applyCredentialLock() {
        if (credentialLocked()) {
            /* Amber border only — no text message */
            loginBtn.disabled = true;
            loginLock.classList.remove('hidden');
            passInput.classList.remove('field-error', 'field-ok');
            passInput.classList.add('field-warn');
            passwordMsg.classList.add('hidden');
        } else {
            if (!loginLock.classList.contains('hidden')) {
                loginLock.classList.add('hidden');
                loginBtn.disabled = false;
                passInput.classList.remove('field-warn');
            }
        }
    }

    /* ── Form submit ───────────────────────────────────────────────── */
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            var emailOk = validateEmail();
            var passOk  = validatePassword();
            if (!emailOk || !passOk) return;

            loginBtn.disabled = true;
            spinner.classList.remove('hidden');

            try {
                var hp = document.getElementById('hp_login');
                var result = await ExamGuardApi.login(
                    emailInput.value.trim(),
                    passInput.value,
                    '',
                    hp ? hp.value : ''
                );
                clearFailedCredentials();
                var role = result.user && result.user.role;
                window.location.href = window.ExamGuardRoute?.restoreForRole(role)
                    || (role === 'professor' ? '/professor?view=exams' : '/student');

            } catch (err) {
                if (err.needs_verification) {
                    showVerifyPanel(err.email || emailInput.value.trim());
                    return;
                }
                if (err.locked_out) {
                    startLockoutCountdown(err.retry_after || 900);
                    spinner.classList.add('hidden');
                    return;
                }
                /* Wrong credentials — show inline on password field + shake */
                markFailedCredentials(emailInput.value.trim(), passInput.value);
                fieldError(passInput, passwordMsg, 'Invalid email or password.');
                passInput.classList.remove('field-warn');  /* override warn with red for first failure */
                shake(emailInput);
                shake(passInput);

            } finally {
                if (!credentialLocked() && lockoutInterval === null) {
                    loginBtn.disabled = false;
                }
                spinner.classList.add('hidden');
            }
        });
    }

    /* ── Lockout countdown ─────────────────────────────────────────── */
    var lockoutInterval  = null;
    var lockoutPanel     = document.getElementById('lockoutPanel');
    var lockoutTimer     = document.getElementById('lockoutTimer');

    function showLockoutPanel() {
        formPanel.classList.add('hidden');
        lockoutPanel.classList.remove('hidden', 'panel-in');
        void lockoutPanel.offsetWidth;
        lockoutPanel.classList.add('panel-in');
    }

    function hideLockoutPanel() {
        lockoutPanel.classList.add('hidden');
        formPanel.classList.remove('hidden', 'panel-in');
        void formPanel.offsetWidth;
        formPanel.classList.add('panel-in');
        loginBtn.disabled = false;
    }

    function startLockoutCountdown(seconds) {
        if (lockoutInterval) clearInterval(lockoutInterval);
        showLockoutPanel();
        function pad(n) { return String(n).padStart(2, '0'); }
        function tick() {
            if (seconds <= 0) {
                clearInterval(lockoutInterval);
                lockoutInterval = null;
                hideLockoutPanel();
                return;
            }
            var m = Math.floor(seconds / 60);
            var s = seconds % 60;
            lockoutTimer.textContent = pad(m) + ':' + pad(s);
            seconds--;
        }
        tick();
        lockoutInterval = setInterval(tick, 1000);
    }

    /* ── Verify panel helpers ──────────────────────────────────────── */
    function showVerifyPanel(email) {
        if (verifyEmail) verifyEmail.textContent = email || '';
        formPanel.classList.add('hidden');
        verifyPanel.classList.remove('hidden', 'panel-in');
        void verifyPanel.offsetWidth;
        verifyPanel.classList.add('panel-in');

        if (resendBtn) {
            var cooldown = 0;
            resendBtn.addEventListener('click', async function () {
                if (!email) return;
                resendBtn.disabled = true;
                resendBtn.textContent = 'sending…';
                resentBanner.classList.add('hidden');
                try {
                    await fetch('/api/email/resend', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ email: email }),
                    });
                    resentBanner.classList.remove('hidden');
                } catch (e) {}
                cooldown = 60;
                var iv = setInterval(function () {
                    cooldown--;
                    resendBtn.textContent = 'resend (' + cooldown + 's)';
                    if (cooldown <= 0) {
                        clearInterval(iv);
                        resendBtn.disabled = false;
                        resendBtn.textContent = 'resend';
                    }
                }, 1000);
            });
        }
    }

    /* ── Back to sign in ───────────────────────────────────────────── */
    if (backToLogin) {
        backToLogin.addEventListener('click', function () {
            verifyPanel.classList.add('hidden');
            formPanel.classList.remove('hidden', 'panel-in');
            void formPanel.offsetWidth;
            formPanel.classList.add('panel-in');
            resentBanner.classList.add('hidden');
        });
    }

})();
