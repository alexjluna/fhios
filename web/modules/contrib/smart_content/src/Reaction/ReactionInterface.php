<?php

namespace Drupal\smart_content\Reaction;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\smart_content\Decision\PlaceholderDecisionInterface;
use Drupal\smart_content\Segment;

/**
 * Defines an interface for Reaction plugins.
 */
interface ReactionInterface extends PluginInspectionInterface {

  /**
   * Return the dependent Segment ID.
   *
   * This method returns the UUID from the Segment dependency.  We avoid
   * storing the Segment instance on the Reaction so that we can load the
   * Reaction without knowledge of how to load the Segment.  Because of this,
   * the implementer of the Reaction is responsible for maintaining this
   * dependency.
   *
   * @return mixed
   *   The Segment Uuid.
   */
  public function getSegmentDependencyId();

  /**
   * Add a Segment as a dependency.
   *
   * Adds a Segment class to the Reaction as a dependency.
   *
   * @param \Drupal\smart_content\Segment $segment
   *   A Segment object.
   *
   * @return $this
   *   Return this.
   */
  public function setSegmentDependency(Segment $segment);

  /**
   * Return an AJAX response.
   *
   * @param \Drupal\smart_content\Decision\PlaceholderDecisionInterface $decision
   *   A decision instance, for context.
   *   todo: Should we require this to be placeholder decision interface?
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to replace placeholder content.
   */
  public function getResponse(PlaceholderDecisionInterface $decision);

  /**
   * Provides a plain text summary of this reaction.
   *
   * @return string
   *   A summary of the reaction object.
   */
  public function getPlainTextSummary();

  /**
   * Returns an HTML string summarizing the configuration of the reaction.
   *
   * @return array
   *   A render array representing a summary of the reaction.
   */
  public function getHtmlSummary();

}
