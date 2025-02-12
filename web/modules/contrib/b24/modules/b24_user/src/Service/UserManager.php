<?php

namespace Drupal\b24_user\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\b24\Service\RestManager;
use Drupal\user\Entity\User;

/**
 * Contains logic for working with Bitrix24 contacts.
 */
class UserManager {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * An array of entities present in Bitrix24.
   *
   * @var array
   */
  protected $existing;

  /**
   * An array of submitted values.
   *
   * @var array
   */
  protected $settings;

  /**
   * Drupal\b24\Service\RestManager definition.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected $restManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new UserManager object.
   */
  public function __construct(
    RestManager $b24_rest_manager,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    Token $token,
    Connection $connection,
  ) {
    $this->restManager = $b24_rest_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->database = $connection;
    $this->existing = $this->getExistingUsers();
  }

  /**
   * Returns list of already imported contacts.
   *
   * @return array
   *   An array of users.
   */
  public function getExistingUsers() {
    $existing_records = $this->database->select('b24_reference', 'r')
      ->fields('r', ['entity_id', 'ext_id'])
      ->condition('ext_type', 'contact')
      ->condition('bundle', 'user')
      ->execute()->fetchAll();

    return array_column($existing_records, 'ext_id', 'entity_id');
  }

  /**
   * Processes a single user.
   *
   * @param int $id
   *   The Drupal user id.
   * @param bool $update
   *   Flag indicating if a Bitrix24 contact should be updated.
   */
  public function processUser($id, $update = FALSE) {
    $storage = $this->entityTypeManager->getStorage('user');
    /** @var \Drupal\user\Entity\User $user */
    $user = $storage->load($id);
    if (array_key_exists($user->id(), $this->existing) && !$update) {
      return;
    }
    $fields = $this->getValues($user);

    $existing_ids = $this->existing ?? [];
    $id = $existing_ids[$user->id()] ?? FALSE;
    $hash = $this->restManager->getHash($fields);
    if ($update && $id) {
      $fields = $this->getValues($user, $id);
      $this->restManager->updateContact($id, $fields);
      $this->restManager->updateHash($user, 'contact', $hash);
    }
    else {
      $id = $this->restManager->addContact($fields);
      $this->database->insert('b24_reference')
        ->fields([
          'entity_id' => $user->id(),
          'bundle' => 'user',
          'ext_id' => $id,
          'ext_type' => 'contact',
          'hash' => $hash,
        ])->execute();
    }
  }

  /**
   * Returns fields values of a Drupal user.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param int|null $id
   *   The Bitrix24 user id.
   *
   * @return array
   *   An array of user field values.
   *
   * @todo The following function is already has been implemented in in a similar way b24_commerce module thus it worth to be moved to a separate function of a parent module.
   */
  public function getValues(User $user, ?int $id = NULL) {
    $fields = array_filter($this->configFactory->get("b24_user.mapping")->get());
    $field_definitions = $this->configFactory->get("b24_user.field_types")->get();

    if ($id) {
      $existing_entity = $this->restManager->getContact($id);
    }

    foreach ($fields as $field_name => &$value) {
      $bubbleable_metadata = new BubbleableMetadata();
      $value = nl2br($this->token->replace($value, ['user' => $user], ['clear' => TRUE], $bubbleable_metadata));
      if ($field_definitions[$field_name]['isRequired'] && !$value) {
        $value = $this->t('- Not set -');
      }
      /*
       * In Bitrix24 each item of a multiple field is a separate entity.
       * Updating multiple field items without mentioning id causes adding
       * another item. So we need to get all the related ids previously.
       */
      if ($field_definitions[$field_name]['type'] == 'crm_multifield') {
        $value = [['VALUE' => $value, 'VALUE_TYPE' => 'HOME']];
        if (!empty($existing_entity[$field_name])) {
          // @todo Support multiple drupal fields.
          $value[0]['ID'] = reset($existing_entity[$field_name])['ID'];
        }
      }
    }
    return $fields;
  }

  /**
   * Imports user from Bitrix24.
   *
   * @param array $data
   *   User data provided by Bitrix24.
   *
   * @return false|int|null
   *   Created user id.
   */
  public function importUser(array $data) {
    $storage = $this->entityTypeManager->getStorage('user');
    $query = $storage->getQuery();
    $group = $query->orConditionGroup();
    $group->condition('name', $data['NAME']);
    $group->condition('mail', reset($data['EMAIL'])['VALUE']);
    $query->condition($group);
    $query->range(0, 1);
    $query->accessCheck();
    $ids = $query->execute();
    if (empty($ids)) {
      $user = User::create([
        'name' => $data['NAME'],
        'mail' => reset($data['EMAIL'])['VALUE'],
        'roles' => $data['roles'],
        'status' => 1,
      ]);
      $save = $user->save();
      $fields = $this->getValues($user, $data['ID']);
      $hash = $this->restManager->getHash($fields);
      $this->database->insert('b24_reference')
        ->fields([
          'entity_id' => $user->id(),
          'bundle' => 'user',
          'ext_id' => $data['ID'],
          'ext_type' => 'contact',
          'hash' => $hash,
        ])->execute();
      return $save;
    }
    return FALSE;
  }

}
