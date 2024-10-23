<?php

namespace Drupal\vlsuite_generator;

use Drupal\Component\Serialization\Yaml;
use Drupal\layout_builder\Plugin\Block\FieldBlock;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\vlsuite_block\Plugin\Block\VLSuiteInlineBlock;
use Drupal\vlsuite_layout\Plugin\Layout\VLSuiteLayoutBase;
use Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper;
use DrupalCodeGenerator\Asset\AssetCollection as Assets;

/**
 * Helper "VLSuiteGeneratorModuleGeneratorProcessStylesTrait" trait.
 */
trait VLSuiteGeneratorModuleGeneratorProcessStylesTrait {

  /**
   * Process styles assets.
   *
   * @param array $vars
   *   Vars to use in templates.
   * @param \DrupalCodeGenerator\Asset\AssetCollection $assets
   *   Assets to add what is needed related to styles.
   */
  protected function generateStylesAssets(array $vars, Assets $assets) {
    $assets->addDirectory("{$vars['machine_name']}/config/partial");
    $utilities_pristine = \Drupal::configFactory()->get('vlsuite_utility_classes.settings')->get('utilities');

    // Look for used utilities.
    $used_utilities_def = [];
    foreach ($this->utilitiesUsed as $utility_identifier => $apply_to) {
      $def = $utilities_pristine[$utility_identifier];
      $def['apply_to'] = array_intersect($def['apply_to'], $apply_to);
      $used_utilities_def[$utility_identifier] = $def;
    }
    $this->utilities = $used_utilities_def;

    // Look for used apply to enabled block utilities.
    $apply_to_enabled_pristine = \Drupal::configFactory()->get('vlsuite_block.settings')->get('utility_classes_apply_to_enabled');
    $used_apply_to_enabled_def = [];
    foreach ($this->applyToEnabledUsed as $entity_type_id => $bundles) {
      foreach (array_keys($bundles) as $bundle) {
        if (!empty($apply_to_enabled_pristine[$entity_type_id][$bundle])) {
          $used_apply_to_enabled_def[$entity_type_id][$bundle] = $apply_to_enabled_pristine[$entity_type_id][$bundle];
        }
      }
    }
    $this->applyToEnabled = $used_apply_to_enabled_def;

    // Generate partial config to merge when installing new module.
    $assets->addFile("{$vars['machine_name']}/config/partial/vlsuite_utility_classes.settings.utilities.yml")->content(Yaml::encode($this->utilities));
    $assets->addFile("{$vars['machine_name']}/config/partial/vlsuite_block.settings.utility_classes_apply_to_enabled.yml")->content(Yaml::encode($this->applyToEnabled));
  }

  /**
   * Process section styles dependencies.
   *
   * @param \Drupal\layout_builder\Section $section
   *   Section to process.
   */
  protected function processSectionStylesDependencies(Section $section) {
    // Library!?
    if ($section->getLayout() instanceof VLSuiteLayoutBase) {
      $this->processUtilitiesDependencies($section->getLayout()->getConfiguration()[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] ?? []);
    }
  }

  /**
   * Process component styles dependencies.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   Component to process its styles dependencies.
   */
  protected function processComponentStylesDependencies(SectionComponent $component) {
    $block_plugin = $component->getPlugin();
    if ($block_plugin instanceof FieldBlock) {
      [$entity_type_id, $bundle] = explode($block_plugin::DERIVATIVE_SEPARATOR, $block_plugin->getDerivativeId(), 3);
      $bundle_entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getBundleEntityType();
    }
    elseif ($block_plugin instanceof VLSuiteInlineBlock) {
      $reflectionMethod = new \ReflectionMethod($block_plugin, 'getEntity');
      $reflectionMethod->setAccessible(TRUE);
      $block_content = $reflectionMethod->invoke($block_plugin);
      $entity_type_id = $block_content->getEntityTypeId();
      $bundle = $block_content->bundle();
      $bundle_entity_type = $block_content->getEntityType()->getBundleEntityType();
    }
    else {
      $entity_type_id = NULL;
      $bundle = NULL;
      $bundle_entity_type = NULL;
    }
    // Avoid processing discarded entity type bundles.
    if (!empty($entity_type_id) && !empty($bundle) && !empty($this->entityTypes[$bundle_entity_type][$bundle])) {
      $this->librariesCandidates[$entity_type_id] = TRUE;
      $this->librariesCandidates[$bundle] = TRUE;
      $this->librariesCandidates[$bundle_entity_type] = TRUE;
      $this->applyToEnabledUsed[$entity_type_id][$bundle] = TRUE;
      $this->processUtilitiesDependencies($component->get('configuration')[VLSuiteUtilityClassesHelper::UTILITY_CLASSES_KEY] ?? []);
    }
  }

  /**
   * Process utilities dependencies.
   *
   * @param array $utilities_apply_to
   *   Utilities used into section or component.
   */
  protected function processUtilitiesDependencies(array $utilities_apply_to) {
    foreach ($utilities_apply_to as $apply_to => $utilities) {
      foreach (array_keys($utilities) as $utility_identifier) {
        $this->utilitiesUsed[$utility_identifier][$apply_to] = $apply_to;
      }
    }
  }

  /**
   * Process styles print summary.
   */
  protected function processStylesPrintSummary() {
    $main_theme_name = \Drupal::service('theme.manager')->getActiveTheme()->getName();
    $main_theme_libraries = \Drupal::service('library.discovery')->getLibrariesByExtension($main_theme_name);
    foreach (array_keys($this->librariesCandidates) as $library_candidate) {
      if (isset($main_theme_libraries[$library_candidate]) || isset($main_theme_libraries[str_replace('_', '-', $library_candidate)])) {
        $this->logger->warning("Library name match found in main theme like \"{$library_candidate}\": Make sure you include it manually if needed (assets & templates)!");
      }
    }
  }

}
