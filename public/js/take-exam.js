const activeExamId = window.ExamGuardRoute?.resolveTakeExamId?.()
  || new URLSearchParams(location.search).get('examId');

let activeExam = null;
let activeAttempt = null;
let secondsRemaining = 0;
let examFinished = false;
let heartbeatTimer = null;
let pendingSubmitAuto = false;
const startedAt = new Date().toISOString();
let runtimeStarted = false;
let preflightPassed = false;
let preflightAudioStream = null;
let preflightVideoStream = null;
let micMonitorStream = null;
let audioMonitorTimer = null;
let maxWarningAction = 'notify';

const els = {
  title: document.getElementById('activeExamTitle'),
  className: document.getElementById('activeExamClass'),
  instructions: document.getElementById('activeExamInstructions'),
  instructionsWrap: document.getElementById('instructionsWrap'),
  instructionsBlock: document.getElementById('instructionsBlock'),
  instructionsToggle: document.getElementById('instructionsToggle'),
  questions: document.getElementById('examQuestions'),
  checklist: document.getElementById('questionChecklist'),
  timer: document.getElementById('examTimer'),
  timerWrap: document.getElementById('examTimerWrap'),
  warningCount: document.getElementById('warningCount'),
  warningLimit: document.getElementById('warningLimitDisplay'),
  warningBadge: document.getElementById('warningBadge'),
  submitBtns: () => document.querySelectorAll('.te-submit-btn'),
  confirmModal: document.getElementById('submitConfirmModal'),
  confirmOk: document.getElementById('submitConfirmOk'),
  confirmCancel: document.getElementById('submitConfirmCancel'),
  unansweredModal: document.getElementById('unansweredConfirmModal'),
  unansweredBack: document.getElementById('unansweredConfirmBack'),
  unansweredSubmit: document.getElementById('unansweredConfirmSubmit'),
  submitSuccessModal: document.getElementById('submitSuccessModal'),
  submitSuccessTitle: document.getElementById('submitSuccessTitle'),
  submitSuccessScore: document.getElementById('submitSuccessScore'),
  submitSuccessMessage: document.getElementById('submitSuccessMessage'),
  submitSuccessOk: document.getElementById('submitSuccessOk'),
  cameraToggle: document.getElementById('teCameraToggle'),
  cameraExpand: document.getElementById('teCameraExpand'),
  cameraDot: document.getElementById('cameraStatusDot'),
  submitBottom: document.getElementById('submitExamBtnBottom'),
};

function openUnansweredConfirm() {
  return new Promise((resolve) => {
    if (!els.unansweredModal) return resolve(false);
    els.unansweredModal.hidden = false;

    const cleanup = () => {
      els.unansweredModal.hidden = true;
      els.unansweredBack?.removeEventListener('click', onBack);
      els.unansweredSubmit?.removeEventListener('click', onSubmit);
      els.unansweredModal?.removeEventListener('click', onBackdrop);
      document.removeEventListener('keydown', onKeyDown);
    };

    const onBack = () => { cleanup(); resolve(false); };
    const onSubmit = () => { cleanup(); resolve(true); };
    const onBackdrop = (e) => { if (e.target === els.unansweredModal) onBack(); };
    const onKeyDown = (e) => { if (e.key === 'Escape') onBack(); };

    els.unansweredBack?.addEventListener('click', onBack);
    els.unansweredSubmit?.addEventListener('click', onSubmit);
    els.unansweredModal?.addEventListener('click', onBackdrop);
    document.addEventListener('keydown', onKeyDown);
  });
}

function openSubmitSuccessModal({ score, total, autoSubmitted }) {
  return new Promise((resolve) => {
    if (!els.submitSuccessModal) {
      resolve();
      return;
    }

    if (els.submitSuccessTitle) {
      els.submitSuccessTitle.textContent = autoSubmitted ? 'Session ended' : 'Exam submitted';
    }
    if (els.submitSuccessScore) {
      els.submitSuccessScore.innerHTML = `${score}<span>/${total}</span>`;
    }
    if (els.submitSuccessMessage) {
      els.submitSuccessMessage.textContent = autoSubmitted
        ? 'Your session ended and your exam was submitted automatically.'
        : 'Your answers have been recorded. You can review your results on the dashboard.';
    }

    els.submitSuccessModal.hidden = false;

    const cleanup = () => {
      els.submitSuccessModal.hidden = true;
      els.submitSuccessOk?.removeEventListener('click', onOk);
      document.removeEventListener('keydown', onKeyDown);
    };

    const onOk = () => { cleanup(); resolve(); };
    const onKeyDown = (e) => { if (e.key === 'Enter' || e.key === 'Escape') onOk(); };

    els.submitSuccessOk?.addEventListener('click', onOk);
    document.addEventListener('keydown', onKeyDown);
    els.submitSuccessOk?.focus();
  });
}

