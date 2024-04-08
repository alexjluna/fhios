<?php

namespace Drupal\smart_content\Cache;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;

/**
 * A AjaxResponse that contains and can expose cacheability metadata.
 *
 * Supports Drupal's caching concepts: cache tags for invalidation and cache
 * contexts for variations.  This class should eventually be replaced by a core
 * equivalent.  See @link https://www.drupal.org/project/drupal/issues/2701085.
 *
 * @see \Drupal\Core\Cache\Cache
 * @see \Drupal\Core\Cache\CacheableMetadata
 * @see \Drupal\Core\Cache\CacheableResponseTrait
 */
class CacheableAjaxResponse extends AjaxResponse implements CacheableResponseInterface {
  use CacheableResponseTrait;

}
