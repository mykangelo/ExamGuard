(function () {
  'use strict';

  let currentUser = null;
  let bound = false;

  function initials(name) {
    const parts = (name || 'U').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return 'U';
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  function getAlert() {
    return document.getElementById('sdSettingsAlert');
  }

  function showAlert(message, type = 'success') {
    const alert = getAlert();
    if (!alert) return;
    const icon = type === 'error' ? 'ti-alert-circle' : 'ti-circle-check';
    alert.innerHTML = `<i class="ti ${icon}" aria-hidden="true"></i><span>${message}</span>`;
    alert.classList.remove('hidden', 'is-error', 'is-success');
    alert.classList.add(type === 'error' ? 'is-error' : 'is-success');
    alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function hideAlert() {
    const alert = getAlert();
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
    getFormCard(form)?.classList.remove('has-errors', 'is-shake');
  }

  function setFieldError(form, fieldName, message) {
    const wrap = form.querySelector(`.pg-settings-field[data-field="${fieldName}"]`);
    if (!wrap) return false;
    const input = wrap.querySelector('input, select');
    const errorEl = wrap.querySelector('[data-field-error]');
    const errorId = `sd-settings-error-${fieldName}`;

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

    if (!applied && fallbackMessage) setFormError(form, fallbackMessage);

    if (applied || fallbackMessage) {
      shakeCard(form);
      (firstInput ?? form.querySelector('[data-form-error]'))?.focus?.({ preventScroll: true });
      firstInput?.scrollIntoView?.({ behavior: 'smooth', block: 'center' });
    }

    return applied > 0;
  }

  function validateProfileForm(form) {
    const errors = {};
    const name = form.querySelector('#sdSettingsName')?.value.trim() ?? '';
    const email = form.querySelector('#sdSettingsEmail')?.value.trim() ?? '';

    if (!name) errors.name = 'Full name is required.';
    else if (name.length > 255) errors.name = 'Full name must be 255 characters or fewer.';

    if (!email) errors.email = 'Email address is required.';
    else if (!isValidEmail(email)) errors.email = 'Enter a valid email address.';

    return errors;
  }

  function validatePasswordForm(form) {
    const errors = {};
    const current = form.querySelector('#sdSettingsCurrentPassword')?.value ?? '';
    const next = form.querySelector('#sdSettingsNewPassword')?.value ?? '';
    const confirm = form.querySelector('#sdSettingsConfirmPassword')?.value ?? '';

    if (!current) errors.current_password = 'Enter your current password.';
    if (!next) errors.password = 'Enter a new password.';
    else if (next.length < 8) errors.password = 'New password must be at least 8 characters.';
    if (!confirm) errors.password_confirmation = 'Confirm your new password.';
    else if (next && confirm !== next) errors.password_confirmation = 'Passwords do not match.';

    return errors;
  }

  function handleClientValidation(form, validator) {
    const errors = validator(form);
    if (!Object.keys(errors).length) return true;
    applyErrors(form, errors);
    return false;
  }

  function handleApiError(form, error, fallbackMessage) {
    if (error?.errors && applyErrors(form, error.errors, error.message)) return;
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
    window.ExamGuardStudent = window.ExamGuardStudent || {};
    window.ExamGuardStudent.user = user;
    window.ExamGuardStudent.preferences = user.preferences;
    window.ExamGuardStudent?.applyUser?.(user);

    const nameInput = document.getElementById('sdSettingsName');
    const emailInput = document.getElementById('sdSettingsEmail');
    if (nameInput) nameInput.value = user.name || '';
    if (emailInput) emailInput.value = user.email || '';

    const department = document.getElementById('sdSettingsDepartment');
    const yearLevel = document.getElementById('sdSettingsYearLevel');
    const studentId = document.getElementById('sdSettingsStudentId');
    if (department) department.value = user.department || '';
    if (yearLevel) yearLevel.value = user.yearLevel || '';
    if (studentId) studentId.value = user.studentId || '';

    ui?.renderAvatarButton?.(document.getElementById('sdSettingsAvatarBtn'), user);
    const navAvatar = document.getElementById('sdAvatarBtn');
    if (navAvatar) {
      if (user.avatarUrl) {
        navAvatar.innerHTML = `<img src="${user.avatarUrl}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;
      } else {
        navAvatar.textContent = initials(user.name);
      }
    }

    const emailWrap = document.querySelector('#sdSettingsProfileForm .pg-settings-email-wrap');
    if (emailWrap) {
      const badge = emailWrap.querySelector('.pg-settings-badge');
      if (badge) {
        badge.className = `pg-settings-badge ${user.verified ? 'pg-settings-badge-ok' : 'pg-settings-badge-warn'}`;
        badge.innerHTML = user.verified
          ? '<i class="ti ti-circle-check"></i> Verified'
          : 'Unverified';
      }
    }

    const verifiedStatus = document.getElementById('sdSettingsVerifiedStatus');
    if (verifiedStatus) {
      verifiedStatus.innerHTML = user.verified
        ? '<span class="pg-settings-badge pg-settings-badge-ok">Verified</span>'
        : '<span class="pg-settings-badge pg-settings-badge-warn">Unverified</span>';
    }

    const memberSince = document.getElementById('sdSettingsMemberSince');
    if (memberSince && user.member_since) memberSince.textContent = user.member_since;

    const prefs = user.preferences || {};
    const toggles = {
      sdSettingsEmailExamAssigned: prefs.emailExamAssigned,
      sdSettingsEmailClassUpdates: prefs.emailClassUpdates,
      sdSettingsEmailExamReminder: prefs.emailExamReminder,
      sdSettingsEmailExamResults: prefs.emailExamResults,
    };
    Object.entries(toggles).forEach(([id, value]) => {
      const el = document.getElementById(id);
      if (el) el.checked = value !== false;
    });
  }

  async function loadUser() {
    const seed = window.ExamGuardStudent?.user;
    if (seed) {
      applyUser(seed);
      return;
    }
    const { user } = await ExamGuardApi.me();
    applyUser(user);
  }

  async function init() {
    const root = document.getElementById('sdSettingsView');
    if (!root) return;

    hideAlert();
    document.querySelectorAll('#sdSettingsView form').forEach(clearFormErrors);

    try {
      await loadUser();
      const ui = window.ExamGuardSettingsUI;
      ui?.bindPasswordToggles?.(root);
      ui?.bindPasswordStrength?.(
        document.getElementById('sdSettingsNewPassword'),
        document.getElementById('sdSettingsPwStrength'),
      );
      ui?.bindAvatarUpload?.({
        buttonId: 'sdSettingsAvatarBtn',
        inputId: 'sdSettingsAvatarInput',
        onUploaded: applyUser,
      });
      ui?.bindDangerZone?.({
        logoutAllId: 'sdSettingsLogoutAll',
        deleteId: 'sdSettingsDeleteAccount',
      });

      // Settings sub-nav: show one section at a time
      const navButtons = root.querySelectorAll('[data-sd-settings-section]');
      const sections = root.querySelectorAll('.sd-settings-section[data-sd-settings-section]');
      const grid = document.getElementById('sdSettingsGrid');

      const showSection = (key) => {
        if (grid) grid.classList.add('sd-settings-grid-single');
        sections.forEach((el) => el.classList.toggle('hidden', el.dataset.sdSettingsSection !== key));
        navButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.sdSettingsSection === key));
      };

      navButtons.forEach((btn) => {
        if (btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', () => {
          const key = btn.dataset.sdSettingsSection;
          if (!key) return;
          showSection(key);
        });
      });

      showSection('profile');
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

    const btn = document.getElementById('sdSettingsProfileBtn');
    setButtonLoading(btn, true, 'Saving…');

    try {
      const result = await ExamGuardApi.updateProfile({
        name: form.querySelector('#sdSettingsName')?.value.trim(),
        email: form.querySelector('#sdSettingsEmail')?.value.trim(),
        department: form.querySelector('#sdSettingsDepartment')?.value.trim() || '',
        yearLevel: form.querySelector('#sdSettingsYearLevel')?.value.trim() || '',
        studentId: form.querySelector('#sdSettingsStudentId')?.value.trim() || '',
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

    const btn = document.getElementById('sdSettingsPasswordBtn');
    setButtonLoading(btn, true, 'Updating…');

    try {
      await ExamGuardApi.updatePassword({
        current_password: form.querySelector('#sdSettingsCurrentPassword')?.value,
        password: form.querySelector('#sdSettingsNewPassword')?.value,
        password_confirmation: form.querySelector('#sdSettingsConfirmPassword')?.value,
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

    const btn = document.getElementById('sdSettingsNotificationsBtn');
    setButtonLoading(btn, true, 'Saving…');

    try {
      const result = await ExamGuardApi.updatePreferences({
        emailExamAssigned: document.getElementById('sdSettingsEmailExamAssigned')?.checked ?? false,
        emailClassUpdates: document.getElementById('sdSettingsEmailClassUpdates')?.checked ?? false,
        emailExamReminder: document.getElementById('sdSettingsEmailExamReminder')?.checked ?? false,
        emailExamResults: document.getElementById('sdSettingsEmailExamResults')?.checked ?? false,
      });
      applyUser(result.user);
      window.ExamGuardSettingsUI?.toast?.('Notification preferences saved.', 'success');
    } catch (error) {
      handleApiError(form, error, 'Unable to save notifications.');
    } finally {
      setButtonLoading(btn, false, 'Save notifications');
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
    if (bound) return;
    bound = true;

    const profileForm = document.getElementById('sdSettingsProfileForm');
    const passwordForm = document.getElementById('sdSettingsPasswordForm');
    const notificationsForm = document.getElementById('sdSettingsNotificationsForm');

    profileForm?.addEventListener('submit', handleProfileSubmit);
    passwordForm?.addEventListener('submit', handlePasswordSubmit);
    notificationsForm?.addEventListener('submit', handleNotificationsSubmit);

    [profileForm, passwordForm].filter(Boolean).forEach(bindFieldClear);
  }

  bindForms();

  window.ExamGuardStudentSettings = { init };
})();
