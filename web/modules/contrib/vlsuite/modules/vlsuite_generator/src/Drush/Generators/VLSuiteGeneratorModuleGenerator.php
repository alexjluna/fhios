<?php

namespace Drupal\vlsuite_generator\Drush\Generators;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\default_content\ExporterInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\section_library\Entity\SectionLibraryTemplateInterface;
use Drupal\vlsuite_generator\VLSuiteGeneratorModuleGeneratorProcessConfigTrait;
use Drupal\vlsuite_generator\VLSuiteGeneratorModuleGeneratorProcessStylesTrait;
use Drupal\vlsuite_generator\VLSuiteGeneratorModuleGeneratorProcessTrait;
use DrupalCodeGenerator\Asset\AssetCollection as Assets;
use DrupalCodeGenerator\Attribute\Generator;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\GeneratorType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * VLSuite generator module.
 */
#[Generator(
  name: 'vlsuite-generator:module',
  aliases: ['vlsuite-module'],
  description: 'Generates a VLSuite module with components from library selection',
  templatePath: __DIR__ . '/../../../templates/generator/_module',
  type: GeneratorType::MODULE,
)]
final class VLSuiteGeneratorModuleGenerator extends BaseGenerator implements ContainerInjectionInterface {

  use VLSuiteGeneratorModuleGeneratorProcessTrait;
  use VLSuiteGeneratorModuleGeneratorProcessConfigTrait;
  use VLSuiteGeneratorModuleGeneratorProcessStylesTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The default content exporter.
   *
   * @var \Drupal\default_content\ExporterInterface
   */
  protected $defaultContentExporter;

  /**
   * Layout builder entity view displays.
   *
   * @var array[\Drupal\layout_builder\Entity\LayoutEntityDisplayInterface]
   */
  protected $layoutBuilderEntityViewDisplays = [];

  /**
   * Interviewer to interact with user.
   *
   * @var \DrupalCodeGenerator\InputOutput\Interviewer
   */
  protected $interviewer = NULL;

  /**
   * Entity types to export (config entities: e.g: node -> node_type).
   *
   * @var array
   *   [[entity_type_bundle[bundle => included/discarded]]]
   */
  protected $entityTypes = [];

  /**
   * Config names to export.
   *
   * @var array
   *   [[config_name => included/discarded]]
   */
  protected $config = [];

  /**
   * Modules dependencies to export.
   *
   * @var array
   *   [[module_name => included/discarded]]
   */
  protected $modules = [];

  /**
   * Chosen entity id.
   *
   * @var int
   */
  protected $chosenEntityId = NULL;

  /**
   * Utilities to include in generated module as partial config.
   *
   * @var array
   */
  protected $utilities = [];

  /**
   * Utilities used within found & confirmed entities (collectable).
   *
   * @var array
   */
  protected $utilitiesUsed = [];

  /**
   * Apply to enabled utilities usage for vlsuite block config.
   *
   * @var array
   */
  protected $applyToEnabled = [];

  /**
   * Apply to enabled used within found & confirmed entities (collectable).
   *
   * @var array
   */
  protected $applyToEnabledUsed = [];

  /**
   * Libraries candidates names to show warning at finish.
   *
   * @var array
   */
  protected $librariesCandidates = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ExporterInterface $default_content_exporter) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->defaultContentExporter = $default_content_exporter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('default_content.exporter')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function generate(array &$vars, Assets $assets): void {
    // Init layout builder displays.
    $this->layoutBuilderEntityViewDisplays = LayoutBuilderEntityViewDisplay::loadMultiple();

    // Create interviewer to ask required info to generate new module.
    $this->interviewer = $this->createInterviewer($vars);

    // 1. Ask module name.
    $vars['name'] = $this->interviewer->askName();

    // 2. Ask module machine name.
    $vars['machine_name'] = $this->interviewer->askMachineName();
    $vars['description'] = 'VLSuite - Generator: generated module';
    $vars['package'] = 'Visual Layout Suite (VLSuite)';

    // 3. Ask for library entity to export.
    $entity_selection = $this->entityChoose();

    // 4. Process sections of chosen library entity.
    $this->processSections($entity_selection->get('layout_section')->getValue());

    // 5. Generate new module basis.
    $this->processMainAssets($vars, $assets);

    // 6. Generate config assets.
    $this->processConfigGenerateAssets($vars, $assets);

    // 7. Generate styles assets.
    $this->generateStylesAssets($vars, $assets);

    // 8. Generate content of main entities detected & dependencies.
    if ($this->interviewer->confirm("Would you like to generate default content with selected section library template and its references?")) {
      $this->modules['default_content'] = TRUE;
      $default_content_directory = $assets->addDirectory("{$vars['machine_name']}/content")->getPath();
      $this->defaultContentExporter->exportContentWithReferences('section_library_template', $this->chosenEntityId, "modules/custom/{$default_content_directory}");
    }
  }

  /**
   * Entity choose section library template.
   *
   * @return \Drupal\section_library\Entity\SectionLibraryTemplateInterface
   *   Section library template to use.
   */
  protected function entityChoose(): SectionLibraryTemplateInterface {
    $entity_list = $this->entityTypeManager->getStorage('section_library_template')->loadMultiple();
    $options = [];
    foreach ($entity_list as $eid => $entity) {
      // @see https://www.drupal.org/project/section_library/issues/3394377
      try {
        $url_string = ' - ' . Url::fromRoute('entity.section_library_template.preview', [
          'section_library_template' => $eid,
        ], ['absolute' => TRUE])->toString();
      }
      catch (RouteNotFoundException $exception) {
        $url_string = '';
        $this->logger->debug("Section library template preview not possible, check https://www.drupal.org/project/section_library/issues/3394377");
      }
      $options[$eid] = $entity->label() . ' (' . $eid . ')' . $url_string;
    }
    $entity_selection = $this->interviewer->choice('Choose to export', $options);
    $this->chosenEntityId = $entity_list[$entity_selection]->id();
    return $entity_list[$entity_selection];
  }

  /**
   * Sections & its components iterator to loop just once & to use callbacks.
   *
   * @param array $sections
   *   Sections to iterate.
   * @param array $sections_callbacks
   *   Section callbacks to execute for each section found.
   * @param array $components_callbacks
   *   Components callbacks to execute for each component found.
   */
  protected function sectionsIterator(array $sections, array $sections_callbacks = [], array $components_callbacks = []) {
    foreach ($sections as $section_item) {
      $section = is_array($section_item) && !empty($section_item['section']) && $section_item['section'] instanceof Section ? $section_item['section'] : $section_item;
      if (!($section instanceof Section)) {
        continue;
      }
      foreach ($sections_callbacks as $callback) {
        call_user_func_array($callback, [$section]);
      }
      $this->componentsIterator($section->getComponents(), $components_callbacks);
    }
  }

  /**
   * Components iterator to loop just once & to use callbacks.
   *
   * @param array $components
   *   Components to iterate.
   * @param array $components_callbacks
   *   Components callbacks to execute for each component found.
   */
  protected function componentsIterator(array $components, array $components_callbacks) {
    foreach ($components as $component) {
      if (!($component instanceof SectionComponent)) {
        continue;
      }
      foreach ($components_callbacks as $callback) {
        call_user_func_array($callback, [$component]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function printSummary(Assets $dumped_assets, string $base_path): void {
    parent::printSummary($dumped_assets, $base_path);
    $this->processStylesPrintSummary();
    $this->logger->warning("Finished! Please confirm generated module and files before enabling into other site, also check for any templates or libraries to include it manually.");
  }

}
