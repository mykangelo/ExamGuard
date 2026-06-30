(function () {
  'use strict';

  let currentUser = null;

  function initials(name) {
    const parts = (name || 'U').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return 'U';
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  function getActiveAlert() {
    const profileView = document.getElementById('view-profile');
    if (profileView?.classList.contains('active')) {
      return document.getElementById('profileAlert');
    }
    return document.getElementById('settingsAlert');
  }

  function showAlert(message, type = 'success') {
    const alert = getActiveAlert();
    if (!alert) return;
    const icon = type === 'error' ? 'ti-alert-circle' : 'ti-circle-check';
    alert.innerHTML = `<i class="ti ${icon}" aria-hidden="true"></i><span>${message}</span>`;
    alert.classList.remove('hidden', 'is-error', 'is-success');
    alert.classList.add(type === 'error' ? 'is-error' : 'is-success');
  }

  function hideAlert() {
    const alert = getActiveAlert();
    if (!alert) return;
    alert.classList.add('hidden');
    alert.innerHTML = '';
  }

  function getFormCard(form) {
    return form?.closest('.pg-settings-card') ?? null;
  }

  function clearFormErrors(form) {
    if (!form) return;
    form.querySelectorAll('[data-field-error]').forEach((el) => {
      el.textContent = '';
      el.classList.add('hidden');
      el.removeAttribute('id');
    });
    form.querySelectorAll('.pg-settings-field.is-error').forEach((el) => {
      el.classList.remove('is-error');
      el.querySelector('input, select')?.removeAttribute('aria-invalid');
      el.querySelector('input, select')?.removeAttribute('aria-describedby');
    });
    const formError = form.querySelector('[data-form-error]');
    if (formError) {
      formError.textContent = '';
      formError.classList.add('hidden');
      formError.innerHTML = '';
    }
    const card = getFormCard(form);
    card?.classList.remove('has-errors', 'is-shake');
  }

  function setFieldError(form, fieldName, message) {
    const wrap = form.querySelector(`.pg-settings-field[data-field="${fieldName}"]`);
    if (!wrap) return false;
    const input = wrap.querySelector('input, select');
    const errorEl = wrap.querySelector('[data-field-error]');
    const errorId = `settings-error-${fieldName}`;

    wrap.classList.add('is-error');
    if (input) {
      input.setAttribute('aria-invalid', 'true');
      input.setAttribute('aria-describedby', errorId);
    }
    if (errorEl) {
      errorEl.id = errorId;
      errorEl.innerHTML = `<i class="ti ti-alert-circle" aria-hidden="true"></i><span>${message}</span>`;
      errorEl.classList.remove('hidden');
    }
    return true;
  }

  function setFormError(form, message) {
    const formError = form.querySelector('[data-form-error]');
    if (!formError) return;
    formError.innerHTML = `<i class="ti ti-alert-circle" aria-hidden="true"></i><span>${message}</span>`;
    formError.classList.remove('hidden');
  }

  function shakeCard(form) {
    const card = getFormCard(form);
    if (!card) return;
    card.classList.add('has-errors');
    card.classList.remove('is-shake');
    void card.offsetWidth;
    card.classList.add('is-shake');
  }

  function applyErrors(form, errors = {}, fallbackMessage = '') {
    clearFormErrors(form);
    let applied = 0;
    let firstInput = null;

    Object.entries(errors).forEach(([field, messages]) => {
      const message = Array.isArray(messages) ? messages[0] : messages;
      if (!message) return;
      if (setFieldError(form, field, message)) {
        applied += 1;
        if (!firstInput) {
          firstInput = form.querySelector(`.pg-settings-field[data-field="${field}"] input, .pg-settings-field[data-field="${field}"] select`);
        }
      }
    });

    if (!applied && fallbackMessage) {
      setFormError(form, fallbackMessage);
    }

    if (applied || fallbackMessage) {
      shakeCard(form);
      (firstInput ?? form.querySelector('[data-form-error]'))?.focus?.({ preventScroll: true });
      firstInput?.scrollIntoView?.({ behavior: 'smooth', block: 'center' });
    }

    return applied > 0;
  }

  function validateProfileForm(form) {
    const errors = {};
    const name = form.querySelector('#settingsName')?.value.trim() ?? '';
    const email = form.querySelector('#settingsEmail')?.value.trim() ?? '';

    if (!name) errors.name = 'Full name is required.';
    else if (name.length > 255) errors.name = 'Full name must be 255 characters or fewer.';

    if (!email) errors.email = 'Email address is required.';
    else if (!isValidEmail(email)) errors.email = 'Enter a valid email address.';

    return errors;
  }

  function validatePasswordForm(form) {
    const errors = {};
    const current = form.querySelector('#settingsCurrentPassword')?.value ?? '';
    const next = form.querySelector('#settingsNewPassword')?.value ?? '';
    const confirm = form.querySelector('#settingsConfirmPassword')?.value ?? '';

    if (!current) errors.current_password = 'Enter your current password.';
    if (!next) errors.password = 'Enter a new password.';
    else if (next.length < 8) errors.password = 'New password must be at least 8 characters.';
    if (!confirm) errors.password_confirmation = 'Confirm your new password.';
    else if (next && confirm !== next) errors.password_confirmation = 'Passwords do not match.';

    return errors;
  }

  function validateWorkspaceForm(form) {
    const errors = {};
    const raw = form.querySelector('#settingsDefaultTimeLimit')?.value.trim() ?? '';

    if (raw !== '') {
      const value = Number(raw);
      if (!Number.isInteger(value) || value < 1 || value > 480) {
        errors.defaultTimeLimit = 'Time limit must be between 1 and 480 minutes.';
      }
    }

    return errors;
  }

  function handleClientValidation(form, validator) {
    const errors = validator(form);
    if (!Object.keys(errors).length) return true;
    applyErrors(form, errors);
    return false;
  }

  function handleApiError(form, error, fallbackMessage) {
    if (error?.errors && applyErrors(form, error.errors, error.message)) {
      return;
    }
    applyErrors(form, {}, error?.message || fallbackMessage);
  }

  function setButtonLoading(btn, loading, label) {
    if (!btn) return;
    if (loading) {
      btn.dataset.label = btn.textContent;
      btn.disabled = true;
      btn.textContent = label;
      return;
    }
    btn.disabled = false;
    btn.textContent = btn.dataset.label || label;
  }

  function applyUser(user) {
    currentUser = user;
    const ui = window.ExamGuardSettingsUI;
    window.ExamGuardProfessor = window.ExamGuardProfessor || {};
    window.ExamGuardProfessor.user = user;
    window.ExamGuardProfessor.preferences = user.preferences;

    const nameInput = document.getElementById('settingsName');
    const emailInput = document.getElementById('settingsEmail');
    if (nameInput) nameInput.value = user.name || '';
    if (emailInput) emailInput.value = user.email || '';

    const department = document.getElementById('settingsDepartment');
    if (department) department.value = user.department || '';

    ui?.renderAvatarButton?.(document.getElementById('settingsAvatarBtn'), user);
    ui?.renderNavAvatarButton?.(document.querySelector('.pg-floating-profile .pg-avatar'), user);

    const emailWrap = document.querySelector('#settingsProfileForm .pg-settings-email-wrap');
    if (emailWrap) {
      const badge = emailWrap.querySelector('.pg-settings-badge');
      if (badge) {
        badge.className = `pg-settings-badge ${user.verified ? 'pg-settings-badge-ok' : 'pg-settings-badge-warn'}`;
        badge.innerHTML = user.verified
          ? '<i class="ti ti-circle-check"></i> Verified'
          : 'Unverified';
      }
    }

    const verifiedStatus = document.getElementById('settingsVerifiedStatus');
    if (verifiedStatus) {
      verifiedStatus.innerHTML = user.verified
        ? '<span class="pg-settings-badge pg-settings-badge-ok">Verified</span>'
        : '<span class="pg-settings-badge pg-settings-badge-warn">Unverified</span>';
    }

    const memberSince = document.getElementById('settingsMemberSince');
    if (memberSince && user.member_since) memberSince.textContent = user.member_since;

    const prefs = user.preferences || {};
    const examSubmitted = document.getElementById('settingsEmailExamSubmitted');
    const violations = document.getElementById('settingsEmailViolations');
    const examReminder = document.getElementById('settingsEmailExamReminder');
    const timeLimit = document.getElementById('settingsDefaultTimeLimit');
    const warningLimit = document.getElementById('settingsDefaultWarningLimit');

    if (examSubmitted) examSubmitted.checked = !!prefs.emailExamSubmitted;
    if (violations) violations.checked = !!prefs.emailViolations;
    if (examReminder) examReminder.checked = prefs.emailExamReminder !== false;
    if (timeLimit) timeLimit.value = prefs.defaultTimeLimit ?? 60;
    if (warningLimit) warningLimit.value = String(prefs.defaultWarningLimit ?? 3);
  }

  function bindSettingsUi() {
    const root = document.getElementById('settingsView');
    if (!root || root.dataset.uiBound === '1') return;
    root.dataset.uiBound = '1';
    const ui = window.ExamGuardSettingsUI;
    ui?.bindPasswordToggles?.(root);
    ui?.bindPasswordStrength?.(
      document.getElementById('settingsNewPassword'),
      document.getElementById('settingsPwStrength'),
    );
    ui?.bindAvatarUpload?.({
      buttonId: 'settingsAvatarBtn',
      inputId: 'settingsAvatarInput',
      onUploaded: applyUser,
    });
    ui?.bindDangerZone?.({
      logoutAllId: 'settingsLogoutAll',
      deleteId: 'settingsDeleteAccount',
    });
  }

  function clearAllFormErrors() {
    document.querySelectorAll('#settingsView form').forEach(clearFormErrors);
  }

  async function loadUser() {
    const seed = window.ExamGuardProfessor?.user;
    if (seed) {
      applyUser(seed);
      return;
    }
    const { user } = await ExamGuardApi.me();
    applyUser(user);
  }

  async function initProfile() {
    await initSettings();
    window.ExamGuardSettings?.showSection?.('profile');
  }

  async function initSettings() {
    hideAlert();
    clearAllFormErrors();
    try {
      await loadUser();
      bindSettingsUi();

      // Settings sub-nav: show one section at a time (professor settings tab)
      const root = document.getElementById('settingsView');
      if (root && root.dataset.subnavBound !== '1') {
        root.dataset.subnavBound = '1';
        const navButtons = root.querySelectorAll('[data-pg-settings-section]');
        const sections = root.querySelectorAll('.sd-settings-section[data-pg-settings-section]');
        const grid = document.getElementById('pgSettingsGrid');

        const showSection = (key) => {
          if (grid) grid.classList.add('sd-settings-grid-single');
          sections.forEach((el) => el.classList.toggle('hidden', el.dataset.pgSettingsSection !== key));
          navButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.pgSettingsSection === key));
        };

        navButtons.forEach((btn) => {
          btn.addEventListener('click', () => {
            const key = btn.dataset.pgSettingsSection;
            if (!key) return;
            showSection(key);
          });
        });

        // Sidebar "Profile"/"Settings" buttons can request a specific section
        document.querySelectorAll('[data-open-settings-section]').forEach((btn) => {
          if (btn.dataset.boundSettingsSection === '1') return;
          btn.dataset.boundSettingsSection = '1';
          btn.addEventListener('click', () => {
            const key = btn.dataset.openSettingsSection;
            if (!key) return;
            window.setTimeout(() => showSection(key), 0);
          });
        });

        showSection(document.querySelector('[data-open-settings-section].active')?.dataset.openSettingsSection || 'profile');

        // Expose for other scripts
        window.ExamGuardSettings.showSection = showSection;
      }
    } catch (error) {
      showAlert(error.message || 'Unable to load settings.', 'error');
    }
  }

  async function handleProfileSubmit(e) {
    e.preventDefault();
    const form = e.target;
    hideAlert();
    clearFormErrors(form);
    if (!handleClientValidation(form, validateProfileForm)) return;

    const btn = document.getElementById('settingsProfileBtn');
    setButtonLoading(btn, true, 'Saving…');

    try {
      const result = await ExamGuardApi.updateProfile({
        name: form.querySelector('#settingsName')?.value.trim(),
        email: form.querySelector('#settingsEmail')?.value.trim(),
        department: form.querySelector('#settingsDepartment')?.value.trim() || '',
      });
      applyUser(result.user);
      const message = result.email_verification_sent
        ? 'Profile saved. Check your inbox to verify your new email address.'
        : 'Profile updated successfully.';
      window.ExamGuardSettingsUI?.toast?.(message, 'success');
    } catch (error) {
      handleApiError(form, error, 'Unable to save profile.');
    } finally {
      setButtonLoading(btn, false, 'Save profile');
    }
  }

  async function handlePasswordSubmit(e) {
    e.preventDefault();
    const form = e.target;
    hideAlert();
    clearFormErrors(form);
    if (!handleClientValidation(form, validatePasswordForm)) return;

    const btn = document.getElementById('settingsPasswordBtn');
    setButtonLoading(btn, true, 'Updating…');

    try {
      await ExamGuardApi.updatePassword({
        current_password: form.querySelector('#settingsCurrentPassword')?.value,
        password: form.querySelector('#settingsNewPassword')?.value,
        password_confirmation: form.querySelector('#settingsConfirmPassword')?.value,
      });
      form.reset();
      window.ExamGuardSettingsUI?.toast?.('Password changed successfully.', 'success');
    } catch (error) {
      handleApiError(form, error, 'Unable to update password.');
    } finally {
      setButtonLoading(btn, false, 'Update password');
    }
  }

  async function handleNotificationsSubmit(e) {
    e.preventDefault();
    const form = e.target;
    hideAlert();
    clearFormErrors(form);

    const btn = document.getElementById('settingsNotificationsBtn');
    setButtonLoading(btn, true, 'Saving…');

    try {
      const result = await ExamGuardApi.updatePreferences({
        emailExamSubmitted: document.getElementById('settingsEmailExamSubmitted')?.checked ?? false,
        emailViolations: document.getElementById('settingsEmailViolations')?.checked ?? false,
        emailExamReminder: document.getElementById('settingsEmailExamReminder')?.checked ?? false,
      });
      applyUser(result.user);
      window.ExamGuardSettingsUI?.toast?.('Notification preferences saved.', 'success');
    } catch (error) {
      handleApiError(form, error, 'Unable to save notifications.');
    } finally {
      setButtonLoading(btn, false, 'Save notifications');
    }
  }

  async function handleWorkspaceSubmit(e) {
    e.preventDefault();
    const form = e.target;
    hideAlert();
    clearFormErrors(form);
    if (!handleClientValidation(form, validateWorkspaceForm)) return;

    const btn = document.getElementById('settingsWorkspaceBtn');
    setButtonLoading(btn, true, 'Saving…');

    try {
      const timeRaw = form.querySelector('#settingsDefaultTimeLimit')?.value.trim();
      const result = await ExamGuardApi.updatePreferences({
        defaultTimeLimit: timeRaw ? Number(timeRaw) : null,
        defaultWarningLimit: Number(form.querySelector('#settingsDefaultWarningLimit')?.value || 3),
      });
      applyUser(result.user);
      window.ExamGuardSettingsUI?.toast?.('Workspace defaults saved.', 'success');
    } catch (error) {
      handleApiError(form, error, 'Unable to save defaults.');
    } finally {
      setButtonLoading(btn, false, 'Save defaults');
    }
  }

  function bindFieldClear(form) {
    form.querySelectorAll('.pg-settings-field input, .pg-settings-field select').forEach((input) => {
      input.addEventListener('input', () => {
        const wrap = input.closest('.pg-settings-field');
        if (!wrap?.classList.contains('is-error')) return;
        wrap.classList.remove('is-error');
        input.removeAttribute('aria-invalid');
        input.removeAttribute('aria-describedby');
        const errorEl = wrap.querySelector('[data-field-error]');
        if (errorEl) {
          errorEl.textContent = '';
          errorEl.classList.add('hidden');
        }
        const card = getFormCard(form);
        if (card && !form.querySelector('.pg-settings-field.is-error')) {
          card.classList.remove('has-errors');
        }
        form.querySelector('[data-form-error]')?.classList.add('hidden');
      });
    });
  }

  function bindForms() {
    const profileForm = document.getElementById('settingsProfileForm');
    const passwordForm = document.getElementById('settingsPasswordForm');
    const notificationsForm = document.getElementById('settingsNotificationsForm');
    const workspaceForm = document.getElementById('settingsWorkspaceForm');

    profileForm?.addEventListener('submit', handleProfileSubmit);
    passwordForm?.addEventListener('submit', handlePasswordSubmit);
    notificationsForm?.addEventListener('submit', handleNotificationsSubmit);
    workspaceForm?.addEventListener('submit', handleWorkspaceSubmit);

    [profileForm, passwordForm, workspaceForm].filter(Boolean).forEach(bindFieldClear);
  }

  bindForms();

  window.ExamGuardSettings = { init: initSettings, initProfile };
})();
