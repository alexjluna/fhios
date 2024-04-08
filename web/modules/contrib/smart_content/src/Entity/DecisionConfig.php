<?php

namespace Drupal\smart_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\smart_content\Decision\DecisionInterface;
use Drupal\smart_content\Decision\DecisionPluginCollection;

/**
 * Defines the DecisionConfig entity.
 *
 * A decision entity acts as storage for serialized segment data.
 * DecisionConfig should be used when the decision appears within another
 * configuration entity.  This allows for the configuration entity to be
 * export along with the configuration it depends on.
 *
 * The entity should not be interacted with directly with exception to
 * DecisionStorage plugins, which act as a common interface for CRUD
 * operations across storage types.
 *
 * @ConfigEntityType(
 *   id = "smart_content_decision_config",
 *   label = @Translation("Decision"),
 *   config_prefix = "smart_content.decision",
 *   admin_permission = "administer smart content",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   config_export = {
 *     "id",
 *     "settings"
 *   }
 * )
 */
class DecisionConfig extends ConfigEntityBase implements EntityWithPluginCollectionInterface, DecisionEntityInterface {

  // @todo: validate presave to confirm a decision is set.. Do not allow this to be saved without it.

  /**
   * The entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Decision settings.
   *
   * @var array
   */
  public $settings = [];

  /**
   * The plugin collection that holds the block plugin for this entity.
   *
   * @var \Drupal\block\BlockPluginCollection
   */
  protected $pluginCollection;

  /**
   * The decision object.
   *
   * @var \Drupal\smart_content\Decision\DecisionInterface
   */
  protected $decision;

  /**
   * Encapsulates the creation of the block's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The block's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection && isset($this->get('settings')['id'])) {
      $this->pluginCollection = new DecisionPluginCollection(\Drupal::service('plugin.manager.smart_content.decision'), $this->get('settings')['id'], $this->get('settings'));
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'settings' => $this->getPluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function hasDecision() {
    if (empty($this->get('settings')['id'])) {
      return FALSE;
    }
    return $this->getPluginCollection()->has($this->get('settings')['id']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDecision() {
    if (!isset($this->decision)) {
      $this->decision = $this->getPluginCollection()->get($this->get('settings')['id']);
    }
    return $this->decision;
  }

  /**
   * {@inheritdoc}
   */
  public function setDecision(DecisionInterface $decision) {
    $this->set('settings', $decision->getConfiguration());
    $this->decision = $decision;
    return $this;
  }

}
