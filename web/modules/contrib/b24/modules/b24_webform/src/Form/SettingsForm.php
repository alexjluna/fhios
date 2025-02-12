<?php

namespace Drupal\b24_webform\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\b24\Service\RestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for configuring webform submissions export to Bitrix24.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal\b24\Service\RestManager definition.
   *
   * @var \Drupal\b24\Service\RestManager
   */
  protected RestManager $b24RestManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The HTML renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected Renderer $renderer;

  /**
   * Constructs a new SettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    RestManager $b24_rest_manager,
    EntityTypeManagerInterface $entity_type_manager,
    Renderer $renderer,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->b24RestManager = $b24_rest_manager;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('b24.rest_manager'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'b24_webform.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['markup'] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'strong',
        '#value' => $this->t('Webform export settings has been moved to webform handlers.'),
      ],
    ];

    /** @var \Drupal\webform\Entity\Webform[] $webforms */
    $webforms = $this->entityTypeManager->getStorage('webform')->loadMultiple();
    $items = [];
    foreach ($webforms as $webform) {
      $handlers = $webform->getHandlers('b24_webform_handler');
      if ($handlers->getInstanceIds()) {
        foreach ($handlers->getIterator() as $handler) {
          $items[] = [
            [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#value' => $webform->label() . ': ',
            ],
            [
              '#type' => 'link',
              '#title' => $handler->getLabel(),
              '#url' => Url::fromRoute('entity.webform.handler.edit_form', [
                'webform' => $webform->id(),
                'webform_handler' => $handler->getHandlerId(),
              ]),
            ],
          ];
        }
      }
      else {
        $items[] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $webform->label() . ': ',
          ],
          [
            '#type' => 'link',
            '#title' => $this->t('Create'),
            '#url' => Url::fromRoute('entity.webform.handler.add_form', [
              'webform' => $webform->id(),
              'webform_handler' => 'b24_webform_handler',
            ]),
          ],
        ];
      }
    }

    if ($items) {
      $form['markup'][] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('You can edit the already existing ones or create new on the webform configuration page.'),
      ];
      $form['webforms'] = [
        '#theme' => 'item_list',
        '#items' => $items,
      ];
    }

    else {
      $form['markup'][] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('You can create new on the <a href="@link">webform configuration page</a>.', [
          '@link' => Url::fromRoute('entity.webform.handlers', ['webform' => $webform->id()])->toString(),
        ]),
      ];
    }

    return $form;
  }

}
