<?php

namespace Drupal\hello_world_luna\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\hello_world_luna\HelloWorldLunaSalutation;

/**
 * Hello World Luna Salutation
 *
 * @Block(
 *   id = "hello_world_luna_salutation_block",
 *   admin_label = @Translation("Hello World Luna Salutation")
 * )
 */
class HelloWorldLunaSalutationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The salutation service
   * @var \Drupal\hello_world_luna\HelloWorldLunaSalutation
   */
  protected $salutation;

  /**
   * Constructs a HelloWorldLunaSalutationBlock.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, HelloWorldLunaSalutation $salutation){
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->salutation = $salutation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('hello_world_luna.salutation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->salutation->getSalutation(),
    ];
  }

}
