<?php

namespace Drupal\hello_world_luna;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Prepares the salutation to the world
 */
class HelloWorldLunaSalutation {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * HelloWorldLunaSalutation
   * @param \Drupal\Core\Config\ConfigFactoryInterface
   *  $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Return the salutation
   */
  public function getSalutation() {

    $config = $this->configFactory->get('hello_world_luna.custom_salutation');
    $salutation = $config->get('salutation');
    if($salutation !== '' && $salutation) {
      return $salutation;
    }

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
