<?php

namespace Drupal\smart_content\Plugin\smart_content\SegmentSetStorage;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\smart_content\Entity\SegmentSetConfig;
use Drupal\smart_content\SegmentSetStorage\SegmentSetStorageBase;

/**
 * Provides a 'segment_set' SegmentSetStorage.
 *
 * @SmartSegmentSetStorage(
 *  id = "global_segment_set",
 *  label = @Translation("Global Segment Sets"),
 *  global = true,
 *  deriver = "Drupal\smart_content\Plugin\smart_content\SegmentSetStorage\Derivative\GlobalSegmentSetDeriver"
 * )
 */
class GlobalSegmentSet extends SegmentSetStorageBase implements CacheableDependencyInterface {

  /**
   * The segment set entity.
   *
   * @var \Drupal\smart_content\Entity\SegmentSetConfig
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function load() {
    if (!isset($this->entity)) {
      [$plugin_id, $entity_id] = explode(':', $this->getPluginId());
      $this->entity = SegmentSetConfig::load($entity_id);
    }
    return $this->entity->getSegmentSet();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    if (isset($this->entity)) {
      return $this->entity->getCacheContexts();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if (isset($this->entity)) {
      return $this->entity->getCacheTags();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    if (isset($this->entity)) {
      return $this->entity->getCacheMaxAge();
    }
    return 0;
  }

}
