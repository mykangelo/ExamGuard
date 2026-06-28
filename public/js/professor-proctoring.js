(function () {
  'use strict';

  const root = document.getElementById('violationsView');
  if (!root) return;

  const tbody = document.getElementById('violationsTableBody');
  const table = document.getElementById('violationsTable');
  const countEl = document.getElementById('violationVisibleCount');
  const statsStudents = document.getElementById('violationsStatStudents');
  const statsTotal = document.getElementById('violationsStatTotal');
  const statsExams = document.getElementById('violationsStatExams');
  const examFilter = document.getElementById('violationExamFilter');
  const severityFilter = document.getElementById('violationSeverityFilter');
  const searchInput = document.getElementById('violationSearch');
  const noResults = document.getElementById('violationNoResults');
  const detailPanel = document.getElementById('violationDetailPanel');
  const detailTitle = document.getElementById('violationDetailTitle');
  const detailMeta = document.getElementById('violationDetailMeta');
  const detailEvents = document.getElementById('violationDetailEvents');
  const detailClose = document.getElementById('violationDetailClose');

  let records = [];
  let loaded = false;
  let selectedAttemptId = null;

  function esc(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function severityBadges(summary) {
    const parts = [];
    if (summary?.minor) parts.push(`<span class="pg-severity-pill pg-severity-minor">${summary.minor}</span>`);
    if (summary?.moderate) parts.push(`<span class="pg-severity-pill pg-severity-moderate">${summary.moderate}</span>`);
    if (summary?.critical) parts.push(`<span class="pg-severity-pill pg-severity-critical">${summary.critical}</span>`);
    return parts.length ? parts.join(' ') : '<span class="pg-severity-none">0</span>';
  }

  function formatDate(value) {
    if (!value) return 'In progress';
    return new Date(value).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
  }

  function formatDuration(startedAt, submittedAt) {
    if (!startedAt) return '—';
    const end = submittedAt ? new Date(submittedAt) : new Date();
    const totalSeconds = Math.max(0, Math.floor((end - new Date(startedAt)) / 1000));
    if (totalSeconds < 60) return `${totalSeconds}s`;
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return seconds ? `${minutes}m ${seconds}s` : `${minutes}m`;
  }

  function renderEvent(event) {
    const snapshot = event.snapshotUrl
      ? `<img src="${esc(event.snapshotUrl)}" alt="Violation snapshot" class="pg-violation-snapshot" loading="lazy">`
      : '';

    return `
      <article class="pg-violation-event pg-severity-${esc(event.severity)}">
        <div class="pg-violation-event-head">
          <span class="pg-severity-pill pg-severity-${esc(event.severity)}">${esc(event.severity)}</span>
          <span class="pg-violation-event-type">${esc(event.type)}</span>
          <time>${esc(formatDate(event.occurredAt))}</time>
        </div>
        <p>${esc(event.message)}</p>
        ${snapshot}
      </article>
    `;
  }

  function updateStats() {
    if (statsStudents) statsStudents.textContent = String(records.length);
    if (statsTotal) {
      const total = records.reduce((sum, record) => sum + (record.warningCount || 0), 0);
      statsTotal.textContent = String(total);
    }
    if (statsExams) {
      const exams = new Set(records.map((record) => record.examId));
      statsExams.textContent = String(exams.size);
    }
  }

  function updateExamFilterOptions() {
    if (!examFilter) return;
    const current = examFilter.value;
    const exams = new Map();
    records.forEach((record) => exams.set(record.examId, record.examTitle));
    examFilter.innerHTML = '<option value="">All exams</option>' + [...exams.entries()]
      .map(([id, title]) => `<option value="${id}">${esc(title)}</option>`)
      .join('');
    if (current && exams.has(Number(current))) examFilter.value = current;
  }

  function applyFilters() {
    if (!tbody) return;

    const query = (searchInput?.value || '').trim().toLowerCase();
    const examId = examFilter?.value || '';
    const severity = severityFilter?.value || '';
    let visible = 0;

    tbody.querySelectorAll('.pg-violation-row').forEach((row) => {
      const student = row.dataset.student || '';
      const exam = row.dataset.exam || '';
      const rowExamId = row.dataset.examId || '';
      const rowSeverity = row.dataset.severity || '';
      const matchSearch = !query || student.includes(query) || exam.includes(query);
      const matchExam = !examId || rowExamId === examId;
      const matchSeverity = !severity || rowSeverity.includes(severity);
      const show = matchSearch && matchExam && matchSeverity;
      row.hidden = !show;
      if (show) visible += 1;
    });

    if (countEl) countEl.textContent = `${visible} record${visible === 1 ? '' : 's'}`;
    const hasFilter = !!(query || examId || severity);
    noResults?.classList.toggle('visible', visible === 0 && hasFilter && records.length > 0);
    if (table) table.style.display = visible === 0 && hasFilter && records.length > 0 ? 'none' : '';
    document.getElementById('violationFilterClear')?.classList.toggle('visible', hasFilter);
  }

  function renderEmptyRow() {
    if (!tbody) return;
    tbody.innerHTML = `
      <tr class="pg-violations-empty-row">
        <td colspan="5" class="pg-table-empty">No violations recorded yet.</td>
      </tr>
    `;
    if (countEl) countEl.textContent = '0 records';
    updateStats();
    closeDetail();
  }

  function renderTable() {
    if (!tbody) return;

    if (!records.length) {
      renderEmptyRow();
      return;
    }

    tbody.innerHTML = records.map((record, index) => {
      const severityKeys = ['minor', 'moderate', 'critical'].filter((key) => (record.severitySummary?.[key] || 0) > 0);
      const selectedClass = selectedAttemptId === record.attemptId ? ' is-selected' : '';
      return `
        <tr class="pg-violation-row${selectedClass}"
            data-attempt-id="${record.attemptId}"
            data-student="${esc(record.studentName).toLowerCase()}"
            data-exam="${esc(record.examTitle).toLowerCase()}"
            data-exam-id="${record.examId}"
            data-severity="${severityKeys.join(',')}"
            data-sort-index="${index}">
          <td class="exam-col">
            <button type="button" class="pg-violation-student-btn">${esc(record.studentName)}</button>
          </td>
          <td class="exam-col"><span class="pg-exam-cell-title">${esc(record.examTitle)}</span></td>
          <td><div class="pg-severity-summary">${severityBadges(record.severitySummary)}</div></td>
          <td>${esc(formatDuration(record.startedAt, record.submittedAt))}</td>
          <td>${esc(formatDate(record.submittedAt))}</td>
        </tr>
      `;
    }).join('');

    updateStats();
    updateExamFilterOptions();
    applyFilters();
  }

  function openDetail(attemptId) {
    const record = records.find((entry) => entry.attemptId === attemptId);
    if (!record) return;

    if (selectedAttemptId === attemptId && !detailPanel?.classList.contains('hidden')) {
      closeDetail();
      return;
    }

    selectedAttemptId = attemptId;
    tbody?.querySelectorAll('.pg-violation-row').forEach((row) => {
      row.classList.toggle('is-selected', Number(row.dataset.attemptId) === attemptId);
    });

    detailPanel?.classList.remove('hidden');
    detailTitle.textContent = record.studentName;
    detailMeta.textContent = `${record.examTitle} · ${record.severityLabel || 'No violations'}`;
    detailEvents.innerHTML = (record.events || []).length
      ? record.events.map(renderEvent).join('')
      : '<div class="pg-table-empty">No violation events recorded.</div>';
    detailPanel?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function closeDetail() {
    selectedAttemptId = null;
    detailPanel?.classList.add('hidden');
    tbody?.querySelectorAll('.pg-violation-row.is-selected').forEach((row) => {
      row.classList.remove('is-selected');
    });
  }

  async function loadRecords() {
    try {
      const severity = severityFilter?.value || '';
      const payload = await ExamGuardApi.violationRecords(severity || undefined);
      records = payload.records || [];
      loaded = true;
      renderTable();
    } catch (error) {
      if (!loaded && tbody) {
        tbody.innerHTML = `<tr><td colspan="5" class="pg-table-empty">${esc(error.message || 'Unable to load violations.')}</td></tr>`;
      }
    }
  }

  function init() {
    if (!loaded) loadRecords();
  }

  tbody?.addEventListener('click', (event) => {
    const row = event.target.closest('.pg-violation-row');
    if (!row) return;
    openDetail(Number(row.dataset.attemptId));
  });

  searchInput?.addEventListener('input', applyFilters);
  examFilter?.addEventListener('change', applyFilters);
  severityFilter?.addEventListener('change', () => loadRecords());
  document.getElementById('violationFilterClear')?.addEventListener('click', () => {
    if (searchInput) searchInput.value = '';
    if (examFilter) examFilter.value = '';
    if (severityFilter) severityFilter.value = '';
    loadRecords();
  });
  detailClose?.addEventListener('click', closeDetail);

  window.ExamGuardProctoring = {
    init,
    refresh: loadRecords,
    openDetail,
    applyFilters,
    clearFilters() {
      if (searchInput) searchInput.value = '';
      if (examFilter) examFilter.value = '';
      if (severityFilter) severityFilter.value = '';
      loadRecords();
    },
  };
})();
