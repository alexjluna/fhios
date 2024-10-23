<?php

namespace Drupal\vlsuite_utility_classes\Form;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vlsuite_icon_font\VLSuiteIconFontHelper;
use Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure VLSuite utility classes settings.
 */
final class VLSuiteUtilityClassesSettingsForm extends ConfigFormBase {

  /**
   * Utility apply to options.
   *
   * @var array
   */
  private $utilityApplyToOptions;

  /**
   * The utility classes helper.
   *
   * @var \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper
   */
  protected $utilityClassesHelper;

  /**
   * The icon font helper.
   *
   * @var \Drupal\vlsuite_icon_font\VLSuiteIconFontHelper
   */
  protected $iconFontHelper;

  /**
   * Transliteration.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Constructs a new VLSuiteUtilityClassesSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\vlsuite_utility_classes\VLSuiteUtilityClassesHelper $utility_classes_helper
   *   The utility classes helper.
   * @param \Drupal\vlsuite_icon_font\VLSuiteIconFontHelper $icon_font_helper
   *   The icon font helper.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   Transliteration.
   */
  public function __construct(ConfigFactoryInterface $config_factory, VLSuiteUtilityClassesHelper $utility_classes_helper, VLSuiteIconFontHelper $icon_font_helper, TransliterationInterface $transliteration) {
    parent::__construct($config_factory);
    $this->utilityClassesHelper = $utility_classes_helper;
    $this->iconFontHelper = $icon_font_helper;
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('vlsuite_utility_classes.helper'),
      $container->get('vlsuite_icon_font.helper'),
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vlsuite_utility_classes_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vlsuite_utility_classes.settings'];
  }

  /**
   * Utilities tree values processed.
   *
   * @param array $utilities
   *   Utilities.
   *
   * @return array
   *   Utilities processed.
   */
  protected function utilitiesTreeValuesProccesed(array $utilities) {
    $utilities_processed = [];
    foreach ($utilities as $utility_delta => $utility) {
      $utilities_processed[$utility_delta]['identifier'] = $utility['identifier'];
      $utilities_processed[$utility_delta]['icon'] = $utility['icon'];
      $utilities_processed[$utility_delta]['advanced'] = $utility['advanced'];
      $utilities_processed[$utility_delta]['class_prefix'] = $utility['class_prefix'];
      $utilities_processed[$utility_delta]['apply_to'] = $utility['apply_to'];
      $utilities_processed[$utility_delta]['visual_name'] = $utility['visual_name'];

      foreach ($utility['values_wrapper']['values'] as $value_delta => $value) {
        $utilities_processed[$utility_delta]['values'][$value_delta]['icon'] = $value['icon'];
        $utilities_processed[$utility_delta]['values'][$value_delta]['value'] = $value['value'];
        $utilities_processed[$utility_delta]['values'][$value_delta]['visual_name'] = $value['visual_name'];
      }
    }
    return $utilities_processed;
  }

  /**
   * Add utility class value.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function addUtilityClassValue(array $form, FormStateInterface $form_state) {
    $utility_delta = $form_state->getTriggeringElement()['#utility_delta'];
    $utilities = $this->utilitiesTreeValuesProccesed($form_state->getValue('utilities', []));
    $utilities[$utility_delta]['values'][] = [];
    $form_state->set('utilities', $utilities);
    $form_state->setRebuild();
  }

  /**
   * Remove utility class value.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function removeUtilityClassValue(array $form, FormStateInterface $form_state) {
    $utility_delta = $form_state->getTriggeringElement()['#utility_delta'];
    $utility_value_delta = $form_state->getTriggeringElement()['#utility_value_delta'];
    $utilities = $this->utilitiesTreeValuesProccesed($form_state->getValue('utilities', []));
    unset($utilities[$utility_delta]['values'][$utility_value_delta]);
    $form_state->set('utilities', $utilities);
    $form_state->setRebuild();
  }

  /**
   * Add utility.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function addUtility(array $form, FormStateInterface $form_state) {
    $utilities = $this->utilitiesTreeValuesProccesed($form_state->getValue('utilities', []));
    $utilities[] = [];
    $form_state->set('utilities', $utilities);
    $form_state->setRebuild();
  }

  /**
   * Remove utility.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function removeUtility(array $form, FormStateInterface $form_state) {
    $utility_delta = $form_state->getTriggeringElement()['#utility_delta'];
    $utilities = $this->utilitiesTreeValuesProccesed($form_state->getValue('utilities', []));
    unset($utilities[$utility_delta]);
    $form_state->set('utilities', $utilities);
    $form_state->setRebuild();
  }

  /**
   * Get utility apply to options.
   *
   * @return array
   *   Utility apply to options.
   */
  private function getUtilityApplyToOptions() {
    if (empty($this->utilityApplyToOptions)) {
      $this->utilityApplyToOptions = [];
      $apply_to_definitions = $this->utilityClassesHelper->getUtilityApplyToOptions();
      foreach ($apply_to_definitions as $apply_to_key => $definition) {
        $this->utilityApplyToOptions[$apply_to_key] = $definition['admin_title'];
      }
    }
    return $this->utilityApplyToOptions;
  }

