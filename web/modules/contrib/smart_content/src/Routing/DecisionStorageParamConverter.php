<?php

namespace Drupal\smart_content\Routing;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\smart_content\Decision\Storage\DecisionStorageManager;
use Symfony\Component\Routing\Route;

/**
 * Loads the segment storage plugin.
 *
 * @internal
 *   Tagged services are internal.
 */
class DecisionStorageParamConverter implements ParamConverterInterface {

  /**
   * The DecisionStorage plugin.
   *
   * @var \Drupal\smart_content\Decision\Storage\DecisionStorageManager
   */
  protected $decisionStorageManager;

  /**
   * Constructs a new DecisionStorageParamConverter.
   *
   * @param \Drupal\smart_content\Decision\Storage\DecisionStorageManager $decision_storage_manager
   *   The decision storage manager.
   */
  public function __construct(DecisionStorageManager $decision_storage_manager) {
    $this->decisionStorageManager = $decision_storage_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if ($this->decisionStorageManager->hasDefinition($value)) {
      return $this->decisionStorageManager->createInstance($value);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'decision_storage');
  }

}
