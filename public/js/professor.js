/* professor.js — nav-driven professor workspace */
(function () {
  'use strict';

  const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  const navLinks         = document.querySelectorAll('.pg-nav-link[data-view]');
  const views            = document.querySelectorAll('.pg-view[data-view]');
  const pgBody           = document.querySelector('.pg-body');
  const examsListView    = document.getElementById('examsListView');
  const examsDetailView  = document.getElementById('examsDetailView');
  const detailPanels     = document.querySelectorAll('.pg-detail');

  const examDateFrom     = document.getElementById('examDateFrom');
  const examDateTo       = document.getElementById('examDateTo');
  const examSearch       = document.getElementById('examSearch');
  const examStatusFilter = document.getElementById('examStatusFilter');
  const examSort         = document.getElementById('examSort');
  const examFilterClear  = document.getElementById('examFilterClear');
  const examNoResults    = document.getElementById('examNoResults');
  const examsTable       = document.getElementById('examsTable');

  const violationSearch      = document.getElementById('violationSearch');
  const violationExamFilter  = document.getElementById('violationExamFilter');
  const violationFilterClear = document.getElementById('violationFilterClear');

  const viewTitles = {
    workspace: 'Workspace',
    exams: 'Exams',
    'create-exam': 'Create exam',
    classes: 'Classes',
    'overall-results': 'Overall results',
    'live-sessions': 'Proctoring',
    violations: 'Students with violations',
    settings: 'Settings',
    profile: 'Profile',
    help: 'Help & Support',
  };

  let createExamInitialized = false;

  let currentView = 'exams';
  let selectedExamId = null;

  function filterExamTable(config) {
    const rows = document.querySelectorAll(config.rowSelector);
    const tbody = document.querySelector(config.tbodySelector);
    if (!rows.length || !tbody) return;

    const query = (config.searchEl?.value ?? '').trim().toLowerCase();
    const from = config.dateFromEl?.value ?? '';
    const to = config.dateToEl?.value ?? '';
    const status = config.statusEl?.value ?? '';
    const sortBy = config.sortEl?.value ?? 'updated-desc';
    const hasFilter = query || from || to || status || (sortBy && sortBy !== 'updated-desc');
    let visible = 0;

    rows.forEach((row) => {
      const title = (row.dataset.title ?? '').toLowerCase();
      const updated = row.dataset.updated ?? '';
      const rowStatus = row.dataset.status ?? '';
      const matchSearch = !query || title.includes(query);
      const matchFrom = !from || updated >= from;
      const matchTo = !to || updated <= to;
      const matchStatus = !status || rowStatus === status;
      const show = matchSearch && matchFrom && matchTo && matchStatus;

      row.hidden = !show;
      if (show) visible += 1;
    });

    const compareRows = (a, b) => {
      if (a.hidden !== b.hidden) return a.hidden ? 1 : -1;

      switch (sortBy) {
        case 'name-asc':
          return (a.dataset.title ?? '').localeCompare(b.dataset.title ?? '');
        case 'name-desc':
          return (b.dataset.title ?? '').localeCompare(a.dataset.title ?? '');
        case 'created-asc':
          return (a.dataset.created ?? '').localeCompare(b.dataset.created ?? '');
        case 'created-desc':
          return (b.dataset.created ?? '').localeCompare(a.dataset.created ?? '');
        case 'status-asc':
          return (a.dataset.status ?? '').localeCompare(b.dataset.status ?? '');
        case 'status-desc':
          return (b.dataset.status ?? '').localeCompare(a.dataset.status ?? '');
        case 'updated-asc':
          return (a.dataset.updated ?? '').localeCompare(b.dataset.updated ?? '');
        default:
          return (b.dataset.updated ?? '').localeCompare(a.dataset.updated ?? '');
      }
    };

    const ordered = [...rows].sort(compareRows);

    ordered.forEach((row) => tbody.appendChild(row));

    config.clearEl?.classList.toggle('visible', !!hasFilter);
    config.noResultsEl?.classList.toggle('visible', visible === 0 && !!hasFilter);
    if (config.tableEl) {
      config.tableEl.style.display = visible === 0 && !!hasFilter ? 'none' : '';
    }
  }

  function filterExams() {
    filterExamTable({
      rowSelector: '#examsTableBody .pg-exam-row',
      tbodySelector: '#examsTableBody',
      searchEl: examSearch,
      dateFromEl: examDateFrom,
      dateToEl: examDateTo,
      statusEl: examStatusFilter,
      sortEl: examSort,
      clearEl: examFilterClear,
      noResultsEl: examNoResults,
      tableEl: examsTable,
    });
  }

  function filterViolations() {
    window.ExamGuardProctoring?.applyFilters?.();
  }

  function clearViolationFilters() {
    window.ExamGuardProctoring?.clearFilters?.();
  }

  function clearExamFilters() {
    if (examDateFrom) examDateFrom.value = '';
    if (examDateTo) examDateTo.value = '';
    if (examSearch) examSearch.value = '';
    if (examStatusFilter) examStatusFilter.value = '';
    if (examSort) examSort.value = 'updated-desc';
    filterExams();
  }

  examDateFrom?.addEventListener('change', filterExams);
  examDateTo?.addEventListener('change', filterExams);
  examSearch?.addEventListener('input', filterExams);
  examStatusFilter?.addEventListener('change', filterExams);
  examSort?.addEventListener('change', filterExams);
  examFilterClear?.addEventListener('click', clearExamFilters);

  violationSearch?.addEventListener('input', filterViolations);
  violationExamFilter?.addEventListener('change', filterViolations);
  violationFilterClear?.addEventListener('click', clearViolationFilters);

  /* ── Dropdowns ──────────────────────────────────────────────── */
  function closeDropdowns() {
    document.querySelectorAll('.pg-dropdown.open, .pg-notify-panel.open').forEach((el) => {
      el.classList.remove('open');
    });
  }

  const profileToggle = document.getElementById('topbarProfileToggle');
  const profileDropdown = document.getElementById('topbarProfileDropdown');
  if (profileToggle && profileDropdown) {
    profileToggle.addEventListener('click', (e) => {
      if (e.target.closest('.pg-dropdown')) return;
      e.stopPropagation();
      const open = profileDropdown.classList.contains('open');
      closeDropdowns();
      if (!open) profileDropdown.classList.add('open');
    });
  }

  document.addEventListener('click', () => closeDropdowns());

  document.querySelectorAll('[data-logout]').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeDropdowns();
      try {
        await ExamGuardApi.logout();
      } catch (_) {}
      try {
        sessionStorage.removeItem('examguard:lastRoute');
        sessionStorage.removeItem('examguard:createExamWork');
      } catch (_) {}
      window.location.replace('/login');
    });
  });

  function initCreateExamView(options = {}) {
    if (!window.ExamGuardCreateExam) return;
    const root = document.getElementById('createExamRoot');
    if (!root) return;

    const examId = options.examId ?? null;
    const phase = options.phase ?? null;
    const restore = options.restore === true;

    if (!createExamInitialized) {
      createExamInitialized = true;
      ExamGuardCreateExam.init({
        root,
        embedded: true,
        onSaved: () => { window.location.assign('/professor?view=exams'); },
      });
    }

    if (examId && ExamGuardCreateExam.loadForEdit) {
      ExamGuardCreateExam.loadForEdit(examId);
      return;
    }

    if (restore && ExamGuardCreateExam.restoreWorkInProgress) {
      const restored = ExamGuardCreateExam.restoreWorkInProgress(phase);
      if (restored) return;
    }

    if (ExamGuardCreateExam.resetForm) {
      ExamGuardCreateExam.resetForm();
    }
  }

  const WORKSPACE_VIEWS = ['exams', 'create-exam', 'classes'];
  const STORAGE_VIEW = 'professorView';
  const STORAGE_CREATE_ID = 'professorCreateExamId';
  const STORAGE_DETAIL_ID = 'professorExamDetailId';
  const STORAGE_CREATE_PHASE = 'professorCreatePhase';
  const CREATE_EXAM_PHASES = new Set(['setup', 'builder', 'success']);
  const VALID_VIEWS = new Set([
    'workspace', 'exams', 'create-exam', 'classes',
    'overall-results', 'live-sessions', 'violations', 'settings', 'profile', 'help',
  ]);

  function resolveView(view) {
    return view === 'workspace' ? 'exams' : view;
  }

  function syncProfessorUrl(view, options = {}) {
    if (options.skipUrlSync) return;

    const resolvedView = resolveView(view);
    const params = new URLSearchParams();
    params.set('view', resolvedView);

    if (options.examId && resolvedView === 'create-exam') {
      params.set('id', String(options.examId));
    }

    if (options.phase && resolvedView === 'create-exam' && CREATE_EXAM_PHASES.has(options.phase)) {
      params.set('phase', options.phase);
    }

    if (options.detailExamId && resolvedView === 'exams') {
      params.set('exam', String(options.detailExamId));
    }

    const nextUrl = `/professor?${params.toString()}`;
    const state = {
      view: resolvedView,
      examId: options.examId ?? null,
      detailExamId: options.detailExamId ?? null,
    };

    if (options.pushHistory) {
      window.history.pushState(state, '', nextUrl);
    } else {
      window.history.replaceState(state, '', nextUrl);
    }

    sessionStorage.setItem(STORAGE_VIEW, resolvedView);
    if (options.examId) {
      sessionStorage.setItem(STORAGE_CREATE_ID, String(options.examId));
    } else {
      sessionStorage.removeItem(STORAGE_CREATE_ID);
    }
    if (options.detailExamId) {
      sessionStorage.setItem(STORAGE_DETAIL_ID, String(options.detailExamId));
    } else {
      sessionStorage.removeItem(STORAGE_DETAIL_ID);
    }

    if (options.phase && resolvedView === 'create-exam') {
      sessionStorage.setItem(STORAGE_CREATE_PHASE, options.phase);
    } else if (resolvedView !== 'create-exam') {
      sessionStorage.removeItem(STORAGE_CREATE_PHASE);
    }

    window.ExamGuardRoute?.save(nextUrl);
  }

  function syncCreateExamUrl(phase, examId = null) {
    syncProfessorUrl('create-exam', {
      examId: examId || null,
      phase: phase || null,
    });
  }

  function readBootState() {
    const params = new URLSearchParams(window.location.search);
    let view = params.get('view');
    let createExamId = params.get('id');
    let detailExamId = params.get('exam');
    let createPhase = params.get('phase');

    if (!view || !VALID_VIEWS.has(view)) {
      const lastRoute = window.ExamGuardRoute?.last?.();
      if (lastRoute?.startsWith('/professor')) {
        try {
          const lastParams = new URL(lastRoute, window.location.origin).searchParams;
          view = lastParams.get('view') || view;
          if (!createExamId) createExamId = lastParams.get('id');
          if (!detailExamId) detailExamId = lastParams.get('exam');
          if (!createPhase) createPhase = lastParams.get('phase');
        } catch (_) {}
      }

      if (!view || !VALID_VIEWS.has(view)) {
        view = sessionStorage.getItem(STORAGE_VIEW) || 'exams';
      }
      if (!createExamId) createExamId = sessionStorage.getItem(STORAGE_CREATE_ID);
      if (!detailExamId) detailExamId = sessionStorage.getItem(STORAGE_DETAIL_ID);
      if (!createPhase) createPhase = sessionStorage.getItem(STORAGE_CREATE_PHASE);
    }

    if (!VALID_VIEWS.has(view)) {
      view = 'exams';
    }

    if (createPhase && !CREATE_EXAM_PHASES.has(createPhase)) {
      createPhase = null;
    }

    if (view === 'create-exam' && !createPhase) {
      createPhase = sessionStorage.getItem(STORAGE_CREATE_PHASE);
    }

    if (createPhase && !CREATE_EXAM_PHASES.has(createPhase)) {
      createPhase = null;
    }

    return {
      view,
      createExamId: createExamId ? Number(createExamId) : null,
      detailExamId: detailExamId ? Number(detailExamId) : null,
      createPhase: createPhase || null,
    };
  }

  function restoreExamDetail(detailExamId, options = {}) {
    if (!detailExamId) return;
    const row = document.querySelector(`#examsTableBody .pg-exam-row[data-exam-id="${detailExamId}"]`);
    if (row) {
      showExamDetail(row, options);
      return;
    }
    showExamsList(false, options);
  }

  function restoreProfessorWorkspace() {
    const boot = readBootState();

    if (boot.view === 'create-exam' && boot.createExamId) {
      const row = document.querySelector(`#examsTableBody .pg-exam-row[data-exam-id="${boot.createExamId}"]`);
      if (row?.dataset.status !== 'draft') {
        syncProfessorUrl('exams');
        switchView('exams');
        if (boot.detailExamId) restoreExamDetail(boot.detailExamId);
        return;
      }
    }

    switchView(boot.view, {
      examId: boot.createExamId,
      createPhase: boot.createPhase,
      restoreCreateExam: boot.view === 'create-exam',
      keepExamSelection: Boolean(boot.createExamId),
      keepExamDetail: Boolean(boot.detailExamId),
      detailExamId: boot.detailExamId,
    });

    if (boot.detailExamId && resolveView(boot.view) === 'exams') {
      restoreExamDetail(boot.detailExamId);
    }
  }

  function setNavActive(view) {
    navLinks.forEach((link) => {
      const linkView = link.dataset.view;
      if (linkView === 'workspace') {
        link.classList.toggle('active', WORKSPACE_VIEWS.includes(view));
      } else {
        link.classList.toggle('active', linkView === view);
      }
    });
  }

  function animateCreateExamView() {
    const root = document.getElementById('createExamRoot');
    if (!root) return;
    root.classList.remove('pg-create-enter');
    void root.offsetWidth;
    root.classList.add('pg-create-enter');
  }

  function animatePanel(panel) {
    if (!panel) return;
    panel.classList.remove('pg-view-enter');
    void panel.offsetWidth;
    panel.classList.add('pg-view-enter');
  }

  /* ── Switch main nav view ───────────────────────────────────── */
  function switchView(view, options = {}) {
    const resolvedView = resolveView(view);
    currentView = resolvedView;
    closeDropdowns();
    if (!options.keepExamSelection) {
      selectedExamId = null;
    }

    setNavActive(resolvedView);

    views.forEach((panel) => {
      panel.classList.remove('active', 'pg-view-enter');
    });
    const target = document.querySelector(`.pg-view[data-view="${resolvedView}"]`);
    target?.classList.add('active');
    animatePanel(target);

    pgBody?.classList.toggle('pg-quiz-maker-mode', resolvedView === 'create-exam');

    if (resolvedView === 'exams' && !options.keepExamDetail) {
      showExamsList(false, { skipUrlSync: true });
    }

    if (resolvedView === 'create-exam') {
      initCreateExamView({
        examId: options.examId ?? null,
        phase: options.createPhase ?? null,
        restore: options.restoreCreateExam === true,
      });
      const routeState = window.ExamGuardCreateExam?.getRouteState?.();
      if (routeState) {
        options.examId = routeState.examId ?? options.examId;
        options.createPhase = routeState.phase ?? options.createPhase;
      }
      animateCreateExamView();
    }

    if (resolvedView === 'classes') {
      window.ExamGuardClasses?.init();
    }

    if (resolvedView === 'profile') {
      window.ExamGuardSettings?.initProfile();
    }

    if (resolvedView === 'settings') {
      window.ExamGuardSettings?.init();
    }

    if (resolvedView === 'live-sessions') {
      window.ExamGuardLiveSessions?.start();
    } else {
      window.ExamGuardLiveSessions?.stop();
    }

    if (resolvedView === 'violations') {
      window.ExamGuardProctoring?.init();
    }

    if (!options.skipUrlSync) {
      syncProfessorUrl(resolvedView, {
        examId: options.examId ?? null,
        phase: options.createPhase ?? null,
        detailExamId: options.keepExamDetail ? (options.detailExamId ?? null) : null,
        pushHistory: options.pushHistory === true,
      });
    }
  }

  navLinks.forEach((link) => {
    link.addEventListener('click', () => {
      switchView(link.dataset.view, { pushHistory: true });
    });
  });

  document.querySelectorAll('[data-switch-view]').forEach((el) => {
    el.addEventListener('click', () => {
      const examId = el.dataset.examId ? Number(el.dataset.examId) : null;
      if (examId) {
        const row = document.querySelector(`#examsTableBody .pg-exam-row[data-exam-id="${examId}"]`);
        if (row?.dataset.status !== 'draft') {
          window.ExamGuardDialog?.alert({
            type: 'info',
            title: 'Cannot edit exam',
            message: 'Only draft exams can be edited. Duplicate this exam to make changes.',
            confirmLabel: 'OK',
          });
          return;
        }
      }
      switchView(el.dataset.switchView, {
        examId,
        keepExamSelection: !!examId,
        pushHistory: true,
      });
    });
  });

  /* ── Exams: list ↔ detail ───────────────────────────────────── */
  function showExamsList(animate = true, options = {}) {
    if (examsListView) examsListView.classList.remove('hidden');
    if (examsDetailView) examsDetailView.classList.add('hidden');
    detailPanels.forEach((p) => p.classList.remove('active'));
    selectedExamId = null;
    if (animate) animatePanel(examsListView);
    if (!options.skipUrlSync) {
      syncProfessorUrl('exams', { pushHistory: options.pushHistory === true });
    }
  }

  function showExamDetail(row, options = {}) {
    if (!row) return;
    window.ExamGuardExams?.closeAllMenus?.();
    const examId = row.dataset.examId;
    selectedExamId = examId;

    if (examsListView) examsListView.classList.add('hidden');
    if (examsDetailView) examsDetailView.classList.remove('hidden');

    detailPanels.forEach((panel) => {
      panel.classList.toggle('active', panel.dataset.examId === examId);
    });
    animatePanel(examsDetailView);

    if (!options.skipUrlSync) {
      syncProfessorUrl('exams', {
        detailExamId: Number(examId),
        pushHistory: options.pushHistory === true,
      });
    }
  }

  const examsTableBody = document.getElementById('examsTableBody');
  if (examsTableBody) {
    examsTableBody.addEventListener('keydown', (e) => {
      const titleBtn = e.target.closest('.pg-exam-title-btn');
      if (!titleBtn) return;
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        const row = titleBtn.closest('.pg-exam-row');
        if (row) showExamDetail(row);
      }
    });
  }

  if (examsDetailView) {
    examsDetailView.addEventListener('click', (e) => {
      if (e.target.closest('.pg-detail-back')) {
        showExamsList(true, { pushHistory: true });
      }
    });
  }

  window.addEventListener('popstate', () => {
    const boot = readBootState();
    switchView(boot.view, {
      examId: boot.createExamId,
      createPhase: boot.createPhase,
      restoreCreateExam: boot.view === 'create-exam',
      keepExamSelection: Boolean(boot.createExamId),
      keepExamDetail: Boolean(boot.detailExamId),
      detailExamId: boot.detailExamId,
      skipUrlSync: true,
    });

    if (boot.detailExamId && resolveView(boot.view) === 'exams') {
      restoreExamDetail(boot.detailExamId, { skipUrlSync: true });
    }
  });

  restoreProfessorWorkspace();

  window.ExamGuardProfessor = window.ExamGuardProfessor || {};
  window.ExamGuardProfessor.switchView = switchView;
  window.ExamGuardProfessor.syncCreateExamUrl = syncCreateExamUrl;
  window.ExamGuardProfessor.showExamDetail = showExamDetail;
  window.ExamGuardProfessor.filterExams = filterExams;
  window.ExamGuardProfessor.showExamsList = showExamsList;

})();