function setExamMeta(title, className) {
  if (els.title) els.title.textContent = title;
  if (els.className) els.className.textContent = className;
}

function updateSidebarMeta(exam) {
  const prof = document.getElementById('sidebarProfessor');
  const countEl = document.getElementById('sidebarQuestionCount');
  const due = document.getElementById('sidebarDueDate');
  if (prof) {
    prof.textContent = exam.professorName ? `Prof. ${exam.professorName}` : 'Instructor';
  }
  const n = exam.questionCount ?? exam.questions?.length ?? 0;
  if (countEl) countEl.textContent = `${n} question${n === 1 ? '' : 's'}`;
  if (due) {
    if (exam.closesAt) {
      const d = new Date(exam.closesAt);
      due.textContent = `Due ${d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}`;
      due.hidden = false;
    } else {
      due.hidden = true;
    }
  }
}

function setupScrollProgress() {
  const fill = document.getElementById('scrollProgressFill');
  if (!fill) return;
  const update = () => {
    const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
    const pct = scrollHeight > 0 ? (window.scrollY / scrollHeight) * 100 : 0;
    fill.style.height = `${Math.min(100, Math.max(0, pct))}%`;
  };
  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
}

function setPfStatus(id, ok, text) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.remove('pass', 'fail');
  if (ok === true) el.classList.add('pass');
  if (ok === false) el.classList.add('fail');
  el.textContent = text;
}

function setPfMsg(id, msg) {
  const el = document.getElementById(id);
  if (el) el.textContent = msg;
}

async function stopStream(stream) {
  if (!stream) return;
  stream.getTracks().forEach((t) => t.stop());
}

async function runPreflightChecks() {
  const gate = document.getElementById('preflightGate');
  const proceed = document.getElementById('pfProceedBtn');

  preflightPassed = false;
  if (proceed) proceed.disabled = true;
  if (gate) gate.hidden = false;

  setPfStatus('pfCameraStatus', null, 'Checking…');
  setPfStatus('pfMicStatus', null, 'Checking…');
  setPfStatus('pfBrowserStatus', null, 'Checking…');
  setPfStatus('pfNetStatus', null, 'Checking…');
  setPfStatus('pfReadyStatus', null, 'Pending');

  let cameraOk = false;
  let micOk = false;
  let browserOk = false;
  let netOk = false;

  // Browser compatibility
  browserOk = Boolean(
    navigator.mediaDevices?.getUserMedia
    && navigator.mediaDevices?.enumerateDevices
    && window.fetch
    && window.Promise,
  );
  setPfStatus('pfBrowserStatus', browserOk, browserOk ? 'Pass' : 'Fail');
  setPfMsg('pfBrowserMsg', browserOk
    ? 'Browser supports required APIs.'
    : 'Please use a modern Chromium-based browser (Chrome / Edge).');

  // Camera + Mic permissions (required)
  try {
    await stopStream(preflightVideoStream);
    preflightVideoStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
    cameraOk = true;
    setPfStatus('pfCameraStatus', true, 'Pass');
    setPfMsg('pfCameraMsg', 'Camera detected and accessible.');
  } catch (e) {
    cameraOk = false;
    setPfStatus('pfCameraStatus', false, 'Fail');
    setPfMsg('pfCameraMsg', 'Enable camera permission, then retry.');
  }

  try {
    await stopStream(preflightAudioStream);
    preflightAudioStream = await navigator.mediaDevices.getUserMedia({ video: false, audio: true });
    micOk = true;
    setPfStatus('pfMicStatus', true, 'Pass');
    setPfMsg('pfMicMsg', 'Microphone detected and accessible.');
  } catch (e) {
    micOk = false;
    setPfStatus('pfMicStatus', false, 'Fail');
    setPfMsg('pfMicMsg', 'Enable microphone permission, then retry.');
  }

  // Network check (required): quick ping to same-origin
  try {
    const t0 = performance.now();
    await fetch('/api/auth/me', { method: 'GET', credentials: 'same-origin' });
    const ms = Math.round(performance.now() - t0);
    netOk = true;
    setPfStatus('pfNetStatus', true, 'Pass');
    setPfMsg('pfNetMsg', `Connection looks OK (${ms}ms).`);
  } catch (e) {
    // fallback ping when auth endpoint is unavailable
    try {
      await fetch('/', { method: 'GET', credentials: 'same-origin' });
      netOk = true;
      setPfStatus('pfNetStatus', true, 'Pass');
      setPfMsg('pfNetMsg', 'Connection looks OK.');
    } catch (_) {
      netOk = false;
      setPfStatus('pfNetStatus', false, 'Fail');
      setPfMsg('pfNetMsg', 'Internet connection failed. Check your network, then retry.');
    }
  }

  const requiredOk = cameraOk && micOk && browserOk && netOk;
  setPfStatus('pfReadyStatus', requiredOk, requiredOk ? 'Ready' : 'Pending');
  setPfMsg('pfReadyMsg', requiredOk ? 'All required checks passed.' : 'Fix failed checks and retry.');

  preflightPassed = requiredOk;
  if (proceed) proceed.disabled = !requiredOk;
}

