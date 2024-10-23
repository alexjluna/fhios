<?php

namespace Drupal\vlsuite_generator;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\Entity\ConfigEntityDependency;
use DrupalCodeGenerator\Asset\AssetCollection as Assets;

/**
 * Helper "VLSuiteGeneratorModuleGeneratorProcessConfigTrait" trait.
 */
trait VLSuiteGeneratorModuleGeneratorProcessConfigTrait {

  /**
   * Process config dependencies.
   *
   * @param string $config_name
   *   Config name to process its dependencies.
   * @param bool $require_confirmation
   *   Require confirmation or bypass.
   */
  protected function processConfigDependencies(string $config_name, bool $require_confirmation = TRUE) {
    // Avoid reduntant processor.
    if (isset($this->config[$config_name])) {
      $this->logger->debug("Config \"{$config_name}\": Redundant processor avoided (config already processed or discarded)");
      return;
    }

    $this->logger->notice("Procesing config dependencies \"{$config_name}\"");
    $matches = [];
    // Lookup for main entity types config names to manage reverse dependencies
    // as fields, entity displays, etc (dependency is that way not direct).
    if (preg_match('/([A-Za-z0-9_-]+)\.(?:type|paragraphs_type|vocabulary)\.([A-Za-z0-9_-]+)/', $config_name, $matches)) {
      $bundle = $matches[2];
      $entity_type_id = $matches[1];
      // Obtain bundle entity type of entity type id detected (related config
      // entity type to export its config files).
      $bundle_entity_type = match($entity_type_id) {
        'paragraphs' => \Drupal::entityTypeManager()->getDefinition('paragraph')->getBundleEntityType(),
        'taxonomy' => \Drupal::entityTypeManager()->getDefinition('taxonomy_term')->getBundleEntityType(),
        default => NULL,
      };
      if ($bundle_entity_type === NULL) {
        try {
          $bundle_entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getBundleEntityType();
        }
        catch (\Exception $e) {
          $this->logger->error("Error processing entity type for \"{$config_name}\" config dependency");
          return;
        }
      }
      $this->processConfigDependenciesEntityTypeBundle($config_name, $require_confirmation, $bundle_entity_type, $bundle);
    }
    else {
      $this->processConfigDependenciesGeneric($config_name);
    }
  }

  /**
   * Process config dependencies for entity type bundle.
   *
   * @param string $config_name
   *   Config name.
   * @param bool $require_confirmation
   *   Require confirmation or bypass.
   * @param string $bundle_entity_type
   *   Bundle entity type, e.g: block_content_type.
   * @param string $bundle
   *   Bundle, e.g: vlsuite_cta.
   */
  protected function processConfigDependenciesEntityTypeBundle(string $config_name, bool $require_confirmation, string $bundle_entity_type, string $bundle) {
    $this->logger->debug("Main config {$config_name}");
    // Avoid reduntant processor.
    if ($require_confirmation && isset($this->entityTypes[$bundle_entity_type][$bundle])) {
      $this->logger->debug("Entity \"{$bundle}\" of type \"{$bundle_entity_type}\": Redundant processor avoided (entity type bundle already processed or discarded)");
      return;
    }
    // Confirm detected entity type bundle inclusion or discarded.
    if ($require_confirmation && !$this->interviewer->confirm("Found \"{$bundle}\" entity type \"{$bundle_entity_type}\", would you like to include it")) {
      $this->config[$config_name] = FALSE;
      $this->entityTypes[$bundle_entity_type][$bundle] = FALSE;
      return;
    }
    $this->entityTypes[$bundle_entity_type][$bundle] = TRUE;
    $this->config[$config_name] = TRUE;
    $this->logger->debug("Main config {$config_name} before loop");
    foreach (\Drupal::service('config.manager')->getConfigDependencyManager()->getDependentEntities('config', $config_name) as $config_name_dependent_entity_dependency_name => $config_name_dependent_entity_dependency) {
      // Include & obtain dependencies, use "bundle" as look up criteria.
      if (strpos($config_name_dependent_entity_dependency_name, $bundle) !== FALSE) {
        $this->logger->debug("Main config {$config_name} before inside loop");
        // Try to minimize ambiguous reverse dependencies.
        if (strpos($config_name_dependent_entity_dependency_name, $bundle . '_') !== FALSE &&
          strpos($config_name_dependent_entity_dependency_name, 'core') !== FALSE &&
          !$this->interviewer->confirm("Ambiguous config name reverse dependency found \"{$config_name_dependent_entity_dependency_name}\" for \"{$config_name}\", would you like to include it?")) {
          $this->logger->debug("Ambiguous config name dependency discarded \"{$config_name_dependent_entity_dependency_name}\" for \"{$config_name}\".");
          continue;
        }
        $this->config[$config_name_dependent_entity_dependency_name] = TRUE;
        foreach ($config_name_dependent_entity_dependency->getDependencies('module') as $module_name) {
          $this->modules[$module_name] = TRUE;
        }
        // Avoid discarded configurations to be processed.
        $discarded = array_filter($this->config, function ($included) {
          return !$included;
        });
        $config_dependencies = array_values(array_diff($config_name_dependent_entity_dependency->getDependencies('config'), array_keys($discarded)));
        foreach ($config_dependencies as $config_dependency_name) {
          $this->processConfigDependencies($config_dependency_name, TRUE);
        }
      }
      // Ignore others.
    }
  }

