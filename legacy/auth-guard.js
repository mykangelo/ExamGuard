(async function () {
  try {
    const { user } = await ExamGuardApi.me();
    const requiredRole = document.body.dataset.role;
    if (requiredRole && user.role !== requiredRole) location.replace(user.role === "professor" ? "professor.html" : "student.html");
  } catch (error) {
    location.replace("login.html");
    return;
  }
  document.querySelectorAll('a[href="login.html"]').forEach((link) => link.addEventListener("click", async (event) => { event.preventDefault(); try { await ExamGuardApi.logout(); } finally { location.href = "login.html"; } }));
})();
