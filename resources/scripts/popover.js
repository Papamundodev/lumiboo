document.addEventListener("DOMContentLoaded", () => {
  const burger = document.querySelector("#theme-navbar-toggler");
  const popover = document.getElementById("navmenu-header-mobile");

  if (!burger || !popover) {
    return;
  }

  burger.addEventListener("click", () => {
    burger.classList.toggle("open");
  });

  popover.addEventListener("toggle", (event) => {
    if (event.newState === "closed") {
      burger.classList.remove("open");
      // Ensure mobile menu is not focusable when closed
      const mobileMenuLinks = popover.querySelectorAll("a, button, [tabindex]");
      mobileMenuLinks.forEach((link) => {
        if (link.hasAttribute("tabindex") && link.getAttribute("tabindex") !== "-1") {
          link.setAttribute("data-original-tabindex", link.getAttribute("tabindex"));
        }
        link.setAttribute("tabindex", "-1");
      });
    } else if (event.newState === "open") {
      // Restore focusability when menu is open
      const mobileMenuLinks = popover.querySelectorAll("a, button, [tabindex]");
      mobileMenuLinks.forEach((link) => {
        const originalTabindex = link.getAttribute("data-original-tabindex");
        if (originalTabindex !== null) {
          link.setAttribute("tabindex", originalTabindex);
          link.removeAttribute("data-original-tabindex");
        } else {
          link.removeAttribute("tabindex");
        }
      });
    }
  });
});
