/**
 * Espace compte Lumiboo — interactions.
 *
 * Trois comportements seulement : affichage progressif de l'historique,
 * bascule lecture/édition du profil, et affichage conditionnel des champs
 * logo et texte selon le choix « Sans ». Toutes les règles métier (J-7,
 * complétude, propriété du dossier) restent côté serveur.
 */

const ready = (fn) => {
  if (document.readyState !== 'loading') {
    fn();
    return;
  }
  document.addEventListener('DOMContentLoaded', fn);
};

/**
 * « Voir plus » : révèle les cartes suivantes par lots, sans réordonner ni
 * dupliquer celles déjà affichées.
 */
const initLoadMore = () => {
  const button = document.querySelector('[data-account-load-more]');
  const list = document.querySelector('[data-account-paginate]');

  if (!button || !list) return;

  const step = parseInt(list.dataset.accountPaginate, 10) || 10;

  button.addEventListener('click', () => {
    const hidden = list.querySelectorAll('.account-card[hidden]');

    Array.from(hidden)
      .slice(0, step)
      .forEach((card) => card.removeAttribute('hidden'));

    if (list.querySelectorAll('.account-card[hidden]').length === 0) {
      button.closest('.button-wrapper')?.setAttribute('hidden', '');
    }
  });
};

/**
 * Le bouton « Modifier mes informations » ouvre l'édition ; il ne sauvegarde
 * pas. L'e-mail reste géré par le compte WooCommerce et n'est pas déverrouillé.
 */
const initProfile = () => {
  const section = document.querySelector('[data-account-profile]');

  if (!section) return;

  const inputs = section.querySelectorAll('[data-account-profile-input]');
  const open = section.querySelector('[data-account-profile-open]');
  const submit = section.querySelector('[data-account-profile-submit]');
  const cancel = section.querySelector('[data-account-profile-cancel]');
  const initial = new Map();

  const setEditing = (editing) => {
    inputs.forEach((input) => {
      if (input.name === 'billing_email') return;
      input.toggleAttribute('readonly', !editing);
    });

    open?.toggleAttribute('hidden', editing);
    submit?.toggleAttribute('hidden', !editing);
    cancel?.toggleAttribute('hidden', !editing);
  };

  open?.addEventListener('click', () => {
    inputs.forEach((input) => initial.set(input, input.value));
    setEditing(true);
    section.querySelector('[data-account-profile-input]:not([readonly])')?.focus();
  });

  cancel?.addEventListener('click', () => {
    initial.forEach((value, input) => {
      input.value = value;
    });
    setEditing(false);
  });
};

/**
 * Affiche le champ uniquement lorsque le client choisit de fournir la valeur.
 * Un champ masqué n'équivaut jamais à un choix « Sans » : c'est le radio
 * soumis au serveur qui fait foi.
 */
const initToggles = () => {
  document.querySelectorAll('[data-account-toggle]').forEach((group) => {
    const on = group.querySelector('[data-account-toggle-on]');
    const off = group.querySelector('[data-account-toggle-off]');
    const target = group.querySelector('[data-account-toggle-target]');

    if (!on || !off || !target) return;

    const sync = () => target.toggleAttribute('hidden', !on.checked);

    on.addEventListener('change', sync);
    off.addEventListener('change', sync);
    sync();
  });
};

ready(() => {
  initLoadMore();
  initProfile();
  initToggles();
});
