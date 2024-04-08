<?php

namespace Drupal\smart_content\SegmentSetStorage;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\smart_content\AttachedJavaScriptInterface;
use Drupal\smart_content\SegmentSet;

/**
 * Defines an interface for ReactionSet storage plugins.
 */
interface SegmentSetStorageInterface extends PluginInspectionInterface, AttachedJavaScriptInterface {

  /**
   * Load the segment set instance from configuration.
   *
   * @return \Drupal\smart_content\SegmentSet
   *   The segment set instance.
   */
  public function load();

  /**
   * Set the segment set.
   *
   * @param \Drupal\smart_content\SegmentSet $segmentSet
   *   The segment set.
   *
   * @return $this
   */
  public function setSegmentSet(SegmentSet $segmentSet);

  /**
   * Get the segment set.
   *
   * @return \Drupal\smart_content\SegmentSet
   *   The segment set object.
   */
  public function getSegmentSet();

}