  /**
   * Build utility form element.
   *
   * @param int $delta
   *   Utility delta.
   * @param array $utility
   *   Utility.
   *
   * @return array
   *   Utility form element.
   */
  public function buildUtilityFormElement($delta, array $utility) {
    $utility_values = $utility['values'] ?? [0 => []];
    $apply_to_options = $this->getUtilityApplyToOptions();
    $utility_form_element = [
      '#type' => 'details',
      '#open' => empty($utility),
      '#title' => ($utility['visual_name'] ?? $this->t('New')) . ' - "' . ($utility['identifier'] ?? ' - ') . '"',
      '#attributes' => [
        'class' => [
          'vlsuite-utility-classes-settings-form-apply-to-wrapper',
        ],
      ],
      'icon' => $this->iconFontHelper->getIconFontIconFormElement($utility['icon'] ?? NULL),
      'visual_name' => [
        '#type' => 'textfield',
        '#default_value' => $utility['visual_name'] ?? NULL,
        '#required' => TRUE,
        '#title' => $this->t('Visual name'),
      ],
      'identifier' => [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $utility['identifier'] ?? NULL,
        '#title' => $this->t('Identifier'),
        '#description' => $this->t('Important: Once the identifier has been added, changing its value results in a loss of utility usage. When saving the form, a transliteration of the identifier value will be made to avoid spaces and/or special characters.'),
      ],
      'advanced' => [
        '#type' => 'checkbox',
        '#default_value' => $utility['advanced'] ?? NULL,
        '#title' => $this->t('Advanced utility'),
        '#description' => $this->t('Mark if just editors with granted permission ("Use advanced utility classes") should use it. It requires advanced styling knowledge to use it properly.'),
      ],
      'apply_to' => [
        '#type' => 'checkboxes',
        '#access' => !empty($apply_to_options),
        '#options' => $apply_to_options,
        '#default_value' => $utility['apply_to'] ?? NULL,
        '#title' => $this->t('Apply to'),
      ],
      'class_prefix' => [
        '#type' => 'textfield',
        '#default_value' => $utility['class_prefix'] ?? NULL,
        '#required' => TRUE,
        '#title' => $this->t('Class prefix (with optional additional classes at start)'),
        '#description' => $this->t('Or classes separated by " ". Consider using " " at end for special cases like "badge ".'),
      ],
      'values_wrapper' => [
        '#type' => 'details',
        '#open' => TRUE,
        '#required' => TRUE,
        '#title' => $this->t('Values'),
        '#attributes' => ['id' => 'utility-' . $delta . '-values-wrapper'],
        'add_value' => [
          '#weight' => 999,
          '#tree' => FALSE,
          '#type' => 'submit',
          '#value' => $this->t('Add new utility class value'),
          '#name' => 'utility_add_value_' . $delta,
          '#submit' => ['::addUtilityClassValue'],
          '#utility_delta' => $delta,
          '#ajax' => [
            'callback' => '::refreshUtiltyValuesAjaxCallback',
            'wrapper' => 'utility-' . $delta . '-values-wrapper',
            'disable-refocus' => TRUE,
          ],
        ],
      ],
      'remove_utility' => [
        '#type' => 'submit',
        '#tree' => FALSE,
        '#attributes' => ['class' => ['button--danger']],
        '#value' => $this->t('Remove utility class'),
        '#suffix' => $this->t('WARNING! Make sure no usement before removing.'),
        '#submit' => ['::removeUtility'],
        '#name' => 'utility_remove_utility_' . $delta,
        '#utility_delta' => $delta,
        '#ajax' => [
          'callback' => '::refreshUtilitiesAjaxCallback',
          'wrapper' => 'utilities-wrapper',
          'disable-refocus' => TRUE,
        ],
      ],
    ];
    foreach ($utility_values as $utility_value_key => $utility_value_values) {
      $utility_form_element['values_wrapper']['values'][$utility_value_key] = [
        '#type' => 'details',
        '#open' => empty($utility_value_values),
        '#title' => ($utility_value_values['visual_name'] ?? $this->t('New')) . ' - "' . ($utility_value_values['identifier'] ?? ' - ') . '"',
        '#attributes' => ['class' => ['vlsuite-icon-font-icon-form-element']],
        'icon' => $this->iconFontHelper->getIconFontIconFormElement($utility_value_values['icon'] ?? NULL),
        'visual_name' => [
          '#type' => 'textfield',
          '#default_value' => $utility_value_values['visual_name'] ?? NULL,
          '#required' => TRUE,
          '#title' => $this->t('Value visual name'),
        ],
        'identifier' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#default_value' => $utility_value_values['identifier'] ?? NULL,
          '#title' => $this->t('Value identifier'),
          '#description' => $this->t('Important: Once the identifier has been added, changing its value results in a loss of utility usage. When saving the form, a transliteration of the identifier value will be made to avoid spaces and/or special characters.'),
        ],
        'value' => [
          '#type' => 'textfield',
          '#default_value' => $utility_value_values['value'] ?? NULL,
          '#required' => TRUE,
          '#title' => $this->t('Value class suffix (with optional additional classes to end)'),
          '#description' => $this->t('Use reserved "defined-by-prefix" to determine additional value is not needed for this option, e.g: "rounded".'),
        ],
        'remove_value' => [
          '#weight' => 999,
          '#tree' => FALSE,
          '#type' => 'submit',
          '#attributes' => ['class' => ['button--danger']],
          '#value' => $this->t('Remove utility class value'),
          '#name' => 'utility_remove_value_' . $delta . '_' . $utility_value_key,
          '#suffix' => $this->t('WARNING! Make sure no usement before removing.'),
          '#submit' => ['::removeUtilityClassValue'],
          '#utility_delta' => $delta,
          '#utility_value_delta' => $utility_value_key,
          '#ajax' => [
            'callback' => '::refreshUtiltyValuesAjaxCallback',
            'wrapper' => 'utility-' . $delta . '-values-wrapper',
            'disable-refocus' => TRUE,
          ],
        ],
      ];
    }
    return $utility_form_element;
  }

  /**
   * Refresh utilities values ajax callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Utilities values form element for ajax callback.
   */
  public function refreshUtiltyValuesAjaxCallback(array $form, FormStateInterface $form_state) {
    $utility_delta = $form_state->getTriggeringElement()['#utility_delta'];
    return $form['utilities'][$utility_delta]['values_wrapper'];
  }

  /**
   * Refresh utilities ajax callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Utilities form element for ajax callback.
   */
  public function refreshUtilitiesAjaxCallback(array $form, FormStateInterface $form_state) {
    return $form['utilities'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config_utilities = $this->configFactory->get('vlsuite_utility_classes.settings')->get('utilities');

    $form['#attached']['library'][] = 'vlsuite_utility_classes/settings_form';
    $form['info'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Info'),
      '#description' => $this->t('Default provided is using similar as <a href=":link" target="_blank">Bootstrap 5 utilities</a>, you can adjust to fit your bootstrap customization or to your completly customized styles.',
        [':link' => 'https://getbootstrap.com/docs/5.0/utilities']),
    ];

    $form['basics'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Basics'),
    ];

    $column_widths_form_element = $this->iconFontHelper->getIconFontIconFormElement($this->configFactory->get('vlsuite_utility_classes.settings')->get('column_widths_icon') ?? NULL);
    $column_widths_form_element['#title'] = $this->t('Column widths icon');
    $form['basics']['column_widths_icon'] = $column_widths_form_element;

    $form['basics']['list_unstyled_classes'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('List unstyled base class (or classes)'),
      '#description' => $this->t('Example "list-unstyled". Separated by " " when multiple.'),
      '#default_value' => $this->configFactory->get('vlsuite_utility_classes.settings')->get('list_unstyled_classes'),
    ];

    $form['basics']['container_classes'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Container base class (or classes)'),
      '#description' => $this->t('Example "container". Separated by " " when multiple.'),
      '#default_value' => $this->configFactory->get('vlsuite_utility_classes.settings')->get('container_classes'),
    ];

    $form['basics']['row_classes'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Row base class (or classes)'),
      '#description' => $this->t('Example "row". Separated by " " when multiple.'),
      '#default_value' => $this->configFactory->get('vlsuite_utility_classes.settings')->get('row_classes'),
    ];

    $form['basics']['col_classes'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Col per percentage'),
    ];

    foreach (VLSuiteUtilityClassesHelper::getColPercentageOptions() as $percentage) {
      $form['basics']['col_classes']['col_' . $percentage] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Col @percentage% width base class (or classes)', ['@percentage' => $percentage]),
        '#description' => $this->t('Examples "col-12", "col-12 col-md-6". Separated by " " when multiple.'),
        '#default_value' => $this->configFactory->get('vlsuite_utility_classes.settings')->get('col_classes')['col_' . $percentage] ?? NULL,
      ];
    }

    $form['basics']['col_classes']['col_auto'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Col auto to add when not filling row base class (or classes)', ['@percentage' => $percentage]),
      '#description' => $this->t('Examples "mx-auto", Separated by " " when multiple.'),
      '#default_value' => $this->configFactory->get('vlsuite_utility_classes.settings')->get('col_classes')['col_auto'] ?? NULL,
    ];

    $config_utilities_processed = NULL;
    foreach ($config_utilities as $config_utility_identifier => $config_utility) {
      $config_utility_processed = [
        'identifier' => $config_utility_identifier,
        'icon' => $config_utility['icon'] ?? '',
        'visual_name' => $config_utility['visual_name'],
        'advanced' => $config_utility['advanced'] ?? FALSE,
        'apply_to' => $config_utility['apply_to'],
        'class_prefix' => $config_utility['class_prefix'],
      ];
      foreach ($config_utility['values'] as $value_identifier => $value) {
        $config_utility_processed['values'][] = [
          'identifier' => $value_identifier,
          'icon' => $value['icon'] ?? '',
          'visual_name' => $value['visual_name'],
          'value' => $value['class_suffix'],
        ];
      }
      $config_utilities_processed[] = $config_utility_processed;
    }
    $form['utilities'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Utilities'),
      '#attributes' => ['id' => 'utilities-wrapper'],
    ];
    $utilities = $form_state->get('utilities') ?? ($config_utilities_processed ?? [0 => []]);
    foreach ($utilities as $utility_delta => $utility) {
      $form['utilities'][$utility_delta] = $this->buildUtilityFormElement($utility_delta, $utility);
    }
    $form['actions']['add_utility'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add new utility class'),
      '#submit' => ['::addUtility'],
      '#ajax' => [
        'callback' => '::refreshUtilitiesAjaxCallback',
        'wrapper' => 'utilities-wrapper',
        'disable-refocus' => TRUE,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $utilities_raw = $form_state->getValue('utilities');
    $utilities = [];
    foreach ($utilities_raw as $utility_raw) {
      $utility_identifier = Html::getId($this->transliteration->transliterate(trim($utility_raw['identifier'])));
      $utilities[$utility_identifier]['icon'] = $utility_raw['icon'];
      $utilities[$utility_identifier]['advanced'] = $utility_raw['advanced'];
      $utilities[$utility_identifier]['visual_name'] = $utility_raw['visual_name'];
      $utilities[$utility_identifier]['class_prefix'] = $utility_raw['class_prefix'];
      $utilities[$utility_identifier]['apply_to'] = array_filter($utility_raw['apply_to']);
      foreach ($utility_raw['values_wrapper']['values'] as $utility_value) {
        $utility_value_identifier = Html::getId($this->transliteration->transliterate(trim($utility_value['identifier'])));
        $utilities[$utility_identifier]['values'][$utility_value_identifier] = [
          'icon' => $utility_value['icon'],
          'visual_name' => $utility_value['visual_name'],
          'class_suffix' => $utility_value['value'],
        ];
      }
    }
    $this->config('vlsuite_utility_classes.settings')
      ->set('column_widths_icon', $form_state->getValue('basics')['column_widths_icon'] ?? '')
      ->set('list_unstyled_classes', $form_state->getValue('basics')['list_unstyled_classes'] ?? '')
      ->set('container_classes', $form_state->getValue('basics')['container_classes'] ?? '')
      ->set('row_classes', $form_state->getValue('basics')['row_classes'] ?? '')
      ->set('col_classes', $form_state->getValue('basics')['col_classes'] ?? '')
      ->set('utilities', $utilities)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
