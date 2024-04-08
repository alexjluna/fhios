<?php

namespace Drupal\smart_content\Decision;

/**
 * Interface for decisions that provide placeholders for insert commands.
 */
interface PlaceholderDecisionInterface {

  /**
   * Get the target ID for ajax commands.
   *
   * @return string
   *   The placeholder id.
   */
  public function getPlaceholderId();

}
