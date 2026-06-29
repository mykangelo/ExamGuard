(function () {
  'use strict';

  function initials(name) {
    const parts = (name || 'U').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return 'U';
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
  }

  function passwordStrength(password) {
    if (!password) return { level: '', label: '' };
    let score = 0;
    if (password.length >= 8) score += 1;
    if (password.length >= 12) score += 1;
    if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score += 1;
    if (/\d/.test(password)) score += 1;
    if (/[^A-Za-z0-9]/.test(password)) score += 1;
    if (score <= 2) return { level: 'weak', label: 'Weak' };
    if (score <= 3) return { level: 'fair', label: 'Fair' };
    return { level: 'strong', label: 'Strong' };
  }

  function toast(message, type = 'success') {
    if (window.ExamGuardDialog?.toast) {
      window.ExamGuardDialog.toast(message, type);
      return;
    }
    const root = document.getElementById('pgToastRoot');
    if (!root) return;
    const icons = { success: 'ti-circle-check', error: 'ti-alert-circle', info: 'ti-info-circle' };
    const el = document.createElement('div');
    el.className = `pg-toast is-${type}`;
    el.innerHTML = `<i class="ti ${icons[type] || icons.info}" aria-hidden="true"></i><span>${message}</span>`;
    root.appendChild(el);
    window.setTimeout(() => el.remove(), 3200);
  }

  function bindPasswordToggles(root = document) {
    root.querySelectorAll('[data-pw-toggle]').forEach((btn) => {
      if (btn.dataset.bound === '1') return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', () => {
        const input = btn.closest('.pg-settings-pw-field')?.querySelector('input');
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.innerHTML = `<i class="ti ti-${show ? 'eye-off' : 'eye'}" aria-hidden="true"></i>`;
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      });
    });
  }

  function bindPasswordStrength(input, meter) {
    if (!input || !meter) return;
    const label = meter.querySelector('.pg-settings-pw-strength-label');
    const update = () => {
      const { level, label: text } = passwordStrength(input.value);
      meter.classList.remove('is-weak', 'is-fair', 'is-strong');
      if (!level) {
        if (label) label.textContent = 'Enter a password';
        return;
      }
      meter.classList.add(`is-${level}`);
      if (label) label.textContent = text;
    };
    input.addEventListener('input', update);
    update();
  }

  function renderAvatarButton(btn, user) {
    if (!btn) return;
    const name = user?.name || btn.dataset.name || 'User';
    if (user?.avatarUrl) {
      btn.innerHTML = `<img src="${user.avatarUrl}" alt="">`;
    } else {
      btn.textContent = initials(name);
    }
    btn.dataset.name = name;
  }

  function bindAvatarUpload({ buttonId, inputId, onUploaded }) {
    const btn = document.getElementById(buttonId);
    const input = document.getElementById(inputId);
    if (!btn || !input || btn.dataset.bound === '1') return;
    btn.dataset.bound = '1';

    btn.addEventListener('click', () => input.click());
    input.addEventListener('change', async () => {
      const file = input.files?.[0];
      input.value = '';
      if (!file) return;
      if (!file.type.startsWith('image/')) {
        toast('Please choose an image file.', 'error');
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        toast('Image must be 2 MB or smaller.', 'error');
        return;
      }
      btn.disabled = true;
      try {
        const result = await ExamGuardApi.uploadAvatar(file);
        renderAvatarButton(btn, result.user);
        onUploaded?.(result.user);
        toast('Profile photo updated.');
      } catch (error) {
        toast(error.message || 'Unable to upload photo.', 'error');
      } finally {
        btn.disabled = false;
      }
    });
  }

  async function bindDangerZone({ logoutAllId, deleteId }) {
    const logoutBtn = document.getElementById(logoutAllId);
    const deleteBtn = document.getElementById(deleteId);

    if (logoutBtn && logoutBtn.dataset.bound !== '1') {
      logoutBtn.dataset.bound = '1';
      logoutBtn.addEventListener('click', async () => {
      const confirmed = window.ExamGuardDialog
        ? await window.ExamGuardDialog.confirm({
          title: 'Log out of all devices?',
          message: 'This will end every active session and return you to the login page.',
          confirmLabel: 'Log out everywhere',
          cancelLabel: 'Cancel',
          tone: 'warning',
        })
        : window.confirm('Log out of all devices?');
      if (!confirmed) return;
      try {
        await ExamGuardApi.logoutAllSessions();
        location.href = '/login';
      } catch (error) {
        toast(error.message || 'Unable to log out.', 'error');
      }
      });
    }

    if (deleteBtn && deleteBtn.dataset.bound !== '1') {
      deleteBtn.dataset.bound = '1';
      deleteBtn.addEventListener('click', async () => {
      const confirmed = window.ExamGuardDialog
        ? await window.ExamGuardDialog.confirm({
          title: 'Delete your account?',
          message: 'This permanently removes your profile, exam history, and enrollments. This cannot be undone.',
          confirmLabel: 'Delete account',
          cancelLabel: 'Keep account',
          danger: true,
          tone: 'danger',
        })
        : window.confirm('Delete your account permanently?');
      if (!confirmed) return;

      const password = window.prompt('Enter your password to confirm account deletion:');
      if (!password) return;

      try {
        await ExamGuardApi.deleteAccount(password);
        location.href = '/login';
      } catch (error) {
        toast(error.message || 'Unable to delete account.', 'error');
      }
      });
    }
  }

  window.ExamGuardSettingsUI = {
    initials,
    toast,
    bindPasswordToggles,
    bindPasswordStrength,
    renderAvatarButton,
    bindAvatarUpload,
    bindDangerZone,
  };
})();
