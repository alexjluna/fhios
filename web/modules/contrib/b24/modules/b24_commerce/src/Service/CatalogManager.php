<?php

namespace Drupal\b24_commerce\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\b24\Service\RestManager;

/**
 * Provides synchronizing of site products catalog and in Bitrix24 database.
 */
class CatalogManager {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\b24\Service\RestManager definition.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected $restManager;
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
   * Defines reference between drupal and Bitrix24 entities.
   *
   * @var array
   */
  protected $entityMapping;

  /**
   * Constructs a new CatalogManager object.
   */
  public function __construct(RestManager $b24_rest_manager, ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->restManager = $b24_rest_manager;
    $this->configFactory = $config_factory;
    $this->existing = [
      'commerce_product' => $this->getExistingProducts(),
      'taxonomy_term' => $this->getSections(),
    ];
    $this->settings = $this->configFactory->get('b24_commerce.settings')->get();
    $this->entityMapping = [
      'taxonomy_term' => 'productsection',
      'commerce_product_variation' => 'product',
    ];
  }

  /**
   * Returns reference between drupal and Bitrix24 entities.
   *
   * @return array|string[]
   *   An array of references.
   */
  public function getEntityMapping() {
    return $this->entityMapping;
  }

  /**
   * Returns an array of settings.
   *
   * @return array
   *   An array of settings.
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Returns a list of Bitrix24 catalog sections.
   *
   * @return array
   *   An array of sections.
   */
  public function getSections() {
    // @todo Filter out empty rows in query (does not work now)...
    $b24_sections = $this->restManager->getList('productsection', [
      'filter' => ['!XML_ID' => 'NULL'],
      'select' => ['ID', 'XML_ID'],
      'order' => ['XML_ID' => 'ASC'],
    ]);
    $b24_sections = array_filter($b24_sections, function ($item) {
      return $item['XML_ID'];
    });
    $sections = array_column($b24_sections, 'ID', 'XML_ID');
    return $sections;
  }

  /**
   * Returns list of products present in Bitrix24.
   *
   * @return array
   *   An array of existing products.
   */
  public function getExistingProducts() {
    $products = $this->restManager->getList('product', ['select' => ['XML_ID']]);
    return $products ? array_column($products, 'XML_ID', 'ID') : [];
  }

  /**
   * Returns a list of taxonomy vocabulary ids to be exported.
   *
   * @return array
   *   An array of taxonomy vocabulary ids.
   */
  public function getExportableVocabularies() {
    $settings = $this->getSettings();
    $section_fields = $settings['section_fields'] ?? [];
    $vocabularies = [];
    foreach ($section_fields as $section_field) {
      /** @var Drupal\field\Entity\FieldConfig $field */
      $field = $this->entityTypeManager->getStorage('field_config')->load($section_field);
      $vocabularies = array_merge($vocabularies, $field->getSettings()['handler_settings']['target_bundles']);
    }

    return $vocabularies;
  }

  /**
   * Process a single catalog item.
   *
   * @param int $id
   *   The item entity id.
   * @param string $entity_type_id
   *   The item entity type id.
   * @param bool $update
   *   Flag indicating if a correspondent Bitrix24 entity should be updated.
   */
  public function processItem($id, $entity_type_id, $update = FALSE) {
    $storage = $this->entityTypeManager->getStorage("$entity_type_id");
    if (!$storage) {
      return;
    }
    $entity = $storage->load($id);
    if ($entity->hasField('variations')) {
      $variation_references = $entity->get('variations')->getValue();
      foreach ($variation_references as $variation_reference) {
        $this->processItem($variation_reference['target_id'], 'commerce_product_variation');
      }
    }
    else {
      if (array_key_exists($entity->id(), $this->existing['commerce_product']) && !$update) {
        return;
      }
      $fields = [
        'NAME' => $entity->label(),
        'XML_ID' => $entity->id(),
      ];
      if ($entity->getEntityTypeId() == 'commerce_product_variation') {
        $fields['PRICE'] = $entity->getPrice()->getNumber();
        $sku = $entity->get('sku')->getString();
        // @todo Do we need a token-based field here?
        $fields['NAME'] .= " ($sku)";
        $fields['CURRENCY_ID'] = $entity->getPrice()->getCurrencyCode();
        $section_fields = $this->settings['section_fields'] ?? [];
        $product = $this->entityTypeManager->getStorage('commerce_product')->load($entity->get('product_id')->getString());
        if (!empty($section_fields[$product->bundle()])) {
          $field_name = $this->entityTypeManager->getStorage('field_config')->load($section_fields[$product->bundle()])->getName();
          if ($product->hasField($field_name)) {
            $product_section = $product->get($field_name)->getString();
            if ($product_section) {
              $b24_section = $this->getSections()[$product_section] ?? NULL;
              if ($b24_section) {
                $fields['SECTION_ID'] = $b24_section;
              }
            }
          }
        }
      }

      $b24_entity_name = $this->entityMapping[$entity_type_id];
      $existing_ids = $this->existing[$entity_type_id] ?? [];
      $id = $existing_ids[$entity->id()] ?? FALSE;
      if ($update && $id) {
        $this->restManager->updateEntity($b24_entity_name, $id, $fields);
      }
      else {
        $this->restManager->addEntity($b24_entity_name, $fields);
      }
    }
  }

}
