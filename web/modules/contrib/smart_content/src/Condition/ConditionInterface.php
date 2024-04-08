<?php

namespace Drupal\smart_content\Condition;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\smart_content\AttachedJavaScriptInterface;

/**
 * Defines an interface for Smart condition plugins.
 */
interface ConditionInterface extends PluginInspectionInterface, AttachedJavaScriptInterface {

  /**
   * Returns an HTML string summarizing the configuration of the condition.
   *
   * @return array
   *   A render array representing a summary of the condition.
   */
  public function getHtmlSummary();

  /**
   * Returns a type ID for js to claim for processing.
   *
   * Todo: Can we rethink/improve this?
   *
   * @return string
   *   A string for JS processing.
   */
  public function getTypeId();

  /**
   * Gets if condition is negated.
   *
   * @return bool
   *   If condition is/is not.
   */
  public function isNegated();

  /**
   * Sets Weight of condition.
   *
   * @param int $weight
   *   The weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets weight of condition.
   *
   * @return int
   *   The weight.
   */
  public function getWeight();

  /**
   * Sets if condition is negated.
   *
   * @param bool $value
   *   Condition is/is not.
   *
   * @return $this
   *   Return $this.
   */
  public function setNegated($value = TRUE);

}
