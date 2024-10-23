<?php

namespace Drupal\vlsuite_icon_font\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * VLSuite icon autocomplete controller.
 */
class VLSuiteIconFontAutocomplete extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new VLSuiteIconFontAutocomplete object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Autocomplete handler.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];
    $typed_icon = $request->query->get('q');
    $list_raw = $this->configFactory->get('vlsuite_icon_font.settings')->get('list');
    $list = array_map('trim', explode("\n", $list_raw));
    foreach ($list as $icon) {
      if (strpos($icon, $typed_icon) === 0) {
        $results[$icon] = [
          'value' => $icon,
          'label' => $this->getRenderableLabel($icon),
          'weight' => 1,
        ];
      }
      elseif (strpos($icon, $typed_icon)) {
        $results[$icon] = [
          'value' => $icon,
          'label' => $this->getRenderableLabel($icon),
          'weight' => 2,
        ];
      }
    }
    usort($results, function ($a, $b) {
      if ($a['weight'] === $b['weight']) {
        return 0;
      }
      return $a['weight'] < $b['weight'] ? -1 : 1;
    });
    return new JsonResponse(array_slice($results, 0, 10));
  }

  /**
   * Get renderable icon label.
   *
   * @param string $icon
   *   Icon.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   Label for autocomplete option.
   */
  protected function getRenderableLabel($icon) {
    $main_classes = $this->configFactory->get('vlsuite_icon_font.settings')->get('main_classes');
    $replacement = $this->configFactory->get('vlsuite_icon_font.settings')->get('replacement');
    $icon_label = '';
    if ($replacement === 'text') {
      $icon_label = '<span class="' . $main_classes . '">' . $icon . '</span> (' . $icon . ')';
    }
    elseif ($replacement === 'class') {
      $icon_label = '<span class="' . $main_classes . ' ' . $icon . '"></span> (' . $icon . ')';
    }
    return Markup::create($icon_label);
  }

}
