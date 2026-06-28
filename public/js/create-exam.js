(function () {
  'use strict';

  const LETTERS = ['A', 'B', 'C', 'D'];

  const dialog = () => window.ExamGuardDialog;

  async function alertInfo(title, message) {
    await dialog()?.alert({ type: 'info', title, message, confirmLabel: 'OK' });
  }

  async function alertError(title, message) {
    await dialog()?.alert({
      type: 'error',
      title,
      message: message || 'Please try again.',
      confirmLabel: 'OK',
    });
  }

  async function confirmAction(options) {
    return Boolean(await dialog()?.confirm(options));
  }

  function plainText(el) {
    return (el?.innerText ?? el?.textContent ?? '').trim();
  }

  function truncate(text, len = 48) {
    const t = text.trim();
    return t.length <= len ? t : `${t.slice(0, len)}…`;
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

  function splitScheduleIso(iso) {
    if (!iso) return { date: '', time: '' };
    const local = toDatetimeLocalValue(iso);
    const [date, time = ''] = local.split('T');
    return { date, time };
  }

  function splitDatetimeLocalValue(value) {
    if (!value) return { date: '', time: '' };
    const [date, time = ''] = String(value).split('T');
    return { date, time };
  }

  function combineScheduleDateTime(dateValue, timeValue, fallbackTime) {
    if (!dateValue) return null;
    const time = timeValue || fallbackTime;
    const date = new Date(`${dateValue}T${time}`);
    return Number.isNaN(date.getTime()) ? null : date.toISOString();
  }

  function formatScheduleLabel(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, {
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    });
  }

  function initCreateExam(options = {}) {
    const root = options.root || document;
    const embedded = options.embedded ?? false;
    const onSaved = options.onSaved ?? null;

    const titleEl = root.querySelector('#examTitleInput');
    if (!titleEl || titleEl.dataset.initialized === '1') {
      return;
    }
    titleEl.dataset.initialized = '1';

    if (embedded) {
      initDocBuilder(root, onSaved);
      return;
    }

    initLegacyBuilder(root, onSaved);
  }

  function initDocBuilder(root, onSaved) {
    let currentQuestions = [];
    let activeIndex = -1;
    let editingExamId = null;
    let currentPhase = 'setup';
    let persistWorkTimer = null;

    const CREATE_EXAM_WORK_KEY = 'examguard:createExamWork';

    const setupPhase = root.querySelector('#createSetupPhase');
    const builderPhase = root.querySelector('#createBuilderPhase');
    const successPhase = root.querySelector('#createSuccessPhase');
    const publishOverlay = root.querySelector('#publishOverlay');
    const publishOverlayLabel = root.querySelector('#publishOverlayLabel');
    const publishSuccessTitle = root.querySelector('#publishSuccessTitle');
    const publishSuccessMeta = root.querySelector('#publishSuccessMeta');
    const publishSuccessLead = root.querySelector('#publishSuccessLead');
    const publishSuccessKeyWrap = root.querySelector('#publishSuccessKeyWrap');
    const publishSuccessKey = root.querySelector('#publishSuccessKey');
    const copyPublishKeyBtn = root.querySelector('#copyPublishKeyBtn');
    const viewPublishedExamsBtn = root.querySelector('#viewPublishedExamsBtn');
    const createAnotherExamBtn = root.querySelector('#createAnotherExamBtn');
    const continueBtn = root.querySelector('#continueToBuilderBtn');
    const editSetupBtn = root.querySelector('#editSetupBtn');
    const setupPhaseHint = root.querySelector('#setupPhaseHint');

    const fields = {
      title: root.querySelector('#examTitleInput'),
      instructions: root.querySelector('#instructionsInput'),
      timeLimit: root.querySelector('#timeLimitInput'),
      warningLimit: root.querySelector('#warningLimitInput'),
      classId: root.querySelector('#examClassInput'),
      opensDate: root.querySelector('#examOpensDateInput'),
      opensTime: root.querySelector('#examOpensTimeInput'),
      closesDate: root.querySelector('#examClosesDateInput'),
      closesTime: root.querySelector('#examClosesTimeInput'),
    };

    const SCHEDULE_DEFAULTS = {
      opensTime: '09:00',
      closesTime: '17:00',
    };

    function syncScheduleTimeState(dateEl, timeEl, defaultTime) {
      if (!dateEl || !timeEl) return;
      const hasDate = Boolean(dateEl.value);
      timeEl.disabled = !hasDate;
      if (!hasDate) {
        timeEl.value = '';
        return;
      }
      if (!timeEl.value) {
        timeEl.value = defaultTime;
      }
    }

    function applySchedulePair(dateEl, timeEl, iso, defaultTime) {
      if (!dateEl || !timeEl) return;
      const { date, time } = splitScheduleIso(iso);
      dateEl.value = date;
      timeEl.value = date ? (time || defaultTime) : '';
      syncScheduleTimeState(dateEl, timeEl, defaultTime);
    }

    function readScheduleValue(dateEl, timeEl, defaultTime) {
      if (!dateEl?.value) return null;
      return combineScheduleDateTime(dateEl.value, timeEl?.value, defaultTime);
    }

    function getScheduleFormValues() {
      return {
        opensDate: fields.opensDate?.value ?? '',
        opensTime: fields.opensTime?.value ?? '',
        closesDate: fields.closesDate?.value ?? '',
        closesTime: fields.closesTime?.value ?? '',
      };
    }

    function setScheduleFormValues(values = {}) {
      if (fields.opensDate) fields.opensDate.value = values.opensDate ?? '';
      if (fields.opensTime) fields.opensTime.value = values.opensTime ?? '';
      if (fields.closesDate) fields.closesDate.value = values.closesDate ?? '';
      if (fields.closesTime) fields.closesTime.value = values.closesTime ?? '';
      syncScheduleTimeState(fields.opensDate, fields.opensTime, SCHEDULE_DEFAULTS.opensTime);
      syncScheduleTimeState(fields.closesDate, fields.closesTime, SCHEDULE_DEFAULTS.closesTime);
    }
    const docQuestions = root.querySelector('#docQuestions');
    const docDraft = root.querySelector('#docDraft');
    const questionStack = root.querySelector('#questionStack');
    const questionCount = root.querySelector('#questionCount');
    const quizToolbarTitle = root.querySelector('#quizToolbarTitle');
    const quizToolbarCount = root.querySelector('#quizToolbarCount');
    const saveDraftBtn = root.querySelector('#saveDraftBtn');
    const publishBtn = root.querySelector('#publishExamBtn');
    const saveHint = root.querySelector('#createSaveHint');
    const setupForm = root.querySelector('.pg-create-setup-form');

    const SAVE_DRAFT_BTN_HTML = '<i class="ti ti-device-floppy"></i> Save draft';
    const SAVING_DRAFT_BTN_HTML = '<i class="ti ti-loader-2"></i> Saving…';
    const PUBLISH_BTN_HTML = '<i class="ti ti-send"></i> Publish';
    const PUBLISHING_BTN_HTML = '<i class="ti ti-loader-2"></i> Publishing…';
    const MIN_ACTION_MS = 900;

    function syncCreateExamRoute() {
      window.ExamGuardProfessor?.syncCreateExamUrl?.(currentPhase, editingExamId || null);
    }

    function clearCreateExamWork() {
      try {
        sessionStorage.removeItem(CREATE_EXAM_WORK_KEY);
      } catch (_) {}
    }

    function schedulePersistCreateExamWork() {
      if (editingExamId) {
        syncCreateExamRoute();
        return;
      }
      window.clearTimeout(persistWorkTimer);
      persistWorkTimer = window.setTimeout(persistCreateExamWork, 250);
    }

    function persistCreateExamWork() {
      if (editingExamId) {
        syncCreateExamRoute();
        return;
      }
      try {
        sessionStorage.setItem(CREATE_EXAM_WORK_KEY, JSON.stringify({
          phase: currentPhase,
          title: fields.title?.value ?? '',
          instructions: fields.instructions?.value ?? '',
          timeLimit: fields.timeLimit?.value ?? '',
          warningLimit: fields.warningLimit?.value ?? '3',
          classId: fields.classId?.value ?? '',
          ...getScheduleFormValues(),
          questions: currentQuestions,
          activeIndex,
        }));
      } catch (_) {}
      syncCreateExamRoute();
    }

    function restoreWorkInProgress(phaseHint) {
      clearSetupErrors();
      clearSaveHintError();
      loadClasses();

      try {
        const raw = sessionStorage.getItem(CREATE_EXAM_WORK_KEY);
        if (raw) {
          const data = JSON.parse(raw);
          fields.title.value = data.title ?? '';
          fields.instructions.value = data.instructions ?? '';
          fields.timeLimit.value = data.timeLimit ?? '';
          fields.warningLimit.value = data.warningLimit ?? '3';
          fields.classId.value = data.classId ?? '';
          const scheduleValues = {
            opensDate: data.opensDate ?? '',
            opensTime: data.opensTime ?? '',
            closesDate: data.closesDate ?? '',
            closesTime: data.closesTime ?? '',
          };
          if (!scheduleValues.opensDate && data.opensAt) {
            Object.assign(scheduleValues, {
              opensDate: splitDatetimeLocalValue(data.opensAt).date,
              opensTime: splitDatetimeLocalValue(data.opensAt).time,
            });
          }
          if (!scheduleValues.closesDate && data.closesAt) {
            Object.assign(scheduleValues, {
              closesDate: splitDatetimeLocalValue(data.closesAt).date,
              closesTime: splitDatetimeLocalValue(data.closesAt).time,
            });
          }
          setScheduleFormValues(scheduleValues);
          currentQuestions = Array.isArray(data.questions) ? data.questions : [];
          activeIndex = Number.isInteger(data.activeIndex) ? data.activeIndex : -1;
          applyPhase(phaseHint || data.phase || 'setup');
          renderAll();
          updateSetupPhaseState();
          updateQuizToolbar();
          syncCreateExamRoute();
          return true;
        }
      } catch (_) {}

      if (phaseHint && phaseHint !== 'setup') {
        applyPhase(phaseHint);
        renderAll();
        updateSetupPhaseState();
        syncCreateExamRoute();
        return true;
      }

      return false;
    }

    function getRouteState() {
      return { phase: currentPhase, examId: editingExamId };
    }

    function clearSetupErrors() {
      if (!setupForm) return;
      setupForm.querySelectorAll('[data-field-error]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
      });
      setupForm.querySelectorAll('.pg-create-setup-field-wrap.is-error').forEach((wrap) => {
        wrap.classList.remove('is-error');
        wrap.querySelector('input, textarea, select')?.removeAttribute('aria-invalid');
      });
    }

    function clearSetupFieldError(fieldName) {
      const wrap = setupForm?.querySelector(`[data-field="${fieldName}"]`);
      if (!wrap) return;
      wrap.classList.remove('is-error');
      const errorEl = wrap.querySelector('[data-field-error]');
      if (errorEl) {
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
      }
      wrap.querySelectorAll('input, textarea, select').forEach((el) => {
        el.removeAttribute('aria-invalid');
      });
    }

    function setSetupFieldError(fieldName, message) {
      const wrap = setupForm?.querySelector(`[data-field="${fieldName}"]`);
      if (!wrap) return false;
      const input = wrap.querySelector('input:not(:disabled), textarea, select');
      const errorEl = wrap.querySelector('[data-field-error]');
      wrap.classList.add('is-error');
      input?.setAttribute('aria-invalid', 'true');
      if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
      }
      return Boolean(input);
    }

    function getSetupErrors() {
      const errors = {};
      if (!fields.title?.value.trim()) errors.title = 'Enter an exam title.';
      if (!fields.instructions?.value.trim()) errors.instructions = 'Enter instructions for students.';
      const timeLimit = Number(fields.timeLimit?.value);
      if (!Number.isInteger(timeLimit) || timeLimit < 1) {
        errors.timeLimit = 'Enter a time limit of at least 1 minute.';
      }
      const opensAt = readScheduleValue(
        fields.opensDate,
        fields.opensTime,
        SCHEDULE_DEFAULTS.opensTime,
      );
      const closesAt = readScheduleValue(
        fields.closesDate,
        fields.closesTime,
        SCHEDULE_DEFAULTS.closesTime,
      );
      if (opensAt && closesAt && new Date(closesAt) <= new Date(opensAt)) {
        errors.closesAt = 'Close time must be after open time.';
      }
      return errors;
    }

    function showSetupErrors(errors) {
      clearSetupErrors();
      let firstInput = null;
      Object.entries(errors).forEach(([field, message]) => {
        if (setSetupFieldError(field, message) && !firstInput) {
          firstInput = setupForm?.querySelector(
            `[data-field="${field}"] input:not(:disabled), [data-field="${field}"] textarea, [data-field="${field}"] select`,
          );
        }
      });
      firstInput?.focus();
      firstInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return Object.keys(errors).length > 0;
    }

    function clearInlineErrors(scope) {
      if (!scope) return;
      scope.querySelectorAll('.pg-create-field-error').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
      });
      scope.querySelectorAll('.is-error').forEach((el) => el.classList.remove('is-error'));
    }

    function ensureFieldErrorEl(container) {
      let el = container.querySelector(':scope > .pg-create-field-error');
      if (!el) {
        el = document.createElement('p');
        el.className = 'pg-create-field-error hidden';
        el.setAttribute('role', 'alert');
        container.appendChild(el);
      }
      return el;
    }

    function setInlineError(container, message) {
      if (!container) return;
      const errorEl = ensureFieldErrorEl(container);
      if (message) {
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
        container.classList.add('is-error');
      } else {
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
        container.classList.remove('is-error');
      }
    }

    function questionFieldErrors(item) {
      const errors = {};
      if (!item.question) errors.question = 'Enter a question.';
      LETTERS.forEach((letter, i) => {
        if (!item.choices[i]) errors[`choice-${i}`] = `Enter choice ${letter}.`;
      });
      if (item.correctAnswer < 0 || item.correctAnswer > 3) {
        errors.correct = 'Select the correct answer.';
      }
      if (!item.explanation) errors.explanation = 'Add an explanation.';
      return errors;
    }

    function showQuestionErrors(container, errors) {
      clearInlineErrors(container);
      if (errors.question) setInlineError(container.querySelector('.pg-quiz-q-head'), errors.question);
      LETTERS.forEach((_, i) => {
        const key = `choice-${i}`;
        if (errors[key]) {
          setInlineError(container.querySelectorAll('.pg-quiz-opt')[i], errors[key]);
        }
      });
      if (errors.correct) setInlineError(container.querySelector('.pg-quiz-options'), errors.correct);
      if (errors.explanation) {
        setInlineError(container.querySelector('.pg-quiz-explanation'), errors.explanation);
      }
      const first = container.querySelector(
        '.is-error .pg-quiz-q-text, .is-error .pg-quiz-opt-text, .is-error .pg-quiz-explanation-text',
      );
      first?.focus?.();
      container.scrollIntoView?.({ behavior: 'smooth', block: 'center' });
    }

    function showDraftErrors(errors) {
      const block = docDraft?.querySelector('.pg-quiz-draft-block');
      if (!block) return;
      showQuestionErrors(block, errors);
    }

    function clearSaveHintError() {
      if (!saveHint) return;
      saveHint.classList.remove('is-error');
    }

    function setSaveHintError(message) {
      if (!saveHint) return;
      saveHint.textContent = message;
      saveHint.classList.remove('ready');
      saveHint.classList.add('is-error');
    }

    function bindDraftValidationClear(block) {
      block.querySelectorAll('[contenteditable]').forEach((el) => {
        el.addEventListener('input', () => {
          const parent = el.closest('.pg-quiz-q-head, .pg-quiz-opt, .pg-quiz-explanation');
          if (parent) setInlineError(parent, '');
        });
      });
      block.querySelectorAll('input[type="radio"]').forEach((el) => {
        el.addEventListener('change', () => {
          const opts = block.querySelector('.pg-quiz-options');
          if (opts) setInlineError(opts, '');
        });
      });
    }

    function detailsComplete() {
      const title = fields.title?.value.trim() ?? '';
      const instructions = fields.instructions?.value.trim() ?? '';
      const timeLimit = Number(fields.timeLimit?.value);
      return title && instructions && Number.isInteger(timeLimit) && timeLimit >= 1;
    }

    function updateSetupPhaseState() {
      const ready = detailsComplete();
      if (continueBtn) {
        continueBtn.disabled = false;
        continueBtn.classList.toggle('ready', ready);
      }
      if (setupPhaseHint) {
        setupPhaseHint.textContent = ready
          ? 'Setup complete — continue to build your quiz.'
          : 'Enter a title, instructions, and time limit to continue.';
        setupPhaseHint.classList.toggle('ready', ready);
      }
    }

    function updateQuizToolbar() {
      const title = fields.title?.value.trim() || 'Untitled exam';
      const total = currentQuestions.length;
      if (quizToolbarTitle) quizToolbarTitle.textContent = title;
      if (quizToolbarCount) {
        quizToolbarCount.textContent = total === 1 ? '1 question' : `${total} questions`;
        quizToolbarCount.classList.toggle('has-items', total > 0);
      }
    }

    function applyPhase(next) {
      currentPhase = next;
      setupPhase?.classList.toggle('hidden', next !== 'setup');
      builderPhase?.classList.toggle('hidden', next !== 'builder');
      successPhase?.classList.toggle('hidden', next !== 'success');

      document.getElementById('createExamView')
        ?.classList.toggle('pg-create-setup-mode', next === 'setup');

      if (next === 'builder') {
        updateQuizToolbar();
        renderAll();
      } else if (next === 'setup') {
        updateSetupPhaseState();
        fields.title?.focus();
      } else if (next === 'success') {
        const screen = successPhase?.querySelector('.pg-create-success-screen');
        screen?.classList.remove('pg-create-success-enter');
        void screen?.offsetWidth;
        screen?.classList.add('pg-create-success-enter');
      }
    }

    function setPhase(next) {
      applyPhase(next);
      schedulePersistCreateExamWork();
    }

    function continueToBuilder() {
      if (showSetupErrors(getSetupErrors())) return;
      setPhase('builder');
    }

    function editSetup() {
      setPhase('setup');
    }

    function canSave() {
      return detailsComplete() && currentQuestions.length > 0;
    }

    function updateSaveState() {
      if (saveDraftBtn?.dataset.saving === '1' || publishBtn?.dataset.saving === '1') return;
      const ready = canSave();
      [saveDraftBtn, publishBtn].forEach((btn) => {
        if (!btn) return;
        btn.disabled = false;
        btn.classList.toggle('ready', ready);
      });
      if (saveHint) {
        if (currentQuestions.length > 0) {
          saveHint.classList.remove('is-error');
        }
        if (ready) {
          saveHint.textContent = 'Ready to save as draft or publish.';
          saveHint.classList.add('ready');
        } else if (currentQuestions.length === 0) {
          if (!saveHint.classList.contains('is-error')) {
            saveHint.textContent = 'Add at least one question to save or publish.';
          }
          saveHint.classList.remove('ready');
        } else {
          saveHint.textContent = 'Complete exam setup to save or publish.';
          saveHint.classList.remove('ready');
        }
      }
    }

    function readDraftFromDom() {
      const draft = docDraft?.querySelector('.pg-quiz-draft-block');
      if (!draft) return null;

      const question = plainText(draft.querySelector('[data-draft="question"]'));
      const choices = LETTERS.map((letter) =>
        plainText(draft.querySelector(`[data-draft="choice-${letter}"]`))
      );
      const correctRadio = draft.querySelector('input[name="draftCorrect"]:checked');
      const explanation = plainText(draft.querySelector('[data-draft="explanation"]') ?? draft.querySelector('.pg-quiz-explanation-text'));

      if (!question && choices.every((c) => !c) && !explanation) {
        return null;
      }

      return {
        question,
        choices,
        correctAnswer: correctRadio ? Number(correctRadio.value) : -1,
        explanation,
      };
    }

    function validateQuestion(item) {
      const errors = questionFieldErrors(item);
      const keys = Object.keys(errors);
      return keys.length ? errors[keys[0]] : null;
    }

    function buildDraftBlock() {
      const block = document.createElement('div');
      block.className = 'pg-quiz-draft-block';
      block.innerHTML = `
        <p class="pg-quiz-draft-label">New question</p>
        <div class="pg-quiz-q">
          <div class="pg-quiz-q-head">
            <span class="pg-quiz-q-num">+</span>
            <div class="pg-quiz-q-text" contenteditable="true" spellcheck="true" data-draft="question" data-placeholder="Type your question here…"></div>
          </div>
          <ol class="pg-quiz-options">
            ${LETTERS.map((letter, i) => `
              <li class="pg-quiz-opt">
                <label class="pg-quiz-opt-mark" title="Mark as correct answer">
                  <input type="radio" name="draftCorrect" value="${i}">
                  <span class="pg-quiz-opt-letter">${letter}</span>
                </label>
                <span class="pg-quiz-opt-text" contenteditable="true" spellcheck="true" data-draft="choice-${letter}" data-placeholder="Enter choice ${letter}…"></span>
              </li>
            `).join('')}
          </ol>
          <div class="pg-quiz-explanation">
            <span class="pg-quiz-explanation-label">Explanation</span>
            <span class="pg-quiz-explanation-text" contenteditable="true" spellcheck="true" data-draft="explanation" data-placeholder="Why is this the correct answer?"></span>
          </div>
        </div>
      `;
      return block;
    }

    function renderDraft() {
      if (!docDraft) return;
      const block = buildDraftBlock();
      docDraft.replaceChildren(block);
      bindDraftValidationClear(block);
      docDraft.querySelector('[data-draft="question"]')?.focus();
    }

    function buildQuestionBlock(item, index) {
      const section = document.createElement('section');
      section.className = `pg-quiz-q${activeIndex === index ? ' active' : ''}`;
      section.dataset.index = String(index);

      section.innerHTML = `
        <div class="pg-quiz-q-head">
          <span class="pg-quiz-q-num">${index + 1}.</span>
          <div class="pg-quiz-q-text" contenteditable="true" spellcheck="true" data-field="question">${escapeHtml(item.question)}</div>
          <button type="button" class="pg-quiz-q-remove" aria-label="Remove question ${index + 1}"><i class="ti ti-trash"></i></button>
        </div>
        <ol class="pg-quiz-options">
          ${LETTERS.map((letter, i) => `
            <li class="pg-quiz-opt${item.correctAnswer === i ? ' correct' : ''}">
              <label class="pg-quiz-opt-mark" title="Mark as correct answer">
                <input type="radio" name="correct-${index}" value="${i}"${item.correctAnswer === i ? ' checked' : ''}>
                <span class="pg-quiz-opt-letter">${letter}</span>
              </label>
              <span class="pg-quiz-opt-text" contenteditable="true" spellcheck="true" data-field="choice-${i}">${escapeHtml(item.choices[i])}</span>
            </li>
          `).join('')}
        </ol>
        <div class="pg-quiz-explanation">
          <span class="pg-quiz-explanation-label">Explanation</span>
          <span class="pg-quiz-explanation-text" contenteditable="true" spellcheck="true" data-field="explanation">${escapeHtml(item.explanation)}</span>
        </div>
      `;

      section.querySelector('.pg-quiz-q-remove')?.addEventListener('click', async () => {
        const confirmed = await confirmAction({
          title: 'Remove question?',
          message: `Question ${index + 1} will be removed from this exam.`,
          confirmLabel: 'Remove question',
          cancelLabel: 'Keep question',
          danger: true,
          tone: 'warning',
        });
        if (!confirmed) return;
        currentQuestions.splice(index, 1);
        if (activeIndex >= currentQuestions.length) activeIndex = currentQuestions.length - 1;
        renderAll();
        schedulePersistCreateExamWork();
      });

      section.querySelectorAll('[contenteditable]').forEach((el) => {
        el.addEventListener('blur', () => syncQuestionFromDom(index));
        el.addEventListener('input', () => {
          currentQuestions[index] = readQuestionFromDom(section, currentQuestions[index]);
          const parent = el.closest('.pg-quiz-q-head, .pg-quiz-opt, .pg-quiz-explanation');
          if (parent) setInlineError(parent, '');
          renderStack();
        });
      });

      section.querySelectorAll(`input[name="correct-${index}"]`).forEach((radio) => {
        radio.addEventListener('change', () => {
          currentQuestions[index].correctAnswer = Number(radio.value);
          setInlineError(section.querySelector('.pg-quiz-options'), '');
          renderPaper();
          renderStack();
        });
      });

      section.addEventListener('click', () => {
        activeIndex = index;
        renderStack();
        section.classList.add('active');
      });

      return section;
    }

    function escapeHtml(text) {
      return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    }

    function readQuestionFromDom(section, fallback) {
      const item = { ...fallback };
      item.question = plainText(section.querySelector('[data-field="question"]'));
      item.choices = LETTERS.map((_, i) =>
        plainText(section.querySelector(`[data-field="choice-${i}"]`))
      );
      const checked = section.querySelector('input[type="radio"]:checked');
      item.correctAnswer = checked ? Number(checked.value) : fallback.correctAnswer;
      item.explanation = plainText(section.querySelector('[data-field="explanation"]') ?? section.querySelector('.pg-quiz-explanation-text'));
      return item;
    }

    function syncQuestionFromDom(index) {
      const section = docQuestions?.querySelector(`[data-index="${index}"]`);
      if (!section) return;
      currentQuestions[index] = readQuestionFromDom(section, currentQuestions[index]);
      renderStack();
      updateSaveState();
      schedulePersistCreateExamWork();
    }

    function renderPaper() {
      if (!docQuestions) return;
      docQuestions.replaceChildren();
      currentQuestions.forEach((item, index) => {
        docQuestions.append(buildQuestionBlock(item, index));
      });
    }

    function renderStack() {
      if (!questionStack) return;
      questionStack.replaceChildren();

      if (!currentQuestions.length) {
        const empty = document.createElement('li');
        empty.className = 'pg-create-qlist-empty';
        empty.textContent = 'No questions yet';
        questionStack.append(empty);
      } else {
        currentQuestions.forEach((item, index) => {
          const row = document.createElement('li');
          row.className = `pg-create-qlist-row${activeIndex === index ? ' active' : ''}`;
          row.innerHTML = `
            <span class="pg-create-qlist-num">${index + 1}</span>
            <span class="pg-create-qlist-preview">${truncate(item.question) || 'Untitled question'}</span>
          `;
          row.addEventListener('click', () => {
            activeIndex = index;
            renderStack();
            const target = docQuestions?.querySelector(`[data-index="${index}"]`);
            target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            renderPaper();
          });
          questionStack.append(row);
        });
      }

      if (questionCount) {
        questionCount.textContent = String(currentQuestions.length);
        questionCount.classList.toggle('has-items', currentQuestions.length > 0);
      }
      updateQuizToolbar();
      updateSaveState();
    }

    function renderAll() {
      renderPaper();
      renderStack();
      renderDraft();
      updateSaveState();
    }

    function commitDraft() {
      const draft = readDraftFromDom();
      if (!draft) {
        renderDraft();
        docDraft?.querySelector('[data-draft="question"]')?.focus();
        return;
      }
      const errors = questionFieldErrors(draft);
      if (Object.keys(errors).length) {
        showDraftErrors(errors);
        return;
      }

      currentQuestions.push({
        question: draft.question,
        choices: draft.choices,
        correctAnswer: draft.correctAnswer,
        explanation: draft.explanation,
      });
      activeIndex = currentQuestions.length - 1;
      clearSaveHintError();
      renderAll();
      schedulePersistCreateExamWork();
      docQuestions?.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function resetForm() {
      editingExamId = null;
      clearCreateExamWork();
      const prefs = window.ExamGuardProfessor?.preferences || {};
      fields.title.value = '';
      fields.instructions.value = '';
      fields.timeLimit.value = prefs.defaultTimeLimit ? String(prefs.defaultTimeLimit) : '';
      fields.warningLimit.value = String(prefs.defaultWarningLimit ?? 3);
      fields.classId.value = '';
      setScheduleFormValues({});
      currentQuestions = [];
      activeIndex = -1;
      clearSetupErrors();
      clearSaveHintError();
      hidePublishOverlay();
      setPhase('setup');
      renderAll();
      updateQuizToolbar();
    }

    function showActionOverlay(action, isUpdate) {
      if (!publishOverlayLabel) return;
      if (action === 'draft') {
        publishOverlayLabel.textContent = isUpdate ? 'Saving draft…' : 'Saving your draft…';
      } else {
        publishOverlayLabel.textContent = isUpdate
          ? 'Publishing your changes…'
          : 'Publishing your exam…';
      }
      publishOverlay?.classList.remove('hidden');
    }

    function hidePublishOverlay() {
      publishOverlay?.classList.add('hidden');
    }

    function setActionLoading(action, loading, isUpdate) {
      const activeBtn = action === 'draft' ? saveDraftBtn : publishBtn;
      const idleHtml = action === 'draft' ? SAVE_DRAFT_BTN_HTML : PUBLISH_BTN_HTML;

      if (loading) {
        [saveDraftBtn, publishBtn].forEach((btn) => {
          if (!btn) return;
          btn.disabled = true;
          btn.dataset.saving = '1';
        });
        if (activeBtn) {
          activeBtn.classList.add(action === 'draft' ? 'is-saving' : 'is-publishing');
          activeBtn.innerHTML = action === 'draft' ? SAVING_DRAFT_BTN_HTML : PUBLISHING_BTN_HTML;
        }
        showActionOverlay(action, isUpdate);
        return;
      }

      [saveDraftBtn, publishBtn].forEach((btn) => {
        if (!btn) return;
        delete btn.dataset.saving;
        btn.classList.remove('is-saving', 'is-publishing');
        if (btn === saveDraftBtn) btn.innerHTML = SAVE_DRAFT_BTN_HTML;
        if (btn === publishBtn) btn.innerHTML = PUBLISH_BTN_HTML;
      });
      hidePublishOverlay();
      updateSaveState();
    }

    function showSuccessScreen(payload, savedExam = null) {
      const questionTotal = payload.questions.length;
      const classLabel = fields.classId?.selectedOptions?.[0]?.textContent?.trim();
      const hasClass = Boolean(fields.classId?.value);
      const examKey = savedExam?.examKey ?? null;

      if (publishSuccessTitle) {
        publishSuccessTitle.textContent = payload.title;
      }
      if (publishSuccessMeta) {
        const parts = [
          `${questionTotal} question${questionTotal === 1 ? '' : 's'}`,
          `${payload.timeLimit} min`,
        ];
        if (hasClass && classLabel && classLabel !== 'No class') {
          parts.push(classLabel);
        }
        if (savedExam?.opensAt) {
          parts.push(`Opens ${formatScheduleLabel(savedExam.opensAt)}`);
        }
        if (savedExam?.closesAt) {
          parts.push(`Closes ${formatScheduleLabel(savedExam.closesAt)}`);
        }
        publishSuccessMeta.textContent = parts.join(' · ');
      }
      if (publishSuccessLead) {
        if ((savedExam?.status ?? 'active') === 'scheduled') {
          const when = savedExam?.opensAt
            ? formatScheduleLabel(savedExam.opensAt)
            : 'the scheduled time';
          publishSuccessLead.textContent = `Your exam is scheduled and will open on ${when}.`;
        } else {
          publishSuccessLead.textContent = 'Your exam is published and ready for students.';
        }
      }
      if (publishSuccessKeyWrap && publishSuccessKey) {
        if (examKey) {
          publishSuccessKey.textContent = examKey;
          publishSuccessKeyWrap.classList.remove('hidden');
        } else {
          publishSuccessKeyWrap.classList.add('hidden');
        }
      }

      setPhase('success');
    }

    async function copyExamKey(key) {
      if (!key) return;
      try {
        if (navigator.clipboard?.writeText) {
          await navigator.clipboard.writeText(key);
        } else {
          const input = document.createElement('textarea');
          input.value = key;
          document.body.appendChild(input);
          input.select();
          document.execCommand('copy');
          document.body.removeChild(input);
        }
        window.ExamGuardDialog?.toast('Exam key copied to clipboard.', 'success');
      } catch (_) {
        window.ExamGuardDialog?.alert({
          type: 'info',
          title: 'Exam key',
          message: `Share this key with students: <strong style="font-family:monospace;letter-spacing:0.1em;">${key}</strong>`,
        });
      }
    }

    function waitForActionAnimation(startedAt) {
      const elapsed = Date.now() - startedAt;
      const remaining = MIN_ACTION_MS - elapsed;
      if (remaining <= 0) return Promise.resolve();
      return new Promise((resolve) => setTimeout(resolve, remaining));
    }

    function preparePayload(status) {
      currentQuestions.forEach((_, i) => syncQuestionFromDom(i));
      clearSaveHintError();

      const draft = readDraftFromDom();
      if (draft) {
        const draftErrors = questionFieldErrors(draft);
        if (Object.keys(draftErrors).length) {
          showDraftErrors(draftErrors);
          docDraft?.scrollIntoView({ behavior: 'smooth', block: 'center' });
          return null;
        }
      }

      const draftErr = mergeDraftBeforeSave();
      if (draftErr) {
        const block = docDraft?.querySelector('.pg-quiz-draft-block');
        if (block) showQuestionErrors(block, questionFieldErrors(readDraftFromDom() || {}));
        docDraft?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return null;
      }

      const setupErrors = getSetupErrors();
      if (Object.keys(setupErrors).length) {
        setPhase('setup');
        showSetupErrors(setupErrors);
        return null;
      }

      if (currentQuestions.length === 0) {
        setSaveHintError('Add at least one question to save or publish.');
        questionStack?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return null;
      }

      const payload = {
        ...buildSavePayload(),
        status,
      };

      for (let i = 0; i < payload.questions.length; i += 1) {
        const errors = questionFieldErrors(payload.questions[i]);
        if (Object.keys(errors).length) {
          activeIndex = i;
          renderAll();
          const section = docQuestions?.querySelector(`[data-index="${i}"]`);
          if (section) showQuestionErrors(section, errors);
          return null;
        }
      }

      return payload;
    }

    async function persistExam(status) {
      const payload = preparePayload(status);
      if (!payload) return;

      const action = status === 'active' ? 'publish' : 'draft';
      const isUpdate = Boolean(editingExamId);
      const startedAt = Date.now();
      setActionLoading(action, true, isUpdate);

      try {
        let result;
        if (editingExamId) {
          result = await ExamGuardApi.updateExam(editingExamId, payload);
        } else {
          result = await ExamGuardApi.createExam(payload);
        }

        if (result?.exam?.id) {
          editingExamId = result.exam.id;
          clearCreateExamWork();
          syncCreateExamRoute();
        }

        await waitForActionAnimation(startedAt);
        setActionLoading(action, false, isUpdate);

        if (status === 'active') {
          showSuccessScreen(payload, result?.exam);
          return;
        }

        if (typeof onSaved === 'function') {
          onSaved();
        } else {
          window.location.href = '/professor?view=exams';
        }
      } catch (error) {
        setActionLoading(action, false, isUpdate);
        await alertError(
          status === 'active' ? 'Unable to publish exam' : 'Unable to save draft',
          error.message || `This exam could not be ${status === 'active' ? 'published' : 'saved'}. Please try again.`,
        );
      }
    }

    function saveDraft() {
      return persistExam('draft');
    }

    function publishExam() {
      return persistExam('active');
    }

    async function loadExam(examId) {
      if (!examId) return;

      setPhase('builder');

      try {
        await loadClasses();
        const { exam } = await ExamGuardApi.exam(examId);

        editingExamId = exam.id;

        if (!['draft'].includes(exam.status ?? 'draft')) {
          setPhase('setup');
          await alertInfo(
            'Cannot edit exam',
            'Only draft exams can be edited. Open this exam from your list to view results.',
          );
          if (typeof onSaved === 'function') {
            onSaved();
          } else {
            window.location.href = '/professor?view=exams';
          }
          return;
        }

        fields.title.value = exam.title ?? '';
        fields.instructions.value = exam.instructions ?? '';
        fields.timeLimit.value = exam.timeLimit != null ? String(exam.timeLimit) : '';
        fields.warningLimit.value = String(exam.warningLimit ?? 3);
        fields.classId.value = exam.classId != null ? String(exam.classId) : '';
        applySchedulePair(
          fields.opensDate,
          fields.opensTime,
          exam.opensAt,
          SCHEDULE_DEFAULTS.opensTime,
        );
        applySchedulePair(
          fields.closesDate,
          fields.closesTime,
          exam.closesAt,
          SCHEDULE_DEFAULTS.closesTime,
        );

        currentQuestions = (exam.questions ?? []).map((item) => ({
          question: item.question ?? '',
          choices: LETTERS.map((_, index) => item.choices?.[index] ?? ''),
          correctAnswer: Number.isInteger(item.correctAnswer) ? item.correctAnswer : -1,
          explanation: item.explanation ?? '',
        }));

        activeIndex = currentQuestions.length > 0 ? 0 : -1;
        clearCreateExamWork();
        updateSetupPhaseState();
        updateQuizToolbar();
        renderAll();
        syncCreateExamRoute();
      } catch (error) {
        setPhase('setup');
        await alertError('Unable to load exam', error.message || 'This exam could not be loaded. Please try again.');
      }
    }

    async function clearExam() {
      const confirmed = await confirmAction({
        title: 'Clear exam?',
        message: 'This will remove all exam details and questions from the builder.',
        confirmLabel: 'Clear exam',
        cancelLabel: 'Keep working',
        danger: true,
        tone: 'warning',
      });
      if (!confirmed) return;
      resetForm();
    }

    function normalizeQuestion(item) {
      return {
        question: (item.question ?? '').trim(),
        choices: LETTERS.map((_, index) => (item.choices?.[index] ?? '').trim()),
        correctAnswer: Number.isInteger(item.correctAnswer) ? item.correctAnswer : -1,
        explanation: (item.explanation ?? '').trim(),
      };
    }

    function mergeDraftBeforeSave() {
      const draft = readDraftFromDom();
      if (!draft) return null;

      const err = validateQuestion(draft);
      if (err) return err;

      currentQuestions.push({
        question: draft.question,
        choices: draft.choices,
        correctAnswer: draft.correctAnswer,
        explanation: draft.explanation,
      });
      activeIndex = currentQuestions.length - 1;
      renderAll();
      return null;
    }

    function buildSavePayload() {
      return {
        title: fields.title.value.trim(),
        instructions: fields.instructions.value.trim(),
        timeLimit: Number(fields.timeLimit.value),
        warningLimit: Number(fields.warningLimit.value),
        classId: fields.classId.value ? Number(fields.classId.value) : null,
        opensAt: readScheduleValue(
          fields.opensDate,
          fields.opensTime,
          SCHEDULE_DEFAULTS.opensTime,
        ),
        closesAt: readScheduleValue(
          fields.closesDate,
          fields.closesTime,
          SCHEDULE_DEFAULTS.closesTime,
        ),
        questions: currentQuestions.map(normalizeQuestion),
      };
    }

    async function loadClasses() {
      try {
        const classResult = await ExamGuardApi.classes();
        fields.classId.replaceChildren();
        const blank = document.createElement('option');
        blank.value = '';
        blank.textContent = 'None';
        fields.classId.append(blank);
        classResult.classes.forEach((classroom) => {
          const opt = document.createElement('option');
          opt.value = classroom.id;
          opt.textContent = `${classroom.name} - ${classroom.subject}`;
          fields.classId.append(opt);
        });
      } catch (_) {}
    }

    fields.title?.addEventListener('input', () => {
      clearSetupFieldError('title');
      updateSetupPhaseState();
      updateQuizToolbar();
      schedulePersistCreateExamWork();
    });
    fields.instructions?.addEventListener('input', () => {
      clearSetupFieldError('instructions');
      updateSetupPhaseState();
      schedulePersistCreateExamWork();
    });
    fields.timeLimit?.addEventListener('input', () => {
      clearSetupFieldError('timeLimit');
      updateSetupPhaseState();
      schedulePersistCreateExamWork();
    });
    [fields.warningLimit, fields.classId].forEach((el) => {
      el?.addEventListener('input', () => {
        updateSetupPhaseState();
        schedulePersistCreateExamWork();
      });
      el?.addEventListener('change', () => {
        updateSetupPhaseState();
        schedulePersistCreateExamWork();
      });
    });
    function bindScheduleField(dateEl, timeEl, fieldName, defaultTime) {
      dateEl?.addEventListener('change', () => {
        syncScheduleTimeState(dateEl, timeEl, defaultTime);
        clearSetupFieldError(fieldName);
        updateSetupPhaseState();
        schedulePersistCreateExamWork();
      });
      timeEl?.addEventListener('input', () => {
        clearSetupFieldError(fieldName);
        updateSetupPhaseState();
        schedulePersistCreateExamWork();
      });
      timeEl?.addEventListener('change', () => {
        clearSetupFieldError(fieldName);
        updateSetupPhaseState();
        schedulePersistCreateExamWork();
      });
    }

    bindScheduleField(fields.opensDate, fields.opensTime, 'opensAt', SCHEDULE_DEFAULTS.opensTime);
    bindScheduleField(fields.closesDate, fields.closesTime, 'closesAt', SCHEDULE_DEFAULTS.closesTime);

    continueBtn?.addEventListener('click', continueToBuilder);
    editSetupBtn?.addEventListener('click', editSetup);

    root.querySelector('#addQuestionBtn')?.addEventListener('click', commitDraft);
    root.querySelector('#newQuestionBtn')?.addEventListener('click', () => {
      renderDraft();
      docDraft?.querySelector('[data-draft="question"]')?.focus();
    });
    saveDraftBtn?.addEventListener('click', saveDraft);
    publishBtn?.addEventListener('click', publishExam);

    viewPublishedExamsBtn?.addEventListener('click', () => {
      if (typeof onSaved === 'function') {
        onSaved();
      } else {
        window.location.href = '/professor?view=exams';
      }
    });

    createAnotherExamBtn?.addEventListener('click', () => {
      resetForm();
    });

    copyPublishKeyBtn?.addEventListener('click', () => {
      copyExamKey(publishSuccessKey?.textContent?.trim());
    });

    docDraft?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) {
        e.preventDefault();
        commitDraft();
      }
    });

    renderAll();
    loadClasses();

    ExamGuardCreateExam.loadForEdit = loadExam;
    ExamGuardCreateExam.resetForm = resetForm;
    ExamGuardCreateExam.saveDraft = saveDraft;
    ExamGuardCreateExam.publishExam = publishExam;
    ExamGuardCreateExam.restoreWorkInProgress = restoreWorkInProgress;
    ExamGuardCreateExam.getRouteState = getRouteState;
  }

  function initLegacyBuilder(root, onSaved) {
    const classes = {
      empty: 'eg-empty',
      item: 'eg-builder-item',
      btnSecondary: 'eg-btn-secondary',
      btnDanger: 'eg-btn-danger',
      meta: 'text-slate-300',
      title: 'font-semibold',
    };

    let exams = [];
    let currentQuestions = [];

    const fields = {
      title: root.querySelector('#examTitleInput'),
      instructions: root.querySelector('#instructionsInput'),
      timeLimit: root.querySelector('#timeLimitInput'),
      warningLimit: root.querySelector('#warningLimitInput'),
      classId: root.querySelector('#examClassInput'),
      question: root.querySelector('#questionInput'),
      choices: LETTERS.map((letter) => root.querySelector(`#choice${letter}`)),
      correctAnswer: root.querySelector('#correctAnswerInput'),
      explanation: root.querySelector('#explanationInput'),
    };
    const questionPreview = root.querySelector('#questionPreview');
    const questionCount = root.querySelector('#questionCount');
    const savedExams = root.querySelector('#savedExams');
    const saveBtn = root.querySelector('#saveExamBtn');

    const empty = (message) => {
      const item = document.createElement('div');
      item.className = classes.empty;
      item.textContent = message;
      return item;
    };

    const action = (label, handler, danger = false) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = danger ? classes.btnDanger : classes.btnSecondary;
      button.textContent = label;
      button.addEventListener('click', handler);
      return button;
    };

    function clearQuestionForm() {
      fields.question.value = '';
      fields.choices.forEach((c) => { c.value = ''; });
      fields.correctAnswer.value = '';
      fields.explanation.value = '';
    }

    function addQuestion() {
      const question = fields.question.value.trim();
      const choices = fields.choices.map((c) => c.value.trim());
      const correctAnswer = fields.correctAnswer.value;
      const explanation = fields.explanation.value.trim();
      if (!question || choices.some((c) => !c) || correctAnswer === '' || !explanation) {
        void alertInfo(
          'Incomplete question',
          'Enter the question, all four choices, the correct answer, and an explanation.',
        );
        return;
      }
      currentQuestions.push({ question, choices, correctAnswer: Number(correctAnswer), explanation });
      clearQuestionForm();
      renderQuestionPreview();
    }

    function renderQuestionPreview() {
      questionPreview.replaceChildren();
      if (!currentQuestions.length) {
        if (questionCount) questionCount.textContent = 'No questions added yet.';
        questionPreview.append(empty('Questions you add will appear here.'));
        return;
      }
      if (questionCount) questionCount.textContent = `${currentQuestions.length} question(s)`;
      currentQuestions.forEach((item, index) => {
        const card = document.createElement('article');
        card.className = classes.item;
        const content = document.createElement('div');
        const title = document.createElement('h4');
        title.className = classes.title;
        title.textContent = `${index + 1}. ${item.question}`;
        const answer = document.createElement('p');
        answer.className = classes.meta;
        answer.textContent = `Correct answer: ${item.choices[item.correctAnswer]}`;
        content.append(title, answer);
        card.append(content, action('Remove', () => {
          currentQuestions.splice(index, 1);
          renderQuestionPreview();
        }, true));
        questionPreview.append(card);
      });
    }

    function clearCurrentExamWithoutConfirmation() {
      fields.title.value = '';
      fields.instructions.value = '';
      fields.timeLimit.value = '';
      fields.warningLimit.value = '3';
      fields.classId.value = '';
      currentQuestions = [];
      clearQuestionForm();
      renderQuestionPreview();
    }

    async function saveExam() {
      const payload = {
        title: fields.title.value.trim(),
        instructions: fields.instructions.value.trim(),
        timeLimit: Number(fields.timeLimit.value),
        warningLimit: Number(fields.warningLimit.value),
        classId: fields.classId.value ? Number(fields.classId.value) : null,
        questions: currentQuestions,
      };
      if (!payload.title || !payload.instructions || !Number.isInteger(payload.timeLimit) || payload.timeLimit < 1) {
        await alertInfo(
          'Incomplete exam details',
          'Enter the exam title, instructions, and a time limit of at least 1 minute.',
        );
        return;
      }
      if (!currentQuestions.length) {
        await alertInfo('No questions added', 'Add at least one question before saving this exam.');
        return;
      }
      try {
        await ExamGuardApi.createExam(payload);
        clearCurrentExamWithoutConfirmation();
        await loadData();
        dialog()?.toast('Exam saved successfully.', 'success');
      } catch (error) {
        await alertError('Unable to save exam', error.message);
      }
    }

    async function loadData() {
      try {
        const [examResult, classResult] = await Promise.all([ExamGuardApi.exams(), ExamGuardApi.classes()]);
        exams = examResult.exams;
        fields.classId.replaceChildren();
        const blank = document.createElement('option');
        blank.value = '';
        blank.textContent = 'Save without assigning';
        fields.classId.append(blank);
        classResult.classes.forEach((classroom) => {
          const opt = document.createElement('option');
          opt.value = classroom.id;
          opt.textContent = `${classroom.name} - ${classroom.subject}`;
          fields.classId.append(opt);
        });
        if (savedExams) {
          savedExams.replaceChildren();
          if (!exams.length) savedExams.append(empty('No saved exams yet.'));
          else exams.forEach((exam, index) => {
            const card = document.createElement('article');
            card.className = classes.item;
            const title = document.createElement('h4');
            title.className = classes.title;
            title.textContent = `${index + 1}. ${exam.title}`;
            card.append(title, action('Delete', async () => {
              const confirmed = await confirmAction({
                title: 'Delete exam?',
                message: `This will permanently delete <strong>${escapeHtml(exam.title)}</strong>.`,
                confirmLabel: 'Delete exam',
                cancelLabel: 'Keep exam',
                danger: true,
                tone: 'danger',
              });
              if (!confirmed) return;
              try {
                await ExamGuardApi.deleteExam(exam.id);
                dialog()?.toast('Exam deleted.', 'success');
                await loadData();
              } catch (error) {
                await alertError('Unable to delete exam', error.message);
              }
            }, true));
            savedExams.append(card);
          });
        }
      } catch (error) {
        if (savedExams) savedExams.replaceChildren(empty(error.message));
        await alertError('Unable to load exams', error.message);
      }
    }

    function escapeHtml(text) {
      return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    root.querySelector('#addQuestionBtn')?.addEventListener('click', addQuestion);
    root.querySelector('#clearExamBtn')?.addEventListener('click', async () => {
      const confirmed = await confirmAction({
        title: 'Clear form?',
        message: 'This will remove all exam details and questions from the form.',
        confirmLabel: 'Clear form',
        cancelLabel: 'Keep working',
        danger: true,
        tone: 'warning',
      });
      if (confirmed) clearCurrentExamWithoutConfirmation();
    });
    saveBtn?.addEventListener('click', saveExam);
    root.querySelector('#deleteAllExamsBtn')?.addEventListener('click', async () => {
      if (!exams.length) return;
      const confirmed = await confirmAction({
        title: 'Delete all exams?',
        message: `This will permanently delete all ${exams.length} saved exam${exams.length === 1 ? '' : 's'}.`,
        confirmLabel: 'Delete all',
        cancelLabel: 'Cancel',
        danger: true,
        tone: 'danger',
      });
      if (!confirmed) return;
      try {
        for (const exam of exams) await ExamGuardApi.deleteExam(exam.id);
        dialog()?.toast('All exams deleted.', 'success');
        await loadData();
      } catch (error) {
        await alertError('Unable to delete exams', error.message);
      }
    });

    renderQuestionPreview();
    loadData();
  }

  window.ExamGuardCreateExam = {
    init: initCreateExam,
    loadForEdit: null,
    resetForm: null,
    saveExam: null,
  };

  if (document.getElementById('createExamPage')) {
    initCreateExam({ root: document, embedded: false });
  }
})();
