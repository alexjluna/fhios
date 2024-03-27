<?php

namespace Drupal\rsvplist\Controller;

/**
 * @file
 * Provide site administrators width a list.
 */

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Report Controller Class.
 */
class ReportController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new ReportController class.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   */
  public function __construct(Connection $database, MessengerInterface $messenger) {
    $this->database = $database;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger')
    );
  }

  /**
   * Get and returns all RSVPs for all nodes.
   *
   * @return array|null
   *   return array|null.
   */
  protected function load() {
    try {
      $selectQuery = $this->database->select('rsvplist', 'r');

      // Join the user table, so we can get the entry creator.
      $selectQuery->join('users_field_data', 'u', 'r.uid = u.uid');
      // Join the node table, so we can get the event name.
      $selectQuery->join('node_field_data', 'n', 'r.nid = n.nid');

      // Select these specific fields for the output.
      $selectQuery->addField('u', 'name', 'username');
      $selectQuery->addField('n', 'title');
      $selectQuery->addField('r', 'mail');

      $entries = $selectQuery->execute()->fetchAll(\PDO::FETCH_ASSOC);

      return $entries;
    }
    catch (\Exception $e) {
      $this->messenger->addStatus($this->t('Unable to access. Try again'));

      return NULL;
    }
  }

  /**
   * Create the RSVPList report page.
   *
   * @return array
   *   Render array for the RSVPList report output.
   */
  public function report() {
    $content = [];

    $content['message'] = [
      '#markup' => $this->t('Bellow is a list of all Event RSVPs including username, email address and the name of the event.'),
    ];

    $headers = [
      $this->t('Username'),
      $this->t('Event'),
      $this->t('Email'),
    ];

    $tableRows = $this->load();

    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $tableRows,
      '#empty' => $this->t('No entries availables.'),
    ];

    $content['#cache']['max-age'] = 0;

    return $content;
  }

}
