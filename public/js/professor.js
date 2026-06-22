async function renderProfessorData() {
  const body = document.getElementById("participationLogBody");
  body.replaceChildren();
  try {
    const { summary, attempts } = await ExamGuardApi.dashboard();
    document.getElementById("enrolledStudentCount").textContent = summary.enrolledStudents;
    document.getElementById("recordedWarningCount").textContent = summary.warnings;
    document.getElementById("submissionCount").textContent = summary.submissions;

    if (!attempts.length) {
      const row = document.createElement("tr");
      const cell = document.createElement("td");
      cell.colSpan = 4;
      cell.className = "px-4 py-4 text-slate-400";
      cell.textContent = "No student participation recorded yet.";
      row.append(cell);
      body.append(row);
      return;
    }

    attempts.forEach((attempt) => {
      const row = document.createElement("tr");
      row.className = "border-t border-white/10";
      [attempt.studentName, attempt.examTitle, new Date(attempt.submittedAt).toLocaleString()].forEach((text) => {
        const cell = document.createElement("td");
        cell.className = "px-4 py-3";
        cell.textContent = text;
        row.append(cell);
      });
      const statusCell = document.createElement("td");
      statusCell.className = "px-4 py-3";
      const badge = document.createElement("span");
      badge.className = attempt.warningCount ? "eg-badge-warning" : "eg-badge-success";
      badge.textContent = `${attempt.score}/${attempt.total} | ${attempt.warningCount} warning(s)`;
      statusCell.append(badge);
      row.append(statusCell);
      body.append(row);
    });
  } catch (error) {
    const row = document.createElement("tr");
    const cell = document.createElement("td");
    cell.colSpan = 4;
    cell.className = "px-4 py-4 text-red-300";
    cell.textContent = error.message;
    row.append(cell);
    body.append(row);
  }
}

renderProfessorData();
