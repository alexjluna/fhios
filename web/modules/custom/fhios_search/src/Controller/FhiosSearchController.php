<?php

/**
 * @file
 * Contains \Drupal\fhios_search\Controller\FhiosSearchController.
 */

namespace Drupal\fhios_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use \Drupal\Core\Database\Connection;

class FhiosSearchController extends ControllerBase {

  protected $currentUser;
  protected $database;

  public function __construct(AccountProxy $current_user, Connection $database) {
    $this->currentUser = $current_user;
    $this->database = $database;
 }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('database')
    );
  }

  public function username($username) {

    $query = $this->database->query('SELECT name FROM {users_field_data} WHERE name = :uname', [':uname' => $username])->fetchAssoc();

    if($query['name'] == $username){
      return[
        '#markup' => '<h2>'.$this->t('The user exist ;)').'</h2>',
        '#cache' => [
          'max-age' => -1
        ]
      ];
    }else{
      return[
        '#markup' => '<h2>'.$this->t('The user does not exist :(').'</h2>',
        '#cache' => [
          'max-age' => -1
        ]
      ];
    }
  }
}
