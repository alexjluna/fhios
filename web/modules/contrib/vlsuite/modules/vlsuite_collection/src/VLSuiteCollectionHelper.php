<?php

namespace Drupal\vlsuite_collection;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\file\FileRepositoryInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\section_library\Entity\SectionLibraryTemplate;
use Drupal\vlsuite_block\Entity\Bundle\VLSuiteBlockBase;

/**
 * Helper "VLSuiteCollectionHelper" service object.
 */
class VLSuiteCollectionHelper {

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file repository.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * Constructs a "VLSuiteCollectionHelper" object.
   *
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resovler
   *   The extension path resolver.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_sysyem
   *   The file system.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $file_repository
   *   The file repository.
   */
  public function __construct(ExtensionPathResolver $extension_path_resovler, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_sysyem, FileRepositoryInterface $file_repository) {
    $this->extensionPathResolver = $extension_path_resovler;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_sysyem;
    $this->fileRepository = $file_repository;
  }

  /**
   * Section library template generate Yml file.
   *
   * @param string $uuid
   *   Uuid to look for.
   * @param string $module_name
   *   Module name where will be place inside assets folder.
   */
  public function sectionLibraryTemplateGenerateYmlFile(string $uuid, string $module_name) {
    $templates = $this->entityTypeManager->getStorage('section_library_template')->loadByProperties(['uuid' => $uuid]);
    $template = reset($templates);

    if (empty($template)) {
      return;
    }

    $template_values = [
      'uuid' => $template->uuid(),
      'label' => $template->label(),
      'type' => 'template',
      'layout_section' => [],
    ];

    $sections = $template->get('layout_section')->getValue();

    foreach ($sections as $section) {
      $template_values['layout_section'][] = $section['section']->toArray();
    }
    $yml_path = $this->extensionPathResolver->getPath('module', $module_name) . '/assets/section-library-template-' . $template->uuid() . '.yml';
    file_put_contents($yml_path, Yaml::encode($template_values));
  }

  /**
   * Section library template create from Yml.
   *
   * @param string $uuid
   *   Section library template uuid to use.
   * @param string $module_name
   *   Module name.
   * @param array $media_placehold_it
   *   [Media field name | Media uuid] to use.
   */
  public function sectionLibraryTemplateCreateFromYmlFile(string $uuid, string $module_name, array $media_placehold_it = []) {
    $yml_path = $this->extensionPathResolver->getPath('module', $module_name) . '/assets/section-library-template-' . $uuid . '.yml';
    $yml_content = file_get_contents($yml_path);

    if (!empty($this->entityTypeManager->getStorage('section_library_template')->loadByProperties([
      'uuid' => $uuid,
      'type' => 'template',
    ])) || !$yml_content) {
      return;
    }

    $source = Yaml::decode($yml_content);
    $new_template = [
      'label' => $source['label'],
      'type' => 'template',
      'layout_section' => [],
    ];
    foreach ($source['layout_section'] as $source) {
      $components = [];
      $section = $section = new Section(
        $source['layout_id'],
        $source['layout_settings'],
        $components,
        $source['third_party_settings'],
      );
      foreach ($source['components'] as $uuid => $component_array) {
        $this->blockMediaPlaceholdIt($component_array, $media_placehold_it);
        $section->appendComponent((new SectionComponent($uuid, $component_array['region'], $component_array['configuration'])));
      }
      $new_template['layout_section'][] = $section;
    }
    $new = SectionLibraryTemplate::create($new_template);
    $new->save();
  }

  /**
   * Section component block media palcehold it.
   *
   * @param array $component_array
   *   Component array.
   * @param array $media_placehold_it
   *   [Media field name | Media uuid] to use.
   */
  protected function blockMediaPlaceholdIt(array &$component_array, array $media_placehold_it) {
    foreach ($media_placehold_it as $media_field_name => $media_uuid) {
      // phpcs:ignore DrupalPractice.FunctionCalls.InsecureUnserialize.InsecureUnserialize
      $block = !empty($component_array['configuration']['block_serialized']) ? unserialize($component_array['configuration']['block_serialized']) : NULL;
      if ($block instanceof VLSuiteBlockBase && $block->hasField($media_field_name) && !$block->get($media_field_name)->isEmpty()) {
        $medias = $this->entityTypeManager->getStorage('media')->loadByProperties(['uuid' => $media_uuid]);
        $media_image_id = !empty($medias) ? reset($medias)->id() : NULL;
        $new_value = [];
        foreach ($block->get($media_field_name)->getValue() as $media_field_item) {
          $new_value[] = ['target_id' => !empty($media_field_item) ? $media_image_id : $media_image_id];
        }
        $block->set($media_field_name, $new_value);
        $component_array['configuration']['block_serialized'] = !empty($media_image_id) ? serialize($block) : $component_array['configuration']['block_serialized'];
      }
    }
  }

  /**
   * Generate media image.
   *
   * @param string $media_type
   *   Media type.
   * @param string $uuid
   *   Media uuid to use.
   * @param string $module_name
   *   Module name.
   * @param string $file_name
   *   Image file name.
   * @param string $file_uuid
   *   Fille uuid to use.
   */
  public function generateMedia(string $media_type, string $uuid, string $module_name, string $file_name, string $file_uuid) {
    $file_source = $this->extensionPathResolver->getPath('module', $module_name) . '/assets/' . $file_name;
    $destination = 'public://' . $module_name . '/';

    if (empty($this->entityTypeManager->getStorage('file')->loadByProperties(['uuid' => $file_uuid]))) {
      $image_content = file_get_contents($file_source);
      if (!$image_content) {
        return;
      }
      $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      $file = $this->fileRepository->writeData($image_content, $destination . $file_name, FileSystemInterface::EXISTS_REPLACE);
      $file->set('uuid', $file_uuid);
      $file->save();
    }
    else {
      $files = $this->entityTypeManager->getStorage('file')->loadByProperties(['uuid' => $file_uuid]);
      $file = reset($files);
    }
    if (empty($this->entityTypeManager->getStorage('media')->loadByProperties([
      'uuid' => $uuid,
      'bundle' => $media_type,
    ]))) {
      if (empty($this->entityTypeManager->getStorage('media_type')->load($media_type))) {
        // @see https://www.drupal.org/project/drupal/issues/3174255
        return;
      }
      $media_array = [
        'name' => 'VLSuite collection placeholder media',
        'bundle' => $media_type,
        'uuid' => $uuid,
      ];
      $media_array[$this->entityTypeManager->getStorage('media_type')->load($media_type)->getSource()->getConfiguration()['source_field']] = [
        'target_id' => $file->id(),
      ];
      $this->entityTypeManager->getStorage('media')->create($media_array)->save();
    }
  }

}
