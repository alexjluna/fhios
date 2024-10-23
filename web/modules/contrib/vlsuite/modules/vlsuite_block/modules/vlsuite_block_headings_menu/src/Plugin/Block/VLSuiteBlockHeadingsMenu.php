<?php

namespace Drupal\vlsuite_block_headings_menu\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides VLSUite Headings menu block.
 *
 * @Block(
 *   id = "vlsuite_block_headings_menu",
 *   admin_label = @Translation("Block Headings Menu (VLSuite)"),
 *   category = @Translation("VLSuite"),
 * )
 */
class VLSuiteBlockHeadingsMenu extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'label_display' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['label_display']['#type'] = 'hidden';
    $form['label_display']['#default_value'] = TRUE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @note links depend on others block content,
    // @see \Drupal\vlsuite_layout\VLSuiteLayoutHeadingsMenuTrait.
    // @see \Drupal\vlsuite_layout\Plugin\Layout\VLSuiteLayoutTwoCols.
    $base_class = 'vlsuite-block-headings-menu';
    $title_start_icon = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="9.14286" height="3.28205" rx="1.64103" fill="currentColor"/><rect y="6.35889" width="16" height="3.28205" rx="1.64103" fill="currentColor"/><rect y="12.718" width="12.8" height="3.28205" rx="1.64103" fill="currentColor"/></svg>';
    $title_end_icon = '<svg class="' . $base_class . '__title__indicator' . '" width="16" height="10" viewBox="0 0 16 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.753 0.980428C16.0823 1.3098 16.0823 1.84382 15.753 2.17319L8.59638 9.32978C8.26701 9.65915 7.73299 9.65915 7.40362 9.32978L0.247028 2.17319C-0.0823441 1.84382 -0.0823441 1.3098 0.247028 0.980427C0.576401 0.651055 1.11042 0.651054 1.43979 0.980428L8 7.54063L14.5602 0.980428C14.8896 0.651055 15.4236 0.651055 15.753 0.980428Z" fill="currentColor"/>';
    return [
      '#title_attributes' => ['class' => ['visually-hidden']],
      'headings_menu_nav' => [
        '#theme' => 'vlsuite_block_headings_menu_nav',
        '#attributes' => [
          'class' => [$base_class . '__nav-main'],
          'id' => Html::getUniqueId('headings-menu-nav'),
        ],
        '#inner_attributes' => ['class' => [$base_class . '__nav-inner']],
        '#link_attributes' => ['class' => [$base_class . '__nav-link']],
        '#title_start_icon' => $title_start_icon,
        '#title_end_icon' => $title_end_icon,
        '#title' => $this->label(),
        '#title_attributes' => [
          'class' => [$base_class . '__title'],
          'tabindex' => 0,
        ],
      ],
      '#attributes' => ['class' => [$base_class]],
      '#attached' => [
        'library' => ['vlsuite_block_headings_menu/headings-menu'],
      ],
    ];
  }

}
