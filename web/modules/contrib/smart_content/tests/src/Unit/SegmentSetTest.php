<?php

namespace Drupal\Tests\smart_content\Unit;

use Drupal\smart_content\SegmentSet;
use Drupal\smart_content\Segment;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\smart_content\SegmentSet
 * @group smart_content
 */
class SegmentSetTest extends UnitTestCase {

  /**
   * The SegmentSet object to test.
   *
   * @var \Drupal\smart_content\SegmentSet
   */
  protected $segmentSetInstance;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->segmentSetInstance = new SegmentSet(
      [
        new Segment('existing-uuid', [], 0, 'Segment 1', FALSE),
        new Segment('first-uuid', [], 1, 'Segment 2', TRUE),
        new Segment('second-uuid', [], 3, 'Segment 3', FALSE),
        new Segment('third-uuid', [], 2, 'Segment 4', FALSE),
      ]
    );
  }

  /**
   * @covers ::__construct
   * @covers ::setSegment
   * @covers ::getSegments
   */
  public function testGetSegments() {
    $expected = [
      'existing-uuid' => (new Segment('existing-uuid', [], 0, 'Segment 1', FALSE)),
      'first-uuid' => (new Segment('first-uuid', [], 1, 'Segment 2', TRUE)),
      'second-uuid' => (new Segment('second-uuid', [], 3, 'Segment 3', FALSE)),
      'third-uuid' => (new Segment('third-uuid', [], 2, 'Segment 4', FALSE)),
    ];

    $this->assertSegments($expected, $this->segmentSetInstance);
  }

  /**
   * @covers ::getSegment
   */
  public function testGetSegmentInvalidUuid() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid UUID "invalid-uuid"');
    $this->segmentSetInstance->getSegment('invalid-uuid');
  }

  /**
   * @covers ::getSegment
   */
  public function testGetSegment() {
    $expected = new Segment('existing-uuid', [], 0, 'Segment 1', FALSE);
    $this->assertEquals($expected, $this->segmentSetInstance->getSegment('existing-uuid'));
  }

  /**
   * @covers ::removeSegment
   */
  public function testRemoveSegment() {
    $expected = [
      'existing-uuid' => (new Segment('existing-uuid', [], 0, 'Segment 1', FALSE)),
      'first-uuid' => (new Segment('first-uuid', [], 1, 'Segment 2', TRUE)),
      'third-uuid' => (new Segment('third-uuid', [], 2, 'Segment 4', FALSE)),
    ];

    $this->segmentSetInstance->removeSegment('second-uuid');
    $this->assertSegments($expected, $this->segmentSetInstance);
  }

  /**
   * @covers ::setDefaultSegment
   * @covers ::getSegments
   */
  public function testSetDefaultSegment() {
    $expected = [
      'existing-uuid' => (new Segment('existing-uuid', [], 0, 'Segment 1', FALSE)),
      'first-uuid' => (new Segment('first-uuid', [], 1, 'Segment 2', FALSE)),
      'second-uuid' => (new Segment('second-uuid', [], 3, 'Segment 3', TRUE)),
      'third-uuid' => (new Segment('third-uuid', [], 2, 'Segment 4', FALSE)),
    ];
    $this->segmentSetInstance->setDefaultSegment('second-uuid');
    $this->assertSegments($expected, $this->segmentSetInstance);
  }

  /**
   * @covers ::sortSegments
   * @covers ::getSegments
   */
  public function testSortSegments() {
    $expected = [
      'existing-uuid' => (new Segment('existing-uuid', [], 0, 'Segment 1', FALSE)),
      'first-uuid' => (new Segment('first-uuid', [], 1, 'Segment 2', TRUE)),
      'third-uuid' => (new Segment('third-uuid', [], 2, 'Segment 4', FALSE)),
      'second-uuid' => (new Segment('second-uuid', [], 3, 'Segment 3', FALSE)),
    ];
    $this->segmentSetInstance->sortSegments();
    $this->assertSegments($expected, $this->segmentSetInstance);
  }

  /**
   * Asserts that the SegmentSet has the expected segments.
   *
   * @param \Drupal\smart_content\Segment[] $expected
   *   The expected segments.
   * @param \Drupal\smart_content\SegmentSet $segment_set
   *   The SegmentSet storage to check.
   */
  protected function assertSegments(array $expected, SegmentSet $segment_set) {
    $result = $segment_set->getSegments();
    $this->assertEquals($expected, $result);
    $this->assertSame(array_keys($expected), array_keys($result));
  }

}
