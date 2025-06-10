const togglePassword = document.getElementById("togglePassword");
const password = document.getElementById("password");

togglePassword.addEventListener("click", () => {
  const type =
    password.getAttribute("type") === "password" ? "text" : "password";
  password.setAttribute("type", type);

  togglePassword.setAttribute(
    "name",
    type === "password" ? "eye-outline" : "eye-off-outline"
  );
});
