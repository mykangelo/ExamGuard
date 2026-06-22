(function () {
  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || "";
  }

  async function request(path, options = {}) {
    const response = await fetch(path, {
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": csrfToken(),
        ...(options.headers || {}),
      },
      ...options,
    });

    const result = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(result.error || result.message || "Request failed.");
    return result;
  }

  window.ExamGuardApi = {
    request,
    login: (email, password, role) =>
      request("/api/auth/login", { method: "POST", body: JSON.stringify({ email, password, role }) }),
    logout: () => request("/api/auth/logout", { method: "POST" }),
    me: () => request("/api/auth/me"),
    classes: () => request("/api/classes"),
    professorClasses: () => request("/api/professor/classes"),
    createClass: (name, subject) =>
      request("/api/classes", { method: "POST", body: JSON.stringify({ name, subject }) }),
    deleteClass: (id) => request(`/api/classes/${id}`, { method: "DELETE" }),
    joinClass: (code) => request("/api/classes/join", { method: "POST", body: JSON.stringify({ code }) }),
    exams: () => request("/api/exams"),
    exam: (id) => request(`/api/exams/${id}`),
    createExam: (exam) => request("/api/exams", { method: "POST", body: JSON.stringify(exam) }),
    deleteExam: (id) => request(`/api/exams/${id}`, { method: "DELETE" }),
    assignExam: (examId, classId) =>
      request("/api/assignments", { method: "POST", body: JSON.stringify({ examId, classId }) }),
    submitExam: (examId, payload) =>
      request(`/api/exams/${examId}/attempts`, { method: "POST", body: JSON.stringify(payload) }),
    dashboard: () => request("/api/professor/dashboard"),
  };
})();
