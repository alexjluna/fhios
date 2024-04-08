<?php

namespace Drupal\smart_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\smart_content\SegmentSet;

/**
 * Defines the SegmentSet entity.
 *
 * @ConfigEntityType(
 *   id = "smart_content_segment_set",
 *   label = @Translation("Segment Set"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\smart_content\SegmentSetConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\smart_content\Form\SegmentSetConfigEntityForm",
 *       "edit" = "Drupal\smart_content\Form\SegmentSetConfigEntityForm",
 *       "delete" = "Drupal\smart_content\Form\SegmentSetConfigEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\smart_content\SegmentSetConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "smart_content.segment_set",
 *   admin_permission = "administer smart content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "settings"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/smart_content_segment_set/{smart_content_segment_set}",
 *     "add-form" = "/admin/structure/smart_content_segment_set/add",
 *     "edit-form" = "/admin/structure/smart_content_segment_set/{smart_content_segment_set}/edit",
 *     "delete-form" = "/admin/structure/smart_content_segment_set/{smart_content_segment_set}/delete",
 *     "collection" = "/admin/structure/smart_content_segment_set"
 *   }
 * )
 */
class SegmentSetConfig extends ConfigEntityBase {

  /**
   * The entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The SegmentSet settings.
   *
   * @var array
   */
  public $settings = [];

  /**
   * The SegmentSet instance.
   *
   * @var \Drupal\smart_content\SegmentSet
   */
  protected $segmentSetInstance;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->segmentSetInstance = SegmentSet::fromArray($this->settings);
  }

  /**
   * Get SegmentSet instance.
   *
   * @return \Drupal\smart_content\SegmentSet
   *   The segment set instance.
   */
  public function getSegmentSet() {
    return $this->segmentSetInstance;
  }

  /**
   * Set the SegmentSet.
   *
   * Generally this is only needed when overriding the instantiated SegmentSet,
   * as normally this instance is automatically loaded during __construct().
   *
   * @param \Drupal\smart_content\SegmentSet $segment_set
   *   The segment set.
   *
   * @return $this
   *   Return this.
   */
  public function setSegmentSet(SegmentSet $segment_set) {
    $this->segmentSetInstance = $segment_set;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $this->settings = $this->getSegmentSet()->toArray();
    parent::save();
    // We clear cache for segment set storage as we provide a deriver for all
    // global segment sets.  @see https://www.drupal.org/node/3013690
    \Drupal::service('plugin.manager.smart_content.segment_set_storage')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    // We clear cache for segment set storage as we provide a deriver for all
    // global segment sets.  @see https://www.drupal.org/node/3013690
    \Drupal::service('plugin.manager.smart_content.segment_set_storage')->clearCachedDefinitions();
  }

  /**
   * Magic method: Implements a deep clone.
   */
  public function __clone() {
    $this->setSegmentSet(clone $this->getSegmentSet());
  }

}
