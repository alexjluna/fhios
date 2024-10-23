<?php

namespace Drupal\vlsuite_bundle_field;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helper "VLSuiteBundleFieldHelper" service object.
 */
class VLSuiteBundleFieldHelper {

  /**
   * The entity definitions update manager object.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionsUpdateManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a "VLSuiteVLSuiteBundleFieldsHelper" object.
   */
  public function __construct(EntityDefinitionUpdateManagerInterface $entity_definitions_update_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityDefinitionsUpdateManager = $entity_definitions_update_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Install block content bundle fields.
   *
   * @param string $bundle_key
   *   Bundle key.
   * @param string $class
   *   Block content class.
   */
  public function installBlockContentBundleFields($bundle_key, $class) {
    $this->installEntityTypeBundleFields('block_content', $bundle_key, $class);
  }

  /**
   * Install entity type bundle fields.
   *
   * @param string $entity_type_key
   *   Entity type key.
   * @param string $bundle_key
   *   Bundle key.
   * @param string $class
   *   Content entity class.
   */
  private function installEntityTypeBundleFields($entity_type_key, $bundle_key, $class) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_key);
    $bundle_field_definitions = $entity_type instanceof EntityTypeInterface ? $class::bundleFieldDefinitions($entity_type, $bundle_key, []) : [];
    if (!empty($bundle_field_definitions)) {
      foreach ($bundle_field_definitions as $bundle_field_definition) {
        $this->entityDefinitionsUpdateManager->installFieldStorageDefinition($bundle_field_definition->getName(), $entity_type_key, 'vlsuite', $bundle_field_definition);
      }
    }
    // In other cases may be useful:
    // @code $field_storage_defs = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('node'); @code
    // @code $this->entityDefinitionsUpdateManager->installFieldableEntityType($entity_type, $field_storage_defs); @code
    // @code $this->entityDefinitionsUpdateManager->updateFieldableEntityType($entity_type, $field_storage_defs); @code
    // @code $this->entityDefinitionsUpdateManager->updateEntityType($entity_type); @endcode
    // @code $this->entityDefinitionsUpdateManager->getChangeList($entity_type); @endcode
    // @code $this->entityDefinitionsUpdateManager->uninstallFieldStorageDefinition($field_storage_defs['deprecatedfield']); @endcode
  }

}
