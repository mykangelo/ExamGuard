(function () {
  'use strict';

  const toggle = document.getElementById('topbarNotifyToggle');
  const panel = document.getElementById('topbarNotifyPanel');
  const list = document.getElementById('notifyList');
  const badge = document.getElementById('notifyBadge');
  const markAllBtn = document.getElementById('notifyMarkAllBtn');

  let notifications = [];
  let unreadCount = 0;
  let loaded = false;

  function iconForType(type) {
    return type === 'violation' ? 'ti-alert-triangle' : 'ti-file-check';
  }

  function iconClassForType(type) {
    return type === 'violation' ? 'is-violation' : 'is-submission';
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

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
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
      const payload = await ExamGuardApi.notifications();
      applyPayload(payload);
    } catch (error) {
      list.innerHTML = `<div class="pg-notify-empty">${escapeHtml(error.message || 'Unable to load notifications.')}</div>`;
    }
  }

  async function markRead(ids) {
    if (!ids.length) return;
    try {
      const payload = await ExamGuardApi.markNotificationsRead({ ids });
      applyPayload(payload);
    } catch (_) {}
  }

  async function markAllRead() {
    try {
      const payload = await ExamGuardApi.markNotificationsRead({ all: true });
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
    const workspace = window.ExamGuardProfessor || {};

    if (action.view === 'exams' && action.examId && workspace.switchView && workspace.showExamDetail) {
      workspace.switchView('exams', { keepExamSelection: true });
      const row = document.querySelector(`#examsTableBody .pg-exam-row[data-exam-id="${action.examId}"]`);
      if (row) workspace.showExamDetail(row);
      return;
    }

    if (action.view === 'live-sessions' && workspace.switchView) {
      workspace.switchView('live-sessions');
      return;
    }

    if (action.view && workspace.switchView) {
      workspace.switchView(action.view);
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
    const item = notifications.find((entry) => entry.id === id);
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

  window.ExamGuardNotifications = {
    refresh: loadNotifications,
  };
})();
