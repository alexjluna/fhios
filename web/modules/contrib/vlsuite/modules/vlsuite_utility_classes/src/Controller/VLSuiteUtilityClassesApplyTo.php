<?php

namespace Drupal\vlsuite_utility_classes\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Utility classes apply to controller.
 */
class VLSuiteUtilityClassesApplyTo implements ContainerInjectionInterface {

  use LayoutRebuildTrait;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The utility classes helper.
   *
   * @var \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper
   */
  protected $utilityClassesHelper;

  /**
   * LayoutController constructor.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper $utility_classes_helper
   *   The utility classes helper.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, VLSuiteUtilityClassesHelper $utility_classes_helper) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->utilityClassesHelper = $utility_classes_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('vlsuite_utility_classes.helper')
    );
  }

  /**
   * For the layout builder.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section.
   * @param string $uuid
   *   The UUID for block or '_none' form section.
   * @param string $apply_to
   *   The apply to.
   * @param string $identifier
   *   The utility identifier.
   * @param string $value
   *   The utility value or '_none' to apply default.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function layoutBuilder(SectionStorageInterface $section_storage, int $delta, string $uuid, string $apply_to, string $identifier, string $value) {
    $apply_to_block = FALSE;
    if (empty($identifier) || empty($apply_to) || (empty($delta) && $delta !== 0) || (empty($value) && $value !== '0')) {
      return $this->rebuildLayout($section_storage);
    }
    $section = $section_storage->getSection($delta);
    if (!($section instanceof Section)) {
      return $this->rebuildLayout($section_storage);
    }
    $is_column_widths = $identifier === $section->getLayoutId() . ':column_widths';
    if (!empty($uuid) && $uuid !== '_none') {
      $component = $section->getComponent($uuid);
      if (!($component instanceof SectionComponent)) {
        return $this->rebuildLayout($section_storage);
      }
      $apply_to_block = TRUE;
    }
    if ($value !== '_none' && !$this->utilityClassesHelper->checkUtilityApplyToValueIsValid($apply_to, $identifier, $value) && !$is_column_widths) {
      return $this->rebuildLayout($section_storage);
    }

    if ($apply_to_block) {
      $component_config = $component->get('configuration');
      if (!isset($component_config['vlsuite_utility_class'])) {
        return $this->rebuildLayout($section_storage);
      }
      $utility_component_config = $component_config['vlsuite_utility_class'] ?? [];
      if ($value === '_none') {
        unset($utility_component_config[$apply_to][$identifier]);
      }
      else {
        $utility_component_config[$apply_to][$identifier] = $value;
      }
      $component_config['vlsuite_utility_class'] = $utility_component_config;
      $component->setConfiguration($component_config);
    }
    elseif ($is_column_widths) {
      $layout_settings = $section->getLayoutSettings();
      if (isset($layout_settings['column_widths'])) {
        $layout_settings['column_widths'] = $value;
      }
      $section->setLayoutSettings($layout_settings);
    }
    else {
      $layout_settings = $section->getLayoutSettings();
      if (!isset($layout_settings['vlsuite_utility_class'])) {
        return $this->rebuildLayout($section_storage);
      }
      $section_utilities = $layout_settings['vlsuite_utility_class'] ?? [];
      if ($value === '_none') {
        unset($section_utilities[$apply_to][$identifier]);
      }
      else {
        $section_utilities[$apply_to][$identifier] = $value;
      }
      $layout_settings['vlsuite_utility_class'] = $section_utilities;
      $section->setLayoutSettings($layout_settings);
    }

    $this->layoutTempstoreRepository->set($section_storage);
    return $this->rebuildLayout($section_storage);
  }

}