function blockExam(message) {
  setExamMeta('Exam unavailable', '');
  if (els.instructions) els.instructions.textContent = message;
  if (els.instructionsWrap) els.instructionsWrap.hidden = false;
  if (els.instructionsBlock) els.instructionsBlock.hidden = false;
  els.submitBtns().forEach((btn) => { btn.disabled = true; });
}

function showMaxWarningsModal(action) {
  const modal = document.getElementById('maxWarningsModal');
  const msg = document.getElementById('maxWarningsMessage');
  if (!modal || !msg) return;

  const actionText = action || 'notify';
  if (actionText === 'auto_submit') {
    msg.textContent = 'You have reached the maximum number of warnings. Your exam has been automatically submitted.';
  } else if (actionText === 'lock') {
    msg.textContent = 'You have reached the maximum number of warnings. Your exam has been locked. Your professor has been notified.';
  } else {
    msg.textContent = 'You have reached the maximum number of warnings. Your professor has been notified.';
  }

  modal.hidden = false;
}

function returnToStudentDashboard() {
  window.ExamGuardRoute?.clearTakeExamId?.();
  location.href = '/student';
}

function lockExamUI() {
  examFinished = true;
  if (heartbeatTimer) {
    clearInterval(heartbeatTimer);
    heartbeatTimer = null;
  }
  // disable all inputs so student cannot continue
  document.querySelectorAll('input, button').forEach((el) => {
    if (el.id === 'submitConfirmCancel' || el.id === 'submitConfirmOk') return;
    if (el.id === 'maxWarningsReturn') return;
    if (el.id === 'maxWarningsModal') return;
    if (el.closest && el.closest('#maxWarningsModal')) return;
    if (el.closest && el.closest('#preflightGate')) return;
    if (el.id === 'pfRetryBtn') return;
    if (el.tagName === 'BUTTON' && el.id === 'pfProceedBtn') return;
    el.disabled = true;
  });
}

function setSubmitDisabled(disabled) {
  els.submitBtns().forEach((btn) => { btn.disabled = disabled; });
  if (els.submitBottom) els.submitBottom.disabled = disabled;
}

function updateCameraDot(status) {
  const dot = els.cameraDot;
  if (!dot) return;
  dot.className = 'te-cam-dot';
  if (status === 'Active') dot.classList.add('ok');
  else if (status === 'Blocked') dot.classList.add('bad');
  else dot.classList.add('warn');
}

function toggleCameraExpand() {
  const open = els.cameraExpand?.hidden !== false;
  if (els.cameraExpand) els.cameraExpand.hidden = !open;
  els.cameraToggle?.setAttribute('aria-expanded', String(open));
}

function closeCameraExpand() {
  if (els.cameraExpand) els.cameraExpand.hidden = true;
  els.cameraToggle?.setAttribute('aria-expanded', 'false');
}

