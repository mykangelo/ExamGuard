const studentIdentity = document.getElementById("studentIdentity");
const availableExamList = document.getElementById("availableExamList");
const emptyMessage = (text) => {
  const item = document.createElement("div");
  item.className = "eg-empty";
  item.textContent = text;
  return item;
};

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
      title.className = "text-lg font-semibold";
      title.textContent = exam.title;
      details.className = "text-slate-300";
      details.textContent = exam.attempt
        ? `Submitted | Score: ${exam.attempt.score}/${exam.attempt.total}`
        : `${exam.questionCount} question(s) | ${exam.timeLimit} minutes`;
      content.append(title, details);
      const action = document.createElement("a");
      action.className = exam.attempt ? "eg-btn-secondary" : "eg-btn-primary";
      action.textContent = exam.attempt ? "Completed" : "Take Exam";
      action.href = exam.attempt ? "#" : `/take-exam?examId=${exam.id}`;
      if (exam.attempt) action.addEventListener("click", (event) => event.preventDefault());
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

renderStudentClassroom();
