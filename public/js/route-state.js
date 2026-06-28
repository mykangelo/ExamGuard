(function () {
  'use strict';

  const STORAGE_ROUTE = 'examguard:lastRoute';
  const STORAGE_STUDENT_SECTION = 'examguard:studentSection';
  const STORAGE_TAKE_EXAM_ID = 'examguard:takeExamId';

  const AUTH_ROUTES = [
    /^\/login(?:\?|$)/,
    /^\/register(?:\?|$)/,
    /^\/verify-email(?:\?|$)/,
  ];

  function normalizePath(path) {
    if (!path) return '/';
    try {
      const url = new URL(path, window.location.origin);
      return `${url.pathname}${url.search}${url.hash}`;
    } catch (_) {
      return path;
    }
  }

  function currentPath() {
    return normalizePath(
      `${window.location.pathname}${window.location.search}${window.location.hash}`,
    );
  }

  function isAuthPage(path) {
    return AUTH_ROUTES.some((pattern) => pattern.test(normalizePath(path)));
  }

  function isPersistable(path) {
    const normalized = normalizePath(path);
    if (normalized === '/' || isAuthPage(normalized)) return false;
    return true;
  }

  function roleForPath(path) {
    const normalized = normalizePath(path);
    if (normalized.startsWith('/professor') || normalized.startsWith('/create-exam')) {
      return 'professor';
    }
    if (
      normalized.startsWith('/student')
      || normalized.startsWith('/take-exam')
      || normalized.startsWith('/exam-room')
    ) {
      return 'student';
    }
    return null;
  }

  function defaultDashboard(role) {
    return role === 'professor' ? '/professor?view=exams' : '/student';
  }

  function save(path) {
    const route = normalizePath(path || currentPath());
    if (!isPersistable(route)) return;
    try {
      sessionStorage.setItem(STORAGE_ROUTE, route);
    } catch (_) {}

    if (route.startsWith('/student#')) {
      try {
        sessionStorage.setItem(STORAGE_STUDENT_SECTION, route.slice('/student'.length));
      } catch (_) {}
    }

    const takeExamMatch = route.match(/^\/take-exam\?examId=(\d+)/);
    if (takeExamMatch) {
      try {
        sessionStorage.setItem(STORAGE_TAKE_EXAM_ID, takeExamMatch[1]);
      } catch (_) {}
    }
  }

  function last() {
    try {
      return sessionStorage.getItem(STORAGE_ROUTE);
    } catch (_) {
      return null;
    }
  }

  function restoreForRole(role) {
    const stored = last();
    if (stored && roleForPath(stored) === role) {
      return stored;
    }
    return defaultDashboard(role);
  }

  function restoreStudentSection() {
    if (!window.location.pathname.startsWith('/student')) return;

    let hash = window.location.hash;
    if (!hash) {
      const storedRoute = last();
      if (storedRoute?.startsWith('/student#')) {
        hash = storedRoute.slice('/student'.length);
      } else {
        try {
          hash = sessionStorage.getItem(STORAGE_STUDENT_SECTION) || '';
        } catch (_) {
          hash = '';
        }
      }

      if (hash) {
        history.replaceState(null, '', `/student${hash}`);
        save(`/student${hash}`);
      }
    } else {
      try {
        sessionStorage.setItem(STORAGE_STUDENT_SECTION, hash);
      } catch (_) {}
      save(currentPath());
    }

    if (!hash) return;

    const target = document.querySelector(hash);
    if (!target) return;

    window.requestAnimationFrame(() => {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  function resolveTakeExamId() {
    const params = new URLSearchParams(window.location.search);
    let examId = params.get('examId');

    if (!examId) {
      try {
        examId = sessionStorage.getItem(STORAGE_TAKE_EXAM_ID);
      } catch (_) {
        examId = null;
      }

      if (examId) {
        const next = `/take-exam?examId=${encodeURIComponent(examId)}`;
        history.replaceState(null, '', next);
        save(next);
      }
    } else {
      try {
        sessionStorage.setItem(STORAGE_TAKE_EXAM_ID, examId);
      } catch (_) {}
      save(currentPath());
    }

    return examId;
  }

  function clearTakeExamId() {
    try {
      sessionStorage.removeItem(STORAGE_TAKE_EXAM_ID);
    } catch (_) {}
  }

  function shouldPreserveStoredRoute(current, stored) {
    if (!stored) return false;
    const normalized = normalizePath(current);
    const storedNormalized = normalizePath(stored);
    if (normalized === '/professor' && storedNormalized.startsWith('/professor?')) return true;
    if (normalized === '/student' && storedNormalized.startsWith('/student')) return true;
    return false;
  }

  function initAutoSave() {
    const current = currentPath();
    const stored = last();
    if (!shouldPreserveStoredRoute(current, stored)) {
      save(current);
    }

    window.addEventListener('beforeunload', () => save(currentPath()));
    window.addEventListener('hashchange', () => save(currentPath()));
    window.addEventListener('popstate', () => {
      window.setTimeout(() => save(currentPath()), 0);
    });
  }

  window.ExamGuardRoute = {
    save,
    last,
    currentPath,
    restoreForRole,
    defaultDashboard,
    restoreStudentSection,
    resolveTakeExamId,
    clearTakeExamId,
    isPersistable,
    roleForPath,
  };

  initAutoSave();
})();
