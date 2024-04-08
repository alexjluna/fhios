<?php

namespace Drupal\smart_content;

use Drupal\smart_content\Condition\ConditionInterface;
use Drupal\smart_content\Condition\ConditionsHelperTrait;
use Drupal\smart_content\Condition\ObjectWithConditionPluginCollectionInterface;

/**
 * A Segment is a group of conditions to be evaluated.
 *
 * Segments that are exposed typed conditions normally contain a single 'Group'
 * condition.  The form or referencing class is generally responsible for
 * CRUD operations on conditions within a Segment.
 */
class Segment implements ObjectWithConditionPluginCollectionInterface {

  use ConditionsHelperTrait;

  /**
   * The UUID for this Segment.
   *
   * We use a UUID in case so that dependencies can have a reliable ID to
   * reference.  This is to avoid instance where future alterations to a
   * SegmentSet don't accidentally create new segments with the same names as
   * removed segments.
   *
   * @var string
   */
  protected $uuid;

  /**
   * If default Segment.
   *
   * This value is only used when multiple segments exist in a SegmentSet.  The
   * SegmentSet is responsible for making sure multiple segments are not set as
   * default.
   *
   * @var bool
   */
  protected $default;

  /**
   * The weight of the Segment.
   *
   * This value is only used when multiple segments exist in a SegmentSet.  The
   * SegmentSet is responsible for sorting segments.
   *
   * @var int
   */
  protected $weight;

  /**
   * The label of the Segment.
   *
   * This value can be set programmatically or made available in the form.
   *
   * @var string
   */
  protected $label;

  /**
   * Returns the Segments Uuid.
   *
   * @return string
   *   The segment uuid.
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Constructs a new Segment.
   *
   * @param string $uuid
   *   (optional)  The Uuid of the segment.
   * @param \Drupal\smart_content\Condition\Conditioninterface[] $conditions
   *   (optional) The condition configuration.
   * @param int $weight
   *   (optional) The segment weight.
   * @param string $label
   *   (optional) The segment label.
   * @param bool $default
   *   (optional) If segment default.
   */
  public function __construct($uuid = NULL, array $conditions = [], $weight = 0, $label = NULL, $default = FALSE) {
    $this->uuid = !empty($uuid) ? $uuid : \Drupal::service('uuid')->generate();
    // Conditions are set as configuration and lazy loaded as needed.
    $this->conditions = $conditions;
    $this->weight = $weight;
    $this->label = $label;
    $this->default = $default;
  }

  /**
   * Gets the plugin collections used by this object.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection[]
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getPluginCollections() {
    return [
      'conditions' => $this->getConditionPluginCollection(),
    ];
  }

  /**
   * Gets the segments weight.
   *
   * @return int
   *   The segments weight.
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * Sets the segments weight.
   *
   * @param int $weight
   *   The weight of the segment.
   *
   * @return $this
   *   Return this.
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * Gets the segments label.
   *
   * @return string
   *   The segments label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Sets the segments label.
   *
   * @param string $label
   *   The label of the segment.
   *
   * @return $this
   *   Return this.
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * Returns if Segment is default.
   *
   * @return bool
   *   Boolean if segment is default.
   */
  public function isDefault() {
    return $this->default;
  }

  /**
   * Set if Segment is default.
   *
   * @param bool $default
   *   (optional) The default value.
   *
   * @return $this
   */
  public function setDefault($default = TRUE) {
    $this->default = $default;
    return $this;
  }

  /**
   * Returns an array Drupal libraries.
   *
   * @return array
   *   An array libraries for SegmentSet processing.
   *
   * @throws \Exception
   */
  public function getLibraries() {
    // @todo: confirm this is the best way to go from collection to array.
    // Build array of library arrays.
    $libraries = array_map(function (ConditionInterface $condition) {
      return $condition->getLibraries();
    }, $this->getConditions()->getIterator()->getArrayCopy());
    // Return flattened library arrays.
    return array_unique(array_reduce($libraries, 'array_merge', []));
  }

  /**
   * Returns an array JS settings.
   *
   * @return array
   *   An array settings for SegmentSet processing.
   *
   * @throws \Exception
   */
  public function getAttachedSettings() {
    // @todo: confirm this is the best way to go from collection to array.
    return [
      'uuid' => $this->getUuid(),
      'conditions' => array_map(function (ConditionInterface $condition) {
        return $condition->getAttachedSettings();
      }, $this->getConditions()->getIterator()->getArrayCopy()),
    ];
  }

  /**
   * Returns an array representation of the segment.
   *
   * @return array
   *   An array representation of the segment.
   */
  public function toArray() {
    return [
      'uuid' => $this->getUuid(),
      'conditions' => $this->getConditionPluginCollection()->getConfiguration(),
      'weight' => $this->getWeight(),
      'label' => $this->getLabel(),
      'default' => $this->isDefault(),
    ];
  }

  /**
   * Creates an object from an array representation of the segment.
   *
   * @param array $segment
   *   An array of segment data in the format returned by ::toArray().
   *
   * @return static
   *   The segment object.
   */
  public static function fromArray(array $segment = []) {
    $segment += [
      'uuid' => NULL,
      'conditions' => [],
      'weight' => 0,
      'label' => NULL,
      'default' => FALSE,
    ];
    return new static($segment['uuid'], (array) $segment['conditions'], $segment['weight'], $segment['label'], $segment['default']);
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return ['#markup' => 'todo segment'];
  }

}
