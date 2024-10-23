<?php

namespace Drupal\vlsuite_animations\Form;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vlsuite_animations\VLSuiteAnimationsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure VLSuite animations classes settings.
 */
final class VLSuiteAnimationsSettingsForm extends ConfigFormBase {

  /**
   * Animation apply at options.
   *
   * @var array
   */
  private $animationApplyAtOptions;

  /**
   * The animations helper.
   *
   * @var \Drupal\vlsuite_animations\VLSuiteAnimationsHelper
   */
  protected $animationsHelper;

  /**
   * Transliteration.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Constructs a new VLSuiteAnimationsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\vlsuite_animations\VLSuiteAnimationsHelper $animations_helper
   *   VLSuite animations helper.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   Transliteration.
   */
  public function __construct(ConfigFactoryInterface $config_factory, VLSuiteAnimationsHelper $animations_helper, TransliterationInterface $transliteration) {
    parent::__construct($config_factory);
    $this->animationsHelper = $animations_helper;
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('vlsuite_animations.helper'),
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vlsuite_animations_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vlsuite_animations.settings'];
  }

  /**
   * Animations tree values processed.
   *
   * @param array $animations
   *   Animations.
   *
   * @return array
   *   Animations processed.
   */
  protected function animationsTreeValuesProccesed(array $animations) {
    $animations_processed = [];
    foreach ($animations as $animation_delta => $animation) {
      $animations_processed[$animation_delta]['identifier'] = $animation['identifier'];
      $animations_processed[$animation_delta]['classes'] = $animation['classes'];
      $animations_processed[$animation_delta]['apply_at'] = $animation['apply_at'];
      $animations_processed[$animation_delta]['visual_name'] = $animation['visual_name'];
    }
    return $animations_processed;
  }

  /**
   * Add animation.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function addAnimation(array $form, FormStateInterface $form_state) {
    $animations = $this->animationsTreeValuesProccesed($form_state->getValue('animations', []));
    $animations[] = [];
    $form_state->set('animations', $animations);
    $form_state->setRebuild();
  }

  /**
   * Remove animation.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function removeAnimation(array $form, FormStateInterface $form_state) {
    $animation_delta = $form_state->getTriggeringElement()['#animation_delta'];
    $animations = $this->animationsTreeValuesProccesed($form_state->getValue('animations', []));
    unset($animations[$animation_delta]);
    $form_state->set('animations', $animations);
    $form_state->setRebuild();
  }

  /**
   * Get animation apply at options.
   *
   * @return array
   *   Animation apply at options.
   */
  private function getAnimationApplyAtOptions() {
    if (empty($this->animationApplyAtOptions)) {
      $this->animationApplyAtOptions = [];
      $apply_at_definitions = $this->animationsHelper->getAnimationApplyAtOptions();
      foreach ($apply_at_definitions as $apply_at_key => $definition) {
        $this->animationApplyAtOptions[$apply_at_key] = $definition['title'] . ' - ' . $definition['description'];
      }
    }
    return $this->animationApplyAtOptions;
  }

  /**
   * Build animation form element.
   *
   * @param int $delta
   *   Animation delta.
   * @param array $animation
   *   Animation.
   *
   * @return array
   *   Animation form element.
   */
  public function buildAnimationFormElement($delta, array $animation) {
    $apply_at_options = $this->getAnimationApplyAtOptions();
    $animation_form_element = [
      '#type' => 'details',
      '#open' => empty($animation),
      '#title' => ($animation['visual_name'] ?? $this->t('New')) . ' - "' . ($animation['identifier'] ?? ' - ') . '"',
      '#attributes' => ['class' => ['vlsuite-animations-settings-form-apply-to-wrapper']],
      'visual_name' => [
        '#type' => 'textfield',
        '#default_value' => $animation['visual_name'] ?? NULL,
        '#required' => TRUE,
        '#title' => $this->t('Visual name'),
      ],
      'identifier' => [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $animation['identifier'] ?? NULL,
        '#title' => $this->t('Identifier'),
        '#description' => $this->t('Important: Once the identifier has been added, changing its value results in a loss of animation usage. When saving the form, a transliteration of the identifier value will be made to avoid spaces and/or special characters.'),
      ],
      'apply_at' => [
        '#type' => 'checkboxes',
        '#access' => !empty($apply_at_options),
        '#options' => $apply_at_options,
        '#default_value' => $animation['apply_at'] ?? NULL,
        '#title' => $this->t('Apply at'),
      ],
      'classes' => [
        '#type' => 'textfield',
        '#default_value' => $animation['classes'] ?? NULL,
        '#required' => TRUE,
        '#title' => $this->t('Class (or classes)'),
        '#description' => $this->t('Separated by " " when multiple.'),
      ],
      'remove_animation' => [
        '#type' => 'submit',
        '#tree' => FALSE,
        '#attributes' => ['class' => ['button--danger']],
        '#value' => $this->t('Remove animation'),
        '#suffix' => $this->t('WARNING! Make sure no usement before removing.'),
        '#submit' => ['::removeAnimation'],
        '#name' => 'animation_remove_animation_' . $delta,
        '#animation_delta' => $delta,
        '#ajax' => [
          'callback' => '::refreshAnimationsAjaxCallback',
          'wrapper' => 'animations-wrapper',
          'disable-refocus' => TRUE,
        ],
      ],
    ];
    return $animation_form_element;
  }

