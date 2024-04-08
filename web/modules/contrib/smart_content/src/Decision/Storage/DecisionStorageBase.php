<?php

namespace Drupal\smart_content\Decision\Storage;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\Decision\DecisionInterface;

/**
 * Base class for ReactionSet storage plugins.
 */
abstract class DecisionStorageBase extends PluginBase implements DecisionStorageInterface {

  /**
   * The decision plugin instance.
   *
   * @var \Drupal\smart_content\Decision\DecisionInterface
   */
  protected $decision;

  /**
   * {@inheritdoc}
   */
  public function setDecision(DecisionInterface $decision) {
    $this->decision = $decision;
    $this->decision->setStorage($this);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDecision() {
    return $this->decision;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDecision() {
    return isset($this->decision);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'plugin_id' => $this->getPluginId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'plugin_id' => $this->getPluginId(),
    ];
  }

  /**
   * Saves storage and sets to form_state.
   *
   * Todo: Decide if this should go in a trait.
   *
   * @param array $parents
   *   An array of parents.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The forms state.
   * @param \Drupal\smart_content\Decision\Storage\DecisionStorageInterface $decision_storage
   *   The decision storage you want to save.
   */
  public static function setWidgetState(array $parents, FormStateInterface $form_state, DecisionStorageInterface $decision_storage) {
    NestedArray::setValue($form_state->getStorage(), array_merge(['smart_content'], $parents, ['decision_storage']), $decision_storage);
  }

  /**
   * Restores storage and sets from form_state to object.
   *
   * Todo: Decide if this should go in a trait.
   *
   * @param array $parents
   *   An array of parents.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The forms state.
   *
   * @return \Drupal\smart_content\Decision\Storage\DecisionStorageInterface
   *   The decision storage object.
   */
  public static function getWidgetState(array $parents, FormStateInterface $form_state) {
    return NestedArray::getValue($form_state->getStorage(), array_merge(['smart_content'], $parents, ['decision_storage']));
  }

}
