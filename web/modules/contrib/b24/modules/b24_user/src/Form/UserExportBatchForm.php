<?php

namespace Drupal\b24_user\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\b24\Service\RestManager;
use Drupal\b24_user\Service\UserManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for batch export of Drupal users to Bitrix24 contacts.
 */
class UserExportBatchForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Batch Builder.
   *
   * @var \Drupal\Core\Batch\BatchBuilder
   */
  protected $batchBuilder;

  /**
   * The Bitrix24 user manager.
   *
   * @var \Drupal\b24_user\Service\UserManager
   */
  protected $userManager;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('b24.rest_manager'),
      $container->get('entity_type.manager'),
      $container->get('b24_user.manager'),
      $container->get('extension.list.module')
    );
  }

  /**
   * Constructs a UserExportBatchForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RestManager $b24_rest_manager,
    EntityTypeManagerInterface $entity_type_manager,
    UserManager $user_manager,
    ModuleExtensionList $module_extension_list,
  ) {
    if (!$this->configFactory) {
      $this->configFactory = $config_factory;
    }
    $this->b24RestManager = $b24_rest_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->batchBuilder = new BatchBuilder();
    $this->userManager = $user_manager;
    $this->extensionListModule = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'b24_user_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled_roles = $this->configFactory->get('b24_user.settings')->get('exported_roles');
    $enabled_roles = array_filter($enabled_roles);

    $storage = $this->entityTypeManager->getStorage('user');

    $query = $storage->getQuery()
      ->condition('status', 1);
    if (!in_array('authenticated', $enabled_roles)) {
      $group = $query->orConditionGroup();
      $group->condition('roles', $enabled_roles, 'IN');
      // @todo Can't load administrators.
      $query->condition($group);
    }
    $query->accessCheck();

    $ids = $query->execute();

    if (!$ids) {
      return;
    }

    $this->batchBuilder
      ->setTitle($this->t('Processing'))
      ->setInitMessage($this->t('Initializing.'))
      ->setProgressMessage($this->t('Completed @current of @total.'))
      ->setErrorMessage($this->t('An error has occurred.'))
      ->setFile($this->extensionListModule->getPath('b24_user') . '/src/Form/UserExportBatchForm.php');

    $this->existingUsers = $this->userManager->getExistingUsers();

    $this->batchBuilder->addOperation([$this, 'processItems'], [$ids]);

    $this->batchBuilder->setFinishCallback([$this, 'finished']);

    batch_set($this->batchBuilder->toArray());
  }

  /**
   * Processor for batch operations.
   */
  public function processItems($items, array &$context) {
    // Elements per operation.
    $limit = 50;

    // Set default progress values.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($items);
    }

    // Save items to array which will be changed during processing.
    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $items;
    }

    $counter = 0;
    if (!empty($context['sandbox']['items'])) {
      // Remove already processed items.
      if ($context['sandbox']['progress'] != 0) {
        array_splice($context['sandbox']['items'], 0, $limit);
      }

      foreach ($context['sandbox']['items'] as $item) {
        if ($counter != $limit) {
          $this->userManager->processUser($item);

          $counter++;
          $context['sandbox']['progress']++;

          $context['message'] = $this->t('Now processing user :progress of :count', [
            ':progress' => $context['sandbox']['progress'],
            ':count' => $context['sandbox']['max'],
          ]);

          // Increment total processed item values. Will be used in finished
          // callback.
          $context['results']['processed'] = $context['sandbox']['progress'];
        }
      }
    }

    // If not finished all tasks, we count percentage of process. 1 = 100%.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finished callback for batch.
   */
  public function finished($success, $results, $operations) {
    $message = $this->t('Number of users affected by batch: @count', [
      '@count' => $results['processed'],
    ]);

    $this->messenger()
      ->addStatus($message);
  }

}
