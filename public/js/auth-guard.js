(async function () {
  try {
    const { user } = await ExamGuardApi.me();
    const requiredRole = document.body.dataset.role;
    if (requiredRole && user.role !== requiredRole) {
      location.replace(user.role === "professor" ? "/professor" : "/student");
    }
  } catch (error) {
    location.replace("/login");
    return;
  }

  document.querySelectorAll("[data-logout], a[href='/login']").forEach((link) =>
    link.addEventListener("click", async (event) => {
      event.preventDefault();
      try {
        await ExamGuardApi.logout();
      } finally {
        location.href = "/login";
      }
    })
  );
})();
