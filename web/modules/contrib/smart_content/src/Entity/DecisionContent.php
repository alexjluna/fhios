<?php

namespace Drupal\smart_content\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\smart_content\Decision\DecisionInterface;

/**
 * Defines the DecisionContent entity.
 *
 * A decision set entity acts as storage for serialized segment data.
 * DecisionContent should be used when a decision is nested within another
 * content entity, allowing corresponding revisions and language support.
 *
 * The entity should not be interacted with directly with exception to
 * DecisionStorage plugins, which act as a common interface for CRUD
 * operations across storage types.
 *
 * @ingroup decision
 *
 * @ContentEntityType(
 *   id = "smart_content_decision_content",
 *   label = @Translation("Decision"),
 *   base_table = "smart_content_decision",
 *   data_table = "smart_content_decision_field_data",
 *   revision_table = "smart_content_decision_revision",
 *   revision_data_table = "smart_content_decision_field_revision",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "vid",
 *     "langcode" = "langcode",
 *   },
 * )
 */
class DecisionContent extends ContentEntityBase implements DecisionEntityInterface {

  // @todo: validate presave to confirm a decision is set.. Do not allow this to be saved without it.

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $definitions = parent::baseFieldDefinitions($entity_type);
    $definitions['settings'] = BaseFieldDefinition::create('smart_content_decision')
      ->setLabel(t('decision Settings'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The settings from the decision.'));
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDecision() {
    return !$this->get('settings')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getDecision() {
    return $this->get('settings')->first()->getDecision();
  }

  /**
   * {@inheritdoc}
   */
  public function setDecision(DecisionInterface $decision) {
    $this->get('settings')->setValue($decision);
    return $this;
  }

}
