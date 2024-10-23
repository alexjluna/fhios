<?php

namespace Drupal\vlsuite_block\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\layout_builder_restrictions\Controller\ChooseBlockController as ChooseBlockControllerCore;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Choose a new block.
 */
final class VLSuiteBlockChooseBlockController extends ChooseBlockControllerCore {
  /**
   * The File URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionList;

  /**
   * VLSuiteBlockChooseBlockController constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Block manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   File url generator.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list
   *   Extension list.
   */
  public function __construct(
    BlockManagerInterface $block_manager,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
    FileUrlGeneratorInterface $file_url_generator,
    ModuleExtensionList $extension_list
  ) {
    parent::__construct($block_manager, $entity_type_manager, $current_user);
    $this->fileUrlGenerator = $file_url_generator;
    $this->extensionList = $extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('file_url_generator'),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(SectionStorageInterface $section_storage, $delta, $region) {
    $build = parent::build($section_storage, $delta, $region);
    if (!empty($build['add_block'])) {
      $build['add_block']['#access'] = FALSE;
    }
    // Add wrapper to get filters working @see
    // https://www.drupal.org/project/drupal/issues/3308658#comment-15002205
    $build['#type'] = 'container';
    if (!empty($build['block_categories'])) {
      foreach (Element::children($build['block_categories']) as $category_key) {
        if ($category_key == (string) $this->t('VLSuite: Inline blocks')) {
          $build['block_categories'][$category_key]['#open'] = TRUE;
          $build['block_categories'][$category_key]['#weight'] = -200;
          $build['block_categories'][$category_key]['#title'] = $this->t('VLSuite: Add new block');
        }
        elseif ($category_key == (string) $this->t('VLSuite: Field blocks')) {
          $build['block_categories'][$category_key]['#open'] = FALSE;
          $build['block_categories'][$category_key]['#weight'] = -199;
          $build['block_categories'][$category_key]['#title'] = $this->t('VLSuite: Add field block');
        }
        elseif ($category_key == (string) $this->t('VLSuite: List block (views)')) {
          $build['block_categories'][$category_key]['#open'] = FALSE;
          $build['block_categories'][$category_key]['#weight'] = -198;
          $build['block_categories'][$category_key]['#title'] = $this->t('VLSuite: Add list block');
        }
        else {
          $build['block_categories'][$category_key]['#open'] = FALSE;
        }
        $this->categoryBlockLinksAddIcon($build['block_categories'][$category_key], $category_key);
      }
    }
    return $build;
  }

  /**
   * Generate block icons for category block links.
   *
   * @param array $category
   *   Category.
   * @param string $category_key
   *   Category key.
   */
  protected function categoryBlockLinksAddIcon(array &$category, $category_key) {
    if (!empty($category['links']) && !empty($category['links']['#links'])) {
      // Default block image.
      $icon_base_path = base_path() . $this->extensionList->getPath('vlsuite_block') . '/assets/';

      foreach (Element::children($category['links']['#links']) as $delta) {
        $icon_url = $icon_base_path . 'puzzlepiece.svg';
        if (!empty($category['links']['#links'][$delta]['url'])) {
          if ($category_key == $this->t('VLSuite: Inline blocks')) {
            $plugin_id = $category['links']['#links'][$delta]['url']->getRouteParameters()['plugin_id'];
            [, $vlsuite_block_type] = explode(':', $plugin_id);
            $icon_uuid = $this->entityTypeManager->getStorage('block_content_type')->load($vlsuite_block_type)->get('vlsuite_block_icon');
            $files = !empty($icon_uuid) ? $this->entityTypeManager->getStorage('file')->loadByProperties(['uuid' => $icon_uuid]) : [];
            $icon_url = !empty($files) && reset($files) instanceof FileInterface ? $this->fileUrlGenerator->generate(reset($files)->getFileUri())->toString() : $icon_url;
          }
          elseif ($category_key == (string) $this->t('VLSuite: Field blocks')) {
            $icon_url = $icon_base_path . 'star.svg';
          }
          elseif ($category_key == (string) $this->t('VLSuite: List block (views)')) {
            $icon_url = $icon_base_path . 'grid.svg';
          }
          $label = $category['links']['#links'][$delta]['title'];
          $category['links']['#links'][$delta]['title'] = Markup::create('<img src="' . $icon_url . '" class="block-link-img" /> ' . '<span class="block-link-label">' . $label . '</span>');
        }
      }
    }
  }

}
