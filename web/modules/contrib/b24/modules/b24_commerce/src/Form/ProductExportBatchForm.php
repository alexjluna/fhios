<?php

namespace Drupal\b24_commerce\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\b24\Service\RestManager;
use Drupal\b24_commerce\Service\CatalogManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for batch export of products from site to Bitrix24.
 */
class ProductExportBatchForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Batch Builder.
   *
   * @var \Drupal\Core\Batch\BatchBuilder
   */
  protected $batchBuilder;

  /**
   * The Commerce Store storage.
   *
   * @var \Drupal\commerce_store\StoreStorage
   */
  protected $storeStorage;

  /**
   * The entity type bundle info interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Defines reference between drupal and Bitrix24 entities.
   *
   * @var array
   */
  protected $entityMapping;

  /**
   * The Bitrix24 REST manager service.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected $restManager;

  /**
   * The Bitrix24 catalog manager service.
   *
   * @var \Drupal\b24_commerce\Service\CatalogManager
   */
  protected $catalogManager;

  /**
   * An array of submitted values.
   *
   * @var array
   */
  protected $values;

  /**
   * An array of products present in Bitrix24.
   *
   * @var array
   */
  protected $existingProducts;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a new ProductExportBatchForm object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    MessengerInterface $messenger,
    RestManager $rest_manager,
    CatalogManager $catalog_manager,
    ModuleExtensionList $module_extension_list,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->storeStorage = $entity_type_manager->getStorage('commerce_store');
    $this->messenger = $messenger;
    $this->batchBuilder = new BatchBuilder();
    $this->bundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->restManager = $rest_manager;
    $this->catalogManager = $catalog_manager;
    $this->entityMapping = $this->catalogManager->getEntityMapping();
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('messenger'),
      $container->get('b24.rest_manager'),
      $container->get('b24_commerce.catalog_manager'),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'product_export_batch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo Create another submit function to generate export file.
    $config = $this->configFactory()->get('b24_commerce.settings');

    $options = [];
    $stores = $this->storeStorage->loadMultiple();
    foreach ($stores as $store) {
      $options[$store->id()] = $store->label();
    }

    $form['stores'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Export products from stores'),
      '#options' => $options,
      '#description' => $this->t('Choose stores to export products from. The chosen stores theirselves will be exported to B24 catalog entities'),
      '#default_value' => $config->get('exportable_stores') ? $config->get('exportable_stores') : [],
    ];

    $product_types = $this->bundleInfo->getBundleInfo('commerce_product');
    $form['section_fields'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Section defining field'),
      '#description' => $this->t('Choose field designated a product catalog section.'),
      '#tree' => TRUE,
    ];

    foreach ($product_types as $product_type_id => $product_type) {
      $bundle_fields = $this->entityFieldManager->getFieldDefinitions('commerce_product', $product_type_id);
      $fields = [];
      foreach ($bundle_fields as $bundle_field) {
        if ($bundle_field->getTargetBundle() && $bundle_field->getType() == 'entity_reference' && $bundle_field->getSetting('target_type') == 'taxonomy_term') {
          $fields[$bundle_field->id()] = $bundle_field->getLabel();
        }
      }
      $form['section_fields'][$product_type_id] = [
        '#type' => 'select',
        '#options' => $fields,
        '#title' => $this->t('Section field for «@type» products', ['@type' => $product_type['label']]),
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $config->get("section_fields.{$product_type_id}"),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#name' => 'submit',
    ];

    $form['actions']['export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
      '#name' => 'export',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($form_state->getTriggeringElement()['#name'] == 'export') {
      $stores = array_filter($values['stores']);
      if (empty($stores)) {
        $form_state->setErrorByName('stores', $this->t('Choose store to export from.'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->values = $form_state->getValues();
    $config = $this->configFactory()->getEditable('b24_commerce.settings');
    $config->set('exportable_stores', $form_state->getValue('stores'));
    $config->set('section_fields', $form_state->getValue('section_fields'));
    $config->save();

    if ($form_state->getTriggeringElement()['#name'] == 'export') {
      $this->batchBuilder
        ->setTitle($this->t('Processing'))
        ->setInitMessage($this->t('Initializing.'))
        ->setProgressMessage($this->t('Completed @current of @total.'))
        ->setErrorMessage($this->t('An error has occurred.'))
        ->setFile($this->moduleExtensionList->getPath('b24_commerce') . '/src/Form/ProductExportBatchForm.php');

      $stores = array_filter($form_state->getValue('stores'));
      $stores = array_keys($stores);
      // Export stores. todo: add if it will be realized in B24.
      /*$this->batchBuilder->addOperation([$this, 'processItems'], [$stores, 'commerce_store']);*/

      if ($section_fields = array_filter($form_state->getValue('section_fields'))) {
        $field_storage = $this->entityTypeManager->getStorage('field_config');
        foreach ($section_fields as $section_field) {
          $settings = $field_storage->load($section_field)->getSettings();
          $target_bundles = isset($settings['handler_settings']['target_bundles']) ? array_keys($settings['handler_settings']['target_bundles']) : [];
          foreach ($target_bundles as $target_bundle) {
            $sections = $this->getEntities('taxonomy_term', $target_bundle);
            $this->batchBuilder->addOperation([$this, 'processItems'],
              [$sections, 'taxonomy_term']);
          }
        }
      }

      $product_types = $this->bundleInfo->getBundleInfo('commerce_product');
      $this->existingProducts = $this->catalogManager->getExistingProducts();
      foreach ($product_types as $product_type_id => $product_type) {
        $products = $this->getEntities('commerce_product', $product_type_id, ['stores' => $stores]);
        $this->batchBuilder->addOperation([$this, 'processItems'],
          [$products, 'commerce_product']);
      }

      $this->batchBuilder->setFinishCallback([$this, 'finished']);

      batch_set($this->batchBuilder->toArray());
    }
  }

  /**
   * Processor for batch operations.
   */
  public function processItems($items, $entity_id, array &$context) {
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
          $this->catalogManager->processItem($item, $entity_id);

          $counter++;
          $context['sandbox']['progress']++;

          $context['message'] = $this->t('Now processing @entity_name :progress of :count', [
            '@entity_name' => $entity_id,
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
    $message = $this->t('Number of entities affected by batch: @count', [
      '@count' => $results['processed'],
    ]);

    $this->messenger()
      ->addStatus($message);
  }

  /**
   * Loads entities for given conditions.
   *
   * @param string $entity_id
   *   The entity type id.
   * @param string $bundle_id
   *   The entity bundle id.
   * @param array $additional_filters
   *   The array of additional filters to append.
   *
   * @return array|int
   *   An array of found entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntities(string $entity_id, string $bundle_id, array $additional_filters = []) {
    $storage = $this->entityTypeManager->getStorage($entity_id);
    $keys = $this->entityTypeManager->getDefinition($entity_id)->getKeys();
    if ($storage) {
      $b24_entity_id = $this->entityMapping[$entity_id] ?? NULL;
      $existing_ids = $b24_entity_id ? array_column($this->restManager->getList($b24_entity_id, ['select' => ['XML_ID' => $entity_id]]), 'XML_ID') : [];

      $query = $storage->getQuery()
        ->condition($keys['bundle'], $bundle_id)
        ->condition('status', 1);
      if ($existing_ids) {
        $query->condition($keys['id'], $existing_ids, 'NOT IN');
      }

      foreach ($additional_filters as $condition_name => $condition_value) {
        // It's so ugly that I'll be ashamed if somebody saw it...
        $op = is_array($condition_value) ? 'IN' : '=';
        $query->condition($condition_name, $condition_value, $op);
      }

      $query->accessCheck();

      $items = $query->execute();

      return $items;
    }
    else {
      throw new \Exception("Invalid entity id given for calling a storage.");
    }
  }

}
