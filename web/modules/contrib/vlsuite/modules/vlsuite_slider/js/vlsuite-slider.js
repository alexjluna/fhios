(function ($, Drupal, once, window) {
  Drupal.behaviors.vlsuite_slider = {
    attach(context) {
      once('vlsuite-slider', 'div[data-vlsuite-slider]', context).forEach(slider);
    }
  };

  /**
   * Slider.
   */
  function slider(initElement) {
    var config = JSON.parse(initElement.dataset.vlsuiteSlider);
    if (!config.active || typeof window.Swiper !== 'function') {
      return;
    }
    var sliderElement = initElement.querySelector(config['scope_selector']);

    if (sliderElement === null) {
      return;
    }

    var rows = config['rows'] ?? false;

    var swiperElement = document.createElement('div');
    swiperElement.classList.add('swiper');
    var swiperWrapperElement = document.createElement('div');
    swiperWrapperElement.classList.add('swiper-wrapper');
    sliderElement.querySelectorAll(config['scope_items_selector']).forEach((block, index) => {
      block.classList.add('swiper-slide');
      swiperWrapperElement.appendChild(block);
    });
    swiperElement.appendChild(swiperWrapperElement);
    sliderElement.appendChild(swiperElement);

    var slidesPerViewMedium = config['slides_per_view_medium'] ?? null;
    var slidesPerViewSmall = config['slides_per_view_small'] ?? null;
    var slidesPerView = config['slides_per_view'] ?? 1;
    var slidesPerViewOriginal = slidesPerView;
    var swiperOptions = {
      slidesPerView: slidesPerViewSmall ?? (slidesPerViewMedium ?? slidesPerView)
    };
    if (rows !== false && parseInt(rows) !== 1) {
      swiperOptions['grid'] = {
        rows: rows,
        fill: 'row'
      };
    }
    if ((config['auto_height'] ?? false) && !(rows !== false && parseInt(rows) !== 1)) {
      swiperOptions['autoHeight'] = true;
    }
    if (config['space_between'] ?? true) {
      swiperOptions['spaceBetween'] = config['space_between'] ?? 16;
    }
    if (config['pagination'] ?? false) {
      var pagination = document.createElement('div');
      pagination.classList.add('swiper-pagination');
      swiperElement.appendChild(pagination);

      swiperOptions['pagination'] = {
        el: pagination,
        dynamicBullets: true,
        clickable: true
      };
    }

    if (slidesPerViewSmall !== null && slidesPerViewSmall !== slidesPerViewOriginal) {
      var small_size = config['small_size'] ?? 768;
      swiperOptions['breakpoints'] = {};
      swiperOptions['breakpoints'][small_size] = {
        slidesPerView: slidesPerViewMedium ?? slidesPerView,
        pagination: {
          el: pagination,
          dynamicBullets: true,
          clickable: true
        }
      };
    }
    if (slidesPerViewMedium !== null && slidesPerViewMedium !== slidesPerViewOriginal) {
      var medium_size = config['medium_size'] ?? 1280;
      swiperOptions['breakpoints'] = swiperOptions['breakpoints'] ?? {};
      swiperOptions['breakpoints'][medium_size] = {
        slidesPerView: slidesPerView,
        pagination: {
          el: pagination,
          dynamicBullets: true,
          clickable: true
        }
      };
    }
    if (config['navigation'] ?? false) {
      var naginationNext = document.createElement('div');
      naginationNext.classList.add('swiper-button-next');
      var naginationPrev = document.createElement('div');
      naginationPrev.classList.add('swiper-button-prev');
      swiperElement.appendChild(naginationNext);
      swiperElement.appendChild(naginationPrev);
      swiperOptions['navigation'] = {
        nextEl: naginationNext,
        prevEl: naginationPrev
      };
    }
    if (config['autoplay'] ?? false) {
      swiperOptions['autoplay'] = {
        'delay': config['autoplay'] * 1000,
        'pauseOnMouseEnter': true
      };
    }
    new window.Swiper(swiperElement, swiperOptions);
  }
}(jQuery, Drupal, once, window));
