<?php

namespace Drupal\smart_content\Plugin\smart_content\SegmentSetStorage;

use Drupal\smart_content\SegmentSet;
use Drupal\smart_content\SegmentSetStorage\SegmentSetStorageBase;

/**
 * Provides a 'inline' SegmentSetStorage.
 *
 * @SmartSegmentSetStorage(
 *  id = "inline",
 *  label = @Translation("+ Create custom segment set"),
 *  global = false,
 * )
 */
class Inline extends SegmentSetStorageBase {

  /**
   * Segment set configuration.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function load() {
    return SegmentSet::fromArray((array) $this->settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    $configuration['settings'] = (array) $this->segmentSet->toArray();
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'settings' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);

    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

}
