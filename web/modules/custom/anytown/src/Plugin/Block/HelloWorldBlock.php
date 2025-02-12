<?php

namespace Drupal\anytown\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a hello world block.
 *
 * @Block(
 *   id = "anytown_hello_world",
 *   admin_label = @Translation("Hello world"),
 *   category = @Translation("Custom"),
 * )
 */
final class HelloWorldBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['content'] = [
      '#markup' => $this->t('Hello world!'),
    ];
    return $build;
  }

}
