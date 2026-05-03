(function () {
  const conteneur = document.getElementById("conteneur");
  const showInscription = document.querySelectorAll(".js-show-inscription");
  const showConnexion = document.querySelectorAll(".js-show-connexion");

  if (!conteneur) {
    return;
  }

  function setActif(on) {
    if (on) {
      conteneur.classList.add("actif");
    } else {
      conteneur.classList.remove("actif");
    }
    syncMobileBar();
  }

  function syncMobileBar() {
    const active = conteneur.classList.contains("actif");
    document.querySelectorAll(".js-mb-conn").forEach(function (b) {
      b.classList.toggle("is-active", !active);
    });
    document.querySelectorAll(".js-mb-insc").forEach(function (b) {
      b.classList.toggle("is-active", active);
    });
  }

  showInscription.forEach(function (btn) {
    btn.addEventListener("click", function () {
      setActif(true);
    });
  });

  showConnexion.forEach(function (btn) {
    btn.addEventListener("click", function () {
      setActif(false);
    });
  });

  syncMobileBar();
})();