  /**
   * Process config dependencies generic.
   *
   * @param string $config_name
   *   Config name to obtain direct dependencies.
   */
  protected function processConfigDependenciesGeneric(string $config_name) {
    $this->config[$config_name] = TRUE;
    $config = new ConfigEntityDependency($config_name, \Drupal::configFactory()->get($config_name)->get());
    foreach ($config->getDependencies('module') as $module_name) {
      $this->modules[$module_name] = TRUE;
    }
    $discarded = array_filter($this->config, function ($included) {
      return !$included;
    });
    $config_dependencies = array_values(array_diff($config->getDependencies('config'), array_keys($discarded)));
    foreach ($config_dependencies as $config_name) {
      $this->processConfigDependencies($config_name, TRUE);
    }
  }

  /**
   * Process config generate assets.
   *
   * @param array $vars
   *   Vars to use in templates.
   * @param \DrupalCodeGenerator\Asset\AssetCollection $assets
   *   Assets to add what is needed related to config.
   */
  protected function processConfigGenerateAssets(array $vars, Assets $assets) {
    // Use optional config to get those installed automatically when generated
    // module is installed in other site.
    $assets->addDirectory("{$vars['machine_name']}/config");
    $assets->addDirectory("{$vars['machine_name']}/config/optional");
    $destination_dir = "{$vars['machine_name']}/config/optional";
    $included = array_filter($this->config, function ($included) {
      return $included;
    });
    $discarded = array_filter($this->config, function ($included) {
      return !$included;
    });
    // Clean up uuid & core keys to allow be installed into any site.
    foreach (array_keys($included) as $config_name) {
      $config_content = \Drupal::service('config.storage')->read($config_name);
      if (isset($config_content['uuid'])) {
        unset($config_content['uuid']);
      }
      if (isset($config_content['_core']['default_config_hash'])) {
        unset($config_content['_core']['default_config_hash']);
      }
      if (isset($config_content['_core']) && empty($config_content['_core'])) {
        unset($config_content['_core']);
      }
      // Cleanup discarded dependencies if present.
      if (!empty($config_content['dependencies']['config'])) {
        $config_content['dependencies']['config'] = array_values(array_diff($config_content['dependencies']['config'], array_keys($discarded)));
      }
      foreach (array_keys($discarded) as $discarded_config_name) {
        if (isset($config_content['dependencies']['config'][$discarded_config_name])) {
          $this->logger->debug("Descarded config dependency \"{$discarded_config_name}\": Cleanup for \"{$config_name}\"");
          unset($config_content['dependencies']['config'][$discarded_config_name]);
        }
      }
      $assets->addFile("{$destination_dir}/{$config_name}.yml")->content(Yaml::encode($config_content));
    }
  }

}
