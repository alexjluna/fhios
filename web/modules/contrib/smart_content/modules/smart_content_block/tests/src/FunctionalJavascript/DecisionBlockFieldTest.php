<?php

namespace Drupal\Tests\smart_content_block\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\smart_content\Entity\SegmentSetConfig;

/**
 * Contains test cases with the "block_field" module integration point.
 *
 * @group smart_content_block
 */
class DecisionBlockFieldTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_field',
    'entity_test',
    'smart_content_block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * A user with permissions to administer content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);

    SegmentSetConfig::create([
      'id' => 'true_segment',
      'label' => 'True segment',
      'settings' => [
        'segments' => [
          '32ed1776-9a27-4c06-be8c-a272825dd6a0' => [
            'uuid' => '32ed1776-9a27-4c06-be8c-a272825dd6a0',
            'conditions' => [
              'group' => [
                'id' => 'group',
                'weight' => 0,
                'negate' => FALSE,
                'type' => 'plugin:group',
                'conditions' => [
                  'is_true' => [
                    'id' => 'is_true',
                    'weight' => 0,
                    'negate' => FALSE,
                    'type' => 'plugin:is_true',
                  ],
                ],
                'op' => 'AND',
              ],
            ],
            'weight' => 0,
            'label' => 'True',
            'default' => TRUE,
          ],
        ],
      ],
    ])->save();

    FieldStorageConfig::create([
      'type' => 'block_field',
      'entity_type' => 'entity_test',
      'field_name' => 'field_smart_block',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'field_smart_block',
      'label' => 'Smart block',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent('field_smart_block', [
        'type' => 'block_field_default',
        'settings' => [
          'plugin_id' => '',
          'settings' => [],
          'configuration_form' => 'full',
        ],
      ])->save();

    $display_repository->getViewDisplay('entity_test', 'entity_test')
      ->setComponent('field_smart_block', [
        'type' => 'block_field',
        'settings' => [],
      ])->save();
  }

  /**
   * Test case for the most simplistic use case imagineable.
   */
  public function testDecisionBlockCreation() {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/entity_test/add');

    $page->fillField('Smart block', 'smart_content_decision_block');

    $assert->waitForField('field_smart_block[0][settings][label]');

    $assert->fieldValueEquals('field_smart_block[0][settings][label]', 'Decision Block');
    $assert->checkboxNotChecked('field_smart_block[0][settings][label_display]');

    $assert->fieldValueEquals('field_smart_block[0][settings][decision][decision_select][list]', '');
    $assert->optionExists('field_smart_block[0][settings][decision][decision_select][list]', 'global_segment_set:true_segment');
    $assert->optionExists('field_smart_block[0][settings][decision][decision_select][list]', 'inline');

    // Select a very simple global segment.
    $page->fillField('field_smart_block[0][settings][decision][decision_select][list]', 'global_segment_set:true_segment');
    $page->pressButton('Select Segment Set');

    $segment_wrapper = $assert->waitForElementVisible('css', '[id^="segment--"]');

    // Parse the UUID out of the wrapping div element.
    $uuids_string = str_replace('segment--', '', $segment_wrapper->getAttribute('id'));

    $page->pressButton('edit-segment--' . $uuids_string);

    $block_select = $assert->waitForField('Block Type');
    $block_select->setValue('system_powered_by_block');

    $page->pressButton('Add Block');

    $assert->waitForField('field_smart_block[0][settings][decision][decision_settings][segments][32ed1776-9a27-4c06-be8c-a272825dd6a0][settings][reaction_settings][plugin_form][settings][blocks][system_powered_by_block][plugin_form][label]');
    $this->submitForm(NULL, 'Save');

    // Ensure that the front-end is rendering the proper persisted values.
    $assert->fieldValueEquals('Smart block', 'smart_content_decision_block');
    $assert->fieldValueEquals('Title', 'Decision Block');
    $assert->checkboxNotChecked('Display title');

    self::assertStringContainsString('Powered by Drupal', $page->findById('edit-field-smart-block-wrapper')->getText());

    // Check the front-end to ensure that the block renders properly.
    $this->drupalGet('/entity_test/1');
    $assert->waitForElementRemoved('css', '.smart-content-decision-block');
    $assert->pageTextContains('Powered by Drupal');
  }

}
