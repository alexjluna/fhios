<?php

namespace Drupal\smart_content\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event that is fired when decision is attaching settings.
 *
 * @package Drupal\smart_content\Event
 */
class AttachDecisionSettingsEvent extends Event {

  /**
   * The event name.
   *
   * @var string
   */
  const EVENT_NAME = 'attach_decision_settings';

  /**
   * Settings that other modules can alter.
   *
   * @var array
   */
  protected $settings;

  /**
   * DecisionEvent constructor.
   *
   * @param array $settings
   *   The settings to be altered.
   */
  public function __construct(array &$settings) {
    $this->settings =& $settings;
  }

  /**
   * Gets the settings provided by the event dispatcher.
   *
   * @return array
   *   Settings provided by the event dispatcher.
   */
  public function &getSettings() {
    return $this->settings;
  }

}
