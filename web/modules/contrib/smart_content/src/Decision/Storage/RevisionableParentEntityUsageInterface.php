<?php

namespace Drupal\smart_content\Decision\Storage;

use Drupal\Core\Entity\RevisionableInterface;

/**
 * Interface for decision storage referenced by revisionable parents.
 *
 * Provides an interface for storage items that may be referenced by
 * revisionable parent entities.  In these cases, additional cleanup may be
 * required for past revisions.
 */
interface RevisionableParentEntityUsageInterface {

  /**
   * Static cleanup method for revisionable parents.
   *
   * Because revisionable parents may not reference all related decisions
   * in the latest revision, we provide a cleanup method to find usage in
   * past revisions.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $parent_entity
   *   The parent entity.
   */
  public static function deleteByParent(RevisionableInterface $parent_entity);

  /**
   * Retrieve usage by parent entity.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $parent_entity
   *   The parent entity.
   *
   * @return array
   *   Array of usage.
   */
  public static function getUsageByParentEntity(RevisionableInterface $parent_entity);

  /**
   * Add a usage reference.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $parent_entity
   *   The parent entity.
   *
   * @return $this
   *   Return $this.
   */
  public function addUsage(RevisionableInterface $parent_entity);

  /**
   * Delete usage.
   *
   * @return $this
   *   Return $this.
   */
  public function deleteUsage();

  /**
   * Get usage.
   *
   * @return array
   *   Array of usage.
   */
  public function getUsage();

}
