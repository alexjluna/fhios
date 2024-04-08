<?php

namespace Drupal\smart_content\Plugin\smart_content\SegmentSetStorage\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides condition plugin definitions for Demandbase fields.
 *
 * @see Drupal\smart_content_demandbase\Plugin\smart_content\Condition\DemandbaseCondition
 */
class GlobalSegmentSetDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The segment set entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $segmentSetEntityStorage;

  /**
   * DemandbaseConditionDeriver constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $segment_set_entity_storage
   *   The entity storage manager.
   */
  public function __construct(MessengerInterface $messenger, EntityStorageInterface $segment_set_entity_storage) {
    $this->messenger = $messenger;
    $this->segmentSetEntityStorage = $segment_set_entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('messenger'),
      $container->get('entity_type.manager')->getStorage('smart_content_segment_set')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // We currently have to update cache during entity save/delete.  Ideally,
    // we can implement cacheable dependencies here in the future.
    // @see https://www.drupal.org/node/3013690
    $this->derivatives = [];
    $entities = $this->segmentSetEntityStorage->loadMultiple();
    foreach ($entities as $entity) {
      $definition = [
        'label' => $entity->label(),
        'group' => $base_plugin_definition['label'],
      ];
      $this->derivatives[$entity->id()] = $definition + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
