<?php

namespace Drupal\smart_content\Condition\Type;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\smart_content\AttachedJavaScriptInterface;

/**
 * Defines an interface for Smart condition type plugins.
 */
interface ConditionTypeInterface extends PluginInspectionInterface, AttachedJavaScriptInterface, PluginFormInterface, ConfigurableInterface {

  /**
   * Returns an HTML string summarizing the configuration of the condition.
   *
   * @return array
   *   A render array representing a summary of the condition.
   */
  public function getHtmlSummary();

}
