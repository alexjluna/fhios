<?php

namespace Drupal\vlsuite_layout_builder\Controller;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\section_library\DeepCloningTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout builder duplicate controller.
 */
class VLSuiteLayoutBuilderDuplicate implements ContainerInjectionInterface {

  use LayoutRebuildTrait;
  use DeepCloningTrait;
  use StringTranslationTrait;

  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * LayoutController constructor.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID generator.
   * @param \Drupal\Component\Uuid\UuidInterface $messenger
   *   The messenger service.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, UuidInterface $uuid, MessengerInterface $messenger) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->uuidGenerator = $uuid;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('uuid'),
      $container->get('messenger')
    );
  }

  /**
   * Duplicate block.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section.
   * @param string $region
   *   Region name.
   * @param string $uuid
   *   Block uuid.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function block(SectionStorageInterface $section_storage, $delta, $region, $uuid) {
    $section = $section_storage->getSection($delta);
    if (!($section instanceof Section)) {
      return $this->rebuildLayout($section_storage);
    }
    $component = $section->getComponent($uuid);
    if (!($component instanceof SectionComponent)) {
      return $this->rebuildLayout($section_storage);
    }
    $block_plugin = $component->getPlugin();
    // Support inline blocks for now, use same clone strategy as section
    // library. @see \Drupal\section_library\DeepCloningTrait.
    if ($block_plugin instanceof InlineBlock) {
      $reflectionMethod = new \ReflectionMethod($block_plugin, 'getEntity');
      $reflectionMethod->setAccessible(TRUE);
      $block_content = $reflectionMethod->invoke($block_plugin);
      $component_array = $component->toArray();
      $configuration = $component_array['configuration'];
      $additional = $component_array['additional'];
      $cloned_block_content = $block_content->createDuplicate();
      $configuration['block_uuid'] = NULL;
      $configuration['block_revision_id'] = NULL;
      $configuration['block_serialized'] = NULL;
      $this->cloneReferencedEntities($cloned_block_content);
      $configuration['block_serialized'] = serialize($cloned_block_content);
      $cloned_component = new SectionComponent($this->uuidGenerator->generate(), $region, $configuration, $additional);
      $section->insertAfterComponent($uuid, $cloned_component);
    }
    else {
      $this->messenger->addMessage($this->t('This type of block cannot be duplicated.'), MessengerInterface::TYPE_WARNING);
      return $this->rebuildLayout($section_storage);
    }
    $this->layoutTempstoreRepository->set($section_storage);
    return $this->rebuildLayout($section_storage);
  }

}