async function startSession() {
  try {
    const { attempt } = await ExamGuardApi.startExamSession(activeExamId);
    activeAttempt = attempt;

    if (activeAttempt?.startedAt) {
      const elapsed = Math.floor((Date.now() - new Date(activeAttempt.startedAt).getTime()) / 1000);
      secondsRemaining = Math.max(0, (activeExam.timeLimit * 60) - elapsed);
    }

    window.ExamGuardSession = {
      examId: activeExamId,
      attemptId: activeAttempt.id,
      async reportViolation(payload) {
        if (!activeAttempt?.id || examFinished) return null;
        try {
          const result = await ExamGuardApi.reportViolation(activeExamId, activeAttempt.id, {
            type: payload.type,
            severity: payload.severity,
            message: payload.message,
            snapshot: payload.snapshot || null,
            occurredAt: payload.occurredAt,
          });
          if (els.warningCount && typeof result?.warningCount === 'number') {
            els.warningCount.textContent = String(result.warningCount);
            syncWarningBadgeColor();
          }
          return result;
        } catch (_) {
          return null;
        }
      },
    };

    if (els.warningCount && typeof activeAttempt?.warningCount === 'number') {
      els.warningCount.textContent = String(activeAttempt.warningCount);
    }
    document.dispatchEvent(new CustomEvent('examguard:session-started', {
      detail: { warningCount: activeAttempt?.warningCount || 0 },
    }));

    heartbeatTimer = setInterval(async () => {
      if (examFinished || !activeAttempt?.id) return;
      try {
        await ExamGuardApi.examHeartbeat(activeExamId, activeAttempt.id);
      } catch (_) {}
    }, 20000);
  } catch (error) {
    if (error.code === 'violation_exceeded') {
      blockExam(error.message || 'Maximum proctoring violations exceeded. You cannot continue this exam.');
      throw error;
    }
    blockExam(error.message);
    throw error;
  }
}

function beginExamRuntime() {
  if (runtimeStarted) return;
  runtimeStarted = true;
  syncWarningBadgeColor();
  updateCameraDot(document.getElementById('cameraStatus')?.textContent || 'Waiting');
  setupScrollProgress();

  updateTimer();
  setInterval(() => {
    if (examFinished) return;
    secondsRemaining -= 1;
    updateTimer();
    if (secondsRemaining <= 0) requestSubmit(true);
  }, 1000);
}

function isQuestionAnswered(index) {
  return Boolean(document.querySelector(`input[name="question-${index}"]:checked`));
}

function updateProgress() {
  if (!els.checklist) return;
  els.checklist.querySelectorAll('.te-check-item').forEach((item, i) => {
    item.classList.toggle('done', isQuestionAnswered(i));
  });
}

function bindChoiceHighlight(label, input) {
  const sync = () => {
    label.closest('.te-question')?.querySelectorAll('.te-choice').forEach((el) => {
      el.classList.remove('selected');
    });
    if (input.checked) label.classList.add('selected');
    updateProgress();
  };
  input.addEventListener('change', sync);
  sync();
}

function buildChecklist(total) {
  if (!els.checklist) return;
  els.checklist.innerHTML = '';
  for (let i = 0; i < total; i += 1) {
    const li = document.createElement('li');
    li.className = 'te-check-item';
    li.setAttribute('aria-label', `Question ${i + 1}`);
    const box = document.createElement('span');
    box.className = 'te-check-box';
    box.setAttribute('aria-hidden', 'true');
    const label = document.createElement('span');
    label.className = 'te-check-label';
    label.textContent = `Q${i + 1}`;
    li.append(box, label);
    els.checklist.appendChild(li);
  }
}

function toggleInstructions() {
  const expanded = els.instructionsBlock?.hidden !== false;
  if (els.instructionsBlock) els.instructionsBlock.hidden = !expanded;
  const toggleText = els.instructionsToggle?.querySelector('.te-instructions-toggle-text');
  if (toggleText) {
    toggleText.textContent = expanded ? 'Hide instructions' : 'View instructions';
  }
  if (els.instructionsToggle) {
    els.instructionsToggle.setAttribute('aria-expanded', String(expanded));
  }
}

