document.getElementById("loginForm").addEventListener("submit", async (event) => {
  event.preventDefault();
  const button = document.getElementById("loginBtn");
  button.disabled = true;
  try {
    const role = document.getElementById("roleSelect").value;
    await ExamGuardApi.login(
      document.getElementById("emailInput").value.trim(),
      document.getElementById("passwordInput").value,
      role
    );
    location.href = role === "professor" ? "/professor" : "/student";
  } catch (error) {
    alert(error.message);
    button.disabled = false;
  }
});
