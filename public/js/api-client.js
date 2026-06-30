(function () {
  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || "";
  }

  function sanitizeErrorMessage(message) {
    if (!message || typeof message !== 'string') {
      return 'Something went wrong. Please try again.';
    }
    if (/SQLSTATE|SQL:|Unknown column|field list|Connection:/i.test(message)) {
      return 'Unable to complete this request. Please refresh and try again.';
    }
    return message;
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
        if (!response.ok) {
          let message = sanitizeErrorMessage(result.error || result.message || 'Request failed.');
          if (result.errors && typeof result.errors === 'object') {
            const firstError = Object.values(result.errors).flat()[0];
            if (firstError) message = sanitizeErrorMessage(firstError);
          }
          const err = new Error(message);
          err.errors = result.errors && typeof result.errors === 'object' ? result.errors : null;
          err.status = response.status;
          err.code = result.code || null;
          err.exam = result.exam || null;
          err.needs_verification  = result.needs_verification  || false;
          err.email               = result.email               || null;
          err.locked_out          = result.locked_out          || false;
          err.retry_after         = result.retry_after         || null;
          err.attempts_remaining  = result.attempts_remaining  ?? null;
          throw err;
        }
    return result;
  }

  async function uploadRequest(path, formData) {
    const response = await fetch(path, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: formData,
    });

    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
      let message = sanitizeErrorMessage(result.error || result.message || 'Upload failed.');
      if (result.errors && typeof result.errors === 'object') {
        const firstError = Object.values(result.errors).flat()[0];
        if (firstError) message = sanitizeErrorMessage(firstError);
      }
      const err = new Error(message);
      err.errors = result.errors && typeof result.errors === 'object' ? result.errors : null;
      throw err;
    }
    return result;
  }

  window.ExamGuardApi = {
    request,
    login: (email, password, role, honeypot) =>
      request("/api/auth/login", { method: "POST", body: JSON.stringify({ email, password, role, website: honeypot || "" }) }),
    register: (name, email, password, password_confirmation, role, honeypot) =>
      request("/api/auth/register", { method: "POST", body: JSON.stringify({ name, email, password, password_confirmation, role, website: honeypot || "" }) }),
    forgotPassword: (email, honeypot) =>
      request("/api/auth/forgot-password", { method: "POST", body: JSON.stringify({ email, website: honeypot || "" }) }),
    resetPassword: (payload) =>
      request("/api/auth/reset-password", { method: "POST", body: JSON.stringify(payload) }),
    contact: (payload) =>
      request("/api/contact", { method: "POST", body: JSON.stringify(payload) }),
    logout: () => request("/api/auth/logout", { method: "POST" }),
    me: () => request("/api/auth/me"),
    updateProfile: (payload) =>
      request("/api/auth/profile", { method: "PUT", body: JSON.stringify(payload) }),
    updatePassword: (payload) =>
      request("/api/auth/password", { method: "PUT", body: JSON.stringify(payload) }),
    updatePreferences: (payload) =>
      request("/api/auth/preferences", { method: "PUT", body: JSON.stringify(payload) }),
    uploadAvatar: (file) => {
      const formData = new FormData();
      formData.append('avatar', file);
      return uploadRequest('/api/auth/avatar', formData);
    },
    logoutAllSessions: () => request('/api/auth/logout-all', { method: 'POST' }),
    deleteAccount: (password) =>
      request('/api/auth/account', { method: 'DELETE', body: JSON.stringify({ password }) }),
    classes: () => request("/api/classes"),
    professorClasses: () => request("/api/professor/classes"),
    createClass: (name, subject) =>
      request("/api/classes", { method: "POST", body: JSON.stringify({ name, subject }) }),
    deleteClass: (id) => request(`/api/classes/${id}`, { method: "DELETE" }),
    joinClass: (code) => request("/api/classes/join", { method: "POST", body: JSON.stringify({ code }) }),
    exams: () => request("/api/exams"),
    accessExamByKey: (examKey) =>
      request("/api/exams/access-by-key", { method: "POST", body: JSON.stringify({ examKey }) }),
    exam: (id) => request(`/api/exams/${id}`),
    createExam: (exam) => request("/api/exams", { method: "POST", body: JSON.stringify(exam) }),
    updateExam: (id, exam) => request(`/api/exams/${id}`, { method: "PUT", body: JSON.stringify(exam) }),
    deleteExam: (id) => request(`/api/exams/${id}`, { method: "DELETE" }),
    duplicateExam: (id) => request(`/api/exams/${id}/duplicate`, { method: "POST" }),
    closeExam: (id) => request(`/api/exams/${id}/close`, { method: "POST" }),
    scheduleExam: (id, payload) =>
      request(`/api/exams/${id}/schedule`, { method: "PUT", body: JSON.stringify(payload) }),
    assignExam: (examId, classId) =>
      request("/api/assignments", { method: "POST", body: JSON.stringify({ examId, classId }) }),
    submitExam: (examId, payload) =>
      request(`/api/exams/${examId}/attempts`, { method: "POST", body: JSON.stringify(payload) }),
    startExamSession: (examId) =>
      request(`/api/exams/${examId}/attempts/start`, { method: "POST" }),
    examHeartbeat: (examId, attemptId) =>
      request(`/api/exams/${examId}/attempts/${attemptId}/heartbeat`, { method: "POST" }),
    reportViolation: (examId, attemptId, payload) =>
      request(`/api/exams/${examId}/attempts/${attemptId}/violations`, { method: "POST", body: JSON.stringify(payload) }),
    liveSessions: () => request("/api/professor/live-sessions"),
    violationRecords: (severity) => {
      const query = severity ? `?severity=${encodeURIComponent(severity)}` : "";
      return request(`/api/professor/violations${query}`);
    },
    attemptViolations: (attemptId) => request(`/api/professor/attempts/${attemptId}/violations`),
    dashboard: () => request("/api/professor/dashboard"),
    notifications: () => request("/api/professor/notifications"),
    markNotificationsRead: (payload) =>
      request("/api/professor/notifications/read", { method: "PUT", body: JSON.stringify(payload) }),
    studentNotifications: () => request("/api/student/notifications"),
    markStudentNotificationsRead: (payload) =>
      request("/api/student/notifications/read", { method: "PUT", body: JSON.stringify(payload) }),
  };
})();
