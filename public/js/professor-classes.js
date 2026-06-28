(function () {
  'use strict';

  let initialized = false;
  let classes = [];
  let exams = [];

  const dialog = () => window.ExamGuardDialog;

  const loadingEl = document.getElementById('classesLoading');
  const contentEl = document.getElementById('classesContent');
  const tableBody = document.getElementById('classesTableBody');
  const classSearch = document.getElementById('classSearch');
  const classNoResults = document.getElementById('classNoResults');
  const classVisibleCount = document.getElementById('classVisibleCount');
  const classesTable = document.getElementById('classesTable');
  const assignExamSelect = document.getElementById('assignExamSelect');
  const assignClassSelect = document.getElementById('assignClassSelect');
  const assignExamBtn = document.getElementById('assignExamBtn');
  const createClassForm = document.getElementById('createClassForm');
  const assignExamForm = document.getElementById('assignExamForm');
  const classNameInput = document.getElementById('classNameInput');
  const classSubjectInput = document.getElementById('classSubjectInput');
  const createClassBtn = createClassForm?.querySelector('button[type="submit"]');
  const assignExamSubmitBtn = assignExamForm?.querySelector('button[type="submit"]');

  function setVisible(el, show) {
    el?.classList.toggle('hidden', !show);
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  async function copyText(text) {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
      return;
    }
    const input = document.createElement('textarea');
    input.value = text;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
  }

  function setButtonLoading(btn, loading, label) {
    if (!btn) return;
    if (loading) {
      btn.dataset.label = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = label;
      return;
    }
    btn.disabled = false;
    if (btn.dataset.label) btn.innerHTML = btn.dataset.label;
  }

  function option(value, label) {
    const item = document.createElement('option');
    item.value = String(value);
    item.textContent = label;
    return item;
  }

  function updateAssignForm() {
    if (!assignExamSelect || !assignClassSelect || !assignExamBtn) return;

    assignExamSelect.replaceChildren();
    assignClassSelect.replaceChildren();
    assignExamSelect.append(option('', 'Exam'));
    assignClassSelect.append(option('', 'Class'));

    exams.forEach((exam) => {
      assignExamSelect.append(option(exam.id, exam.title));
    });

    classes.forEach((classroom) => {
      assignClassSelect.append(option(classroom.id, `${classroom.name} — ${classroom.subject}`));
    });

    assignExamBtn.disabled = !exams.length || !classes.length;
  }

  function filterClasses() {
    const rows = document.querySelectorAll('#classesTableBody .pg-class-row');
    const query = (classSearch?.value ?? '').trim().toLowerCase();
    let visible = 0;

    rows.forEach((row) => {
      const name = row.dataset.name ?? '';
      const subject = row.dataset.subject ?? '';
      const show = !query || name.includes(query) || subject.includes(query);
      row.hidden = !show;
      if (show) visible += 1;
    });

    classNoResults?.classList.toggle('visible', !!query && visible === 0);
    if (classesTable) {
      classesTable.style.display = query && visible === 0 ? 'none' : '';
    }
    if (classVisibleCount) {
      classVisibleCount.textContent = `${visible} class${visible === 1 ? '' : 'es'}`;
    }
  }

  async function handleCopyCode(code) {
    try {
      await copyText(code);
      dialog()?.toast('Join code copied to clipboard.', 'success');
    } catch (_) {
      await dialog()?.alert({
        tone: 'info',
        title: 'Join code',
        message: `Share this code with students:<br><br><strong style="font-family:monospace;letter-spacing:0.12em;">${escapeHtml(code)}</strong>`,
        confirmLabel: 'Got it',
      });
    }
  }

  async function handleDeleteClass(id) {
    const classroom = classes.find((item) => item.id === id);
    const confirmed = await dialog()?.confirm({
      title: 'Delete class?',
      message: `This will permanently delete <strong>${escapeHtml(classroom?.name || 'this class')}</strong> and remove all student enrollments.`,
      confirmLabel: 'Delete class',
      cancelLabel: 'Keep class',
      danger: true,
      tone: 'danger',
    });
    if (!confirmed) return;

    try {
      await ExamGuardApi.deleteClass(id);
      dialog()?.toast('Class deleted.', 'success');
      await loadClasses();
    } catch (error) {
      await dialog()?.alert({
        type: 'error',
        title: 'Unable to delete class',
        message: error.message || 'This class could not be deleted. Please try again.',
      });
    }
  }

  function renderTable() {
    if (!tableBody) return;

    tableBody.replaceChildren();

    if (!classes.length) {
      const row = document.createElement('tr');
      row.className = 'pg-classes-empty-row';
      row.innerHTML = `
        <td colspan="5">No classes yet. Use Create above to add one.</td>
      `;
      tableBody.append(row);
      if (classVisibleCount) classVisibleCount.textContent = '0 classes';
      if (classesTable) classesTable.style.display = '';
      classNoResults?.classList.remove('visible');
      return;
    }

    classes.forEach((classroom, index) => {
      const row = document.createElement('tr');
      row.className = 'pg-class-row';
      row.dataset.name = classroom.name.toLowerCase();
      row.dataset.subject = classroom.subject.toLowerCase();
      row.dataset.sortIndex = String(index);

      const studentCount = classroom.students?.length ?? 0;
      const examCount = classroom.exams?.length ?? 0;
      const examTitles = (classroom.exams ?? []).map((exam) => exam.title).join(', ') || 'None';

      row.innerHTML = `
        <td class="exam-col">
          <div class="exam-cell">
            <div>
              <span class="pg-exam-cell-title">${escapeHtml(classroom.name)}</span>
              <div class="pg-class-meta">${escapeHtml(classroom.subject)}</div>
            </div>
          </div>
        </td>
        <td>
          <span class="pg-class-code">${escapeHtml(classroom.code)}</span>
        </td>
        <td>${studentCount}</td>
        <td>
          <span class="pg-class-meta" title="${escapeHtml(examTitles)}">${examCount} assigned</span>
        </td>
        <td>
          <div class="pg-class-actions">
            <button type="button" class="pg-classes-btn pg-classes-btn-secondary pg-class-copy" data-code="${escapeHtml(classroom.code)}" title="Copy join code">
              <i class="ti ti-copy"></i>
            </button>
            <button type="button" class="pg-classes-btn pg-classes-btn-danger pg-class-delete" data-id="${classroom.id}">
              <i class="ti ti-trash"></i>
            </button>
          </div>
        </td>
      `;

      row.querySelector('.pg-class-copy')?.addEventListener('click', (e) => {
        handleCopyCode(e.currentTarget.dataset.code);
      });

      row.querySelector('.pg-class-delete')?.addEventListener('click', (e) => {
        handleDeleteClass(Number(e.currentTarget.dataset.id));
      });

      tableBody.append(row);
    });

    filterClasses();
  }

  function showState() {
    setVisible(loadingEl, false);
    setVisible(contentEl, true);
  }

  async function loadClasses() {
    setVisible(loadingEl, true);
    setVisible(contentEl, false);

    try {
      const [classResult, examResult] = await Promise.all([
        ExamGuardApi.professorClasses(),
        ExamGuardApi.exams(),
      ]);

      classes = classResult.classes ?? [];
      exams = examResult.exams ?? [];
      updateAssignForm();
      renderTable();
      showState();
    } catch (error) {
      setVisible(loadingEl, false);
      setVisible(contentEl, true);
      if (tableBody) {
        tableBody.replaceChildren();
        const row = document.createElement('tr');
        row.className = 'pg-classes-empty-row';
        row.innerHTML = `<td colspan="5">${escapeHtml(error.message || 'Unable to load classes.')}</td>`;
        tableBody.append(row);
      }
      await dialog()?.alert({
        type: 'error',
        title: 'Unable to load classes',
        message: error.message || 'Please refresh and try again.',
      });
    }
  }

  function bindEvents() {
    createClassForm?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const name = classNameInput?.value.trim() ?? '';
      const subject = classSubjectInput?.value.trim() ?? '';

      if (!name || !subject) {
        await dialog()?.alert({
          type: 'info',
          title: 'Missing details',
          message: 'Enter both a class name and subject before creating a class.',
          confirmLabel: 'OK',
        });
        return;
      }

      setButtonLoading(createClassBtn, true, '<i class="ti ti-loader-2"></i>');

      try {
        const { classroom } = await ExamGuardApi.createClass(name, subject);
        if (classNameInput) classNameInput.value = '';
        if (classSubjectInput) classSubjectInput.value = '';
        await loadClasses();
        await dialog()?.alert({
          tone: 'success',
          title: 'Class created',
          message: `Share this join code with students:<br><br><strong style="font-family:monospace;letter-spacing:0.12em;">${escapeHtml(classroom.code)}</strong>`,
          confirmLabel: 'Done',
        });
      } catch (error) {
        await dialog()?.alert({
          type: 'error',
          title: 'Unable to create class',
          message: error.message || 'This class could not be created. Please try again.',
        });
      } finally {
        setButtonLoading(createClassBtn, false, '<i class="ti ti-plus"></i>');
      }
    });

    assignExamForm?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const examId = Number(assignExamSelect?.value);
      const classId = Number(assignClassSelect?.value);

      if (!examId || !classId) {
        await dialog()?.alert({
          type: 'info',
          title: 'Select exam and class',
          message: 'Choose both an exam and a class before assigning.',
          confirmLabel: 'OK',
        });
        return;
      }

      setButtonLoading(assignExamSubmitBtn, true, '<i class="ti ti-loader-2"></i>');

      try {
        await ExamGuardApi.assignExam(examId, classId);
        await loadClasses();
        if (assignExamForm) assignExamForm.reset();
        updateAssignForm();
        dialog()?.toast('Exam assigned to class.', 'success');
      } catch (error) {
        await dialog()?.alert({
          type: 'error',
          title: 'Unable to assign exam',
          message: error.message || 'This exam could not be assigned. Please try again.',
        });
      } finally {
        setButtonLoading(assignExamSubmitBtn, false, '<i class="ti ti-link"></i>');
      }
    });

    classSearch?.addEventListener('input', filterClasses);
  }

  function init() {
    if (!document.getElementById('classesView')) return;

    if (!initialized) {
      bindEvents();
      initialized = true;
    }

    loadClasses();
  }

  window.ExamGuardClasses = { init, refresh: loadClasses };
})();
