<?php

namespace Drupal\smart_content\SegmentSetStorage;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\smart_content\SegmentSet;

/**
 * Base class for segment set storage plugins.
 */
abstract class SegmentSetStorageBase extends PluginBase implements SegmentSetStorageInterface, ConfigurableInterface {

  /**
   * The segment set object.
   *
   * @var \Drupal\smart_content\SegmentSet
   */
  protected $segmentSet;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->segmentSet = $this->load($configuration);
    if (empty($this->segmentSet)) {
      $this->segmentSet = SegmentSet::fromArray([]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSegmentSet(SegmentSet $segmentSet) {
    $this->segmentSet = $segmentSet;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSegmentSet() {
    return $this->segmentSet;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings() {
    return ['segments' => $this->getSegmentSet()->getAttachedSettings()];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return $this->getSegmentSet()->getLibraries();
  }

}
