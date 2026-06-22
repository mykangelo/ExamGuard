const classNameInput = document.getElementById("classNameInput");
const classSubjectInput = document.getElementById("classSubjectInput");
const classList = document.getElementById("classList");
const examSelect = document.getElementById("examSelect");
const classSelect = document.getElementById("classSelect");
const option = (value, label) => { const item = document.createElement("option"); item.value = value; item.textContent = label; return item; };
const emptyMessage = (text) => { const item = document.createElement("div"); item.className = "builder-empty"; item.textContent = text; return item; };

async function renderClasses() {
  try {
    const [{ classes }, { exams }] = await Promise.all([ExamGuardApi.professorClasses(), ExamGuardApi.exams()]);
    classList.replaceChildren(); examSelect.replaceChildren(); classSelect.replaceChildren();
    document.getElementById("classCount").textContent = `${classes.length} class(es)`;
    if (!classes.length) classList.append(emptyMessage("No classes created yet."));
    classes.forEach((classroom) => {
      classSelect.append(option(classroom.id, `${classroom.name} - ${classroom.subject}`));
      const card = document.createElement("article"); card.className = "builder-item";
      const content = document.createElement("div"); const title = document.createElement("h3"); title.textContent = classroom.name;
      const details = document.createElement("p"); details.textContent = `${classroom.subject} | Class code: ${classroom.code}`;
      const members = document.createElement("p"); members.textContent = classroom.students.length ? `Students: ${classroom.students.map((item) => item.name).join(", ")}` : "No students enrolled yet.";
      const assignments = document.createElement("p"); assignments.textContent = classroom.exams.length ? `Exams: ${classroom.exams.map((item) => item.title).join(", ")}` : "No exams assigned yet.";
      content.append(title, details, members, assignments);
      const remove = document.createElement("button"); remove.className = "btn btn-danger"; remove.type = "button"; remove.textContent = "Delete";
      remove.addEventListener("click", async () => { if (confirm("Delete this class and its enrollments?")) { await ExamGuardApi.deleteClass(classroom.id); renderClasses(); } });
      card.append(content, remove); classList.append(card);
    });
    exams.forEach((exam) => examSelect.append(option(exam.id, exam.title)));
    document.querySelector("#assignExamForm button").disabled = !exams.length || !classes.length;
  } catch (error) { classList.replaceChildren(emptyMessage(error.message)); }
}

document.getElementById("createClassForm").addEventListener("submit", async (event) => {
  event.preventDefault(); const name = classNameInput.value.trim(); const subject = classSubjectInput.value.trim();
  if (!name || !subject) return alert("Please enter a class name and subject.");
  try { const { classroom } = await ExamGuardApi.createClass(name, subject); classNameInput.value = ""; classSubjectInput.value = ""; await renderClasses(); alert(`Class created. Join code: ${classroom.code}`); } catch (error) { alert(error.message); }
});
document.getElementById("assignExamForm").addEventListener("submit", async (event) => { event.preventDefault(); if (!examSelect.value || !classSelect.value) return; try { await ExamGuardApi.assignExam(Number(examSelect.value), Number(classSelect.value)); await renderClasses(); alert("Exam assigned successfully!"); } catch (error) { alert(error.message); } });
renderClasses();
