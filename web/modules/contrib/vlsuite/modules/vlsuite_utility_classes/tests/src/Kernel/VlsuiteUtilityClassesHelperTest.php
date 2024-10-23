<?php

namespace Drupal\Tests\vlsuite_utility_classes\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests.
 *
 * @group vlsuite_utility_classes
 */
class VlsuiteUtilityClassesHelperTest extends KernelTestBase {

  /**
   * Utility classes helper.
   *
   * @var \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper
   */
  protected $vlsuiteUtilityClassesHelper;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'vlsuite',
    'vlsuite_utility_classes',
    'vlsuite_icon_font',
    'user',
    'vlsuite_utility_classes_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('vlsuite_utility_classes');

    // Define utility classes for block content.
    $this->config('vlsuite_utility_classes.settings')->set('utilities', [
      'existing-utility-not-applicable' => [
        'icon' => '',
        'advanced' => FALSE,
        'apply_to' => [
          'block_content:basic:body:item' => 'block_content:basic:body:item',
        ],
        'visual_name' => 'Random',
        'class_prefix' => 'random-',
        'values' => [
          'random' => [
            'visual_name' => 'random',
            'class_suffix' => 'random',
          ],
        ],
      ],
      'text-align' => [
        'icon' => '',
        'advanced' => FALSE,
        'apply_to' => [
          'block_content:basic' => 'block_content:basic',
          'block_content:basic:body' => 'block_content:basic:body',
          'block_content:basic:body:item' => 'block_content:basic:body:item',
        ],
        'visual_name' => 'Text color',
        'class_prefix' => 'hello-world gazpacho text-',
        'values' => [
          'left' => [
            'visual_name' => 'Left',
            'class_suffix' => 'start',
          ],
          'right' => [
            'visual_name' => 'Right',
            'class_suffix' => 'end chatgpt bitcoin',
          ],
        ],
      ],
      'background-color' => [
        'icon' => '',
        'advanced' => FALSE,
        'apply_to' => [
          'block_content:basic' => 'block_content:basic',
          'block_content:basic:body' => 'block_content:basic:body',
          'block_content:basic:body:item' => 'block_content:basic:body:item',
        ],
        'visual_name' => 'Background color',
        'class_prefix' => 'bg-',
        'values' => [
          'primary' => [
            'visual_name' => 'Primary',
            'class_suffix' => 'primary',
          ],
          'secondary' => [
            'visual_name' => 'Secondary',
            'class_suffix' => 'secondary',
          ],
        ],
      ],
      'shadow' => [
        'icon' => '',
        'advanced' => FALSE,
        'apply_to' => [
          'block_content:basic:body:item' => 'block_content:basic:body:item',
        ],
        "visual_name" => "Shadow",
        "class_prefix" => "shadow",
        "values" => [
          "shadow" => [
            "visual_name" => "Shadow",
            "class_suffix" => "defined-by-prefix",
          ],
          "small" => [
            "visual_name" => "Small",
            "class_suffix" => "shadow-sm",
          ],
          "large" => [
            "visual_name" => "Large",
            "class_suffix" => "shadow-lg",
          ],
        ],
      ],
      'position-advanced' => [
        'icon' => '',
        'advanced' => TRUE,
        'apply_to' => [
          'block_content:basic:body:item' => 'block_content:basic:body:item',
        ],
        "visual_name" => "Position",
        "class_prefix" => "position-",
        "values" => [
          "top" => [
            "visual_name" => "Top",
            "class_suffix" => "top",
          ],
          "bottom" => [
            "visual_name" => "Bottom",
            "class_suffix" => "bottom",
          ],
        ],
      ],
    ])->save();
  }

  /**
   * Obtain utility classes' helper.
   *
   * @return \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper
   *   VLSuite Utility Helper.
   */
  protected function getVlsuiteUtilityClassesHelper() {
    if (empty($this->vlsuiteUtilityClassesHelper)) {
      $this->vlsuiteUtilityClassesHelper = $this->container->get('vlsuite_utility_classes.helper');
    }

    return $this->vlsuiteUtilityClassesHelper;
  }

  /**
   * Tests hook vlsuite options alter.
   */
  public function testUtilityClassesUtilityApplyToOptionsAlter() {
    $utility_classes_helper = $this->getVlsuiteUtilityClassesHelper();
    $this->assertEquals($utility_classes_helper->getUtilityApplyToOptions(), ['test' => 'test']);
  }

  /**
   * Tests utilities valid list.
   */
  public function testGetUtilitiesApplyToList() {
    $utility_classes_helper = $this->getVlsuiteUtilityClassesHelper();

    $utilities_to_apply = $utility_classes_helper->getUtilitiesApplyToList('block_content:basic', FALSE);
    $expected = [
      'text-align' => [
        'icon' => '',
        'advanced' => FALSE,
        'visual_name' => 'Text color',
        'class_prefix' => 'hello-world gazpacho text-',
        'values' => [
          'left' => [
            'visual_name' => 'Left',
            'class_suffix' => 'start',
          ],
          'right' => [
            'visual_name' => 'Right',
            'class_suffix' => 'end chatgpt bitcoin',
          ],
        ],
      ],
      'background-color' => [
        'icon' => '',
        'advanced' => FALSE,
        'visual_name' => 'Background color',
        'class_prefix' => 'bg-',
        'values' => [
          'primary' => [
            'visual_name' => 'Primary',
            'class_suffix' => 'primary',
          ],
          'secondary' => [
            'visual_name' => 'Secondary',
            'class_suffix' => 'secondary',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $utilities_to_apply);

    $utilities_to_apply = $utility_classes_helper->getUtilitiesApplyToList('block_content:basic:body:item', FALSE);
    $expected += [
      'shadow' => [
        'icon' => '',
        'advanced' => FALSE,
        "visual_name" => "Shadow",
        "class_prefix" => "shadow",
        "values" => [
          "shadow" => [
            "visual_name" => "Shadow",
            "class_suffix" => "defined-by-prefix",
          ],
          "small" => [
            "visual_name" => "Small",
            "class_suffix" => "shadow-sm",
          ],
          "large" => [
            "visual_name" => "Large",
            "class_suffix" => "shadow-lg",
          ],
        ],
      ],
      'existing-utility-not-applicable' => [
        'icon' => '',
        'advanced' => FALSE,
        'visual_name' => 'Random',
        'class_prefix' => 'random-',
        'values' => [
          'random' => [
            'visual_name' => 'random',
            'class_suffix' => 'random',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $utilities_to_apply);

    // Check advanced utility.
    $utilities_to_apply = $utility_classes_helper->getUtilitiesApplyToList('block_content:basic:body:item', TRUE);
    $expected += [
      'position-advanced' => [
        'icon' => '',
        'advanced' => TRUE,
        "visual_name" => "Position",
        "class_prefix" => "position-",
        "values" => [
          "top" => [
            "visual_name" => "Top",
            "class_suffix" => "top",
          ],
          "bottom" => [
            "visual_name" => "Bottom",
            "class_suffix" => "bottom",
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $utilities_to_apply);
  }

  /**
   * Tests the application of the utility classes to a build.
   */
  public function testBuildApplyUtilityClasses() {
    $utility_classes_helper = $this->getVlsuiteUtilityClassesHelper();

    $build = [
      'body' => [
      ['#attributes' => ['class' => ['test-item-1']]],
      ['#attributes' => ['class' => ['test-item-2']]],
      ],
    ];

    $apply_to_list = [
      'block_content:basic' => [
        'none-existing-utility' => 'primary',
        'existing-utility-not-applicable' => 'random',
        'text-align' => 'left',
        'background-color' => 'primary',
      ],
      'block_content:basic:body' => [
        'none-existing-utility' => 'primary',
        'text-align' => 'none-existing-utility-value',
        'background-color' => 'primary',
      ],
      'block_content:basic:body:item' => [
        'text-align' => 'right',
        'background-color' => 'primary',
        'shadow' => 'shadow',
        'position-advanced' => 'top',
      ],
    ];

    $utility_classes_helper->buildApplyUtilityClasses($apply_to_list, $build);

    $expected = [
      '#attributes' => [
        'class' => [
          'hello-world',
          'gazpacho',
          'text-start',
          'bg-primary',
        ],
      ],
      'body' => [
        '#attributes' => ['class' => ['bg-primary']],
        [
          '#attributes' => [
            'class' => [
              'test-item-1',
              'hello-world',
              'gazpacho',
              'text-end',
              'chatgpt',
              'bitcoin',
              'bg-primary',
              'shadow',
              'position-top',
            ],
          ],
          '#item_attributes' => [
            'class' => [
              'hello-world',
              'gazpacho',
              'text-end',
              'chatgpt',
              'bitcoin',
              'bg-primary',
              'shadow',
              'position-top',
            ],
          ],
        ],
        [
          '#attributes' => [
            'class' => [
              'test-item-2',
              'hello-world',
              'gazpacho',
              'text-end',
              'chatgpt',
              'bitcoin',
              'bg-primary',
              'shadow',
              'position-top',
            ],
          ],
          '#item_attributes' => [
            'class' => [
              'hello-world',
              'gazpacho',
              'text-end',
              'chatgpt',
              'bitcoin',
              'bg-primary',
              'shadow',
              'position-top',
            ],
          ],
        ],
      ],
    ];

    $this->assertEquals($expected, $build);

  }

}
