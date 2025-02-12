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
 * Provides form for batch import of contacts.
 */
class UserImportBatchForm extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Bitrix24 REST manager service.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected $b24restManager;

  /**
   * Batch Builder.
   *
   * @var \Drupal\Core\Batch\BatchBuilder
   */
  protected $batchBuilder;

  /**
   * The Bitrix24 user manager service.
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
   * Status of live export setting.
   *
   * @var bool
   */
  protected $liveExportStatus;

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
   * Constructs a UserImportBatchForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RestManager $b24_rest_manager,
    EntityTypeManagerInterface $entity_type_manager,
    UserManager $user_manager,
    ModuleExtensionList $module_extension_list,
  ) {
    $this->configFactory = $config_factory;
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
    return 'b24_user_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('b24_user.import');
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    array_walk($roles, function (&$item) {
      $item = $item->label();
    });
    unset($roles['anonymous']);
    unset($roles['authenticated']);
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Assign roles'),
      '#description' => $this->t('Assign roles to users imported from Bitrix24'),
      '#options' => $roles,
      '#default_value' => $config->get('roles') ? $config->get('roles') : [],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('b24_user.import');
    $config
      ->set('roles', $form_state->getValue('roles'))
      ->save();
    $remote_contacts = $this->b24RestManager->getList('contact', [
      'select' => [
        "ID",
        "NAME",
        "EMAIL",
      ],
    ]);

    $this->batchBuilder
      ->setTitle($this->t('Processing'))
      ->setInitMessage($this->t('Initializing users import.'))
      ->setProgressMessage($this->t('Completed @current of @total.'))
      ->setErrorMessage($this->t('An error has occurred.'))
      ->setFile($this->extensionListModule->getPath('b24_user') . '/src/Form/UserImportBatchForm.php');

    $this->existingUsers = $this->userManager->getExistingUsers();

    $this->batchBuilder->addOperation([
      $this,
      'processItems',
    ], [$remote_contacts]);

    $this->batchBuilder->setFinishCallback([$this, 'finished']);

    $config = $this->configFactory->getEditable('b24_user.settings');
    $this->liveExportStatus = $config->get('enabled');
    $config->set('enabled', 0)->save();

    batch_set($this->batchBuilder->toArray());

  }

  /**
   * Processor for batch operations.
   */
  public function processItems($items, array &$context) {
    // Elements per operation.
    $limit = 50;
    $roles = array_filter($this->configFactory->get('b24_user.import')
      ->get('roles'));

    // Set default progress values.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($items);
      $context['sandbox']['count_new'] = 0;
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
          $item['roles'] = array_values($roles);
          if ($this->userManager->importUser($item)) {
            $context['sandbox']['count_new']++;
          }

          $counter++;
          $context['sandbox']['progress']++;

          $context['message'] = $this->t('Now processing user :progress of :count', [
            ':progress' => $context['sandbox']['progress'],
            ':count' => $context['sandbox']['max'],
          ]);
          $context['results']['processed'] = $context['sandbox']['progress'];
          $context['results']['count_new'] = $context['sandbox']['count_new'];
        }
      }
    }

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finished callback for batch.
   */
  public function finished($success, $results, $operations) {
    $message = $this->t('Number of remote users processed by batch: @count_total, number of imported users: @count_new', [
      '@count_total' => $results['processed'],
      '@count_new' => $results['count_new'],
    ]);

    $this->messenger()
      ->addStatus($message);

    $this->configFactory->getEditable('b24_user.settings')
      ->set('enabled', $this->liveExportStatus)
      ->save();
  }

}
