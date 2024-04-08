<?php

namespace Drupal\Tests\smart_content\Unit;

use Drupal\smart_content\Segment;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\smart_content\Segment
 * @group smart_content
 */
class SegmentTest extends UnitTestCase {

  /**
   * The section object to test.
   *
   * @var \Drupal\smart_content\Segment
   */
  protected $segment;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $language_condition_base = [
      'id' => 'browser:language',
      'weight' => 0,
      'negate' => FALSE,
      'type' => "type:textfield",
      'condition_type_settings' => [],
    ];
    $group_condition_base = [
      'id' => 'group',
      'weight' => 0,
      'negate' => FALSE,
      'type' => "plugin_group",
      'conditions' => [],
    ];

    $language_condition_0 = [
      'condition_type_settings' => [
        'op' => 'equals',
        'value' => 'en',
      ],
    ] + $language_condition_base;

    $language_condition_1 = [
      'condition_type_settings' => [
        'op' => 'starts_with',
        'value' => 'es',
      ],
    ] + $language_condition_base;

    $group_condition_0_0 = [
      'op' => 'AND',
      'conditions' => [
        'browser:language' => $language_condition_0,
        'browser:language_1' => $language_condition_1,
      ],
    ] + $group_condition_base;

    $group_condition_0 = [
      'op' => 'OR',
      'conditions' => [
        'browser:language' => $language_condition_0,
        'group' => $group_condition_0_0,
      ],
    ] + $group_condition_base;

    $conditions = ['group' => $group_condition_0];

    $this->segment = new Segment('uuid', [], 0, FALSE);
  }

  /**
   * @covers ::__construct
   * @covers ::getUuid
   */
  public function testGetUuid() {
    $this->assertEquals('uuid', $this->segment->getUuid());
  }

}
