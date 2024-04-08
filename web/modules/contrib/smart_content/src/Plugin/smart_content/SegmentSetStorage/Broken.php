<?php

namespace Drupal\smart_content\Plugin\smart_content\SegmentSetStorage;

use Drupal\smart_content\SegmentSet;
use Drupal\smart_content\SegmentSetStorage\SegmentSetStorageBase;

/**
 * Provides a 'broken' segment set storage.
 *
 * Provides a 'broken' segment set storage as a fallback for plugin
 * definitions that can cease existence.  Example: A plugin deriver from
 * a third-party API that removes a field definition from their API.
 *
 * Broken conditions automatically evaluate to false, and will remain until
 * manually removed in the UI.
 *
 * @SmartSegmentSetStorage(
 *  id = "broken",
 *  label = @Translation("Broken"),
 *  global = false,
 * )
 */
class Broken extends SegmentSetStorageBase {

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
