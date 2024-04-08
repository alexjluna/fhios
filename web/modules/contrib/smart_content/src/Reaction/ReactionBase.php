<?php

namespace Drupal\smart_content\Reaction;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;
use Drupal\smart_content\AttachedJavaScriptInterface;
use Drupal\smart_content\Segment;

/**
 * Base class for Reaction plugins.
 */
abstract class ReactionBase extends PluginBase implements ContextAwarePluginInterface, ReactionInterface, ConfigurableInterface, AttachedJavaScriptInterface {

  use ContextAwarePluginTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * The Uuid of the Segment object.
   *
   * @var string
   */
  protected $segmentId;

  /**
   * {@inheritdoc}
   */
  public function getSegmentDependencyId() {
    return $this->segmentId;
  }

  /**
   * {@inheritdoc}
   */
  public function setSegmentDependency(Segment $segment) {
    $this->segmentId = $segment->getUuid();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'segment_id' => $this->segmentId,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration = $configuration + $this->defaultConfiguration();
    if (isset($configuration['segment_id'])) {
      $this->segmentId = $configuration['segment_id'];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings() {
    $settings = [
      'id' => $this->getSegmentDependencyId(),
    ];
    if ($this instanceof ReactionContextRequirementsInterface) {
      $this->setReactionContextRequirements();
      $settings['contexts'] = $this->getReactionContextAttached();
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setReactionContextRequirements() {
    if (isset($this->getPluginDefinition()['reaction_context_requirements'])) {
      foreach ($this->getPluginDefinition()['reaction_context_requirements'] as $key) {
        $this->getContextDefinition($key)->setRequired();
      }
    }
    return $this;
  }

}
