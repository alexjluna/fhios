<?php

namespace Drupal\smart_content;

use Drupal\Core\Form\FormStateInterface;

/**
 * Helper class for managing form widget states.
 *
 * @package Drupal\smart_content
 */
class SegmentSetStateHandler {

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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state that stores the widget state.
   * @param string|null $key
   *   An optional key to store the widget state.
   *
   * @return int
   *   The current widget state.
   */
  public static function getWidgetState(FormStateInterface $form_state, $key = NULL) {
    $keys = ['widget_state'];
    if ($key) {
      array_push($keys, $key);
    }
    $state = $form_state->get($keys);
    if (is_null($state)) {
      self::setWidgetState($form_state, $key, self::CLOSED);
      $state = self::CLOSED;
    }
    return $state;
  }

  /**
   * Sets the widget state for a decision.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state that stores the widget state.
   * @param string|null $key
   *   An optional key to store the widget state.
   * @param int $value
   *   The current widget state.
   */
  public static function setWidgetState(FormStateInterface $form_state, $key = NULL, $value = WidgetStateHandler::CLOSED) {
    $keys = ['widget_state'];
    if ($key) {
      array_push($keys, $key);
    }
    $form_state->set($keys, $value);
  }

  /**
   * Toggles the widget state for a segment.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state that stores the widget state.
   * @param string|null $key
   *   An optional key to store the widget state.
   */
  public static function toggleWidgetState(FormStateInterface $form_state, $key = NULL) {
    $keys = ['widget_state'];
    if ($key) {
      array_push($keys, $key);
    }
    $state = self::getWidgetState($form_state, $key);
    self::setWidgetState($form_state, $key, !$state);
  }

}