async function renderExam() {
  if (!activeExamId) {
    return blockExam('Return to the student dashboard and select an assigned exam.');
  }

  try {
    ({ exam: activeExam } = await ExamGuardApi.exam(activeExamId));
  } catch (error) {
    if (error.code === 'violation_exceeded') {
      return blockExam(error.message || 'Maximum proctoring violations exceeded. You cannot continue this exam.');
    }
    return blockExam(error.message);
  }

  secondsRemaining = activeExam.timeLimit * 60;
  setExamMeta(activeExam.title, activeExam.className || 'Class exam');
  updateSidebarMeta(activeExam);
  if (els.warningLimit) els.warningLimit.textContent = activeExam.warningLimit;
  syncWarningBadgeColor();

  if (activeExam.instructions?.trim()) {
    if (els.instructions) els.instructions.textContent = activeExam.instructions;
    if (els.instructionsWrap) els.instructionsWrap.hidden = false;
  }

  maxWarningAction = activeExam.maxWarningAction || 'notify';
  window.ExamGuardProctoring = {
    maxWarningAction,
    triggers: activeExam.proctoringTriggers || null,
  };
  document.dispatchEvent(new CustomEvent('examguard:warning-limit', { detail: activeExam.warningLimit }));

  buildChecklist(activeExam.questions.length);

  if (!els.questions) return;
  els.questions.innerHTML = '';
  activeExam.questions.forEach((question, questionIndex) => {
    const card = document.createElement('article');
    card.className = 'te-question';
    card.id = `question-card-${questionIndex}`;
    const title = document.createElement('h3');
    const num = document.createElement('span');
    num.className = 'te-q-num';
    num.textContent = `${questionIndex + 1}.`;
    const text = document.createElement('span');
    text.className = 'te-q-text';
    text.textContent = question.question;
    title.append(num, text);
    card.append(title);

    const choicesWrap = document.createElement('div');
    choicesWrap.className = 'te-choices';
    question.choices.forEach((choice, choiceIndex) => {
      const label = document.createElement('label');
      label.className = 'te-choice';
      const input = document.createElement('input');
      input.type = 'radio';
      input.name = `question-${questionIndex}`;
      input.value = choiceIndex;
      const text = document.createElement('span');
      text.textContent = choice;
      label.append(input, text);
      bindChoiceHighlight(label, input);
      choicesWrap.append(label);
    });
    card.append(choicesWrap);
    els.questions.appendChild(card);
  });

  // Resume an in-progress attempt (e.g. page refresh) without re-running preflight.
  const resumeAttempt = activeExam.attempt;
  if (resumeAttempt?.violationLocked) {
    return blockExam('Maximum proctoring violations exceeded. You cannot continue this exam.');
  }
  if (resumeAttempt?.status === 'in_progress' || resumeAttempt?.status === 'disconnected') {
    if (resumeAttempt.startedAt) {
      const elapsed = Math.floor((Date.now() - new Date(resumeAttempt.startedAt).getTime()) / 1000);
      secondsRemaining = Math.max(0, (activeExam.timeLimit * 60) - elapsed);
    }
    document.getElementById('preflightGate')?.setAttribute('hidden', 'hidden');
    preflightPassed = true;
    try {
      await startSession();
      beginExamRuntime();
    } catch (_) {
      return;
    }
    return;
  }

  // Mandatory pre-flight gate (fresh start only)
  await runPreflightChecks();

  const retryBtn = document.getElementById('pfRetryBtn');
  retryBtn?.addEventListener('click', () => runPreflightChecks());

  const proceedBtn = document.getElementById('pfProceedBtn');
  proceedBtn?.addEventListener('click', async () => {
    if (!preflightPassed) return;

    // stop preflight streams (monitoring.js will request its own)
    await stopStream(preflightVideoStream);
    await stopStream(preflightAudioStream);
    preflightVideoStream = null;
    preflightAudioStream = null;

    document.getElementById('preflightGate')?.setAttribute('hidden', 'hidden');

    try {
      await startSession();
    } catch (_) {
      return;
    }

    beginExamRuntime();
  }, { once: true });

  // wait for proceed click
}

function updateTimer() {
  const minutes = Math.floor(Math.max(secondsRemaining, 0) / 60);
  const seconds = Math.max(secondsRemaining, 0) % 60;
  if (els.timer) {
    els.timer.textContent = `${minutes}:${String(seconds).padStart(2, '0')}`;
  }
  if (!els.timerWrap) return;
  els.timerWrap.classList.remove('te-timer--warn', 'te-timer--danger');
  if (secondsRemaining <= 300) els.timerWrap.classList.add('te-timer--danger');
  else if (secondsRemaining <= 600) els.timerWrap.classList.add('te-timer--warn');
}

function flashWarningBadge() {
  if (!els.warningBadge) return;
  els.warningBadge.classList.remove('te-warn-flash');
  void els.warningBadge.offsetWidth;
  els.warningBadge.classList.add('te-warn-flash');
  window.setTimeout(() => els.warningBadge?.classList.remove('te-warn-flash'), 600);
  syncWarningBadgeColor();
}

