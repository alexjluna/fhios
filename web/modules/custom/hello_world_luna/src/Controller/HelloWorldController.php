<?php

namespace Drupal\hello_world_luna\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hello_world_luna\HelloWorldLunaSalutation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the salutation message
 */
class HelloWorldController extends ControllerBase {

  /**
   * @var \Drupal\hello_world_luna\HelloWorldLunaSalutation
   */
  protected $salutation;

  /**
   * HelloWorldLunaController constructor
   */
  public function __construct(HelloWorldLunaSalutation $salutation) {
    $this->salutation = $salutation;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hello_world_luna.salutation')
    );
  }

  /**
   * Hello world
   *
   * @return array
   *  Our message
   */
  public function helloWorld() {
    return [
      '#markup' => $this->salutation->getSalutation()
    ];
  }
}
