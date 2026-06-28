(function () {
  'use strict';

  const POLL_MS = 4000;
  const root = document.getElementById('proctoringView');
  if (!root) return;

  const tbody = document.getElementById('proctoringTableBody');
  const table = document.getElementById('proctoringTable');
  const countEl = document.getElementById('proctoringVisibleCount');
  const noResults = document.getElementById('proctoringNoResults');
  const searchInput = document.getElementById('proctoringSearch');
  const statusFilter = document.getElementById('proctoringStatusFilter');
  const filterClear = document.getElementById('proctoringFilterClear');
  const statActive = document.getElementById('proctoringStatActive');
  const statInProgress = document.getElementById('proctoringStatInProgress');
  const statAlerts = document.getElementById('proctoringStatAlerts');
  const alertBanner = document.getElementById('liveSessionsAlert');
  const detailPanel = document.getElementById('proctoringSessionDetail');
  const detailTitle = document.getElementById('liveSessionDetailTitle');
  const detailMeta = document.getElementById('liveSessionDetailMeta');
  const detailEvents = document.getElementById('liveSessionDetailEvents');
  const detailClose = document.getElementById('liveSessionDetailClose');

  let pollTimer = null;
  let active = false;
  let lastSnapshot = {};
  let selectedAttemptId = null;
  let sessions = [];

  function esc(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatDuration(seconds) {
    const mins = Math.floor(Math.max(seconds, 0) / 60);
    const secs = Math.max(seconds, 0) % 60;
    return `${mins}:${String(secs).padStart(2, '0')}`;
  }

  function statusClass(status) {
    if (status === 'disconnected') return 'is-disconnected';
    return 'is-active';
  }

  function statusLabel(status) {
    if (status === 'disconnected') return 'Disconnected';
    return 'In Progress';
  }

  function severityBadges(summary) {
    const parts = [];
    if (summary?.minor) parts.push(`<span class="pg-severity-pill pg-severity-minor">${summary.minor}</span>`);
    if (summary?.moderate) parts.push(`<span class="pg-severity-pill pg-severity-moderate">${summary.moderate}</span>`);
    if (summary?.critical) parts.push(`<span class="pg-severity-pill pg-severity-critical">${summary.critical}</span>`);
    return parts.length ? `<div class="pg-severity-summary">${parts.join('')}</div>` : '<span class="pg-severity-none">0</span>';
  }

  function violationTotal(summary) {
    return (summary?.minor || 0) + (summary?.moderate || 0) + (summary?.critical || 0);
  }

  function updateStats(list) {
    const inProgress = list.filter((session) => session.status === 'in_progress').length;
    const withAlerts = list.filter((session) => violationTotal(session.severitySummary) > 0).length;
    if (statActive) statActive.textContent = String(list.length);
    if (statInProgress) statInProgress.textContent = String(inProgress);
    if (statAlerts) statAlerts.textContent = String(withAlerts);
  }

  function applyFilters() {
    if (!tbody) return;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const status = statusFilter?.value || '';
    let visible = 0;

    tbody.querySelectorAll('.pg-proctoring-row').forEach((row) => {
      const student = row.dataset.student || '';
      const exam = row.dataset.exam || '';
      const rowStatus = row.dataset.status || '';
      const matchSearch = !query || student.includes(query) || exam.includes(query);
      const matchStatus = !status || rowStatus === status;
      const show = matchSearch && matchStatus;
      row.hidden = !show;
      if (show) visible += 1;
    });

    if (countEl) countEl.textContent = `${visible} session${visible === 1 ? '' : 's'}`;
    const hasFilter = !!(query || status);
    filterClear?.classList.toggle('visible', hasFilter);
    noResults?.classList.toggle('visible', visible === 0 && hasFilter && sessions.length > 0);
    if (table) table.style.display = visible === 0 && hasFilter && sessions.length > 0 ? 'none' : '';
  }

  function renderEmptyRow() {
    if (!tbody) return;
    tbody.innerHTML = `
      <tr class="pg-proctoring-empty-row">
        <td colspan="6" class="pg-table-empty">No students are taking an exam right now.</td>
      </tr>
    `;
    if (countEl) countEl.textContent = '0 sessions';
    closeDetail();
  }

  function renderSessions(list) {
    sessions = list;
    updateStats(list);

    if (!list.length) {
      renderEmptyRow();
      return;
    }

    if (!tbody) return;

    tbody.innerHTML = list.map((session, index) => {
      const alertClass = session.hasNewViolation ? ' has-alert' : '';
      const selectedClass = selectedAttemptId === session.attemptId ? ' is-selected' : '';
      const alertFlag = session.hasNewViolation
        ? '<span class="pg-proctoring-alert-flag"><i class="ti ti-alert-triangle"></i> New</span>'
        : '';

      return `
        <tr class="pg-proctoring-row${alertClass}${selectedClass}"
            data-attempt-id="${session.attemptId}"
            data-student="${esc(session.studentName).toLowerCase()}"
            data-exam="${esc(session.examTitle).toLowerCase()}"
            data-status="${esc(session.status)}"
            data-sort-index="${index}">
          <td class="exam-col">
            <div class="exam-cell">
              <button type="button" class="pg-proctoring-student-btn">${esc(session.studentName)}</button>
              ${alertFlag}
            </div>
          </td>
          <td class="exam-col">
            <div class="exam-cell">
              <span class="pg-exam-cell-title">${esc(session.examTitle)}</span>
            </div>
          </td>
          <td>
            <span class="pg-proctoring-status ${statusClass(session.status)}">${statusLabel(session.status)}</span>
          </td>
          <td>${formatDuration(session.elapsedSeconds)}</td>
          <td>${formatDuration(session.remainingSeconds)}</td>
          <td>${severityBadges(session.severitySummary)}</td>
        </tr>
      `;
    }).join('');

    applyFilters();
  }

  function showAlert(message) {
    if (!alertBanner) return;
    alertBanner.textContent = message;
    alertBanner.classList.add('visible');
    setTimeout(() => alertBanner.classList.remove('visible'), 5000);
  }

  function detectNewViolations(list) {
    list.forEach((session) => {
      const key = String(session.attemptId);
      const prev = lastSnapshot[key];
      const currentTotal = violationTotal(session.severitySummary);

      if (session.hasNewViolation || (prev && currentTotal > prev.total)) {
        showAlert(`${session.studentName} triggered a violation during ${session.examTitle}`);
        window.ExamGuardNotifications?.refresh?.();
      }

      lastSnapshot[key] = { total: currentTotal };
    });
  }

  function renderEvent(event) {
    const time = event.occurredAt ? new Date(event.occurredAt).toLocaleString() : 'Unknown time';
    const snapshot = event.snapshotUrl
      ? `<img src="${esc(event.snapshotUrl)}" alt="Violation snapshot" class="pg-violation-snapshot" loading="lazy">`
      : '';

    return `
      <article class="pg-violation-event pg-severity-${esc(event.severity)}">
        <div class="pg-violation-event-head">
          <span class="pg-severity-pill pg-severity-${esc(event.severity)}">${esc(event.severity)}</span>
          <span class="pg-violation-event-type">${esc(event.type)}</span>
          <time>${esc(time)}</time>
        </div>
        <p>${esc(event.message)}</p>
        ${snapshot}
      </article>
    `;
  }

  async function openDetail(attemptId) {
    if (selectedAttemptId === attemptId && !detailPanel?.classList.contains('hidden')) {
      closeDetail();
      return;
    }

    selectedAttemptId = attemptId;
    tbody?.querySelectorAll('.pg-proctoring-row').forEach((row) => {
      row.classList.toggle('is-selected', Number(row.dataset.attemptId) === attemptId);
    });

    if (!detailPanel) return;
    detailPanel.classList.remove('hidden');
    detailTitle.textContent = 'Loading session…';
    detailMeta.textContent = '';
    detailEvents.innerHTML = '<div class="pg-table-empty">Loading violation log…</div>';
    detailPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    try {
      const payload = await ExamGuardApi.attemptViolations(attemptId);
      const attempt = payload.attempt || {};
      detailTitle.textContent = attempt.studentName || 'Student session';
      detailMeta.textContent = `${attempt.examTitle || 'Exam'} · ${attempt.severityLabel || 'No violations'}`;
      const events = payload.events || [];
      detailEvents.innerHTML = events.length
        ? events.map(renderEvent).join('')
        : '<div class="pg-table-empty">No violations recorded for this session yet.</div>';
    } catch (error) {
      detailEvents.innerHTML = `<div class="pg-table-empty">${esc(error.message || 'Unable to load violation log.')}</div>`;
    }
  }

  function closeDetail() {
    selectedAttemptId = null;
    detailPanel?.classList.add('hidden');
    tbody?.querySelectorAll('.pg-proctoring-row.is-selected').forEach((row) => {
      row.classList.remove('is-selected');
    });
  }

  async function refresh() {
    if (!active) return;
    try {
      const payload = await ExamGuardApi.liveSessions();
      const nextSessions = payload.sessions || [];
      detectNewViolations(nextSessions);
      renderSessions(nextSessions);
      if (selectedAttemptId) {
        const stillActive = nextSessions.some((session) => session.attemptId === selectedAttemptId);
        if (!stillActive) closeDetail();
      }
    } catch (_) {}
  }

  function start() {
    if (active) return;
    active = true;
    refresh();
    pollTimer = setInterval(refresh, POLL_MS);
  }

  function stop() {
    active = false;
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = null;
    closeDetail();
  }

  tbody?.addEventListener('click', (event) => {
    const row = event.target.closest('.pg-proctoring-row');
    if (!row) return;
    openDetail(Number(row.dataset.attemptId));
  });

  searchInput?.addEventListener('input', applyFilters);
  statusFilter?.addEventListener('change', applyFilters);
  filterClear?.addEventListener('click', () => {
    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = '';
    applyFilters();
  });
  detailClose?.addEventListener('click', closeDetail);

  window.ExamGuardLiveSessions = { start, stop, refresh };

  if (document.getElementById('view-live-sessions')?.classList.contains('active')) {
    start();
  }
})();
