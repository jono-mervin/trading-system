document.querySelectorAll("form").forEach((form) => {
  form.addEventListener("submit", () => {
    const button = form.querySelector("button[type='submit'], button:not([type])");
    if (button) {
      button.disabled = true;
      button.classList.add("opacity-70", "cursor-not-allowed");
    }
  });
});
