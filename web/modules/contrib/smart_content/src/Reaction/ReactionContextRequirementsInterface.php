<?php

namespace Drupal\smart_content\Reaction;

/**
 * Interface ReactionContextRequirementsInterface.
 *
 * Use this interface for Reactions that have multiple contexts for processing.
 *
 * @package Drupal\smart_content\Reaction
 */
interface ReactionContextRequirementsInterface {

  /**
   * Sets the context definitions to required based on definition settings.
   *
   * Maps 'reaction_context_requirements' definition values as required context
   * for processing reaction.
   *
   * @return $this
   *   This.
   */
  public function setReactionContextRequirements();

  /**
   * Gets an array of context js settings.
   *
   * Returns an array of context settings for processing a single decision with
   * multiple contexts.
   *
   * @return array
   *   An array of settings.
   */
  public function getReactionContextAttached();

}
