const form = document.getElementById("custom-contact-form");
if (form) {
  const siteKey = form.dataset.siteKey || "";
  const tokenField = form.querySelector('input[name="cf_recaptcha_token"]');

  if (siteKey && tokenField && window.grecaptcha) {
    let submitting = false;

    form.addEventListener("submit", (event) => {
      if (submitting) return;
      event.preventDefault();
      submitting = true;

      window.grecaptcha.ready(() => {
        window.grecaptcha
          .execute(siteKey, { action: "contact" })
          .then((token) => {
            tokenField.value = token;
            form.submit();
          })
          .catch(() => {
            submitting = false;
          });
      });
    });
  }
}
