<?php

namespace Drupal\vlsuite_icon_font\TwigExtension;

use Drupal\vlsuite_icon_font\VLSuiteIconFontHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension "VLSuiteIconFontTwigExtension" object.
 */
class VLSuiteIconFontTwigExtension extends AbstractExtension {

  /**
   * The icon font helper.
   *
   * @var \Drupal\vlsuite_icon_font\VLSuiteIconFontHelper
   */
  protected $iconFontHelper;

  /**
   * Constructs a "VLSuiteIconFontTwigExtension" object.
   *
   * @param \Drupal\vlsuite_icon_font\VLSuiteIconFontHelper $icon_font_helper
   *   The icon font helper.
   */
  public function __construct(VLSuiteIconFontHelper $icon_font_helper) {
    $this->iconFontHelper = $icon_font_helper;
  }

  /**
   * Get functions.
   *
   * @return array
   *   Twig extension functions.
   */
  public function getFunctions() {
    return [
      new TwigFunction('vlsuite_icon_font_icon', [$this, 'buildIconFontIcon']),
    ];
  }

  /**
   * Build renderable icon.
   *
   * @param string $icon
   *   Icon to build.
   *
   * @return array
   *   Renderable icon font icon.
   */
  public function buildIconFontIcon(string $icon) {
    return $this->iconFontHelper->buildIconFontIcon($icon);
  }

}
