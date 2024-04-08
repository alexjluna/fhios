<?php

namespace Drupal\smart_content\Cache;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\AjaxBasePageNegotiator;

/**
 * Provides temporary solve for cacheable ajax requests.
 *
 * @link https://www.drupal.org/project/drupal/issues/956186.
 *
 * @see Drupal\smart_content\Cache\CacheableAjaxResponse
 */
class CacheableAjaxBasePageNegotiator extends AjaxBasePageNegotiator {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $ajax_page_state = $this->requestStack->getCurrentRequest()->query->all('ajax_page_state');
    return !empty($ajax_page_state['theme']) && isset($ajax_page_state['theme_token']);
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $ajax_page_state = $this->requestStack->getCurrentRequest()->query->all('ajax_page_state');
    $theme = $ajax_page_state['theme'];
    $token = $ajax_page_state['theme_token'];

    // Prevent a request forgery from giving a person access to a theme they
    // shouldn't be otherwise allowed to see. However, since everyone is
    // allowed to see the default theme, token validation isn't required for
    // that, and bypassing it allows most use-cases to work even when accessed
    // from the page cache.
    if ($theme === $this->configFactory->get('system.theme')->get('default') || $this->csrfGenerator->validate($token, $theme)) {
      return $theme;
    }
  }

}
