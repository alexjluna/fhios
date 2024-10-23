<?php

namespace Drupal\vlsuite_generator;

use Drupal\Core\Block\BlockBase;
use Drupal\layout_builder\Plugin\Block\FieldBlock;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\vlsuite_block\Plugin\Block\VLSuiteFieldBlock;
use Drupal\vlsuite_block\Plugin\Block\VLSuiteInlineBlock;
use DrupalCodeGenerator\Asset\AssetCollection as Assets;

/**
 * Helper "VLSuiteGeneratorModuleGeneratorProcessTrait" trait.
 */
trait VLSuiteGeneratorModuleGeneratorProcessTrait {

  /**
   * Process main assets.
   *
   * @param array $vars
   *   Vars to use in templates.
   * @param \DrupalCodeGenerator\Asset\AssetCollection $assets
   *   Assets to add what is needed related to config.
   */
  protected function processMainAssets(array &$vars, Assets $assets) {
    $module_dependencies = [];
    $module_list = \Drupal::service('extension.list.module')->getList();
    foreach (array_keys($this->modules) as $module_name) {
      if ($module_name !== 'core') {
        $origin = ($module_list[$module_name]->origin ?? NULL) === 'core' ? 'drupal' : ($module_list[$module_name]->origin ?? NULL);
        $project = !empty($origin) ? $origin : ($module_list[$module_name]->info['project'] ?? NULL);
        $module_dependencies[] = (!empty($project) ? $project : $module_name) . ':' . $module_name;
      }
    }
    $vars['dependencies'] = array_unique($module_dependencies);
    $assets->addFile('{machine_name}/{machine_name}.info.yml', 'model.info.yml.twig');
    $assets->addFile('{machine_name}/{machine_name}.install', 'model.install.twig');
  }

  /**
   * Process sections.
   *
   * @param array $sections
   *   Sections to process.
   */
  protected function processSections(array $sections) {
    $sections_callbacks = [
      [$this, 'processSection'],
    ];
    $components_callbacks = [
      [$this, 'processComponent'],
    ];
    $this->sectionsIterator($sections, $sections_callbacks, $components_callbacks);
  }

  /**
   * Process section.
   *
   * @param \Drupal\layout_builder\Section $section
   *   Section to process.
   */
  protected function processSection(Section $section) {
    $this->logger->notice("Procesing section \"{$section->getLayoutId()}\"");
    $this->modules[$section->getLayout()->getPluginDefinition()->getProvider()] = TRUE;
    $this->processSectionStylesDependencies($section);
  }

  /**
   * Process component (per type).
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   Component to process.
   */
  protected function processComponent(SectionComponent $component) {
    $block_plugin = $component->getPlugin();
    $process_result = TRUE;
    if ($block_plugin instanceof FieldBlock) {
      $this->processComponentFieldBlock($component, $block_plugin);
      // As vluiste uses same base class, this process is complementary.
      if ($block_plugin instanceof VLSuiteFieldBlock) {
        $this->processComponentVlsuiteFieldBlock($component, $block_plugin);
      }
    }
    elseif ($block_plugin instanceof InlineBlock) {
      $this->processComponentInlineBlock($component, $block_plugin);
      // As vluiste uses same base class, this process is complementary.
      if ($block_plugin instanceof VLSuiteInlineBlock) {
        $this->processComponentVlsuiteInlineBlock($component, $block_plugin);
      }
    }
    elseif ($block_plugin instanceof ViewsBlock) {
      $this->processComponentViewsBlock($component, $block_plugin);
    }
    elseif ($block_plugin instanceof BlockBase) {
      $this->processComponentBlockBase($component, $block_plugin);
    }
    else {
      $process_result = FALSE;
    }
    if ($process_result) {
      $this->logger->notice("Procesing section component \"{$component->getPluginId()}: OK");
    }
    else {
      $this->logger->warning("Procesing section component \"{$component->getPluginId()}: KO");
    }
  }

  /**
   * Process inline block component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   Component to process.
   * @param \Drupal\layout_builder\Plugin\Block\InlineBlock $block_plugin
   *   Component block plugin.
   */
  protected function processComponentInlineBlock(SectionComponent $component, InlineBlock $block_plugin) {
    $reflectionMethod = new \ReflectionMethod($block_plugin, 'getEntity');
    $reflectionMethod->setAccessible(TRUE);
    $block_content = $reflectionMethod->invoke($block_plugin);
    $bundle_entity_type = $block_content->getEntityType()->getBundleEntityType();
    $bundle = $block_content->bundle();
    if (isset($this->entityTypes[$bundle_entity_type][$bundle])) {
      $this->logger->debug("Entity \"{$bundle}\" of type \"{$bundle_entity_type}\": Redundant processor avoided (entity type bundle already processed or discarded)");
      return;
    }
    if ($this->interviewer->confirm("Found \"{$bundle}\" entity type \"{$bundle_entity_type}\", would you like to include it?")) {
      $this->entityTypes[$bundle_entity_type][$bundle] = TRUE;
      $config_name = \Drupal::entityTypeManager()->getStorage($bundle_entity_type)->load($bundle)->getConfigDependencyName();
      $this->modules[$block_plugin->getPluginDefinition()['provider']] = TRUE;
      $this->processConfigDependencies($config_name, FALSE);
      $this->processEntityTypeBundleDisplays($block_content->getEntityTypeId(), $bundle);
    }
    else {
      $this->entityTypes[$bundle_entity_type][$bundle] = FALSE;
    }
  }