  /**
   * Refresh animations ajax callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Animations form element for ajax callback.
   */
  public function refreshAnimationsAjaxCallback(array $form, FormStateInterface $form_state) {
    return $form['animations'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config_animations = $this->configFactory->get('vlsuite_animations.settings')->get('animations');

    $form['info'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Info'),
      '#description' => $this->t('Default provided is <a href=":link" target="_blank">Animate.css</a>, you can adjust them to fit your own.',
      [':link' => 'https://animate.style']),
    ];

    $form['basics'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Basics'),
    ];

    $form['basics']['is_playing_classes'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Animation is playing class (or classes)'),
      '#description' => $this->t('Example "isPlaying". Separated by " " when multiple.'),
      '#default_value' => $this->configFactory->get('vlsuite_animations.settings')->get('is_playing_classes'),
    ];

    $form['basics']['is_shown_classes'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Entrance animation is done & element is shown class (or classes)'),
      '#description' => $this->t('Example "isShown". Separated by " " when multiple.'),
      '#default_value' => $this->configFactory->get('vlsuite_animations.settings')->get('is_shown_classes'),
    ];

    $form['basics']['infinite_classes'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Infinite animation class (or classes)'),
      '#description' => $this->t('Example "animate__infinite". Separated by " " when multiple.'),
      '#default_value' => $this->configFactory->get('vlsuite_animations.settings')->get('infinite_classes'),
    ];

    $form['basics']['main_classes'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Main class (or classes) to add along with the animation spicific one'),
      '#description' => $this->t('Example "animate__animated". Separated by " " when multiple.'),
      '#default_value' => $this->configFactory->get('vlsuite_animations.settings')->get('main_classes'),
    ];

    $form['basics']['root_margin'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Margin around the root'),
      '#description' => $this->t('Example "0px". Can have values similar to the CSS margin property, e.g. "10px 20px 30px 40px" (top, right, bottom, left). The values can be percentages.'),
      '#default_value' => $this->configFactory->get('vlsuite_animations.settings')->get('root_margin'),
    ];

    $form['basics']['threshold'] = [
      '#type' => 'number',
      '#min' => '0.05',
      '#max' => '0.95',
      '#step' => '0.05',
      '#required' => TRUE,
      '#title' => $this->t('Threshold when animation should occur'),
      '#description' => $this->t('Example "0.25".'),
      '#default_value' => $this->configFactory->get('vlsuite_animations.settings')->get('threshold'),
    ];

    $config_animations_processed = NULL;
    foreach ($config_animations as $config_animation_identifier => $config_animation) {
      $config_animation_processed = [
        'identifier' => $config_animation_identifier,
        'visual_name' => $config_animation['visual_name'],
        'apply_at' => $config_animation['apply_at'],
        'classes' => $config_animation['classes'],
      ];
      $config_animations_processed[] = $config_animation_processed;
    }
    $form['animations'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Animations'),
      '#attributes' => ['id' => 'animations-wrapper'],
    ];
    $animations = $form_state->get('animations') ?? ($config_animations_processed ?? [0 => []]);
    foreach ($animations as $animation_delta => $animation) {
      $form['animations'][$animation_delta] = $this->buildAnimationFormElement($animation_delta, $animation);
    }
    $form['actions']['add_animation'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add new animation'),
      '#submit' => ['::addAnimation'],
      '#ajax' => [
        'callback' => '::refreshAnimationsAjaxCallback',
        'wrapper' => 'animations-wrapper',
        'disable-refocus' => TRUE,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $animations_raw = $form_state->getValue('animations');
    $animations = [];
    foreach ($animations_raw as $animation_raw) {
      $animation_identifier = Html::getId($this->transliteration->transliterate(trim($animation_raw['identifier'])));
      $animations[$animation_identifier]['visual_name'] = $animation_raw['visual_name'];
      $animations[$animation_identifier]['classes'] = $animation_raw['classes'];
      $animations[$animation_identifier]['apply_at'] = array_filter($animation_raw['apply_at']);
    }
    $this->config('vlsuite_animations.settings')
      ->set('is_playing_classes', $form_state->getValue('basics')['is_playing_classes'] ?? '')
      ->set('is_shown_classes', $form_state->getValue('basics')['is_shown_classes'] ?? '')
      ->set('main_classes', $form_state->getValue('basics')['main_classes'] ?? '')
      ->set('root_margin', $form_state->getValue('basics')['root_margin'] ?? '')
      ->set('threshold', $form_state->getValue('basics')['threshold'] ?? '')
      ->set('infinite_classes', $form_state->getValue('basics')['infinite_classes'] ?? '')
      ->set('animations', $animations)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
