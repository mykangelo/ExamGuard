(async function () {
  const redirectForRole = (role) => {
    const target = window.ExamGuardRoute?.restoreForRole(role)
      ?? (role === 'professor' ? '/professor?view=exams' : '/student');
    location.replace(target);
  };

  try {
    const { user } = await ExamGuardApi.me();
    const requiredRole = document.body.dataset.role;
    if (requiredRole && user.role !== requiredRole) {
      redirectForRole(user.role);
    }
  } catch (error) {
    window.ExamGuardRoute?.save(window.location.pathname + window.location.search + window.location.hash);
    location.replace('/login');
    return;
  }

  document.querySelectorAll('[data-logout], a[href="/login"]').forEach((link) =>
    link.addEventListener('click', async (event) => {
      event.preventDefault();
      try {
        await ExamGuardApi.logout();
      } finally {
        location.href = '/login';
      }
    }),
  );
})();
