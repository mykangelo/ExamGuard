const studentIdentity = document.getElementById("studentIdentity");
const availableExamList = document.getElementById("availableExamList");
const emptyMessage = (text) => {
  const item = document.createElement("div");
  item.className = "eg-empty";
  item.textContent = text;
  return item;
};

function formatExamWhen(iso) {
  if (!iso) return "";
  return new Date(iso).toLocaleString(undefined, {
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

async function renderStudentClassroom() {
  availableExamList.replaceChildren();
  try {
    const [{ user }, { exams }] = await Promise.all([ExamGuardApi.me(), ExamGuardApi.exams()]);
    studentIdentity.textContent = user.name;
    document.getElementById("studentNameInput").value = user.name;
    document.getElementById("studentNameInput").disabled = true;
    document.getElementById("availableExamCount").textContent = `${exams.length} exam(s)`;

    if (!exams.length) {
      availableExamList.append(emptyMessage("No exams have been assigned to your classes yet."));
      return;
    }

    exams.forEach((exam) => {
      const card = document.createElement("article");
      card.className = "eg-builder-item";
      const content = document.createElement("div");
      const title = document.createElement("h3");
      const details = document.createElement("p");
      const isSubmitted = exam.attempt?.submittedAt;
      const isInProgress = exam.attempt && !isSubmitted;
      const isScheduled = !exam.attempt && exam.status === "scheduled";
      title.className = "text-lg font-semibold";
      title.textContent = exam.title;
      details.className = "text-slate-300";
      if (isSubmitted) {
        details.textContent = `Submitted | Score: ${exam.attempt.score}/${exam.attempt.total}`;
      } else if (isInProgress) {
        details.textContent = `In progress | ${exam.questionCount} question(s) | ${exam.timeLimit} minutes`;
      } else if (isScheduled) {
        const when = formatExamWhen(exam.opensAt);
        details.textContent = `${exam.questionCount} question(s) | Opens ${when}`;
      } else {
        const parts = [`${exam.questionCount} question(s)`, `${exam.timeLimit} minutes`];
        if (exam.closesAt) parts.push(`Closes ${formatExamWhen(exam.closesAt)}`);
        details.textContent = parts.join(" | ");
      }
      content.append(title, details);
      const action = document.createElement("a");
      if (isSubmitted) {
        action.className = "eg-btn-secondary";
        action.textContent = "Completed";
        action.href = "#";
        action.addEventListener("click", (event) => event.preventDefault());
      } else if (isInProgress) {
        action.className = "eg-btn-primary";
        action.textContent = "Resume Exam";
        action.href = `/take-exam?examId=${exam.id}`;
        action.addEventListener("click", (event) => {
          event.preventDefault();
          const target = `/take-exam?examId=${exam.id}`;
          window.ExamGuardRoute?.save(target);
          window.location.href = target;
        });
      } else if (isScheduled) {
        action.className = "eg-btn-secondary";
        action.textContent = "Scheduled";
        action.href = "#";
        action.addEventListener("click", (event) => event.preventDefault());
      } else {
        action.className = "eg-btn-primary";
        action.textContent = "Take Exam";
        action.href = `/take-exam?examId=${exam.id}`;
        action.addEventListener("click", (event) => {
          event.preventDefault();
          const target = `/take-exam?examId=${exam.id}`;
          window.ExamGuardRoute?.save(target);
          window.location.href = target;
        });
      }
      card.append(content, action);
      availableExamList.append(card);
    });
  } catch (error) {
    availableExamList.append(emptyMessage(error.message));
  }
}

document.getElementById("joinClassForm").addEventListener("submit", async (event) => {
  event.preventDefault();
  const code = document.getElementById("classCodeInput").value.trim();
  if (!code) return alert("Please enter a class code.");
  try {
    const { classroom } = await ExamGuardApi.joinClass(code);
    document.getElementById("classCodeInput").value = "";
    await renderStudentClassroom();
    alert(`You joined ${classroom.name}.`);
  } catch (error) {
    alert(error.message);
  }
});

document.getElementById("joinExamForm")?.addEventListener("submit", async (event) => {
  event.preventDefault();
  const examKey = document.getElementById("examKeyInput")?.value.trim();
  if (!examKey) return alert("Please enter an exam key.");
  try {
    const { exam } = await ExamGuardApi.accessExamByKey(examKey.toUpperCase());
    const target = `/take-exam?examId=${exam.id}`;
    window.ExamGuardRoute?.save(target);
    window.location.href = target;
  } catch (error) {
    alert(error.message || "Invalid or inactive exam key.");
  }
});

document.getElementById("examKeyInput")?.addEventListener("input", (event) => {
  event.target.value = event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, "").slice(0, 8);
});

window.ExamGuardRoute?.restoreStudentSection?.();

document.querySelectorAll('a[href^="/student#"]').forEach((link) => {
  link.addEventListener("click", () => {
    const href = link.getAttribute("href");
    if (href) window.ExamGuardRoute?.save(href);
  });
});

renderStudentClassroom();
