const activeExamId = new URLSearchParams(location.search).get("examId");
let activeExam = null;
let secondsRemaining = 0;
let examFinished = false;
const startedAt = new Date().toISOString();

function blockExam(message) {
  document.getElementById("activeExamTitle").textContent = "Exam unavailable";
  document.getElementById("activeExamInstructions").textContent = message;
  document.getElementById("submitExamBtn").disabled = true;
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
  try {
    const { attempt } = await ExamGuardApi.submitExam(activeExamId, {
      answers,
      warningCount: Number(document.getElementById("warningCount").textContent) || 0,
      startedAt,
    });
    document.getElementById("sessionStatus").textContent = "Submitted";
    document.getElementById("submitExamBtn").disabled = true;
    alert(`${autoSubmitted ? "Session ended. " : ""}Exam submitted. Score: ${attempt.score}/${attempt.total}`);
    location.href = "/student#classes";
  } catch (error) {
    examFinished = false;
    alert(error.message);
  }
}

document.getElementById("submitExamBtn").addEventListener("click", () => finishExam(false));
document.addEventListener("examguard:locked", () => finishExam(true));
renderExam();
