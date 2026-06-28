(function () {
  'use strict';

  const ICONS = {
    danger: { className: 'is-danger', icon: 'ti-trash' },
    warning: { className: 'is-warning', icon: 'ti-alert-triangle' },
    info: { className: 'is-info', icon: 'ti-info-circle' },
    success: { className: 'is-success', icon: 'ti-circle-check' },
  };

  let root;
  let backdrop;
  let iconWrap;
  let titleEl;
  let messageEl;
  let cancelBtn;
  let confirmBtn;
  let toastRoot;
  let resolvePromise = null;
  let mode = 'alert';

  function init() {
    root = document.getElementById('pgDialogRoot');
    if (!root) return false;

    backdrop = root.querySelector('[data-dialog-dismiss]');
    iconWrap = document.getElementById('pgDialogIcon');
    titleEl = document.getElementById('pgDialogTitle');
    messageEl = document.getElementById('pgDialogMessage');
    cancelBtn = document.getElementById('pgDialogCancel');
    confirmBtn = document.getElementById('pgDialogConfirm');
    toastRoot = document.getElementById('pgToastRoot');

    backdrop?.addEventListener('click', () => close(mode === 'confirm' ? false : undefined));
    cancelBtn?.addEventListener('click', () => close(false));
    confirmBtn?.addEventListener('click', () => close(mode === 'confirm' ? true : undefined));

    document.addEventListener('keydown', (event) => {
      if (root?.classList.contains('hidden')) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        close(mode === 'confirm' ? false : undefined);
      }
    });

    return true;
  }

  function setIcon(type) {
    const config = ICONS[type] || ICONS.info;
    iconWrap.className = `pg-dialog-icon ${config.className}`;
    iconWrap.innerHTML = `<i class="ti ${config.icon}" aria-hidden="true"></i>`;
  }

  function openDialog(options, dialogMode) {
    if (!root && !init()) {
      return Promise.resolve(dialogMode === 'confirm' ? window.confirm(options.message || options.title) : (window.alert(options.message || options.title), undefined));
    }

    mode = dialogMode;
    const isConfirm = dialogMode === 'confirm';
    const tone = options.tone || (isConfirm && options.danger ? 'danger' : 'info');

    setIcon(tone);
    titleEl.textContent = options.title || (isConfirm ? 'Are you sure?' : 'Notice');
    messageEl.innerHTML = options.message || '';

    cancelBtn.classList.toggle('hidden', !isConfirm);
    cancelBtn.textContent = options.cancelLabel || 'Cancel';

    confirmBtn.textContent = options.confirmLabel || (isConfirm ? 'Confirm' : 'OK');
    confirmBtn.classList.toggle('is-danger', !!options.danger);

    root.classList.remove('hidden');
    root.setAttribute('aria-hidden', 'false');
    (isConfirm ? cancelBtn : confirmBtn).focus();

    return new Promise((resolve) => {
      resolvePromise = resolve;
    });
  }

  function close(result) {
    if (!root) return;
    root.classList.add('hidden');
    root.setAttribute('aria-hidden', 'true');
    confirmBtn.classList.remove('is-danger');

    const resolve = resolvePromise;
    resolvePromise = null;
    resolve?.(result);
  }

  function toast(message, type = 'success', duration = 3200) {
    if (!toastRoot && !init()) return;

    const icons = { success: 'ti-circle-check', error: 'ti-alert-circle', info: 'ti-info-circle' };
    const el = document.createElement('div');
    el.className = `pg-toast is-${type}`;
    el.innerHTML = `<i class="ti ${icons[type] || icons.info}" aria-hidden="true"></i><span>${message}</span>`;
    toastRoot.appendChild(el);

    window.setTimeout(() => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(6px)';
      el.style.transition = 'opacity 0.2s, transform 0.2s';
      window.setTimeout(() => el.remove(), 220);
    }, duration);
  }

  window.ExamGuardDialog = {
    confirm(options = {}) {
      return openDialog(options, 'confirm');
    },
    alert(options = {}) {
      const payload = typeof options === 'string' ? { message: options } : options;
      return openDialog(
        {
          tone: payload.type === 'error' ? 'danger' : payload.type || 'info',
          title: payload.title || (payload.type === 'error' ? 'Something went wrong' : 'Notice'),
          message: payload.message || '',
          confirmLabel: payload.confirmLabel || 'OK',
        },
        'alert',
      ).then(() => {});
    },
    toast,
  };

  init();
})();
