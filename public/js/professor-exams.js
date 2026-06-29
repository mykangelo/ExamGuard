(function () {
  'use strict';

  const menuOpenClass = 'open';
  const menuUpClass = 'open-up';
  const dialog = () => window.ExamGuardDialog;

  function resetMenuPanel(panel) {
    panel.classList.remove(menuUpClass);
  }

  function closeAllMenus() {
    document.querySelector('#view-exams .pg-exams-table-scroll')?.classList.remove('pg-exams-table-scroll--menu-open');
    document.querySelector('#view-exams .pg-table-wrap')?.classList.remove('pg-exams-table-wrap--menu-open');
    document.querySelectorAll('.pg-row-menu-panel.open').forEach((panel) => {
      panel.classList.remove(menuOpenClass);
      resetMenuPanel(panel);
    });
  }

  function positionMenuPanel(menuBtn, panel) {
    resetMenuPanel(panel);

    const viewportBottom = window.innerHeight - 8;
    const panelHeight = panel.offsetHeight || 220;
    const btnRect = menuBtn.getBoundingClientRect();

    if (btnRect.bottom + 4 + panelHeight > viewportBottom) {
      panel.classList.add(menuUpClass);
    }
  }

  function getRow(examId) {
    return document.querySelector(`#examsTableBody .pg-exam-row[data-exam-id="${examId}"]`);
  }

  function copyText(text) {
    if (navigator.clipboard?.writeText) {
      return navigator.clipboard.writeText(text);
    }
    const input = document.createElement('textarea');
    input.value = text;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
    return Promise.resolve();
  }

  function shareLink(examId) {
    return `${window.location.origin}/take-exam?examId=${examId}`;
  }

  function bindExamKeyCopy() {
    document.addEventListener('click', (event) => {
      const btn = event.target.closest('[data-exam-key-copy]');
      if (!btn) return;
      event.stopPropagation();
      closeAllMenus();
      copyExamKey(btn.dataset.examKeyCopy || btn.getAttribute('data-exam-key-copy'));
    });
  }

  function esc(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function toDatetimeLocalValue(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (value) => String(value).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
  }

  function fromDatetimeLocalValue(value) {
    if (!value) return null;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date.toISOString();
  }

  function scheduleDialogHtml(examId, opensAt, closesAt) {
    const opensValue = toDatetimeLocalValue(opensAt);
    const closesValue = toDatetimeLocalValue(closesAt);
    return `
      <p class="pg-schedule-dialog-hint">Set when students can access this exam. Leave open blank to make it available immediately.</p>
      <div class="pg-schedule-dialog-fields">
        <label for="schedule-opens-${examId}">
          Opens
          <input type="datetime-local" id="schedule-opens-${examId}" value="${opensValue}">
        </label>
        <label for="schedule-closes-${examId}">
          Closes
          <input type="datetime-local" id="schedule-closes-${examId}" value="${closesValue}">
        </label>
      </div>
    `;
  }

  async function handleDuplicate(examId) {
    closeAllMenus();
    try {
      const { exam } = await ExamGuardApi.duplicateExam(examId);
      window.location.assign(`/professor?view=create-exam&id=${exam.id}`);
    } catch (error) {
      await dialog()?.alert({
        type: 'error',
        title: 'Unable to duplicate',
        message: error.message || 'This exam could not be duplicated. Please try again.',
      });
    }
  }

  async function handleDelete(examId, title) {
    closeAllMenus();
    const confirmed = await dialog()?.confirm({
      title: 'Delete exam?',
      message: `This will permanently delete <strong>${esc(title)}</strong> and all related attempts. This cannot be undone.`,
      confirmLabel: 'Delete exam',
      cancelLabel: 'Keep exam',
      danger: true,
      tone: 'danger',
    });
    if (!confirmed) return;

    try {
      await ExamGuardApi.deleteExam(examId);
      const row = getRow(examId);
      row?.remove();
      const detail = document.getElementById(`detail-${examId}`);
      detail?.remove();
      dialog()?.toast('Exam deleted.', 'success');
      if (!document.querySelector('#examsTableBody .pg-exam-row')) {
        window.location.reload();
      }
      window.ExamGuardProfessor?.filterExams?.();
    } catch (error) {
      await dialog()?.alert({
        type: 'error',
        title: 'Unable to delete',
        message: error.message || 'This exam could not be deleted. Please try again.',
      });
    }
  }

  async function handleSchedule(examId, title, opensAt, closesAt) {
    closeAllMenus();
    const confirmed = await dialog()?.confirm({
      title: 'Schedule exam',
      message: scheduleDialogHtml(examId, opensAt, closesAt),
      confirmLabel: 'Save schedule',
      cancelLabel: 'Cancel',
    });
    if (!confirmed) return;

    const opensValue = document.getElementById(`schedule-opens-${examId}`)?.value ?? '';
    const closesValue = document.getElementById(`schedule-closes-${examId}`)?.value ?? '';
    const opensAtIso = fromDatetimeLocalValue(opensValue);
    const closesAtIso = fromDatetimeLocalValue(closesValue);

    if (opensValue && closesValue) {
      const opens = new Date(opensValue);
      const closes = new Date(closesValue);
      if (!Number.isNaN(opens.getTime()) && !Number.isNaN(closes.getTime()) && closes <= opens) {
        await dialog()?.alert({
          type: 'error',
          title: 'Invalid schedule',
          message: 'Close time must be after open time.',
          confirmLabel: 'OK',
        });
        return;
      }
    }

    try {
      await ExamGuardApi.scheduleExam(examId, {
        opensAt: opensAtIso,
        closesAt: closesAtIso,
      });
      dialog()?.toast('Exam schedule updated.', 'success');
      window.location.reload();
    } catch (error) {
      await dialog()?.alert({
        type: 'error',
        title: 'Unable to update schedule',
        message: error.message || 'This exam schedule could not be updated. Please try again.',
      });
    }
  }

  async function handleClose(examId, title) {
    closeAllMenus();
    const confirmed = await dialog()?.confirm({
      title: 'Close exam?',
      message: `Students will no longer be able to submit <strong>${esc(title)}</strong>. You can still view results and violations.`,
      confirmLabel: 'Close exam',
      cancelLabel: 'Keep open',
      danger: true,
      tone: 'warning',
    });
    if (!confirmed) return;

    try {
      await ExamGuardApi.closeExam(examId);
      window.location.reload();
    } catch (error) {
      await dialog()?.alert({
        type: 'error',
        title: 'Unable to close exam',
        message: error.message || 'This exam could not be closed. Please try again.',
      });
    }
  }

  async function handleShare(examId, examKey) {
    closeAllMenus();
    if (!examKey) {
      await dialog()?.alert({
        type: 'info',
        title: 'No exam key',
        message: 'Publish this exam to generate a student exam key.',
        confirmLabel: 'OK',
      });
      return;
    }

    try {
      await copyText(examKey);
      dialog()?.toast('Exam key copied. Students can enter it on their dashboard.', 'success');
    } catch (_) {
      await dialog()?.alert({
        type: 'info',
        title: 'Exam key',
        message: `Students can enter this key on their dashboard:<br><br><strong style="font-family:monospace;letter-spacing:0.12em;">${esc(examKey)}</strong>`,
        confirmLabel: 'Got it',
      });
    }
  }

  async function copyExamKey(key) {
    if (!key) return;
    try {
      await copyText(key);
      dialog()?.toast('Exam key copied to clipboard.', 'success');
    } catch (_) {
      await dialog()?.alert({
        type: 'info',
        title: 'Exam key',
        message: `Share this key with students: <strong style="font-family:monospace;letter-spacing:0.1em;">${esc(key)}</strong>`,
        confirmLabel: 'Got it',
      });
    }
  }

  function bindMenus() {
    const table = document.getElementById('examsTableBody');
    if (!table) return;

    table.addEventListener('click', (event) => {
      const menuBtn = event.target.closest('.pg-row-menu-btn');
      if (menuBtn) {
        event.stopPropagation();
        const panel = menuBtn.parentElement?.querySelector('.pg-row-menu-panel');
        if (!panel) return;
        const isOpen = panel.classList.contains(menuOpenClass);
        closeAllMenus();
        if (!isOpen) {
          document.querySelector('#view-exams .pg-exams-table-scroll')?.classList.add('pg-exams-table-scroll--menu-open');
          document.querySelector('#view-exams .pg-table-wrap')?.classList.add('pg-exams-table-wrap--menu-open');
          panel.classList.add(menuOpenClass);
          positionMenuPanel(menuBtn, panel);
        }
        return;
      }

      const actionBtn = event.target.closest('[data-exam-action]');
      if (!actionBtn) return;

      event.stopPropagation();
      const row = actionBtn.closest('.pg-exam-row');
      if (!row) return;

      const examId = Number(row.dataset.examId);
      const title = row.dataset.title || 'this exam';
      const status = row.dataset.status || 'draft';
      const examKey = row.dataset.examKey || '';
      const opensAt = row.dataset.opensAt || '';
      const closesAt = row.dataset.closesAt || '';
      const action = actionBtn.dataset.examAction;

      if (action === 'view') {
        closeAllMenus();
        window.ExamGuardProfessor?.showExamDetail?.(row, { pushHistory: true });
        return;
      }

      if (action === 'edit') {
        closeAllMenus();
        if (status !== 'draft') {
          dialog()?.alert({
            type: 'info',
            title: 'Cannot edit exam',
            message: 'Only draft exams can be edited. Duplicate this exam to make changes.',
            confirmLabel: 'OK',
          });
          return;
        }
        window.ExamGuardProfessor?.switchView?.('create-exam', { examId, keepExamSelection: true });
        return;
      }

      if (action === 'duplicate') {
        handleDuplicate(examId);
        return;
      }

      if (action === 'delete') {
        handleDelete(examId, title);
        return;
      }

      if (action === 'share') {
        handleShare(examId, examKey);
        return;
      }

      if (action === 'schedule') {
        handleSchedule(examId, title, opensAt, closesAt);
        return;
      }

      if (action === 'close') {
        handleClose(examId, title);
      }
    });

    table.addEventListener('click', (event) => {
      const titleBtn = event.target.closest('.pg-exam-title-btn');
      if (!titleBtn) return;
      event.stopPropagation();
      const row = titleBtn.closest('.pg-exam-row');
      if (row) window.ExamGuardProfessor?.showExamDetail?.(row, { pushHistory: true });
    });
    document.querySelector('.pg-exams-table-scroll')?.addEventListener('scroll', closeAllMenus, { passive: true });
    window.addEventListener('resize', closeAllMenus);
  }

  document.addEventListener('click', closeAllMenus);

  bindMenus();
  bindExamKeyCopy();

  window.ExamGuardExams = { closeAllMenus, copyExamKey };
})();
