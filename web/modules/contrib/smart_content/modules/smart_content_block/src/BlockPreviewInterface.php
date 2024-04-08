<?php

namespace Drupal\smart_content_block;

/**
 * Allows a block to provide a preview version of itself.
 */
interface BlockPreviewInterface {

  /**
   * Returns a render array for previewing a block.
   *
   * This method returns a render array to display a block in a preview mode.
   * This is useful when the full block display may impact the ability to edit
   * the block in the preview display, such as the case in layout builder.
   *
   * @return array
   *   The block preview render array.
   */
  public function buildPreview();

}
