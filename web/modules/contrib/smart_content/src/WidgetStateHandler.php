<?php

namespace Drupal\smart_content;

use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\Decision\DecisionInterface;

/**
 * Helper class for managing form widget states.
 *
 * @package Drupal\smart_content
 */
class WidgetStateHandler {

  /**
   * Constant indicating a closed or preview state.
   */
  const CLOSED = 0;

  /**
   * Constant indicating an open or editable state.
   */
  const OPEN = 1;

  /**
   * Gets the widget state for a decision.
   *
   * @param \Drupal\smart_content\Decision\DecisionInterface $decision
   *   The decision object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state that stores the widget state.
   * @param string|null $key
   *   An optional key to store the widget state.
   *
   * @return int
   *   The current widget state.
   */
  public static function getWidgetState(DecisionInterface $decision, FormStateInterface $form_state, $key = NULL) {
    $keys = ['widget_state', $decision->getToken()];
    if ($key) {
      array_push($keys, $key);
    }
    $state = $form_state->get($keys);
    if (is_null($state)) {
      self::setWidgetState($decision, $form_state, $key, self::CLOSED);
      $state = self::CLOSED;
    }
    return $state;
  }

  /**
   * Sets the widget state for a decision.
   *
   * @param \Drupal\smart_content\Decision\DecisionInterface $decision
   *   The decision object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state that stores the widget state.
   * @param string|null $key
   *   An optional key to store the widget state.
   * @param int $value
   *   The current widget state.
   */
  public static function setWidgetState(DecisionInterface $decision, FormStateInterface $form_state, $key = NULL, $value = WidgetStateHandler::CLOSED) {
    $keys = ['widget_state', $decision->getToken()];
    if ($key) {
      array_push($keys, $key);
    }
    $form_state->set($keys, $value);
  }

  /**
   * Toggles the widget state for a segment.
   *
   * @param \Drupal\smart_content\Decision\DecisionInterface $decision
   *   The decision object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state that stores the widget state.
   * @param string|null $key
   *   An optional key to store the widget state.
   */
  public static function toggleWidgetState(DecisionInterface $decision, FormStateInterface $form_state, $key = NULL) {
    $keys = ['widget_state', $decision->getToken()];
    if ($key) {
      array_push($keys, $key);
    }
    $state = self::getWidgetState($decision, $form_state, $key);
    self::setWidgetState($decision, $form_state, $key, !$state);
  }

}