function syncWarningBadgeColor() {
  if (!els.warningBadge) return;
  const count = Number(els.warningCount?.textContent) || 0;
  const limit = Number(els.warningLimit?.textContent) || 3;
  els.warningBadge.classList.remove('eg-badge-warning', 'eg-badge-danger');
  els.warningBadge.classList.remove('te-warn-orange', 'te-warn-max');
  if (count >= limit) {
    els.warningBadge.classList.add('eg-badge-danger');
    els.warningBadge.classList.add('te-warn-max');
  } else if (limit > 0 && count === limit - 1) {
    // orange escalation using same warning token, stronger
    els.warningBadge.classList.add('eg-badge-warning');
    els.warningBadge.classList.add('te-warn-orange');
  } else {
    els.warningBadge.classList.add('eg-badge-warning');
  }
}

function openSubmitConfirm(autoSubmitted) {
  pendingSubmitAuto = autoSubmitted;
  if (els.confirmModal) els.confirmModal.hidden = false;
}

function closeSubmitConfirm() {
  if (els.confirmModal) els.confirmModal.hidden = true;
}

function requestSubmit(autoSubmitted = false) {
  if (examFinished) return;
  if (autoSubmitted) {
    finishExam(true);
    return;
  }
  openSubmitConfirm(false);
}

async function finishExam(autoSubmitted = false) {
  if (examFinished || !activeExam) return;
  closeSubmitConfirm();

  const answers = activeExam.questions.map((question, index) => {
    const selected = document.querySelector(`input[name="question-${index}"]:checked`);
    return selected ? Number(selected.value) : null;
  });

  if (!autoSubmitted && answers.includes(null)) {
    const ok = await openUnansweredConfirm();
    if (!ok) return;
  }

  examFinished = true;
  if (heartbeatTimer) clearInterval(heartbeatTimer);
  window.ExamGuardSession = null;
  setSubmitDisabled(true);

  try {
    const { attempt } = await ExamGuardApi.submitExam(activeExamId, {
      answers,
      startedAt: activeAttempt?.startedAt || startedAt,
    });
    await openSubmitSuccessModal({
      score: attempt.score,
      total: attempt.total,
      autoSubmitted,
    });
    window.ExamGuardRoute?.clearTakeExamId?.();
    location.href = '/student#results';
  } catch (error) {
    examFinished = false;
    setSubmitDisabled(false);
    alert(error.message);
  }
}

els.submitBtns().forEach((btn) => {
  btn.addEventListener('click', () => requestSubmit(false));
});

els.confirmCancel?.addEventListener('click', closeSubmitConfirm);
els.confirmOk?.addEventListener('click', () => finishExam(pendingSubmitAuto));
els.confirmModal?.addEventListener('click', (e) => {
  if (e.target === els.confirmModal) closeSubmitConfirm();
});

els.instructionsToggle?.addEventListener('click', toggleInstructions);

els.cameraToggle?.addEventListener('click', (e) => {
  e.stopPropagation();
  toggleCameraExpand();
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeCameraExpand();
});

document.addEventListener('examguard:locked', () => finishExam(true));
document.addEventListener('examguard:warning-added', flashWarningBadge);

// Max warning outcome handler (non-dismissible modal)
document.addEventListener('examguard:max-warnings', async (e) => {
  const action = e.detail?.action || maxWarningAction || 'notify';
  showMaxWarningsModal(action);

  if (action === 'auto_submit') {
    await finishExam(true);
    return;
  }

  lockExamUI();
});

document.getElementById('maxWarningsReturn')?.addEventListener('click', returnToStudentDashboard);

const cameraStatusEl = document.getElementById('cameraStatus');
if (cameraStatusEl) {
  const observer = new MutationObserver(() => {
    updateCameraDot(cameraStatusEl.textContent);
  });
  observer.observe(cameraStatusEl, { childList: true, characterData: true, subtree: true });
}

const tabStatusEl = document.getElementById('tabStatus');
if (tabStatusEl) {
  const observer = new MutationObserver(() => {
    if (!/active/i.test(tabStatusEl.textContent) && els.cameraDot) {
      els.cameraDot.className = 'te-cam-dot bad';
    }
  });
  observer.observe(tabStatusEl, { childList: true, characterData: true, subtree: true });
}

renderExam();
