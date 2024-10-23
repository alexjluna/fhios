((Drupal, once, window) => {
  /**
   * Scrollpy navlink.
   *
   * @param navLink object
   *   Navlink.
   */
  function scrollSpy(navLink) {
    const handler = (entries) => {
      entries.forEach((entry) => {
        navLink.classList.toggle('active', entry.isIntersecting);
      });
    };

    const observer = new window.IntersectionObserver(handler);
    const observerElement = document.querySelector(
      navLink.getAttribute('href')
    );
    if (observerElement !== 'undefined') {
      observer.observe(observerElement);
    }
  }

  Drupal.behaviors.vlsuite_block_headings_menu = {
    attach(context) {
      once(
        'vlsuite-block-headings-menu',
        '.vlsuite-block-headings-menu .vlsuite-block-headings-menu__nav-link',
        context
      ).forEach(scrollSpy);
    }
  };
})(Drupal, once, window);
