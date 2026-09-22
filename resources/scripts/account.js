/**
 * Compte client — toggles Oui/Non du formulaire de personnalisation.
 * Cible : [data-account-toggle] / [data-account-toggle-target] / [data-account-toggle-on].
 * Enqueued only on the native view-order endpoint (Vite lazy entry `account`).
 */
function bindAccountToggles(root = document) {
  root.querySelectorAll("[data-account-toggle]").forEach((group) => {
    const target = group.querySelector("[data-account-toggle-target]");

    if (!target) {
      return;
    }

    const sync = () => {
      target.hidden = !group.querySelector("[data-account-toggle-on]:checked");
    };

    group.addEventListener("change", sync);
    sync();
  });
}

bindAccountToggles();
