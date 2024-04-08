<?php

namespace Drupal\smart_content_utm\Plugin\smart_content\Condition;

use Drupal\smart_content\Condition\ConditionTypeConfigurableBase;

/**
 * Provides a default Smart Condition.
 *
 * @SmartCondition(
 *   id = "utm",
 *   label = @Translation("UTM"),
 *   group = "utm",
 *   weight = 0,
 *   deriver = "Drupal\smart_content_utm\Plugin\Derivative\UtmDerivative"
 * )
 */
class UtmCondition extends ConditionTypeConfigurableBase {

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    $libraries = array_unique(array_merge(parent::getLibraries(), ['smart_content_utm/condition.utm']));
    return $libraries;
  }

}
