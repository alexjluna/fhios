<?php

namespace Drupal\fhios_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the Search user form controller
 *
 * @see \Drupal\Core\Form\FormBase
 */
class SearchUser extends FormBase {

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search user')
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];
    return $form;
  }

  public function getFormId() {
    return 'fhios_search_form';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $username = strtolower(trim($form_state->getValue('username')));
    $form_state->setRedirectUrl(\Drupal\Core\Url::fromUri('internal:' . '/search/user/'.$username));
  }
}
