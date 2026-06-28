const activeExamId = window.ExamGuardRoute?.resolveTakeExamId?.()
  || new URLSearchParams(location.search).get('examId');
let activeExam = null;
let activeAttempt = null;
let secondsRemaining = 0;
let examFinished = false;
let heartbeatTimer = null;
const startedAt = new Date().toISOString();

function blockExam(message) {
  document.getElementById("activeExamTitle").textContent = "Exam unavailable";
  document.getElementById("activeExamInstructions").textContent = message;
  document.getElementById("submitExamBtn").disabled = true;
}

async function startSession() {
  try {
    const { attempt } = await ExamGuardApi.startExamSession(activeExamId);
    activeAttempt = attempt;

    if (attempt.startedAt) {
      const elapsed = Math.floor((Date.now() - new Date(attempt.startedAt).getTime()) / 1000);
      secondsRemaining = Math.max(0, (activeExam.timeLimit * 60) - elapsed);
    }

    window.ExamGuardSession = {
      examId: activeExamId,
      attemptId: attempt.id,
      async reportViolation(payload) {
        if (!activeAttempt?.id || examFinished) return;
        try {
          await ExamGuardApi.reportViolation(activeExamId, activeAttempt.id, {
            type: payload.type,
            severity: payload.severity,
            message: payload.message,
            snapshot: payload.snapshot || null,
            occurredAt: payload.occurredAt,
          });
        } catch (_) {}
      },
    };

    const warningEl = document.getElementById("warningCount");
    if (warningEl && attempt.warningCount) {
      warningEl.textContent = String(attempt.warningCount);
    }
    document.dispatchEvent(new CustomEvent("examguard:session-started", {
      detail: { warningCount: attempt.warningCount || 0 },
    }));

    heartbeatTimer = setInterval(async () => {
      if (examFinished || !activeAttempt?.id) return;
      try {
        await ExamGuardApi.examHeartbeat(activeExamId, activeAttempt.id);
      } catch (_) {}
    }, 20000);
  } catch (error) {
    blockExam(error.message);
    throw error;
  }
}

async function renderExam() {
  if (!activeExamId) return blockExam("Return to the student dashboard and select an assigned exam.");
  try {
    ({ exam: activeExam } = await ExamGuardApi.exam(activeExamId));
  } catch (error) {
    return blockExam(error.message);
  }

  secondsRemaining = activeExam.timeLimit * 60;
  document.getElementById("activeExamTitle").textContent = activeExam.title;
  document.getElementById("activeExamInstructions").textContent = activeExam.instructions;
  document.getElementById("warningLimitDisplay").textContent = activeExam.warningLimit;
  document.dispatchEvent(new CustomEvent("examguard:warning-limit", { detail: activeExam.warningLimit }));

  const container = document.getElementById("examQuestions");
  activeExam.questions.forEach((question, questionIndex) => {
    const card = document.createElement("article");
    card.className = "rounded-2xl border border-white/10 bg-white/5 p-5";
    const title = document.createElement("h3");
    title.className = "mb-4 text-lg font-semibold";
    title.textContent = `${questionIndex + 1}. ${question.question}`;
    card.append(title);
    question.choices.forEach((choice, choiceIndex) => {
      const label = document.createElement("label");
      label.className = "mb-2 flex cursor-pointer items-center gap-3 rounded-xl bg-white/5 px-4 py-3";
      const input = document.createElement("input");
      input.type = "radio";
      input.name = `question-${questionIndex}`;
      input.value = choiceIndex;
      const text = document.createElement("span");
      text.textContent = choice;
      label.append(input, text);
      card.append(label);
    });
    container.append(card);
  });

  try {
    await startSession();
  } catch (_) {
    return;
  }

  updateTimer();
  setInterval(() => {
    if (examFinished) return;
    secondsRemaining -= 1;
    updateTimer();
    if (secondsRemaining <= 0) finishExam(true);
  }, 1000);
}

function updateTimer() {
  const minutes = Math.floor(Math.max(secondsRemaining, 0) / 60);
  const seconds = Math.max(secondsRemaining, 0) % 60;
  document.getElementById("examTimer").textContent = `${minutes}:${String(seconds).padStart(2, "0")}`;
}

async function finishExam(autoSubmitted = false) {
  if (examFinished || !activeExam) return;
  const answers = activeExam.questions.map((question, index) => {
    const selected = document.querySelector(`input[name="question-${index}"]:checked`);
    return selected ? Number(selected.value) : null;
  });
  if (!autoSubmitted && answers.includes(null) && !confirm("Some questions are unanswered. Submit anyway?")) return;

  examFinished = true;
  if (heartbeatTimer) clearInterval(heartbeatTimer);
  window.ExamGuardSession = null;

  try {
    const { attempt } = await ExamGuardApi.submitExam(activeExamId, {
      answers,
      warningCount: Number(document.getElementById("warningCount").textContent) || 0,
      startedAt: activeAttempt?.startedAt || startedAt,
    });
    document.getElementById("sessionStatus").textContent = "Submitted";
    document.getElementById("submitExamBtn").disabled = true;
    alert(`${autoSubmitted ? "Session ended. " : ""}Exam submitted. Score: ${attempt.score}/${attempt.total}`);
    window.ExamGuardRoute?.clearTakeExamId?.();
    location.href = "/student#classes";
  } catch (error) {
    examFinished = false;
    alert(error.message);
  }
}

document.getElementById("submitExamBtn").addEventListener("click", () => finishExam(false));
document.addEventListener("examguard:locked", () => finishExam(true));
renderExam();
