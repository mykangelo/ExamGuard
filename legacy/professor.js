async function renderProfessorData() {
  const body = document.getElementById("participationLogBody"); body.replaceChildren();
  try {
    const { summary, attempts } = await ExamGuardApi.dashboard();
    document.getElementById("enrolledStudentCount").textContent = summary.enrolledStudents;
    document.getElementById("recordedWarningCount").textContent = summary.warnings;
    document.getElementById("submissionCount").textContent = summary.submissions;
    if (!attempts.length) { const row = document.createElement("tr"); const cell = document.createElement("td"); cell.colSpan = 4; cell.textContent = "No student participation recorded yet."; row.append(cell); body.append(row); return; }
    attempts.forEach((attempt) => { const row = document.createElement("tr"); [attempt.studentName, attempt.examTitle, new Date(`${attempt.submittedAt}Z`).toLocaleString()].forEach((text) => { const cell = document.createElement("td"); cell.textContent = text; row.append(cell); }); const statusCell = document.createElement("td"); const badge = document.createElement("span"); badge.className = `status-badge ${attempt.warningCount ? "warning" : "success"}`; badge.textContent = `${attempt.score}/${attempt.total} | ${attempt.warningCount} warning(s)`; statusCell.append(badge); row.append(statusCell); body.append(row); });
  } catch (error) { const row = document.createElement("tr"); const cell = document.createElement("td"); cell.colSpan = 4; cell.textContent = error.message; row.append(cell); body.append(row); }
}
renderProfessorData();
