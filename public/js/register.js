/* register.js */
(function () {
    'use strict';

    var roleProf      = document.getElementById('roleProf');
    var roleStud      = document.getElementById('roleStud');
    var roleMsg       = document.getElementById('roleMsg');
    var selectedRole  = document.getElementById('selectedRole');
    var form          = document.getElementById('registerForm');
    var submitBtn     = document.getElementById('registerBtn');
    var submitText    = document.getElementById('registerBtnText');
    var spinner       = document.getElementById('registerSpinner');
    var errorBanner   = document.getElementById('registerError');
    var errorText     = document.getElementById('registerErrorText');
    var errorClose    = document.getElementById('registerErrorClose');
    var formPanel     = document.getElementById('formPanel');
    var verifyPanel   = document.getElementById('verifyPanel');
    var verifyEmailEl = document.getElementById('verifyEmail');
    var resendBtn     = document.getElementById('resendBtn');
    var resentBanner  = document.getElementById('resentBanner');

    var nameInput    = document.getElementById('nameInput');
    var emailInput   = document.getElementById('emailInput');
    var passInput    = document.getElementById('passwordInput');
    var confirmInput = document.getElementById('passwordConfirmInput');
    var togglePass   = document.getElementById('togglePassword');
    var eyeIcon      = document.getElementById('eyeIcon');
    var eyeOffIcon   = document.getElementById('eyeOffIcon');

    var nameMsg    = document.getElementById('nameMsg');
    var emailMsg   = document.getElementById('emailMsg');
    var passwordMsg = document.getElementById('passwordMsg');
    var confirmMsg = document.getElementById('confirmMsg');

    var strengthWrap  = document.getElementById('strengthWrap');
    var strengthFill  = document.getElementById('strengthFill');
    var strengthLabel = document.getElementById('strengthLabel');

    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    /* ── Field helpers ─────────────────────────────────────────────── */
    function fieldError(input, msgEl, text) {
        if (!input || !msgEl) return;
        input.classList.add('field-error');
        input.classList.remove('field-ok');
        msgEl.querySelector('span').textContent = text;
        msgEl.classList.remove('hidden', 'ok');
        msgEl.classList.add('err');
    }
    function fieldOk(input, msgEl, text) {
        if (!input || !msgEl) return;
        input.classList.remove('field-error');
        input.classList.add('field-ok');
        if (text) {
            msgEl.querySelector('span').textContent = text;
            msgEl.classList.remove('hidden', 'err');
            msgEl.classList.add('ok');
        } else {
            msgEl.classList.add('hidden');
        }
    }
    function fieldReset(input, msgEl) {
        if (!input || !msgEl) return;
        input.classList.remove('field-error', 'field-ok');
        msgEl.classList.add('hidden');
    }

    function shake(el) {
        el.classList.remove('shake');
        void el.offsetWidth;
        el.classList.add('shake');
        el.addEventListener('animationend', function () { el.classList.remove('shake'); }, { once: true });
    }

    /* ── Banner / panel helpers ────────────────────────────────────── */
    function showError(msg) {
        errorText.textContent = msg;
        errorBanner.classList.remove('hidden', 'auth-banner-in');
        void errorBanner.offsetWidth;
        errorBanner.classList.add('auth-banner-in');
    }
    function hideError() { errorBanner.classList.add('hidden'); }

    function showVerifyPanel(email) {
        if (verifyEmailEl) verifyEmailEl.textContent = email || '';
        formPanel.classList.add('hidden');
        verifyPanel.classList.remove('hidden');
        verifyPanel.classList.remove('panel-in');
        void verifyPanel.offsetWidth;
        verifyPanel.classList.add('panel-in');
        setupResend(email);
    }

    function setupResend(email) {
        if (!resendBtn) return;
        var cooldown = 0;

        function startCooldown(s) {
            resendBtn.disabled = true;
            cooldown = s;
            var iv = setInterval(function () {
                cooldown--;
                resendBtn.textContent = 'resend (' + cooldown + 's)';
                if (cooldown <= 0) {
                    clearInterval(iv);
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'resend';
                }
            }, 1000);
        }

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

            startCooldown(60);
        });
    }

    if (errorClose) errorClose.addEventListener('click', hideError);

    /* ── Role selection ────────────────────────────────────────────── */
    [roleProf, roleStud].forEach(function (btn) {
        if (!btn) return;
        btn.addEventListener('click', function () {
            [roleProf, roleStud].forEach(function (b) {
                b.classList.remove('selected', 'role-error');
            });
            btn.classList.add('selected');
            selectedRole.value = btn.dataset.role;
            roleMsg.classList.add('hidden');
        });
    });

    /* ── Password visibility toggle ───────────────────────────────── */
    if (togglePass) {
        togglePass.addEventListener('click', function () {
            var show = passInput.type === 'password';
            passInput.type = show ? 'text' : 'password';
            eyeIcon.classList.toggle('hidden', show);
            eyeOffIcon.classList.toggle('hidden', !show);
        });
    }

    /* ── Password strength meter ───────────────────────────────────── */
    var reqEls = {
        len:   document.getElementById('req-len'),
        upper: document.getElementById('req-upper'),
        num:   document.getElementById('req-num'),
        sym:   document.getElementById('req-sym'),
    };

    function setReq(el, met) {
        if (!el) return;
        var dot = el.querySelector('.req-dot');
        if (met) {
            el.classList.remove('text-slate-300');
            el.classList.add('text-emerald-500');
            dot.style.background = '#10b981';
        } else {
            el.classList.add('text-slate-300');
            el.classList.remove('text-emerald-500');
            dot.style.background = '';
        }
    }

    var strengthConfig = [
        { pct: '25%',  color: '#ef4444', label: 'Too weak'  },
        { pct: '50%',  color: '#f59e0b', label: 'Weak'      },
        { pct: '75%',  color: '#3b82f6', label: 'Fair'      },
        { pct: '100%', color: '#10b981', label: 'Strong'    },
    ];

    function calcStrength(val) {
        var score = 0;
        var met = {
            len:   val.length >= 8,
            upper: /[A-Z]/.test(val),
            num:   /[0-9]/.test(val),
            sym:   /[^A-Za-z0-9]/.test(val),
        };
        if (met.len)   score++;
        if (met.upper) score++;
        if (met.num)   score++;
        if (met.sym)   score++;
        return { score: score, met: met };
    }

    passInput.addEventListener('input', function () {
        fieldReset(passInput, passwordMsg);
        hideError();
        var val = passInput.value;
        if (!val) {
            strengthWrap.classList.add('hidden');
            return;
        }
        strengthWrap.classList.remove('hidden');
        var res = calcStrength(val);
        var cfg = strengthConfig[res.score - 1] || strengthConfig[0];
        strengthFill.style.width = res.score > 0 ? cfg.pct : '0%';
        strengthFill.style.backgroundColor = res.score > 0 ? cfg.color : '';
        strengthLabel.textContent = res.score > 0 ? cfg.label : '';
        strengthLabel.style.color = res.score > 0 ? cfg.color : '';

        setReq(reqEls.len,   res.met.len);
        setReq(reqEls.upper, res.met.upper);
        setReq(reqEls.num,   res.met.num);
        setReq(reqEls.sym,   res.met.sym);

        /* Live confirm match if already typed */
        if (confirmInput.value) checkConfirm();
    });

    /* ── Confirm password live check ───────────────────────────────── */
    function checkConfirm() {
        if (!confirmInput.value) {
            fieldReset(confirmInput, confirmMsg);
            return true;
        }
        if (passInput.value === confirmInput.value) {
            fieldOk(confirmInput, confirmMsg, 'Passwords match');
            return true;
        } else {
            fieldError(confirmInput, confirmMsg, 'Passwords do not match.');
            return false;
        }
    }
    confirmInput.addEventListener('input', checkConfirm);
    confirmInput.addEventListener('blur', checkConfirm);

    /* ── Blur validation ───────────────────────────────────────────── */
    nameInput.addEventListener('blur', function () {
        if (!nameInput.value.trim()) fieldError(nameInput, nameMsg, 'Full name is required.');
        else fieldOk(nameInput, nameMsg);
    });
    nameInput.addEventListener('input', function () { fieldReset(nameInput, nameMsg); });

    emailInput.addEventListener('blur', function () {
        var val = emailInput.value.trim();
        if (!val) fieldError(emailInput, emailMsg, 'Email address is required.');
        else if (!emailRe.test(val)) fieldError(emailInput, emailMsg, 'Please enter a valid email address.');
        else fieldOk(emailInput, emailMsg);
    });
    emailInput.addEventListener('input', function () { fieldReset(emailInput, emailMsg); hideError(); });

    passInput.addEventListener('blur', function () {
        if (!passInput.value) {
            fieldError(passInput, passwordMsg, 'Password is required.');
        } else if (passInput.value.length < 8) {
            fieldError(passInput, passwordMsg, 'Password must be at least 8 characters.');
        }
    });

    /* ── Full validation on submit ─────────────────────────────────── */
    function validateAll() {
        var ok = true;

        if (!selectedRole.value) {
            roleMsg.classList.remove('hidden');
            shake(roleProf); shake(roleStud);
            [roleProf, roleStud].forEach(function (b) { b.classList.add('role-error'); });
            ok = false;
        }

        if (!nameInput.value.trim()) {
            fieldError(nameInput, nameMsg, 'Full name is required.');
            ok = false;
        }

        var email = emailInput.value.trim();
        if (!email) {
            fieldError(emailInput, emailMsg, 'Email address is required.');
            ok = false;
        } else if (!emailRe.test(email)) {
            fieldError(emailInput, emailMsg, 'Please enter a valid email address.');
            ok = false;
        }

        if (!passInput.value) {
            fieldError(passInput, passwordMsg, 'Password is required.');
            ok = false;
        } else if (passInput.value.length < 8) {
            fieldError(passInput, passwordMsg, 'Password must be at least 8 characters.');
            ok = false;
        }

        if (!confirmInput.value) {
            fieldError(confirmInput, confirmMsg, 'Please confirm your password.');
            ok = false;
        } else if (passInput.value !== confirmInput.value) {
            fieldError(confirmInput, confirmMsg, 'Passwords do not match.');
            ok = false;
        }

        return ok;
    }

    /* ── Form submit ───────────────────────────────────────────────── */
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            hideError();

            if (!validateAll()) return;

            submitBtn.disabled     = true;
            submitText.textContent = 'Creating account…';
            spinner.classList.remove('hidden');

            try {
                var hp = document.getElementById('hp_register');
                var result = await ExamGuardApi.register(
                    nameInput.value.trim(),
                    emailInput.value.trim(),
                    passInput.value,
                    confirmInput.value,
                    selectedRole.value,
                    hp ? hp.value : ''
                );

                showVerifyPanel(result.email || emailInput.value.trim());

            } catch (err) {
                var msg = err.message || 'Registration failed. Please try again.';
                /* Surface server field-specific errors nicely */
                if (/email.*taken|already.*registered|email.*exist/i.test(msg)) {
                    fieldError(emailInput, emailMsg, 'This email is already registered.');
                    emailInput.focus();
                } else {
                    showError(msg);
                }
            } finally {
                submitBtn.disabled     = false;
                submitText.textContent = 'Create account';
                spinner.classList.add('hidden');
            }
        });
    }

})();
