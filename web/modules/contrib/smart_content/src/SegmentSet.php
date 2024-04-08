<?php

namespace Drupal\smart_content;

/**
 * Provides a set of Segment objects.
 *
 * The SegmentSet provides a list of Segments and methods for CRUD operations.
 * The primary usage is for instantiating the SegmentSet from an array of
 * configuration and then writing the configuration back out to an array
 * after the SegmentSet is finished being acted upon.
 */
class SegmentSet {

  /**
   * An array of segments, keyed by UUID.
   *
   * @var \Drupal\smart_content\Segment[]
   */
  protected $segments = [];

  /**
   * Constructs a new SegmentSet.
   *
   * @param \Drupal\smart_content\Segment[] $segments
   *   (optional) The segments.
   */
  public function __construct(array $segments = []) {
    foreach ($segments as $segment) {
      $this->setSegment($segment);
    }
  }

  /**
   * Returns the segments of the SegmentSet.
   *
   * @return \Drupal\smart_content\Segment[]
   *   The segments.
   */
  public function getSegments() {
    return (array) $this->segments;
  }

  /**
   * Gets the segment for a given UUID.
   *
   * @param string $uuid
   *   The UUID of the segment to retrieve.
   *
   * @return \Drupal\smart_content\Segment
   *   The segment.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the expected UUID does not exist.
   */
  public function getSegment($uuid) {
    if (!isset($this->segments[$uuid])) {
      throw new \InvalidArgumentException(sprintf('Invalid UUID "%s"', $uuid));
    }
    return $this->segments[$uuid];
  }

  /**
   * Check if segment exists.
   *
   * @param string $uuid
   *   The UUID of the segment to retrieve.
   *
   * @return bool
   *   If segment exists.
   */
  public function hasSegment($uuid) {
    return isset($this->segments[$uuid]);
  }

  /**
   * Helper method to set a segment.
   *
   * @param \Drupal\smart_content\Segment $segment
   *   The segment.
   *
   * @return $this
   */
  public function setSegment(Segment $segment) {
    $this->segments[$segment->getUuid()] = $segment;
    return $this;
  }

  /**
   * Removes a given segment from the SegmentSet.
   *
   * @param string $uuid
   *   The UUID of the segment to remove.
   *
   * @return $this
   */
  public function removeSegment($uuid) {
    unset($this->segments[$uuid]);
    return $this;
  }

  /**
   * Sets a default segment, and unsets existing default segment.
   *
   * @param string $uuid
   *   The UUID of the segment to retrieve.
   *
   * @return $this
   */
  public function setDefaultSegment($uuid) {
    $this->getSegment($uuid)->setDefault();
    foreach ($this->getSegments() as $segment) {
      if ($segment->getUuid() !== $uuid) {
        $segment->setDefault(FALSE);
      }
    }
    return $this;
  }

  /**
   * Gets default segment.
   *
   * @return \Drupal\smart_content\Segment
   *   The segment.
   */
  public function getDefaultSegment() {
    foreach ($this->getSegments() as $segment) {
      if ($segment->isDefault()) {
        return $segment;
      }
    }
  }

  /**
   * Unsets default segment.
   *
   * @return $this
   *   Return this.
   */
  public function unsetDefaultSegment() {
    foreach ($this->getSegments() as $segment) {
      $segment->setDefault(FALSE);

    }
    return $this;
  }

  /**
   * Sorts segments by weight.
   *
   * @return $this
   *   Return this.
   */
  public function sortSegments() {
    uasort($this->segments, function ($a, $b) {
      return $a->getWeight() - $b->getWeight();
    });
    return $this;
  }

  /**
   * Returns an array Drupal libraries.
   *
   * @return array
   *   An array libraries for SegmentSet processing.
   */
  public function getLibraries() {
    // Build array of library arrays.
    $libraries = array_map(function (Segment $segment) {
      return $segment->getLibraries();
    }, $this->getSegments());
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
    $settings = [];
    foreach ($this->getSegments() as $segment) {
      $settings[$segment->getUuid()] = $segment->getAttachedSettings();
    }
    return $settings;
  }

  /**
   * Returns an array representation of the SegmentSet.
   *
   * @return array
   *   An array representation of the SegmentSet.
   */
  public function toArray() {
    return [
      'segments' => (array) array_map(function (Segment $segment) {
        return $segment->toArray();
      }, $this->getSegments()),
    ];
  }

  /**
   * Creates an object from an array representation of the SegmentSet.
   *
   * @param array $segment_set
   *   An array of SegmentSet data in the format returned by ::toArray().
   *
   * @return static
   *   The SegmentSet object.
   */
  public static function fromArray(array $segment_set) {
    $segment_set += [
      'segments' => [],
    ];
    return new static(
      array_map([Segment::class, 'fromArray'], $segment_set['segments'])
    );
  }

  /**
   * Magic method: Implements a deep clone.
   */
  public function __clone() {
    foreach ($this->getSegments() as $uuid => $segment) {
      $this->segments[$uuid] = clone $segment;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return ['#markup' => 'todo SegmentSet'];
  }

}
