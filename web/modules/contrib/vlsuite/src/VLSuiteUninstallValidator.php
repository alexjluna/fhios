<?php

namespace Drupal\vlsuite;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Prevents module from being uninstalled under certain conditions.
 *
 * These conditions are when any VLSuite entity exist.
 */
abstract class VLSuiteUninstallValidator implements ModuleUninstallValidatorInterface {
  use StringTranslationTrait;

  /**
   * Module to uninstall.
   */
  const MODULE = 'vlsuite';

  /**
   * Entity type that should be removed.
   */
  const ENTITY_TYPE = '';

  /**
   * Bundle key of the entities to uninstall.
   */
  const BUNDLE_KEY = '';

  /**
   * Bundle of the entities to uninstall.
   */
  const BUNDLE = '';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new VLSuiteUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($module == static::MODULE) {
      // Prevent uninstall if there are any blocks of that type.
      if ($this->hasVlSuiteEntity(static::ENTITY_TYPE, static::BUNDLE_KEY, static::BUNDLE)) {
        $reasons[] = $this->t('To uninstall VLSuite module: @module, remove all entities of type: @type and bundle: @bundle.', [
          '@module' => static::MODULE,
          '@type' => static::ENTITY_TYPE,
          '@bundle' => static::BUNDLE,
        ]);
      }
    }
    return $reasons;
  }

  /**
   * Determines if there is any entity or not.
   *
   * @return bool
   *   TRUE if there is an entity FALSE otherwise.
   */
  protected function hasVlSuiteEntity($entity_type, $bundle_key, $bundle) {
    $vlsuite_entity = $this->entityTypeManager->getStorage($entity_type)->getQuery()
      ->condition($bundle_key, $bundle)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return !empty($vlsuite_entity);
  }

}
