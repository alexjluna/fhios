<?php

namespace Drupal\smart_content\Plugin\DataType;

use Drupal\Core\TypedData\TypedData;
use Drupal\smart_content\Decision\DecisionInterface;

/**
 * Provides a data type for \Drupal\smart_content\Decision\DecisionInterface.
 *
 * @DataType(
 *   id = "smart_content_decision",
 *   label = @Translation("Smart Content Decision"),
 *   description = @Translation("A smart content decision"),
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class DecisionData extends TypedData {

  /**
   * The decision object.
   *
   * @var \Drupal\smart_content\Decision\DecisionInterface
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    if ($value) {
      if ($value instanceof DecisionInterface) {
        $this->decision = $value;
        parent::setValue($value->getConfiguration(), $notify);
      }
      elseif (is_array($value) && isset($value['id'])) {
        unset($this->decision);
        parent::setValue($value, $notify);
      }
      else {
        throw new \InvalidArgumentException(sprintf('Value assigned to "%s" is not a valid decision', $this->getName()));
      }
    }
    else {
      parent::setValue($value, $notify);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if (!isset($this->decision) && !empty($this->value)) {
      $this->decision = \Drupal::service('plugin.manager.smart_content.decision')
        ->createInstance($this->value['id'], $this->value);
    }
    if (!empty($this->decision)) {
      if ($this->decision instanceof DecisionInterface) {
        return $this->decision->getConfiguration();
      }
      return $this->decision;
    }
    return $this->value;
  }

}
