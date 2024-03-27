<?php

namespace Drupal\rsvplist\Form;

/**
 * @file
 * Contains the settings for administering the RSVP Form.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config Form to RSVPList.
 */
class RSVPSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rsvplist_admin_settigns';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return [
      'rsvplist.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $type = node_type_get_names();
    $config = $this->config('rsvplist.settings');
    $form['rsvplist_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('The content types to enable RSVP collection for'),
      '#default_value' => $config->get('allowed_types'),
      '#options' => $type,
      '#description' => $this->t('On the specified node types, an RSVP option will be available and can anbled while the node is being edited.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selectedAllowedTypes = array_filter($form_state->getValue('rsvplist_types'));
    sort($selectedAllowedTypes);

    $this->config('rsvplist.settings')
      ->set('allowed_types', $selectedAllowedTypes)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
