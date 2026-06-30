(function () {
  'use strict';

  const toggle = document.getElementById('sdNotifyToggle');
  const panel = document.getElementById('sdNotifyPanel');
  const list = document.getElementById('sdNotifyList');
  const badge = document.getElementById('sdNotifyDot');
  const markAllBtn = document.getElementById('sdNotifyMarkAll');

  let notifications = [];
  let unreadCount = 0;
  let loaded = false;

  function iconForType(type) {
    if (type === 'exam_assigned') return 'ti-file-description';
    if (type === 'class_joined') return 'ti-school';
    if (type === 'exam_deleted') return 'ti-file-off';
    if (type === 'class_deleted') return 'ti-school-off';
    return 'ti-bell';
  }

  function iconClassForType(type) {
    if (type === 'exam_assigned') return 'is-assigned';
    if (type === 'class_joined') return 'is-joined';
    if (type === 'exam_deleted' || type === 'class_deleted') return 'is-removed';
    return 'is-default';
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function updateBadge() {
    if (!badge || !toggle) return;
    if (unreadCount > 0) {
      badge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
      badge.classList.remove('hidden');
      toggle.classList.add('has-unread');
      toggle.setAttribute('aria-label', `Notifications, ${unreadCount} unread`);
    } else {
      badge.classList.add('hidden');
      toggle.classList.remove('has-unread');
      toggle.setAttribute('aria-label', 'Notifications');
    }
    if (markAllBtn) markAllBtn.disabled = unreadCount === 0;
  }

  function renderList() {
    if (!list) return;

    if (!notifications.length) {
      list.innerHTML = '<div class="pg-notify-empty">No notifications yet.</div>';
      return;
    }

    list.innerHTML = notifications.map((item) => `
      <button type="button" class="pg-notify-item${item.read ? '' : ' unread'}" data-notify-id="${item.id}" data-notify-type="${item.type}">
        <span class="pg-notify-item-icon ${iconClassForType(item.type)}">
          <i class="ti ${iconForType(item.type)}" aria-hidden="true"></i>
        </span>
        <span class="pg-notify-item-body">
          <span class="pg-notify-item-title">${escapeHtml(item.title)}</span>
          <span class="pg-notify-item-message">${escapeHtml(item.message)}</span>
          <span class="pg-notify-item-time">${escapeHtml(item.timeLabel || 'Recently')}</span>
        </span>
      </button>
    `).join('');
  }

  function applyPayload(payload) {
    notifications = payload.notifications || [];
    unreadCount = payload.unreadCount || 0;
    renderList();
    updateBadge();
    loaded = true;
  }

  async function loadNotifications() {
    if (!list) return;
    if (!loaded) {
      list.innerHTML = '<div class="pg-notify-loading">Loading notifications…</div>';
    }
    try {
      const payload = await ExamGuardApi.studentNotifications();
      applyPayload(payload);
    } catch (error) {
      list.innerHTML = `<div class="pg-notify-empty">${escapeHtml(error.message || 'Unable to load notifications.')}</div>`;
    }
  }

  async function markRead(ids) {
    if (!ids.length) return;
    try {
      const payload = await ExamGuardApi.markStudentNotificationsRead({ ids: ids.map(Number) });
      applyPayload(payload);
    } catch (_) {}
  }

  async function markAllRead() {
    try {
      const payload = await ExamGuardApi.markStudentNotificationsRead({ all: true });
      applyPayload(payload);
    } catch (_) {}
  }

  function closePanel() {
    panel?.classList.remove('open');
  }

  function openPanel() {
    document.querySelectorAll('.pg-dropdown.open, .pg-notify-panel.open').forEach((el) => {
      el.classList.remove('open');
    });
    panel?.classList.add('open');
    loadNotifications();
  }

  function handleNotificationAction(item) {
    const action = item.action || {};
    const student = window.ExamGuardStudent;

    if (action.view === 'class' && action.classId && student?.switchView) {
      student.switchView('class', { classId: Number(action.classId) });
      return;
    }

    if (action.view && student?.switchView) {
      student.switchView(action.view);
    }
  }

  if (toggle && panel) {
    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = panel.classList.contains('open');
      document.querySelectorAll('.pg-dropdown.open, .pg-notify-panel.open').forEach((el) => {
        el.classList.remove('open');
      });
      if (!isOpen) openPanel();
    });

    toggle.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggle.click();
      }
    });
  }

  markAllBtn?.addEventListener('click', async (e) => {
    e.stopPropagation();
    await markAllRead();
  });

  list?.addEventListener('click', async (e) => {
    const itemEl = e.target.closest('[data-notify-id]');
    if (!itemEl) return;
    e.stopPropagation();

    const id = itemEl.dataset.notifyId;
    const item = notifications.find((entry) => String(entry.id) === String(id));
    if (!item) return;

    closePanel();
    if (!item.read) await markRead([id]);
    handleNotificationAction(item);
  });

  panel?.addEventListener('click', (e) => {
    e.stopPropagation();
  });

  document.addEventListener('click', closePanel);

  loadNotifications();

  window.ExamGuardStudentNotifications = {
    refresh: loadNotifications,
  };
})();
