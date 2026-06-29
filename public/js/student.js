(function () {
  'use strict';

  const CLASS_COLORS = ['#3b82f6', '#8b5cf6', '#f59e0b', '#10b981', '#ef4444', '#ec4899'];
  const CLASS_BANNERS = ['#1d4ed8', '#6d28d9', '#b45309', '#047857', '#b91c1c', '#be185d'];
  const VALID_VIEWS = new Set(['home', 'calendar', 'exam-room', 'results', 'certificates', 'settings', 'class']);
  const STORAGE_SYSTEM_CHECK = 'examguard:systemCheckOk';
  const STORAGE_EXAM_KEY = 'examguard:examKeyUsed';
  const STORAGE_PENDING_EXAM = 'examguard:pendingExam';

  const state = {
    user: null,
    classes: [],
    exams: [],
    currentView: 'home',
    selectedClassId: null,
    resultFilter: 'all',
    liveBannerDismissed: false,
    liveExamId: null,
    pendingExamKey: null,
    examOpenTimer: null,
    calendarMonth: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
    calendarWeekStart: null,
    calendarSelectedDay: null,
    calendarViewMode: 'month',
    calendarClassFilter: '',
  };

  const els = {
    sidebar: document.getElementById('sdSidebar'),
    profileToggle: document.getElementById('sdProfileToggle'),
    avatarBtn: document.getElementById('sdAvatarBtn'),
    profileMenu: document.getElementById('sdProfileMenu'),
    notifyToggle: document.getElementById('sdNotifyToggle'),
    notifyDot: document.getElementById('sdNotifyDot'),
    classList: document.getElementById('sdClassList'),
    classCards: document.getElementById('sdClassCards'),
    stream: document.getElementById('sdStream'),
    upcoming: document.getElementById('sdUpcoming'),
    todo: document.getElementById('sdTodo'),
    calendarGrid: document.getElementById('sdCalendarGrid'),
    calendarDayDetail: document.getElementById('sdCalendarDayDetail'),
    calendarMonthLabel: document.getElementById('sdCalMonthLabel'),
    calPrev: document.getElementById('sdCalPrev'),
    calNext: document.getElementById('sdCalNext'),
    calToday: document.getElementById('sdCalToday'),
    calClassFilter: document.getElementById('sdCalClassFilter'),
    calWeekdays: document.getElementById('sdCalWeekdays'),
    greeting: document.getElementById('sdGreeting'),
    greetingSub: document.getElementById('sdGreetingSub'),
    liveBanner: document.getElementById('sdLiveBanner'),
    liveExamName: document.getElementById('sdLiveExamName'),
    liveEnter: document.getElementById('sdLiveEnter'),
    liveDismiss: document.getElementById('sdLiveDismiss'),
    joinModal: document.getElementById('sdJoinModal'),
    joinForm: document.getElementById('sdJoinForm'),
    joinName: document.getElementById('sdJoinName'),
    joinCode: document.getElementById('sdJoinCode'),
    joinSubmit: document.getElementById('sdJoinSubmit'),
    examKeyForm: document.getElementById('sdExamKeyForm'),
    examKeyInput: document.getElementById('sdExamKeyInput'),
    keyCount: document.getElementById('sdKeyCount'),
    enterExamBtn: document.getElementById('sdEnterExamBtn'),
    examKeyStatus: document.getElementById('sdExamKeyStatus'),
    examKeyFormWrap: document.getElementById('sdExamKeyFormWrap'),
    readiness: document.getElementById('sdReadiness'),
    resultsList: document.getElementById('sdResultsList'),
    resultStats: document.getElementById('sdResultStats'),
    resultModal: document.getElementById('sdResultModal'),
    resultBody: document.getElementById('sdResultBody'),
    resultTitle: document.getElementById('sdResultTitle'),
    certModal: document.getElementById('sdCertModal'),
    certGrid: document.getElementById('sdCertGrid'),
    classStream: document.getElementById('sdClassStream'),
    classInfo: document.getElementById('sdClassInfo'),
    classBanner: document.getElementById('sdClassBanner'),
    classBannerTitle: document.getElementById('sdClassBannerTitle'),
    classBannerSub: document.getElementById('sdClassBannerSub'),
    classBannerAvatar: document.getElementById('sdClassBannerAvatar'),
    classQuickKey: document.getElementById('sdClassQuickKey'),
    classQuickKeyBtn: document.getElementById('sdClassQuickKeyBtn'),
  };

  function esc(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function initials(name) {
    return String(name || 'S').trim().split(/\s+/).map((p) => p[0]).join('').slice(0, 2).toUpperCase();
  }

  function classColor(index) {
    return CLASS_COLORS[index % CLASS_COLORS.length];
  }

  function classBannerColor(index) {
    return CLASS_BANNERS[index % CLASS_BANNERS.length];
  }

  function greeting() {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
  }

  function formatWhen(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, {
      month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
    });
  }

  function timeAgo(iso) {
    if (!iso) return 'Recently';
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    return `${Math.floor(hrs / 24)}d ago`;
  }

  function formatDueShort(iso) {
    if (!iso) return 'soon';
    const d = new Date(iso);
    const now = new Date();
    if (d.toDateString() === now.toDateString()) return 'today';
    return d.toLocaleString(undefined, { month: 'short', day: 'numeric' });
  }

  function formatViewedShort(iso) {
    if (!iso) return 'Recently';
    return new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric' });
  }

  function streamSubtitle(event) {
    const exam = event.exam;
    const parts = [];
    if (exam.timeLimit) parts.push(`${exam.timeLimit} min`);

    if (event.type === 'live') {
      parts.push('Enter with your exam key');
      parts.push(`Due ${formatDueShort(exam.closesAt || exam.opensAt)}`);
    } else if (event.type === 'scheduled') {
      parts.push('Scheduled');
      parts.push(exam.opensAt ? `Opens ${formatDueShort(exam.opensAt)}` : 'Posted recently');
    } else if (event.type === 'result') {
      const attempt = exam.attempt;
      parts.push(`Score: ${attempt?.score ?? 0}/${attempt?.total ?? 100}`);
      parts.push(isPassed(exam) ? 'Passed' : 'Failed');
      parts.push(`Viewed ${formatViewedShort(attempt?.submittedAt)}`);
    } else {
      parts.push('Announced');
      parts.push(timeAgo(event.time));
    }

    return parts.join(' · ');
  }

  function examsForClass(classId) {
    const id = Number(classId);
    return state.exams.filter((exam) =>
      (exam.classIds || (exam.classId != null ? [exam.classId] : []))
        .some((cid) => Number(cid) === id)
    );
  }

  function classForExam(exam, preferredClassId = null) {
    const ids = exam.classIds || (exam.classId != null ? [exam.classId] : []);
    if (preferredClassId && ids.some((cid) => Number(cid) === Number(preferredClassId))) {
      return state.classes.find((c) => Number(c.id) === Number(preferredClassId));
    }
    const classId = ids[0];
    return state.classes.find((c) => Number(c.id) === Number(classId));
  }

  function professorLine(cls) {
    return cls.subject ? String(cls.subject) : 'Instructor';
  }

  function professorInitials(cls) {
    return initials(professorLine(cls));
  }

  function scorePercent(exam) {
    const a = exam.attempt;
    if (!a || !a.total) return 0;
    return Math.round((a.score / a.total) * 100);
  }

  function isPassed(exam) {
    return scorePercent(exam) >= 70;
  }

  function hasSubmittedAttempt(exam) {
    const attempt = exam?.attempt;
    return Boolean(attempt?.submittedAt || attempt?.status === 'submitted');
  }

  function attemptDurationLabel(exam) {
    const attempt = exam?.attempt;
    if (!attempt?.startedAt || !attempt?.submittedAt) return null;
    const ms = new Date(attempt.submittedAt).getTime() - new Date(attempt.startedAt).getTime();
    if (ms <= 0) return null;
    const mins = Math.round(ms / 60000);
    if (mins < 60) return `${mins} min`;
    const hrs = Math.floor(mins / 60);
    const rem = mins % 60;
    return rem ? `${hrs} hr ${rem} min` : `${hrs} hr`;
  }

  function examClassLabel(exam) {
    const cls = classForExam(exam);
    return exam.className || cls?.name || (exam.keyAccess ? 'Exam key access' : 'Class exam');
  }

  function examClassColor(exam, fallbackIndex = 0) {
    const cls = classForExam(exam);
    if (!cls) return classColor(fallbackIndex);
    const idx = state.classes.findIndex((c) => Number(c.id) === Number(cls.id));
    return classColor(idx >= 0 ? idx : fallbackIndex);
  }

  function isFutureOpen(exam) {
    return Boolean(exam?.opensAt && new Date(exam.opensAt).getTime() > Date.now());
  }

  function isLive(exam) {
    if (exam.attempt?.submittedAt) return false;
    if (isFutureOpen(exam)) return false;
    if (exam.attempt && !exam.attempt.submittedAt) return true;
    return exam.status === 'active';
  }

  function isUpcoming(exam) {
    if (exam.attempt?.submittedAt) return false;
    if (isLive(exam)) return true;
    if (exam.status === 'scheduled') return true;
    if (isFutureOpen(exam)) return true;
    return exam.status === 'active' && !exam.attempt;
  }

  function normalizeKeyExam(exam) {
    return {
      id: exam.id,
      title: exam.title,
      timeLimit: exam.timeLimit || 0,
      warningLimit: exam.warningLimit ?? 3,
      status: exam.status,
      opensAt: exam.opensAt || null,
      closesAt: exam.closesAt || null,
      questionCount: exam.questionCount || 0,
      classIds: exam.classIds || (exam.classId ? [exam.classId] : []),
      classId: exam.classId ?? exam.classIds?.[0] ?? null,
      keyAccess: true,
      attempt: exam.attempt || null,
    };
  }

  function mergeExamIntoState(exam) {
    const normalized = normalizeKeyExam(exam);
    const idx = state.exams.findIndex((e) => Number(e.id) === Number(normalized.id));
    if (idx >= 0) {
      state.exams[idx] = { ...state.exams[idx], ...normalized };
    } else {
      state.exams.push(normalized);
    }
  }

  function savePendingExamKey(exam) {
    if (exam.status !== 'scheduled' && !isFutureOpen(exam)) return;
    state.pendingExamKey = normalizeKeyExam(exam);
    try {
      sessionStorage.setItem(STORAGE_PENDING_EXAM, JSON.stringify(state.pendingExamKey));
    } catch (_) {}
  }

  function restorePendingExamKey() {
    try {
      const raw = sessionStorage.getItem(STORAGE_PENDING_EXAM);
      if (!raw) return;
      const exam = JSON.parse(raw);
      if (!exam?.id) return;
      state.pendingExamKey = exam;
      mergeExamIntoState(exam);
    } catch (_) {}
  }

  function clearPendingExamKey() {
    state.pendingExamKey = null;
    try {
      sessionStorage.removeItem(STORAGE_PENDING_EXAM);
    } catch (_) {}
  }

  function examById(examId) {
    return state.exams.find((e) => Number(e.id) === Number(examId))
      || (Number(state.pendingExamKey?.id) === Number(examId) ? state.pendingExamKey : null);
  }

  function upcomingExams() {
    const seen = new Set();
    const items = [];

    state.exams.filter(isUpcoming).forEach((exam) => {
      seen.add(Number(exam.id));
      items.push(exam);
    });

    if (state.pendingExamKey && !seen.has(Number(state.pendingExamKey.id))) {
      const pending = state.pendingExamKey;
      if (isUpcoming(pending)) items.push(pending);
    }

    return items;
  }

  function sortUpcoming(exams) {
    return [...exams].sort((a, b) => {
      const liveA = isLive(a);
      const liveB = isLive(b);
      if (liveA && !liveB) return -1;
      if (!liveA && liveB) return 1;
      const ta = a.opensAt ? new Date(a.opensAt).getTime() : Infinity;
      const tb = b.opensAt ? new Date(b.opensAt).getTime() : Infinity;
      return ta - tb;
    });
  }

  function upcomingWhenLabel(exam) {
    if (isLive(exam)) return 'Live now';
    if (exam.opensAt) return formatWhen(exam.opensAt);
    return 'Available now';
  }

  function hasSystemCheckDone() {
    try {
      return sessionStorage.getItem(STORAGE_SYSTEM_CHECK) === '1';
    } catch (_) {
      return false;
    }
  }

  function markSystemCheckDone() {
    try {
      sessionStorage.setItem(STORAGE_SYSTEM_CHECK, '1');
    } catch (_) {}
  }

  function hasExamKeyActivity() {
    try {
      if (sessionStorage.getItem(STORAGE_EXAM_KEY) === '1') return true;
    } catch (_) {}
    return submittedExams().length > 0 || state.exams.some((e) => isLive(e) || e.attempt);
  }

  function markExamKeyUsed() {
    try {
      sessionStorage.setItem(STORAGE_EXAM_KEY, '1');
    } catch (_) {}
  }

  function openUpcomingExam(examId) {
    const exam = examById(examId);
    if (!exam) return;
    if (isLive(exam)) {
      goToExam(exam.id);
      return;
    }
    const cls = classForExam(exam);
    if (cls) switchView('class', { classId: cls.id });
    else switchView('exam-room');
  }

  function submittedExams() {
    return state.exams
      .filter(hasSubmittedAttempt)
      .sort((a, b) => new Date(b.attempt.submittedAt).getTime() - new Date(a.attempt.submittedAt).getTime());
  }

  function passedExams() {
    return submittedExams().filter(isPassed);
  }

  function filteredExams() {
    return state.exams;
  }

  function switchView(view, options = {}) {
    if (view === 'class' && options.classId) {
      state.selectedClassId = Number(options.classId);
    } else if (view !== 'class') {
      state.selectedClassId = null;
    }
    if (!VALID_VIEWS.has(view)) view = 'home';
    state.currentView = view;

    document.querySelectorAll('.sd-view').forEach((panel) => {
      panel.classList.toggle('active', panel.dataset.sdView === view);
    });
    document.querySelectorAll('.pg-sidebar .pg-nav-link[data-sd-view]').forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.sdView === view && view !== 'class');
    });
    document.querySelectorAll('.sd-class-item').forEach((btn) => {
      btn.classList.toggle('active', view === 'class' && Number(btn.dataset.classId) === state.selectedClassId);
    });

    closeProfileMenu();

    const hash = view === 'class' && state.selectedClassId
      ? `#class-${state.selectedClassId}`
      : `#${view}`;
    if (location.hash !== hash) {
      history.replaceState(null, '', `/student${hash}`);
    }
    window.ExamGuardRoute?.save(`/student${hash}`);

    if (view === 'home') renderHome();
    if (view === 'exam-room') {
      renderExamKeyRoom();
      updateReadiness();
    }
    if (view === 'calendar') {
      if (!state.calendarSelectedDay) state.calendarSelectedDay = dateKey(new Date());
      renderCalendar();
    }
    if (view === 'results') renderResults();
    if (view === 'certificates') renderCertificates();
    if (view === 'class') renderClassStream();
    if (view === 'settings') window.ExamGuardStudentSettings?.init?.();
  }

  function parseHash() {
    const hash = location.hash.replace(/^#/, '') || 'home';
    if (hash.startsWith('class-')) {
      return { view: 'class', classId: Number(hash.slice(6)) };
    }
    return { view: VALID_VIEWS.has(hash) ? hash : 'home' };
  }

  function closeProfileMenu() {
    els.profileMenu?.classList.remove('open');
  }

  function openJoinModal() {
    els.joinModal?.classList.add('open');
    if (state.user) els.joinName.value = state.user.name;
    els.joinCode?.focus();
  }

  function closeJoinModal() {
    els.joinModal?.classList.remove('open');
    if (els.joinCode) els.joinCode.value = '';
    if (els.joinSubmit) els.joinSubmit.disabled = true;
  }

  function clearExamOpenWatcher() {
    if (state.examOpenTimer) {
      clearInterval(state.examOpenTimer);
      state.examOpenTimer = null;
    }
  }

  function formatOpensIn(iso) {
    if (!iso) return 'soon';
    const diff = new Date(iso).getTime() - Date.now();
    if (diff <= 0) return 'now';
    const mins = Math.ceil(diff / 60000);
    if (mins < 60) return `in ${mins} min`;
    const hrs = Math.ceil(mins / 60);
    if (hrs < 24) return `in ${hrs} hr${hrs === 1 ? '' : 's'}`;
    const days = Math.ceil(hrs / 24);
    return `in ${days} day${days === 1 ? '' : 's'}`;
  }

  function setExamKeyLoading(loading) {
    if (!els.enterExamBtn) return;
    els.enterExamBtn.disabled = loading || (els.examKeyInput?.value.length || 0) < 8;
    els.enterExamBtn.textContent = loading ? 'Checking key…' : 'Enter exam room';
    els.examKeyInput?.toggleAttribute('readonly', loading);
  }

  function showExamKeyError(message) {
    if (!els.examKeyStatus) return;
    els.examKeyStatus.hidden = false;
    els.examKeyStatus.className = 'sd-key-status sd-key-status-error';
    els.examKeyStatus.innerHTML = `<i class="ti ti-alert-circle"></i><span>${esc(message)}</span>`;
  }

  function showExamKeyScheduled(exam) {
    const normalized = normalizeKeyExam(exam);
    state.pendingExamKey = normalized;
    savePendingExamKey(normalized);
    mergeExamIntoState(normalized);
    refreshHomePanels();
    if (els.examKeyFormWrap) els.examKeyFormWrap.hidden = true;
    if (!els.examKeyStatus) return;

    const opensAt = normalized.opensAt;
    const ready = exam.available || normalized.available || (opensAt && new Date(opensAt).getTime() <= Date.now());
    const when = opensAt ? formatWhen(opensAt) : 'soon';
    const opensIn = opensAt ? formatOpensIn(opensAt) : '';

    els.examKeyStatus.hidden = false;
    els.examKeyStatus.className = `sd-key-status sd-key-status-${ready ? 'ready' : 'scheduled'}`;
    els.examKeyStatus.innerHTML = `
      <div class="sd-key-status-icon"><i class="ti ti-${ready ? 'circle-check' : 'calendar-time'}"></i></div>
      <div class="sd-key-status-body">
        <strong>${ready ? 'Exam is open' : 'Exam scheduled'}</strong>
        <p class="sd-key-status-title">${esc(normalized.title)}</p>
        <p class="sd-key-status-meta">
          ${ready
            ? `${normalized.timeLimit || 0} min · ${normalized.questionCount || 0} questions`
            : `Opens ${esc(when)}${opensIn ? ` (${esc(opensIn)})` : ''}`}
        </p>
        ${ready
          ? `<button type="button" class="sd-enter-btn sd-key-status-enter" id="sdPendingExamEnter">Start exam</button>`
          : `<p class="sd-key-status-hint">Your key is saved. It will appear in Upcoming and Calendar until the exam opens.</p>
             <button type="button" class="sd-link" data-sd-view="calendar">View calendar</button>
             <button type="button" class="sd-link sd-key-reset" id="sdExamKeyReset">Enter a different key</button>`}
      </div>
    `;

    els.examKeyStatus.querySelector('#sdPendingExamEnter')?.addEventListener('click', () => goToExam(normalized.id));
    els.examKeyStatus.querySelector('#sdExamKeyReset')?.addEventListener('click', resetExamKeyForm);
    els.examKeyStatus.querySelector('[data-sd-view="calendar"]')?.addEventListener('click', () => switchView('calendar'));

    if (!ready && opensAt) startExamOpenWatcher(normalized);
    else clearExamOpenWatcher();
  }

  function showExamKeySubmitted(exam) {
    if (els.examKeyFormWrap) els.examKeyFormWrap.hidden = true;
    if (!els.examKeyStatus) return;
    els.examKeyStatus.hidden = false;
    els.examKeyStatus.className = 'sd-key-status sd-key-status-submitted';
    els.examKeyStatus.innerHTML = `
      <div class="sd-key-status-icon"><i class="ti ti-circle-check"></i></div>
      <div class="sd-key-status-body">
        <strong>Already submitted</strong>
        <p class="sd-key-status-title">${esc(exam?.title || 'This exam')}</p>
        <p class="sd-key-status-meta">You cannot re-enter after submission.</p>
        <button type="button" class="sd-link" data-sd-view="results">View results</button>
        <button type="button" class="sd-link sd-key-reset" id="sdExamKeyReset">Enter a different key</button>
      </div>
    `;
    els.examKeyStatus.querySelector('[data-sd-view="results"]')?.addEventListener('click', () => switchView('results'));
    els.examKeyStatus.querySelector('#sdExamKeyReset')?.addEventListener('click', resetExamKeyForm);
  }

  function resetExamKeyForm() {
    clearExamOpenWatcher();
    clearPendingExamKey();
    if (els.examKeyFormWrap) els.examKeyFormWrap.hidden = false;
    if (els.examKeyStatus) {
      els.examKeyStatus.hidden = true;
      els.examKeyStatus.innerHTML = '';
    }
    if (els.examKeyInput) {
      els.examKeyInput.value = '';
      els.examKeyInput.removeAttribute('readonly');
    }
    if (els.keyCount) els.keyCount.textContent = '0 / 8';
    if (els.enterExamBtn) {
      els.enterExamBtn.disabled = true;
      els.enterExamBtn.textContent = 'Enter exam room';
    }
    els.examKeyInput?.focus();
  }

  function renderExamKeyRoom() {
    if (state.pendingExamKey) {
      showExamKeyScheduled(state.pendingExamKey);
      return;
    }
    if (els.examKeyFormWrap) els.examKeyFormWrap.hidden = false;
    if (els.examKeyStatus) els.examKeyStatus.hidden = true;
  }

  function startExamOpenWatcher(exam) {
    clearExamOpenWatcher();
    if (!exam?.opensAt) return;

    state.examOpenTimer = window.setInterval(() => {
      const opens = new Date(exam.opensAt).getTime();
      if (Date.now() < opens) return;

      const known = state.exams.find((e) => Number(e.id) === Number(exam.id));
      const updated = {
        ...exam,
        ...known,
        available: true,
        status: 'active',
      };
      savePendingExamKey(updated);
      showExamKeyScheduled(updated);
      refreshHomePanels();
    }, 15000);
  }

  function refreshHomePanels() {
    renderGreeting();
    renderLiveBanner();
    renderUpcoming();
    if (els.stream) renderStream(els.stream, filteredExams());
    renderTodo();
    renderCalendar();
  }

  async function refreshExamsAfterKey() {
    try {
      const { exams } = await ExamGuardApi.exams();
      state.exams = exams || [];
      if (state.pendingExamKey) {
        mergeExamIntoState(state.pendingExamKey);
      }
      refreshHomePanels();
    } catch (_) {
      if (state.pendingExamKey) refreshHomePanels();
    }
  }

  function handleExamKeyResult(exam) {
    const normalized = normalizeKeyExam(exam);
    mergeExamIntoState(normalized);
    markExamKeyUsed();

    const inProgress = exam.attempt?.status === 'in_progress';
    const isActive = exam.available || exam.status === 'active';

    if (inProgress || isActive) {
      clearPendingExamKey();
      refreshExamsAfterKey();
      goToExam(exam.id);
      return;
    }

    if (exam.status === 'scheduled' || isFutureOpen(normalized)) {
      savePendingExamKey(normalized);
      showExamKeyScheduled(normalized);
      refreshExamsAfterKey();
      return;
    }

    showExamKeyScheduled({ ...normalized, available: isActive });
    refreshExamsAfterKey();
  }

  async function submitExamKey(key, options = {}) {
    const examKey = String(key || '').trim().toUpperCase();
    if (examKey.length < 8) {
      showExamKeyError('Enter the full 8-character exam key.');
      return;
    }

    if (options.focusRoom && state.currentView !== 'exam-room') {
      switchView('exam-room');
    }

    setExamKeyLoading(true);
    if (els.examKeyStatus) els.examKeyStatus.hidden = true;

    try {
      const { exam } = await ExamGuardApi.accessExamByKey(examKey);
      handleExamKeyResult(exam);
    } catch (error) {
      if (error.code === 'already_submitted' && error.exam) {
        showExamKeySubmitted(error.exam);
        return;
      }
      showExamKeyError(error.message || 'Invalid or inactive exam key.');
    } finally {
      setExamKeyLoading(false);
    }
  }

  function buildStreamEvents(exams) {
    const events = [];
    exams.forEach((exam) => {
      const submitted = exam.attempt?.submittedAt;
      const live = isLive(exam);
      if (submitted) {
        events.push({
          type: 'result',
          exam,
          time: submitted,
          sort: new Date(submitted).getTime(),
        });
      } else if (live) {
        events.push({
          type: 'live',
          exam,
          time: exam.opensAt || exam.attempt?.startedAt || new Date().toISOString(),
          sort: Date.now(),
        });
      } else if (exam.status === 'scheduled') {
        events.push({
          type: 'scheduled',
          exam,
          time: exam.opensAt,
          sort: exam.opensAt ? new Date(exam.opensAt).getTime() : Date.now(),
        });
      } else {
        events.push({
          type: 'announced',
          exam,
          time: exam.closesAt || exam.opensAt,
          sort: Date.now(),
        });
      }
    });
    return events.sort((a, b) => b.sort - a.sort);
  }

  function streamCardHtml(event, index, preferredClassId = null) {
    const exam = event.exam;
    const cls = classForExam(exam, preferredClassId);
    const icons = {
      announced: ['ti-file-description', 'rgba(59,130,246,0.12)', '#3b82f6', 'rgba(59,130,246,0.20)'],
      live: ['ti-writing', 'rgba(34,197,94,0.12)', '#22c55e', 'rgba(34,197,94,0.20)'],
      result: ['ti-chart-bar', 'rgba(245,158,11,0.12)', '#f59e0b', 'rgba(245,158,11,0.20)'],
      scheduled: ['ti-calendar', 'rgba(255,255,255,0.06)', 'rgba(255,255,255,0.40)', 'rgba(255,255,255,0.12)'],
    };
    const [icon, bg, color, border] = icons[event.type] || icons.announced;
    let badge = '';
    let actions = '';

    if (event.type === 'live') {
      badge = '<span class="sd-badge sd-badge-live"><span class="sd-pulse-dot"></span>Live now</span>';
      actions = `<button type="button" class="sd-enter-now-pill" data-take-exam="${exam.id}">Enter now</button>`;
    } else if (event.type === 'scheduled' && exam.opensAt) {
      badge = `<span class="sd-badge sd-badge-due">Due ${formatDueShort(exam.opensAt)}</span>`;
      actions = '<button type="button" class="sd-link">View details</button>';
    } else if (event.type === 'result') {
      const pct = scorePercent(exam);
      const pass = isPassed(exam);
      badge = `<span class="sd-badge sd-badge-scheduled">${pass ? 'Passed' : 'Failed'}</span>`;
      actions = `
        <button type="button" class="sd-link" data-sd-view="results">View score</button>
        <span class="sd-score-pill ${pass ? 'pass' : 'fail'}">${pct}%</span>
      `;
    } else {
      badge = '<span class="sd-badge sd-badge-scheduled">Scheduled</span>';
      actions = '<button type="button" class="sd-link">View details</button>';
    }

    return `
      <article class="sd-stream-card sd-fade-up${event.type === 'live' ? ' sd-stream-card--live' : ''}" style="animation-delay:${index * 60}ms">
        <div class="sd-stream-icon" style="background:${bg};color:${color};border:1px solid ${border}"><i class="ti ${icon}"></i></div>
        <div class="sd-stream-body">
          <div class="sd-stream-title-row">
            <span class="sd-stream-title">${esc(exam.title)}</span>
            ${badge}
          </div>
          <div class="sd-stream-meta">${cls ? `${esc(cls.name)} · ` : ''}${esc(streamSubtitle(event))}</div>
          <div class="sd-stream-actions">${actions}</div>
        </div>
      </article>
    `;
  }

  function renderStream(targetEl, exams, preferredClassId = null) {
    if (!targetEl) return;
    const list = Array.isArray(exams) ? exams : filteredExams();
    const events = buildStreamEvents(list);
    if (!events.length) {
      targetEl.innerHTML = '<div class="sd-stream-empty">No activity yet. Join a class to see exams here.</div>';
      return;
    }
    targetEl.innerHTML = events.map((ev, i) => streamCardHtml(ev, i, preferredClassId)).join('');
    targetEl.querySelectorAll('[data-take-exam]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        goToExam(Number(btn.dataset.takeExam));
      });
    });
    targetEl.querySelectorAll('[data-sd-view]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        switchView(btn.dataset.sdView);
      });
    });
  }

  function joinGhostCardHtml() {
    return `
      <button type="button" class="sd-class-ghost-card" id="sdJoinGhostCard">
        <span class="sd-class-ghost-inner">
          <i class="ti ti-plus" aria-hidden="true"></i>
          <span>Join a class</span>
        </span>
      </button>
    `;
  }

  function bindJoinGhostCard() {
    document.getElementById('sdJoinGhostCard')?.addEventListener('click', openJoinModal);
  }

  function renderClassCards() {
    if (!els.classCards) return;

    if (!state.classes.length) {
      els.classCards.innerHTML = joinGhostCardHtml();
      bindJoinGhostCard();
      return;
    }

    els.classCards.innerHTML = state.classes.map((cls, i) => {
      const classExams = examsForClass(cls.id);
      const count = classExams.length;
      const examLabel = `${count} exam${count === 1 ? '' : 's'}`;
      return `
        <article class="sd-class-card sd-fade-up" data-class-id="${cls.id}" style="animation-delay:${i * 80}ms" tabindex="0" role="link">
          <div class="sd-class-card-banner" style="background:${classBannerColor(i)}">
            <div class="sd-class-card-banner-text">
              <h3>${esc(cls.name)}</h3>
              <p>${esc(professorLine(cls))}</p>
            </div>
            <div class="sd-class-card-avatar">${esc(professorInitials(cls))}</div>
          </div>
          <div class="sd-class-card-body">
            <span class="sd-class-card-exams">${examLabel}</span>
            <div class="sd-class-card-actions">
              <button type="button" class="sd-class-card-icon" data-copy-code="${esc(cls.code)}" aria-label="Copy class code"><i class="ti ti-copy"></i></button>
              <button type="button" class="sd-class-card-icon" aria-label="Class options"><i class="ti ti-dots-vertical"></i></button>
            </div>
          </div>
        </article>
      `;
    }).join('') + joinGhostCardHtml();

    bindJoinGhostCard();

    els.classCards.querySelectorAll('.sd-class-card').forEach((card) => {
      const classId = Number(card.dataset.classId);
      const openClass = () => switchView('class', { classId });
      card.addEventListener('click', (e) => {
        if (e.target.closest('.sd-class-card-actions')) return;
        e.stopPropagation();
        openClass();
      });
      card.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openClass();
        }
      });
    });

    els.classCards.querySelectorAll('[data-copy-code]').forEach((btn) => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        try {
          await navigator.clipboard.writeText(btn.dataset.copyCode || '');
        } catch (_) {}
      });
    });
  }

  function upcomingRowHtml(exam) {
    const live = isLive(exam);
    const when = upcomingWhenLabel(exam);
    const cls = classForExam(exam);
    const classIdx = cls ? state.classes.findIndex((c) => Number(c.id) === Number(cls.id)) : 0;
    const dotColor = classColor(classIdx >= 0 ? classIdx : 0);
    const whenClass = live ? 'sd-upcoming-when sd-upcoming-live' : 'sd-upcoming-when';
    return `
      <button type="button" class="sd-upcoming-row sd-upcoming-row-btn" data-upcoming-exam="${exam.id}" data-upcoming-live="${live ? '1' : '0'}">
        <div class="sd-upcoming-top">
          <span class="sd-upcoming-name"><span class="sd-class-dot" style="background:${dotColor}"></span> ${esc(exam.title)}</span>
          <span class="${whenClass}">${live ? '<span class="sd-pulse-dot"></span> ' : ''}${esc(when)}</span>
        </div>
        <div class="sd-upcoming-sub">${esc(cls?.name || 'Class')} · ${exam.timeLimit || 0} min</div>
      </button>`;
  }

  function dateKey(date) {
    const d = date instanceof Date ? date : new Date(date);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  }

  function parseDateKey(key) {
    const [y, m, d] = String(key).split('-').map(Number);
    return new Date(y, m - 1, d);
  }

  function isFailed(exam) {
    return Boolean(exam.attempt?.submittedAt && !isPassed(exam));
  }

  function isMissed(exam) {
    if (exam.attempt?.submittedAt) return false;
    if (!exam.closesAt) return false;
    return new Date(exam.closesAt).getTime() < Date.now();
  }

  function getWeekStart(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() - d.getDay());
    return d;
  }

  function formatShortTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
  }

  function formatExamTimeRange(exam) {
    const start = exam.opensAt ? formatShortTime(exam.opensAt) : null;
    const end = exam.closesAt ? formatShortTime(exam.closesAt) : null;
    if (start && end) return `${start} – ${end}`;
    if (start) return start;
    if (end) return `Until ${end}`;
    return 'Time TBD';
  }

  function examMatchesClassFilter(exam) {
    if (!state.calendarClassFilter) return true;
    const cid = Number(state.calendarClassFilter);
    const ids = exam.classIds || (exam.classId ? [exam.classId] : []);
    return ids.some((id) => Number(id) === cid);
  }

  function filteredCalendarExams() {
    const list = [...state.exams];
    if (state.pendingExamKey && !list.some((e) => Number(e.id) === Number(state.pendingExamKey.id))) {
      list.push(state.pendingExamKey);
    }
    return list.filter(examMatchesClassFilter);
  }

  function calendarEventType(exam) {
    if (exam.attempt?.submittedAt) return isFailed(exam) ? 'failed' : 'submitted';
    if (isMissed(exam)) return 'missed';
    if (isLive(exam)) return 'live';
    if (exam.status === 'scheduled' || isFutureOpen(exam)) return 'scheduled';
    return 'open';
  }

  function calendarEventLabel(type) {
    return {
      live: 'Live',
      scheduled: 'Upcoming',
      open: 'Upcoming',
      submitted: 'Submitted',
      failed: 'Failed',
      missed: 'Missed',
    }[type] || 'Exam';
  }

  function examTooltipText(exam, type) {
    const cls = classForExam(exam);
    return [
      exam.title,
      cls?.name || 'Class',
      formatExamTimeRange(exam),
      `Status: ${calendarEventLabel(type)}`,
    ].join('\n');
  }

  function calendarExamEntries() {
    const entries = [];
    const seen = new Set();

    const addEntry = (exam, date, type) => {
      if (!date || !examMatchesClassFilter(exam)) return;
      const key = `${Number(exam.id)}:${type}:${dateKey(date)}`;
      if (seen.has(key)) return;
      seen.add(key);
      entries.push({ exam, dateKey: dateKey(date), type });
    };

    filteredCalendarExams().forEach((exam) => {
      if (exam.opensAt) {
        addEntry(exam, new Date(exam.opensAt), calendarEventType(exam));
      } else if (isLive(exam) && !exam.attempt?.submittedAt) {
        addEntry(exam, new Date(), 'live');
      } else if (isUpcoming(exam)) {
        addEntry(exam, new Date(), calendarEventType(exam));
      }
      if (exam.attempt?.submittedAt) {
        addEntry(exam, new Date(exam.attempt.submittedAt), isFailed(exam) ? 'failed' : 'submitted');
      }
      if (isMissed(exam) && exam.closesAt) {
        addEntry(exam, new Date(exam.closesAt), 'missed');
      }
    });

    return entries;
  }

  function calendarEntriesByDay() {
    const map = new Map();
    calendarExamEntries().forEach((entry) => {
      if (!map.has(entry.dateKey)) map.set(entry.dateKey, []);
      map.get(entry.dateKey).push(entry);
    });
    map.forEach((list) => {
      list.sort((a, b) => {
        const order = { live: 0, open: 1, scheduled: 2, submitted: 3, failed: 4, missed: 5 };
        const ta = a.exam.opensAt ? new Date(a.exam.opensAt).getTime() : 0;
        const tb = b.exam.opensAt ? new Date(b.exam.opensAt).getTime() : 0;
        const o = (order[a.type] ?? 9) - (order[b.type] ?? 9);
        return o !== 0 ? o : ta - tb;
      });
    });
    return map;
  }

  function formatCalendarDayLabel(dateKeyValue) {
    return parseDateKey(dateKeyValue).toLocaleDateString(undefined, {
      weekday: 'long', month: 'long', day: 'numeric',
    });
  }

  const CAL_MONTH_MAX_PILLS = 2;
  const CAL_WEEK_MAX_PILLS = 4;

  function cellHeightClass(count, week = false) {
    if (count === 0) return 'sd-cal-cell--empty';
    if (week) return 'sd-cal-cell--week';
    if (count > 1) return 'sd-cal-cell--filled sd-cal-cell--multi';
    return 'sd-cal-cell--filled';
  }

  function calendarOverflowHtml(count, dateKeyValue, maxPills) {
    if (count <= maxPills) return '';
    return `<button type="button" class="sd-cal-more" data-cal-more="${dateKeyValue}">+${count - maxPills} more</button>`;
  }

  function calendarPillHtml(entry, options = {}) {
    const { exam, type } = entry;
    const { week = false, maxPills = 2 } = options;
    const cls = classForExam(exam);
    const time = exam.opensAt && type !== 'submitted' && type !== 'failed'
      ? formatShortTime(exam.opensAt)
      : (exam.attempt?.submittedAt ? formatShortTime(exam.attempt.submittedAt) : 'Now');
  const pillClass = `sd-cal-pill sd-cal-pill--${type}${week ? ' sd-cal-pill--week' : ''}`;
    return `
      <button type="button" class="${pillClass}" data-cal-exam="${exam.id}" data-cal-type="${type}"
              title="${esc(examTooltipText(exam, type))}">
        <span class="sd-cal-pill-time">${type === 'live' ? '● ' : ''}${esc(time)}</span>
        <span class="sd-cal-pill-name">${esc(exam.title)}</span>
        <span class="sd-cal-pill-class">${esc(cls?.name || 'Class')}</span>
      </button>`;
  }

  function buildCalendarCellHtml(dayData, options = {}) {
    const { key, dayNum, outside, isToday, isSelected, entries } = dayData;
    const { week = false } = options;
    const count = entries.length;
    const maxPills = week ? CAL_WEEK_MAX_PILLS : CAL_MONTH_MAX_PILLS;
    const pills = entries.slice(0, maxPills).map((e) => calendarPillHtml(e, { week })).join('');
    const more = calendarOverflowHtml(count, key, maxPills);
    const heightClass = cellHeightClass(count, week);
    const classes = [
      'sd-cal-cell',
      heightClass,
      outside ? 'outside' : '',
      isToday ? 'today' : '',
      isSelected ? 'selected' : '',
      count > 0 ? 'has-exams' : '',
    ].filter(Boolean).join(' ');

    return `
      <div class="${classes}" data-cal-date="${key}" role="gridcell" tabindex="${outside ? '-1' : '0'}"
           aria-label="${esc(formatCalendarDayLabel(key))}${count ? `, ${count} exam${count === 1 ? '' : 's'}` : ''}">
        <span class="sd-cal-day-num">${dayNum}</span>
        ${count ? `<div class="sd-cal-events">${pills}${more}</div>` : ''}
      </div>`;
  }

  function renderCalClassFilter() {
    if (!els.calClassFilter) return;
    const val = state.calendarClassFilter;
    els.calClassFilter.innerHTML = `<option value="">All classes</option>${
      state.classes.map((c) => `<option value="${c.id}"${String(c.id) === String(val) ? ' selected' : ''}>${esc(c.name)}</option>`).join('')
    }`;
  }

  function updateCalendarToolbarLabel() {
    if (!els.calendarMonthLabel) return;
    if (state.calendarViewMode === 'week') {
      const start = state.calendarWeekStart || getWeekStart(new Date());
      const end = new Date(start);
      end.setDate(end.getDate() + 6);
      const sameMonth = start.getMonth() === end.getMonth();
      const startStr = start.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
      const endStr = end.toLocaleDateString(undefined, {
        month: sameMonth ? undefined : 'short', day: 'numeric', year: 'numeric',
      });
      els.calendarMonthLabel.textContent = `${startStr} – ${endStr}`;
    } else {
      els.calendarMonthLabel.textContent = state.calendarMonth.toLocaleDateString(undefined, {
        month: 'long', year: 'numeric',
      });
    }
  }

  function updateCalViewToggle() {
    document.querySelectorAll('[data-cal-view-mode]').forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.calViewMode === state.calendarViewMode);
    });
    if (els.calWeekdays) {
      els.calWeekdays.style.display = state.calendarViewMode === 'week' ? 'none' : '';
    }
  }

  function formatCalendarDayShort(dateKeyValue) {
    return parseDateKey(dateKeyValue).toLocaleDateString(undefined, {
      weekday: 'short', month: 'short', day: 'numeric',
    });
  }

  function renderCalendarDayDetail(dateKeyValue) {
    if (!els.calendarDayDetail) return;
    const entries = calendarEntriesByDay().get(dateKeyValue) || [];
    const todayKey = dateKey(new Date());
    const isToday = dateKeyValue === todayKey;
    const todayTag = isToday ? '<span class="sd-cal-detail-today">Today</span>' : '';

    if (!entries.length) {
      els.calendarDayDetail.innerHTML = `
        <div class="sd-cal-detail-head">
          <h3>${esc(formatCalendarDayShort(dateKeyValue))} ${todayTag}</h3>
        </div>
        <div class="sd-cal-detail-empty">No exams this day</div>`;
      return;
    }

    els.calendarDayDetail.innerHTML = `
      <div class="sd-cal-detail-head">
        <h3>${esc(formatCalendarDayShort(dateKeyValue))} ${todayTag}</h3>
        <span class="sd-cal-detail-count">${entries.length} exam${entries.length === 1 ? '' : 's'}</span>
      </div>
      <div class="sd-cal-detail-list">
        ${entries.map(({ exam, type }) => {
          const cls = classForExam(exam);
          const classIdx = cls ? state.classes.findIndex((c) => Number(c.id) === Number(cls.id)) : 0;
          const dotColor = classColor(classIdx >= 0 ? classIdx : 0);
          const canEnter = ['live', 'scheduled', 'open'].includes(type);
          const scoreHtml = exam.attempt?.submittedAt && exam.attempt?.total
            ? `<p class="sd-cal-detail-score">Score ${exam.attempt.score}/${exam.attempt.total}</p>`
            : '';
          const action = canEnter
            ? `<button type="button" class="sd-btn-pill" data-cal-exam="${exam.id}">Enter</button>`
            : (type === 'submitted' || type === 'failed'
              ? `<button type="button" class="sd-link" data-sd-view="results">Results</button>`
              : '');
          return `
            <div class="sd-cal-detail-card">
              <div class="sd-cal-detail-card-top">
                <div class="sd-cal-detail-card-main">
                  <p class="sd-cal-detail-title">
                    <span class="sd-class-dot" style="background:${dotColor}"></span>
                    ${esc(exam.title)}
                  </p>
                  <p class="sd-cal-detail-meta">${esc(cls?.name || 'Class')}</p>
                  <p class="sd-cal-detail-meta">${esc(formatExamTimeRange(exam))} · ${exam.timeLimit || 0}m</p>
                  ${scoreHtml}
                </div>
                <span class="sd-cal-detail-badge ${esc(type)}">${type === 'live' ? '<span class="sd-pulse-dot"></span>' : ''}${esc(calendarEventLabel(type))}</span>
              </div>
              ${action ? `<div class="sd-cal-detail-side">${action}</div>` : ''}
            </div>`;
        }).join('')}
      </div>`;

    els.calendarDayDetail.querySelectorAll('[data-cal-exam]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const type = entries.find((e) => Number(e.exam.id) === Number(btn.dataset.calExam))?.type;
        if (type === 'live') goToExam(btn.dataset.calExam);
        else openUpcomingExam(btn.dataset.calExam);
      });
    });
    els.calendarDayDetail.querySelector('[data-sd-view="results"]')?.addEventListener('click', () => switchView('results'));
  }

  function selectCalendarDay(dateKeyValue, cell = null) {
    state.calendarSelectedDay = dateKeyValue;
    renderCalendarDayDetail(dateKeyValue);
    els.calendarGrid?.querySelectorAll('[data-cal-date]').forEach((el) => {
      el.classList.toggle('selected', el.dataset.calDate === dateKeyValue);
    });
    if (cell) {
      cell.classList.add('pressed');
      window.setTimeout(() => cell.classList.remove('pressed'), 320);
    }
  }

  function renderMonthCalendar() {
    const monthDate = state.calendarMonth;
    const year = monthDate.getFullYear();
    const month = monthDate.getMonth();
    const todayKey = dateKey(new Date());
    const firstOfMonth = new Date(year, month, 1);
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const startPad = firstOfMonth.getDay();
    const totalCells = Math.ceil((startPad + daysInMonth) / 7) * 7;
    const entriesByDay = calendarEntriesByDay();
    if (!state.calendarSelectedDay) state.calendarSelectedDay = todayKey;

    const weeks = [];
    let week = [];
    for (let i = 0; i < totalCells; i += 1) {
      const dayNum = i - startPad + 1;
      const cellDate = new Date(year, month, dayNum);
      const key = dateKey(cellDate);
      week.push({
        key,
        dayNum: cellDate.getDate(),
        outside: dayNum < 1 || dayNum > daysInMonth,
        isToday: key === todayKey,
        isSelected: key === state.calendarSelectedDay,
        entries: entriesByDay.get(key) || [],
      });
      if (week.length === 7) {
        weeks.push(week);
        week = [];
      }
    }

    els.calendarGrid.innerHTML = weeks.map((days) => {
      const inMonth = days.filter((d) => !d.outside);
      const hasExams = inMonth.some((d) => d.entries.length > 0);
      const emptyLabel = inMonth.length && !hasExams
        ? '<div class="sd-cal-week-empty">No exams this week</div>'
        : '';
      return `
        <div class="sd-cal-week-row">
          ${emptyLabel}
          <div class="sd-cal-week-days">
            ${days.map((d) => buildCalendarCellHtml(d)).join('')}
          </div>
        </div>`;
    }).join('');
  }

  function renderWeekCalendar() {
    const todayKey = dateKey(new Date());
    if (!state.calendarWeekStart) state.calendarWeekStart = getWeekStart(new Date());
    if (!state.calendarSelectedDay) state.calendarSelectedDay = todayKey;

    const entriesByDay = calendarEntriesByDay();
    const days = [];
    for (let i = 0; i < 7; i += 1) {
      const cellDate = new Date(state.calendarWeekStart);
      cellDate.setDate(cellDate.getDate() + i);
      const key = dateKey(cellDate);
      days.push({
        key,
        dayNum: cellDate.getDate(),
        outside: false,
        isToday: key === todayKey,
        isSelected: key === state.calendarSelectedDay,
        entries: entriesByDay.get(key) || [],
      });
    }

    const hasExams = days.some((d) => d.entries.length > 0);
    const emptyLabel = !hasExams ? '<div class="sd-cal-week-empty">No exams this week</div>' : '';

    els.calendarGrid.innerHTML = `
      <div class="sd-cal-week-row">
        ${emptyLabel}
        <div class="sd-cal-week-days sd-cal-week-days--expanded">
          ${days.map((d) => buildCalendarCellHtml(d, { week: true })).join('')}
        </div>
      </div>`;
  }

  function renderCalendar() {
    if (!els.calendarGrid) return;
    if (!state.calendarWeekStart) state.calendarWeekStart = getWeekStart(new Date());
    const todayKey = dateKey(new Date());
    if (!state.calendarSelectedDay) state.calendarSelectedDay = todayKey;

    renderCalClassFilter();
    updateCalendarToolbarLabel();
    updateCalViewToggle();

    if (state.calendarViewMode === 'week') {
      renderWeekCalendar();
    } else {
      renderMonthCalendar();
    }

    renderCalendarDayDetail(state.calendarSelectedDay);
  }

  function shiftCalendarPeriod(delta) {
    if (state.calendarViewMode === 'week') {
      const start = state.calendarWeekStart || getWeekStart(new Date());
      start.setDate(start.getDate() + (delta * 7));
      state.calendarWeekStart = new Date(start);
    } else {
      const d = state.calendarMonth;
      state.calendarMonth = new Date(d.getFullYear(), d.getMonth() + delta, 1);
    }
    renderCalendar();
  }

  function goCalendarToday() {
    const now = new Date();
    state.calendarMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    state.calendarWeekStart = getWeekStart(now);
    state.calendarSelectedDay = dateKey(now);
    renderCalendar();
  }

  function setCalendarViewMode(mode) {
    state.calendarViewMode = mode;
    if (mode === 'week') {
      const anchor = state.calendarSelectedDay ? parseDateKey(state.calendarSelectedDay) : new Date();
      state.calendarWeekStart = getWeekStart(anchor);
    }
    renderCalendar();
  }

  function renderUpcoming() {
    if (!els.upcoming) return;
    const upcoming = sortUpcoming(upcomingExams()).slice(0, 5);

    if (!upcoming.length) {
      els.upcoming.innerHTML = `
        <div class="sd-panel-empty sd-panel-empty-icon">
          <i class="ti ti-calendar" style="font-size:28px;opacity:0.2;display:block;margin-bottom:8px"></i>
          No upcoming exams
        </div>`;
      return;
    }

    els.upcoming.innerHTML = upcoming.map((exam) => upcomingRowHtml(exam)).join('');
  }

  function renderTodo() {
    if (!els.todo) return;
    const hasClass = state.classes.length > 0;
    const items = [
      { label: 'Join a class', done: hasClass, action: 'join' },
      { label: 'Complete system check', done: hasSystemCheckDone(), action: 'system-check' },
      { label: 'Enter an exam key', done: hasExamKeyActivity(), action: 'exam-key' },
    ];
    els.todo.innerHTML = items.map((item) => {
      if (item.done) {
        return `
          <div class="sd-todo-item done">
            <span class="sd-todo-check"></span>
            <span>${esc(item.label)}</span>
          </div>`;
      }
      return `
        <button type="button" class="sd-todo-item" data-todo-action="${item.action}">
          <span class="sd-todo-check"></span>
          <span>${esc(item.label)}</span>
        </button>`;
    }).join('');
  }

  function renderLiveBanner() {
    const liveExam = state.exams.find((e) => isLive(e) && !e.attempt?.submittedAt);
    if (!liveExam || state.liveBannerDismissed) {
      els.liveBanner?.classList.remove('visible');
      state.liveExamId = null;
      return;
    }
    state.liveExamId = liveExam.id;
    if (els.liveExamName) els.liveExamName.textContent = liveExam.title;
    els.liveBanner?.classList.add('visible');
  }

  function renderGreeting() {
    const name = state.user?.name?.split(' ')[0] || 'Student';
    if (els.greeting) els.greeting.textContent = `${greeting()}, ${name}.`;
    const upcoming = upcomingExams().length;
    if (els.greetingSub) {
      els.greetingSub.textContent = upcoming
        ? `You have ${upcoming} upcoming exam${upcoming === 1 ? '' : 's'} this week.`
        : 'You have no upcoming exams this week.';
    }
  }

  function renderClassList() {
    if (!els.classList) return;
    if (!state.classes.length) {
      els.classList.innerHTML = '';
      return;
    }
    els.classList.innerHTML = state.classes.map((cls, i) => `
      <button type="button" class="sd-class-item${state.selectedClassId === cls.id ? ' active' : ''}"
              data-class-id="${cls.id}" title="${esc(cls.name)}">
        <span class="sd-class-dot" style="background:${classColor(i)}"></span>
        <span>${esc(cls.name)}</span>
      </button>
    `).join('');
    els.classList.querySelectorAll('.sd-class-item').forEach((btn) => {
      btn.addEventListener('click', () => switchView('class', { classId: Number(btn.dataset.classId) }));
    });
  }

  function renderClassStream() {
    const cls = state.classes.find((c) => Number(c.id) === Number(state.selectedClassId));
    if (!cls) {
      switchView('home');
      return;
    }
    const idx = state.classes.findIndex((c) => c.id === cls.id);
    if (els.classBanner) els.classBanner.style.background = classBannerColor(idx);
    if (els.classBannerTitle) els.classBannerTitle.textContent = cls.name;
    if (els.classBannerSub) els.classBannerSub.textContent = cls.subject || 'Class';
    if (els.classBannerAvatar) els.classBannerAvatar.textContent = initials(cls.name);

    if (els.classInfo) {
      els.classInfo.innerHTML = `
        <div class="sd-info-row"><span class="sd-info-label">Class code</span><span class="sd-info-value">${esc(cls.code)} <button type="button" class="sd-link" data-copy="${esc(cls.code)}"><i class="ti ti-copy"></i></button></span></div>
        <div class="sd-info-row"><span class="sd-info-label">Subject</span><span class="sd-info-value">${esc(cls.subject || '—')}</span></div>
        <div class="sd-info-row"><span class="sd-info-label">Exams</span><span class="sd-info-value">${examsForClass(cls.id).length}</span></div>
        <div class="sd-info-row"><span class="sd-info-label">Students</span><span class="sd-info-value">—</span></div>
      `;
      els.classInfo.querySelector('[data-copy]')?.addEventListener('click', async (e) => {
        try {
          await navigator.clipboard.writeText(e.currentTarget.dataset.copy);
        } catch (_) {}
      });
    }
    renderStream(els.classStream, examsForClass(cls.id), cls.id);
  }

  function filterResultsList(exams) {
    let list = [...exams];
    const now = new Date();
    if (state.resultFilter === 'passed') list = list.filter(isPassed);
    if (state.resultFilter === 'failed') list = list.filter((e) => !isPassed(e));
    if (state.resultFilter === 'month') {
      list = list.filter((e) => {
        const d = new Date(e.attempt.submittedAt);
        return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
      });
    }
    return list;
  }

  function openResultModal(examId) {
    const exam = examById(examId);
    if (!exam || !hasSubmittedAttempt(exam)) return;

    const pct = scorePercent(exam);
    const pass = isPassed(exam);
    const attempt = exam.attempt;
    const duration = attemptDurationLabel(exam);
    const warnings = attempt.warningCount || 0;
    const warningLimit = exam.warningLimit ?? 3;

    if (els.resultTitle) els.resultTitle.textContent = exam.title;
    if (els.resultBody) {
      els.resultBody.innerHTML = `
        <div class="sd-result-detail-row"><span>Class</span><strong>${esc(examClassLabel(exam))}</strong></div>
        <div class="sd-result-detail-row"><span>Submitted</span><strong>${esc(formatWhen(attempt.submittedAt))}</strong></div>
        <div class="sd-result-detail-row"><span>Score</span><strong>${attempt.score}/${attempt.total} (${pct}%)</strong></div>
        <div class="sd-result-detail-row"><span>Result</span><strong><span class="sd-result-badge ${pass ? 'pass' : 'fail'}">${pass ? 'Passed' : 'Failed'}</span></strong></div>
        <div class="sd-result-detail-row"><span>Warnings</span><strong>${warnings}/${warningLimit}</strong></div>
        ${duration ? `<div class="sd-result-detail-row"><span>Time taken</span><strong>${esc(duration)}</strong></div>` : ''}
        <div class="sd-result-detail-row"><span>Time limit</span><strong>${exam.timeLimit || 0} min</strong></div>
        <div class="sd-result-detail-row"><span>Questions</span><strong>${exam.questionCount || attempt.total || 0}</strong></div>
      `;
    }
    els.resultModal?.classList.add('open');
  }

  function closeResultModal() {
    els.resultModal?.classList.remove('open');
  }

  function openCertDownloadModal() {
    els.certModal?.classList.add('open');
  }

  function closeCertDownloadModal() {
    els.certModal?.classList.remove('open');
  }

  function renderResults() {
    const all = submittedExams();
    const list = filterResultsList(all);

    const avg = all.length
      ? Math.round(all.reduce((s, e) => s + scorePercent(e), 0) / all.length)
      : 0;
    const passRate = all.length
      ? Math.round((all.filter(isPassed).length / all.length) * 100)
      : 0;

    if (els.resultStats) {
      els.resultStats.innerHTML = `
        <div class="sd-stat-card"><div class="sd-stat-num">${all.length}</div><div class="sd-stat-lbl">Exams taken</div></div>
        <div class="sd-stat-card"><div class="sd-stat-num">${avg}%</div><div class="sd-stat-lbl">Average score</div></div>
        <div class="sd-stat-card"><div class="sd-stat-num">${passRate}%</div><div class="sd-stat-lbl">Pass rate</div></div>
      `;
    }

    if (!els.resultsList) return;
    if (!all.length) {
      els.resultsList.innerHTML = `
        <div class="sd-empty-state">
          <i class="ti ti-chart-bar"></i>
          <h3>No results yet</h3>
          <p>Complete an assigned exam to see your scores and performance here.</p>
        </div>
      `;
      return;
    }
    if (!list.length) {
      els.resultsList.innerHTML = '<div class="sd-panel-empty" style="padding:32px;text-align:center">No results match this filter.</div>';
      return;
    }

    els.resultsList.innerHTML = list.map((exam, i) => {
      const pct = scorePercent(exam);
      const pass = isPassed(exam);
      const attempt = exam.attempt;
      const warnings = attempt?.warningCount || 0;
      const classLabel = examClassLabel(exam);
      const avatarColor = examClassColor(exam, i);
      const duration = attemptDurationLabel(exam);
      return `
        <article class="sd-result-card sd-fade-up" style="animation-delay:${i * 60}ms">
          <div class="sd-result-top">
            <div class="sd-result-avatar" style="background:${avatarColor}">${initials(classLabel)}</div>
            <div class="sd-result-info">
              <h3>${esc(exam.title)}</h3>
              <p>${esc(classLabel)}</p>
            </div>
            <div class="sd-score-ring ${pass ? 'pass' : 'fail'}">
              <span class="sd-score-num">${pct}</span>
              <span class="sd-score-of">/ 100</span>
            </div>
          </div>
          <div class="sd-result-foot">
            <span>${formatWhen(attempt.submittedAt)}</span>
            <span class="sd-result-score-pill">${attempt.score}/${attempt.total} correct</span>
            ${duration ? `<span>${esc(duration)}</span>` : ''}
            ${warnings ? `<span class="sd-violations-warn"><i class="ti ti-alert-triangle"></i> ${warnings} warning${warnings === 1 ? '' : 's'}</span>` : ''}
            <button type="button" class="sd-link" data-result-exam="${exam.id}">View details</button>
            ${pass ? '<i class="ti ti-award" style="color:#f59e0b;font-size:16px" title="Passed"></i>' : ''}
          </div>
        </article>
      `;
    }).join('');
  }

  function renderCertificates() {
    const certs = passedExams();
    if (!els.certGrid) return;
    if (!certs.length) {
      els.certGrid.innerHTML = `
        <div class="sd-empty-state" style="grid-column:1/-1">
          <i class="ti ti-award"></i>
          <h3>No certificates yet</h3>
          <p>Pass an exam to earn your first certificate.</p>
        </div>
      `;
      return;
    }
    els.certGrid.innerHTML = certs.map((exam, i) => {
      const classLabel = examClassLabel(exam);
      return `
      <article class="sd-cert-card sd-fade-up" style="animation-delay:${i * 80}ms">
        <div class="sd-cert-strip"></div>
        <div class="sd-cert-body">
          <i class="ti ti-award"></i>
          <h3>Certificate of Completion</h3>
          <p>${esc(exam.title)}</p>
          <p>${esc(classLabel)}</p>
          <p>Issued ${formatWhen(exam.attempt.submittedAt)}</p>
          <div class="sd-cert-divider"></div>
          <button type="button" class="sd-cert-dl">Download PDF</button>
        </div>
      </article>
    `;
    }).join('');
    els.certGrid.querySelectorAll('.sd-cert-dl').forEach((btn) => {
      btn.addEventListener('click', openCertDownloadModal);
    });
  }

  function renderHome() {
    renderGreeting();
    renderLiveBanner();
    renderClassCards();
    renderStream(els.stream, filteredExams());
    renderUpcoming();
    renderTodo();
  }

  async function probeSystemCheck() {
    try {
      if (!navigator.permissions) return;
      const p = await navigator.permissions.query({ name: 'camera' });
      if (p.state === 'granted' && document.hasFocus()) {
        markSystemCheckDone();
        renderTodo();
      }
    } catch (_) {}
  }

  function goToExam(examId) {
    const target = `/take-exam?examId=${examId}`;
    window.ExamGuardRoute?.save(target);
    location.href = target;
  }

  async function updateReadiness() {
    if (!els.readiness) return;
    let camera = 'warn';
    try {
      if (navigator.permissions) {
        const p = await navigator.permissions.query({ name: 'camera' });
        camera = p.state === 'granted' ? 'ok' : p.state === 'denied' ? 'bad' : 'warn';
      }
    } catch (_) {}
    const tab = document.hasFocus() ? 'ok' : 'warn';
    const browser = 'ok';
    const items = [
      { label: 'Camera access', status: camera },
      { label: 'Tab focus', status: tab },
      { label: 'Browser check', status: browser },
    ];
    els.readiness.innerHTML = items.map((item) => `
      <div class="sd-ready-item">
        <span class="sd-status-dot ${item.status === 'ok' ? 'ok' : item.status === 'bad' ? 'bad' : 'warn'}"></span>
        <span>${item.label}</span>
        ${item.status === 'bad' ? '<button type="button" class="sd-fix-link">Fix</button>' : ''}
      </div>
    `).join('');

    if (camera === 'ok' && tab === 'ok' && browser === 'ok') {
      markSystemCheckDone();
      if (state.currentView === 'home') renderTodo();
    }
  }

  async function loadDashboard() {
    try {
      const [{ user }, { classes }, { exams }] = await Promise.all([
        ExamGuardApi.me(),
        ExamGuardApi.classes(),
        ExamGuardApi.exams(),
      ]);
      state.user = user;
      state.classes = classes || [];
      state.exams = exams || [];
      restorePendingExamKey();

      if (els.avatarBtn) els.avatarBtn.textContent = initials(user.name);
      if (els.joinName) els.joinName.value = user.name;

      renderClassList();
      renderHome();
      probeSystemCheck();

      const boot = parseHash();
      if (boot.view === 'class' && boot.classId && state.classes.some((c) => Number(c.id) === Number(boot.classId))) {
        switchView('class', { classId: boot.classId });
      } else if (state.currentView === 'results') {
        renderResults();
      } else if (state.currentView === 'certificates') {
        renderCertificates();
      } else if (state.currentView === 'settings') {
        window.ExamGuardStudentSettings?.init?.();
      }

      window.ExamGuardStudentNotifications?.refresh?.();
    } catch (error) {
      if (els.stream) els.stream.innerHTML = `<div class="sd-panel-empty">${esc(error.message)}</div>`;
    }
  }

  function bindEvents() {
    els.profileToggle?.addEventListener('click', (e) => {
      if (e.target.closest('.pg-dropdown')) return;
      e.stopPropagation();
      document.querySelectorAll('.pg-notify-panel.open').forEach((el) => el.classList.remove('open'));
      const open = els.profileMenu?.classList.contains('open');
      closeProfileMenu();
      if (!open) els.profileMenu?.classList.add('open');
    });
    document.addEventListener('click', () => closeProfileMenu());
    els.profileMenu?.addEventListener('click', (e) => e.stopPropagation());

    document.querySelectorAll('[data-sd-view]:not(.sd-view)').forEach((el) => {
      el.addEventListener('click', () => switchView(el.dataset.sdView));
    });

    document.getElementById('sdJoinClassOpen')?.addEventListener('click', openJoinModal);
    document.getElementById('sdStreamSeeAll')?.addEventListener('click', () => switchView('calendar'));

    els.upcoming?.addEventListener('click', (e) => {
      const row = e.target.closest('[data-upcoming-exam]');
      if (!row) return;
      openUpcomingExam(row.dataset.upcomingExam);
    });

    els.calPrev?.addEventListener('click', () => shiftCalendarPeriod(-1));
    els.calNext?.addEventListener('click', () => shiftCalendarPeriod(1));
    els.calToday?.addEventListener('click', goCalendarToday);

    els.calClassFilter?.addEventListener('change', (e) => {
      state.calendarClassFilter = e.target.value;
      renderCalendar();
    });

    document.querySelectorAll('[data-cal-view-mode]').forEach((btn) => {
      btn.addEventListener('click', () => setCalendarViewMode(btn.dataset.calViewMode));
    });

    els.calendarGrid?.addEventListener('keydown', (e) => {
      const cell = e.target.closest('[data-cal-date]');
      if (!cell || cell.classList.contains('outside') || (e.key !== 'Enter' && e.key !== ' ')) return;
      e.preventDefault();
      selectCalendarDay(cell.dataset.calDate, cell);
    });

    els.calendarGrid?.addEventListener('click', (e) => {
      const moreBtn = e.target.closest('.sd-cal-more');
      if (moreBtn) {
        e.preventDefault();
        const cell = moreBtn.closest('[data-cal-date]');
        if (cell) selectCalendarDay(cell.dataset.calDate, cell);
        return;
      }

      const pill = e.target.closest('.sd-cal-pill');
      if (pill) {
        e.stopPropagation();
        const type = pill.dataset.calType;
        if (type === 'live') goToExam(pill.dataset.calExam);
        else openUpcomingExam(pill.dataset.calExam);
        return;
      }

      const cell = e.target.closest('[data-cal-date]');
      if (!cell || cell.classList.contains('outside')) return;
      selectCalendarDay(cell.dataset.calDate, cell);
    });

    els.todo?.addEventListener('click', (e) => {
      const row = e.target.closest('[data-todo-action]');
      if (!row) return;
      const action = row.dataset.todoAction;
      if (action === 'join') openJoinModal();
      else if (action === 'system-check' || action === 'exam-key') switchView('exam-room');
    });
    document.getElementById('sdClassesSeeAll')?.addEventListener('click', () => {
      const sidebar = document.getElementById('sdSidebar');
      const classSection = sidebar?.querySelectorAll('.pg-nav-label')[1];
      classSection?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      sidebar?.querySelector('.sd-class-item, .sd-join-link')?.classList.add('sd-sidebar-flash');
      window.setTimeout(() => {
        sidebar?.querySelector('.sd-sidebar-flash')?.classList.remove('sd-sidebar-flash');
      }, 1200);
    });
    document.getElementById('sdJoinClose')?.addEventListener('click', closeJoinModal);
    els.joinModal?.addEventListener('click', (e) => {
      if (e.target === els.joinModal) closeJoinModal();
    });

    els.joinCode?.addEventListener('input', (e) => {
      e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
      if (els.joinSubmit) els.joinSubmit.disabled = e.target.value.length < 6;
    });

    els.joinForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const code = els.joinCode?.value.trim();
      if (!code) return;
      try {
        const { classroom } = await ExamGuardApi.joinClass(code);
        closeJoinModal();
        await loadDashboard();
        alert(`You joined ${classroom.name}.`);
      } catch (error) {
        alert(error.message);
      }
    });

    els.examKeyInput?.addEventListener('input', (e) => {
      e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
      const len = e.target.value.length;
      if (els.keyCount) els.keyCount.textContent = `${len} / 8`;
      if (els.enterExamBtn) els.enterExamBtn.disabled = len < 8;
      if (els.examKeyStatus && !els.examKeyStatus.hidden) {
        els.examKeyStatus.hidden = true;
      }
    });

    els.examKeyInput?.addEventListener('paste', (e) => {
      window.setTimeout(() => {
        // Prevent auto-enter on paste. Users must click "Enter exam room".
        const len = els.examKeyInput?.value.length || 0;
        if (els.keyCount) els.keyCount.textContent = `${Math.min(len, 8)} / 8`;
        if (els.enterExamBtn) els.enterExamBtn.disabled = len < 8;
      }, 0);
    });

    els.examKeyForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      await submitExamKey(els.examKeyInput?.value);
    });

    els.classQuickKey?.addEventListener('input', (e) => {
      e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
    });
    els.classQuickKey?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        submitExamKey(els.classQuickKey?.value, { focusRoom: true });
      }
    });
    els.classQuickKeyBtn?.addEventListener('click', () => {
      submitExamKey(els.classQuickKey?.value, { focusRoom: true });
    });

    els.liveEnter?.addEventListener('click', () => {
      if (state.liveExamId) goToExam(state.liveExamId);
    });
    els.liveDismiss?.addEventListener('click', () => {
      state.liveBannerDismissed = true;
      els.liveBanner?.classList.remove('visible');
    });

    document.getElementById('sdResultFilters')?.addEventListener('click', (e) => {
      const pill = e.target.closest('[data-filter]');
      if (!pill) return;
      state.resultFilter = pill.dataset.filter;
      pill.parentElement.querySelectorAll('.sd-pill').forEach((p) => p.classList.remove('active'));
      pill.classList.add('active');
      renderResults();
    });

    els.resultsList?.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-result-exam]');
      if (!btn) return;
      openResultModal(btn.dataset.resultExam);
    });

    document.getElementById('sdResultClose')?.addEventListener('click', closeResultModal);
    els.resultModal?.addEventListener('click', (e) => {
      if (e.target === els.resultModal) closeResultModal();
    });

    document.getElementById('sdCertModalOk')?.addEventListener('click', closeCertDownloadModal);
    els.certModal?.addEventListener('click', (e) => {
      if (e.target === els.certModal) closeCertDownloadModal();
    });

    window.addEventListener('hashchange', () => {
      const { view, classId } = parseHash();
      switchView(view, { classId });
    });
  }

  function init() {
    bindEvents();
    const { view, classId } = parseHash();
    switchView(view, { classId });
    loadDashboard();
  }

  function applyUserFromSettings(user) {
    state.user = user;
    if (els.avatarBtn) els.avatarBtn.textContent = initials(user.name);
    if (els.joinName) els.joinName.value = user.name;
    if (state.currentView === 'home') renderGreeting();
  }

  window.ExamGuardStudent = {
    switchView,
    refresh: loadDashboard,
    applyUser: applyUserFromSettings,
  };
  init();
})();
