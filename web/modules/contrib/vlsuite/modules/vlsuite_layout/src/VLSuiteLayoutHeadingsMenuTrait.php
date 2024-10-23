<?php

namespace Drupal\vlsuite_layout;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;

/**
 * VLSuite layout headings menu trait.
 */
trait VLSuiteLayoutHeadingsMenuTrait {

  /**
   * Headings menu build alter.
   *
   * @param array $regions
   *   Regions.
   * @param array $build
   *   Build.
   * @param string $menu_block_region_id
   *   Menu block region id (where is located).
   * @param string $headings_block_region_id
   *   Headings block region id (where is located).
   */
  public function headingsMenuBuildAlter(array $regions, array &$build, $menu_block_region_id, $headings_block_region_id) {
    if ($this->headingsMenuBuildRequirements($regions, $menu_block_region_id, $headings_block_region_id)) {
      $this->headingsMenuBuildAlterExecute($build, $menu_block_region_id, $headings_block_region_id);
    }
  }

  /**
   * Headings menu build requirements.
   *
   * First check provided region names.
   * Then check menu block in region.
   * Then check text block in region.
   *
   * @param array $regions
   *   Regions.
   * @param string $menu_block_region_id
   *   Menu block region id (where is located).
   * @param string $headings_block_region_id
   *   Headings block region id (where is located).
   */
  protected function headingsMenuBuildRequirements(array $regions, $menu_block_region_id, $headings_block_region_id) {
    if (empty($regions[$menu_block_region_id]) || empty($regions[$headings_block_region_id])) {
      return FALSE;
    }
    $menu_block_region_requirement = FALSE;
    foreach ($regions[$menu_block_region_id] as $block) {
      if (($block['#plugin_id'] ?? NULL) == 'vlsuite_block_headings_menu') {
        $menu_block_region_requirement = TRUE;
        break;
      }
    }
    if (!$menu_block_region_requirement) {
      return FALSE;
    }
    $headings_block_region_requirement = FALSE;
    // @note For now compatible with field / content block of type processed
    // text & views with grouped results.
    foreach ($regions[$headings_block_region_id] as $uuid => $block) {
      if (empty($block['content'])) {
        continue;
      }
      if (($regions[$headings_block_region_id][$uuid]['content']['#type'] ?? NULL) == 'view') {
        $headings_block_region_requirement = TRUE;
        break;
      }
      // @see https://git.drupalcode.org/project/drupal/-/commit/7598b15a28f370ae194153c183b158b13670703a
      foreach (Element::children($block['content']) as $delta) {
        if (($regions[$headings_block_region_id][$uuid]['content'][$delta]['#type'] ?? NULL) == 'processed_text') {
          $headings_block_region_requirement = TRUE;
          break;
        }
        foreach (Element::children($block['content'][$delta]) as $inner_delta) {
          if (($regions[$headings_block_region_id][$uuid]['content'][$delta][$inner_delta]['#type'] ?? NULL) == 'processed_text') {
            $headings_block_region_requirement = TRUE;
            break;
          }
        }
      }
    }
    return $menu_block_region_requirement && $headings_block_region_requirement;
  }

