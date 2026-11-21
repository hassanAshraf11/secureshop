document.addEventListener("DOMContentLoaded", () => {
  const key = document.querySelector("[data-verification-key]");
  if (key && /^#[A-Za-z0-9]{64}$/.test(location.hash)) {
    key.value = location.hash.slice(1);
    history.replaceState(null, "", location.pathname);
  }
  document.querySelectorAll("[data-auto-submit]").forEach((select) => {
    select.addEventListener("change", () => select.form.requestSubmit());
  });
});
