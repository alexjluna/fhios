(function ($, Drupal, once, window) {
  Drupal.behaviors.vlsuite_collection_gallery = {
    attach(context) {
      once('vlsuite-collection-gallery-slides-sync', '.vlsuite-block__vlsuite-collection-gallery .vlsuite-layout', context).forEach(slidesSync);
    }
  };

  /**
   * Collection gallery slides sync.
   */
  function slidesSync(initElement) {
    if (initElement.querySelectorAll('.vlsuite-block__block-contentvlsuite-collection-galleryvlsuite-medias').length !== 2 || typeof window.Swiper !== 'function') {
      return;
    }
    var main = initElement.querySelectorAll('.vlsuite-block__block-contentvlsuite-collection-galleryvlsuite-medias')[0];
    var mainSlide = main.querySelector('.swiper');
    var thumbnails = initElement.querySelectorAll('.vlsuite-block__block-contentvlsuite-collection-galleryvlsuite-medias')[1];
    var thumbnailsSlide = thumbnails.querySelector('.swiper');
    if (typeof mainSlide === 'object' && typeof mainSlide.swiper === 'object' && typeof thumbnailsSlide === 'object' && typeof thumbnailsSlide.swiper === 'object') {
      mainSlide.swiper.thumbs.swiper = thumbnailsSlide.swiper;
      mainSlide.swiper.thumbs.init();
      thumbnailsSlide.swiper.slideTo(mainSlide.swiper.activeIndex);
      mainSlide.swiper.on("slideChange", function () {
        thumbnailsSlide.swiper.slideTo(mainSlide.swiper.activeIndex);
      });
      thumbnails.classList.add('swiper-sync-active');
    }
  }
}(jQuery, Drupal, once, window));