  /**
   * Headings menu build alter execute.
   *
   * @param array $build
   *   Build.
   * @param string $menu_block_region_id
   *   Menu block region id (where is located).
   * @param string $headings_block_region_id
   *   Headings block region id (where is located).
   */
  protected function headingsMenuBuildAlterExecute(array &$build, $menu_block_region_id, $headings_block_region_id) {
    $anchors_tree = [];
    foreach (Element::children($build[$headings_block_region_id], TRUE) as $uuid) {
      $block = $build[$headings_block_region_id][$uuid];
      if (empty($block['content'])) {
        continue;
      }

      if (($build[$headings_block_region_id][$uuid]['content']['#type'] ?? NULL) == 'view') {
        $headings_markup = '';
        foreach ($block['content']['view_build']['#rows'] as $row_delta => $row) {
          if (($row['#title'] ?? NULL) instanceof MarkupInterface) {
            $html_dom = Html::load($row['#title']->__toString());
            $this->headingsMenuProcessHtmlDomText($html_dom);
            $headings_markup .= $row['#title']->__toString();
            $build[$headings_block_region_id][$uuid]['content']['view_build']['#rows'][$row_delta]['#title'] = Markup::create(Html::serialize($html_dom));
          }
          foreach ($row['#rows'] ?? [] as $inner_row_delta => $inner_row) {
            if (($inner_row['group'] ?? NULL) instanceof MarkupInterface) {
              $html_dom = Html::load($inner_row['group']->__toString());
              $this->headingsMenuProcessHtmlDomText($html_dom);
              $headings_markup .= $inner_row['group']->__toString();
              $build[$headings_block_region_id][$uuid]['content']['view_build']['#rows'][$row_delta]['#rows'][$inner_row_delta]['group'] = Markup::create(Html::serialize($html_dom));
            }
          }
        }
        Html::resetSeenIds();
        $html_dom = Html::load($headings_markup);
        $anchors_tree = array_merge($anchors_tree, $this->headingsMenuProcessHtmlDomText($html_dom));
        continue;
      }
      foreach (Element::children($block['content'], TRUE) as $delta) {
        if (($build[$headings_block_region_id][$uuid]['content'][$delta]['#type'] ?? NULL) == 'processed_text') {
          $html_dom = Html::load($build[$headings_block_region_id][$uuid]['content'][$delta]['#text']);
          $anchors_tree = array_merge($anchors_tree, $this->headingsMenuProcessHtmlDomText($html_dom));
          $build[$headings_block_region_id][$uuid]['content'][$delta]['#text'] = Html::serialize($html_dom);
        }
        foreach (Element::children($block['content'][$delta], TRUE) as $inner_delta) {
          if (($build[$headings_block_region_id][$uuid]['content'][$delta][$inner_delta]['#type'] ?? NULL) == 'processed_text') {
            $html_dom = Html::load($build[$headings_block_region_id][$uuid]['content'][$delta][$inner_delta]['#text']);
            $anchors_tree = array_merge($anchors_tree, $this->headingsMenuProcessHtmlDomText($html_dom));
            $build[$headings_block_region_id][$uuid]['content'][$delta][$inner_delta]['#text'] = Html::serialize($html_dom);
          }
        }
      }
    }
    foreach ($build[$menu_block_region_id] as $uuid => $block) {
      if (($block['#plugin_id'] ?? NULL) == 'vlsuite_block_headings_menu') {
        $build[$menu_block_region_id][$uuid]['content']['headings_menu_nav']['#anchors_tree'] = $anchors_tree;
      }
    }
    $build['#theme_wrappers']['container']['#attributes']['class'][] = 'vlsuite-layout-headings-menu';
  }

  /**
   * Headings menu process html DOM text to build mapping.
   *
   * @param \DOMDocument $html_dom
   *   Html DOM.
   *
   * @return array
   *   Result.
   */
  protected function headingsMenuProcessHtmlDomText(\DOMDocument $html_dom) {
    $xpath = new \DOMXPath($html_dom);
    $mapping = [];
    $parents = [];
    $deep_level = 0;
    foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
      $deep_level = empty($deep_level) ? $heading->nodeName[1] : $deep_level;
      // Deeper level, create empty array where items will be added & parent.
      if ($heading->nodeName[1] > $deep_level) {
        $ref = &NestedArray::getValue($mapping, $parents);
        $ref[] = [];
        $parents[] = array_key_last($ref);
        $deep_level = $heading->nodeName[1];
      }
      // Higher level, remove last parent.
      elseif ($heading->nodeName[1] < $deep_level) {
        $deep_level = $heading->nodeName[1];
        array_pop($parents);
      }
      // Add new link at defined parents deep level.
      $ref = &NestedArray::getValue($mapping, $parents);
      $ref += $this->headingsMenuProcessHtmlDomHeading($heading);
    }
    return $mapping;
  }

  /**
   * Headings menu process html DOM heading element.
   *
   * @param \DOMElement $html_dom_heading
   *   Heading dom element.
   *
   * @return array
   *   Result [id => Title].
   */
  protected function headingsMenuProcessHtmlDomHeading(\DOMElement $html_dom_heading) {
    $id_attr = $html_dom_heading->getAttribute('id');
    if (empty($id_attr)) {
      $id_attr = Html::getUniqueId(Html::cleanCssIdentifier('vls-' . \Drupal::service('transliteration')->transliterate($html_dom_heading->textContent)));
      $html_dom_heading->setAttribute('id', $id_attr);
    }
    return [$id_attr => trim($html_dom_heading->textContent)];
  }

}
