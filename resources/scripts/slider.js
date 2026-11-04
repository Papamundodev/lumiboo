const SLIDER_NAMES = ["products", "carrousel-left"];

class SliderPrevNextDesktop {
  constructor(el, name) {
    this.nextButton = el.querySelector(`[data-slider-next-${name}]`);
    this.prevButton = el.querySelector(`[data-slider-prev-${name}]`);
    this.wrapper = el.querySelector(`[data-slider-wrapper-${name}]`);
    if (!this.nextButton || !this.prevButton || !this.wrapper) {
      return;
    }
    this.nextButton.addEventListener("click", () => this.move(1));
    this.prevButton.addEventListener("click", () => this.move(-1));
    this.updateUi();
    this.wrapper.addEventListener("scroll", () => {
      this.updateUi();
    });
  }
  get itemToScroll() {
    return parseInt(window.getComputedStyle(this.wrapper).getPropertyValue("--items"), 10) || 1;
  }
  updateUi() {
    const scrollLeft = this.wrapper.scrollLeft;
    const scrollWidth = this.wrapper.scrollWidth;
    const offsetWidth = this.wrapper.offsetWidth;
    if (scrollLeft <= 1) {
      this.prevButton.setAttribute("hidden", "hidden");
    } else {
      this.prevButton.removeAttribute("hidden");
    }
    if (scrollLeft + offsetWidth >= scrollWidth - 1) {
      this.nextButton.setAttribute("hidden", "hidden");
    } else {
      this.nextButton.removeAttribute("hidden");
    }
  }
  move(n) {
    const itemToScroll = this.itemToScroll;
    const currentScrollLeft = this.wrapper.scrollLeft;
    const offsetWidth = this.wrapper.offsetWidth;
    const scrollWidth = this.wrapper.scrollWidth;
    const children = Array.from(this.wrapper.children || []);
    let currentIndex = 0;
    const isAtEnd = currentScrollLeft + offsetWidth >= scrollWidth - 1;

    if (isAtEnd) {
      const lastAlignedGroupStart = Math.floor((children.length - 1) / itemToScroll) * itemToScroll;
      const lastPossibleGroupStart = Math.max(0, children.length - itemToScroll);
      currentIndex = Math.min(lastAlignedGroupStart, lastPossibleGroupStart);
    } else {
      let firstVisibleIndex = 0;
      for (let i = 0; i < children.length; i++) {
        if (children[i].offsetLeft >= currentScrollLeft) {
          firstVisibleIndex = i;
          break;
        }
      }
      currentIndex = Math.floor(firstVisibleIndex / itemToScroll) * itemToScroll;
    }

    let targetIndex = currentIndex + n * itemToScroll;
    let scrollPosition;
    if (targetIndex < 0) {
      scrollPosition = 0;
    } else if (targetIndex >= this.wrapper.children.length) {
      const lastIndex = Math.max(0, this.wrapper.children.length - itemToScroll);
      scrollPosition = this.wrapper.children[lastIndex].offsetLeft;
    } else {
      scrollPosition = this.wrapper.children[targetIndex].offsetLeft;
    }
    this.wrapper.scrollTo({
      left: scrollPosition,
      behavior: "smooth",
    });
  }
}



document.addEventListener("DOMContentLoaded", () => {
  SLIDER_NAMES.forEach((name) => {
    document.querySelectorAll(`[data-slider-${name}]`).forEach((slider) => {
      if (parseInt(slider.dataset.count, 10) > 1) {
        new SliderPrevNextDesktop(slider, name);
      }
    });
  });
});
