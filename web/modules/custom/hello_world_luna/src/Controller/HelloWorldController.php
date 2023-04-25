<?php

namespace Drupal\hello_world_luna\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the salutation message
 */
class HelloWorldController extends ControllerBase {

  /**
   * Hello world
   *
   * @return array
   *  Our message
   */
  public function helloWorld() {
    return [
      '#markup' => $this->t('Hello World')
    ];
  }
}