  /**
   * Process VLSuite inline block component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   Component to process.
   * @param \Drupal\vlsuite_block\Plugin\Block\VLSuiteInlineBlock $block_plugin
   *   Component block plugin.
   */
  protected function processComponentVlsuiteInlineBlock(SectionComponent $component, VLSuiteInlineBlock $block_plugin) {
    $this->processComponentStylesDependencies($component);
  }

  /**
   * Process field block component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   Component to process.
   * @param \Drupal\layout_builder\Plugin\Block\FieldBlock $block_plugin
   *   Component block plugin.
   */
  protected function processComponentFieldBlock(SectionComponent $component, FieldBlock $block_plugin) {
    [$entity_type_id, $bundle] = explode($block_plugin::DERIVATIVE_SEPARATOR, $block_plugin->getDerivativeId(), 3);
    $bundle_entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getBundleEntityType();
    // Avoid reduntant processor.
    if (isset($this->entityTypes[$bundle_entity_type][$bundle])) {
      $this->logger->debug("Entity \"{$bundle}\" of type \"{$bundle_entity_type}\": Redundant processor avoided (entity type bundle already processed or discarded)");
      return;
    }
    // Confirm detected entity type bundle inclusion or discarded.
    if ($this->interviewer->confirm("Found \"{$bundle}\" entity type \"{$bundle_entity_type}\", would you like to include it?")) {
      $this->entityTypes[$bundle_entity_type][$bundle] = TRUE;
      $config_name = \Drupal::entityTypeManager()->getStorage($bundle_entity_type)->load($bundle)->getConfigDependencyName();
      $this->modules[$block_plugin->getPluginDefinition()['provider']] = TRUE;
      $this->processConfigDependencies($config_name, FALSE);
      $this->processEntityTypeBundleDisplays($entity_type_id, $bundle);
    }
    else {
      $this->entityTypes[$bundle_entity_type][$bundle] = FALSE;
    }
  }

  /**
   * Process VLSuite field block component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   Component to process.
   * @param \Drupal\vlsuite_block\Plugin\Block\VLSuiteInlineBlock $block_plugin
   *   Component block plugin.
   */
  protected function processComponentVlsuiteFieldBlock(SectionComponent $component, VLSuiteFieldBlock $block_plugin) {
    $this->processComponentStylesDependencies($component);
  }

  /**
   * Process views block component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   Component to process.
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block_plugin
   *   Component block plugin.
   */
  protected function processComponentViewsBlock(SectionComponent $component, ViewsBlock $block_plugin) {
    [$view_name] = explode('-', $block_plugin->getDerivativeId(), 2);
    $config_name = \Drupal::entityTypeManager()->getStorage('view')->load($view_name)->getConfigDependencyName();
    $this->modules[$block_plugin->getPluginDefinition()['provider']] = TRUE;
    $this->processConfigDependencies($config_name, TRUE);
  }

  /**
   * Process block base component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   Component to process.
   * @param \Drupal\Core\Block\BlockBase $block_plugin
   *   Component block plugin.
   */
  protected function processComponentBlockBase(SectionComponent $component, BlockBase $block_plugin) {
    $this->modules[$block_plugin->getPluginDefinition()['provider']] = TRUE;
    foreach (($block_plugin->getPluginDefinition()['config_dependencies']['config'] ?? []) as $config_name) {
      $this->processConfigDependencies($config_name, TRUE);
    }
  }

  /**
   * Process entity type bundle displays (those using layout builder too).
   *
   * @param string $entity_type_id
   *   Entity type id to process its displays.
   * @param string $bundle
   *   Entity bundle to process its displays.
   */
  protected function processEntityTypeBundleDisplays($entity_type_id, $bundle) {
    foreach ($this->layoutBuilderEntityViewDisplays as $display_id => $display) {
      /** @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $display */
      if ($display->getTargetEntityTypeId() == $entity_type_id &&
        $display->getTargetBundle() == $bundle &&
        $display->isLayoutBuilderEnabled()) {
        $this->logger->notice("Processing display \"{$display_id}\"");
        $this->processSections($display->getSections());
      }
    }
  }

}
