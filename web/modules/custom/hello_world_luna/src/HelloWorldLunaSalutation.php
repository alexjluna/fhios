<?php

namespace Drupal\hello_world_luna;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Prepares the salutation to the world
 */
class HelloWorldLunaSalutation {

  use StringTranslationTrait;

  /**
   * Return the salutation
   */
  public function getSalutation() {
    $time = new \DateTime();
    if((int) $time->format('G') >= 00 && (int) $time->format('G') < 12) {
      return $this->t('Good morning world');
    }

    if((int) $time->format('G') >= 12 && (int) $time->format('G') < 18) {
      return $this->t('Good afternoon world');
    }

    if((int) $time->format('G') >= 18){
      return $this->t('Good evening world');
    }
  }
}
