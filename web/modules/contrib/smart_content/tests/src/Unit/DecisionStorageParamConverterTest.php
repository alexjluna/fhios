<?php

namespace Drupal\Tests\smart_content\Unit;

use Drupal\smart_content\Decision\Storage\DecisionStorageManager;
use Drupal\smart_content\Routing\DecisionStorageParamConverter;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Contains unit test cases for the decision storage parameter converter.
 *
 * @coversDefaultClass \Drupal\smart_content\Routing\DecisionStorageParamConverter
 *
 * @group smart_content
 */
class DecisionStorageParamConverterTest extends UnitTestCase {

  /**
   * The subject under test.
   *
   * @var \Drupal\smart_content\Routing\DecisionStorageParamConverter
   */
  protected $instance;

  /**
   * The mocked decision storage manager service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\smart_content\Decision\Storage\DecisionStorageManager
   */
  protected $decisionStorageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->decisionStorageManager = $this->createMock(DecisionStorageManager::class);

    $this->decisionStorageManager
      ->method('hasDefinition')
      ->willReturnMap([
        ['do not convert', FALSE],
        ['convert', TRUE],
      ]);

    $this->decisionStorageManager
      ->method('createInstance')
      ->willReturn('converted');

    $this->instance = new DecisionStorageParamConverter($this->decisionStorageManager);
  }

  /**
   * Data provider for the applies method test.
   *
   * @dataProvider
   */
  public function appliesProvider() {
    $route = $this->createMock(Route::class);

    return [
      [['type' => 'decision_storage'], '', $route, TRUE],
      [['type' => 'node_storage'], '', $route, FALSE],
      [['type' => ''], '', $route, FALSE],
      [['uh..' => 'what'], '', $route, FALSE],
    ];
  }

  /**
   * Test cases for the applies method.
   *
   * @param array $definition
   *   The definition to test.
   * @param string $name
   *   The name to test.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to test.
   * @param bool $expected
   *   The expected result.
   *
   * @dataProvider appliesProvider
   */
  public function testApplies(array $definition, $name, Route $route, $expected) {
    $actual = $this->instance->applies($definition, $name, $route);
    self::assertSame($expected, $actual);
  }

  /**
   * Test cases for the convert method.
   */
  public function testConvert() {

    // Ensure that no conversion happens if a definition is not found.
    $actual = $this->instance->convert('do not convert', '', '', []);
    self::assertSame('do not convert', $actual);

    // Ensure that conversion happens if a definition is found.
    $actual = $this->instance->convert('convert', '', '', []);
    self::assertSame('converted', $actual);
  }

}
