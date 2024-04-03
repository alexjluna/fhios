<?php

namespace Drupal\taxonomy_revisions_ui\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list of revisions for a given taxonomy_term.
 */
class TaxonomyRevisionController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a TaxonomyRevisionController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Generates an overview table of older revisions of taxonomy_term.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   A taxonomy_term object.
   *
   * @return array
   *   An array expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionOverview(TermInterface $taxonomy_term) {
    $account = $this->currentUser();
    $langcode = $taxonomy_term->language()->getId();
    $langname = $taxonomy_term->language()->getName();
    $languages = $taxonomy_term->getTranslationLanguages();
    $hasTranslations = (count($languages) > 1);

    /** @var \Drupal\Core\Entity\EntityStorageInterface $taxonomyStorage */
    $taxonomyStorage = $this->entityTypeManager()->getStorage('taxonomy_term');

    $title = $this->t('Revisions for %title', [
      '%title' => $taxonomy_term->label(),
    ]);
    if ($hasTranslations) {
      $title = $this->t('@langname revisions for %title', [
        '@langname' => $langname,
        '%title' => $taxonomy_term->label(),
      ]);
    }

    $build['#title'] = $title;
    $header = [
      $this->t('Revision'),
      $this->t('Operations'),
    ];

    $type = $taxonomy_term->bundle();
    $canRevert = $this->accountHasRevertPermission($type, $account) && $taxonomy_term->access('update');
    $canDelete = $this->accountHasDeletePermission($type, $account) && $taxonomy_term->access('delete');

    $rows = [];
    $defaultRevision = $taxonomy_term->getRevisionId();
    $currentRevisionDisplayed = FALSE;

    foreach ($this->getRevisionIds($taxonomy_term, $taxonomyStorage) as $vid) {
      /** @var \Drupal\taxonomy\TermInterface $revision */
      $revision = $taxonomyStorage->loadRevision($vid);
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        $date = $this->dateFormatter->format(
          $revision->getRevisionCreationTime(),
          'short'
        );

        $isCurrentRevision = $vid == $defaultRevision || (!$currentRevisionDisplayed && $revision->wasDefaultRevision());
        if (!$isCurrentRevision) {
          $link = Link::fromTextAndUrl($date, new Url(
            'entity.taxonomy_term.revision',
            [
              'taxonomy_term' => $taxonomy_term->id(),
              'taxonomy_term_revision' => $vid,
            ]
          ))->toString();
        }
        else {
          $link = $taxonomy_term->toLink($date)->toString();
          $currentRevisionDisplayed = TRUE;
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];

        $this->renderer->addCacheableDependency($column['data'], $username);
        $row[] = $column;

        if ($isCurrentRevision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];

          $rows[] = [
            'data' => $row,
            'class' => ['revision-current'],
          ];
        }
        else {
          $links = [];
          if ($canRevert) {
            $revertLink = Url::fromRoute(
              'entity.taxonomy_term.revision_revert_confirm',
              [
                'taxonomy_term' => $taxonomy_term->id(),
                'taxonomy_term_revision' => $vid,
              ]
            );
            if ($hasTranslations) {
              $revertLink = Url::fromRoute(
                'entity.taxonomy_term.revision_revert_translation_confirm',
                [
                  'taxonomy_term' => $taxonomy_term->id(),
                  'taxonomy_term_revision' => $vid,
                  'langcode' => $langcode,
                ]
              );
            }
            $title = $this->t('Set as current revision');
            if ($vid < $taxonomy_term->getRevisionId()) {
              $title = $this->t('Revert');
            }
            $links['revert'] = [
              'title' => $title,
              'url' => $revertLink,
            ];
          }

          if ($canDelete) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute(
                'entity.taxonomy_term.revision_delete_confirm',
                [
                  'taxonomy_term' => $taxonomy_term->id(),
                  'taxonomy_term_revision' => $vid,
                ]
              ),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];

          $rows[] = $row;
        }
      }
    }

    $build['taxonomy_term_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => [
        'library' => [
          'taxonomy_revisions_ui/taxonomy_revisions_ui.admin',
        ],
      ],
      '#attributes' => [
        'class' => 'taxonomy-term-revision-table',
      ],
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Gets a list of revision IDs for a given taxonomy_term.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   The taxonomy_term entity to search for revisions.
   * @param \Drupal\Core\Entity\EntityStorageInterface $taxonomyStorage
   *   Taxonomy storage to load revisions from.
   *
   * @return int[]
   *   Taxonomy term revision IDs in descending order.
   */
  protected function getRevisionIds(TermInterface $taxonomy_term, EntityStorageInterface $taxonomyStorage) {

    $result = $taxonomyStorage->getQuery()
      ->allRevisions()
      ->condition('tid', $taxonomy_term->id())
      ->sort('revision_id', 'DESC')
      ->accessCheck()
      ->pager(50)
      ->execute();

    return array_keys($result);
  }

  /**
   * Checks if account can revert a given taxonomy vocabularie.
   *
   * @param string $vocabularie
   *   Vocabularie to check permission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account to check for permissions.
   *
   * @return bool
   *   TRUE if account can revert a given vocabularie, otherwise FALSE.
   */
  protected function accountHasRevertPermission($vocabularie, AccountInterface $account) {
    $hasRevertPermission = FALSE;
    $revertPermissions = [
      "revert $vocabularie revisions",
      'revert all taxonomy revisions',
      'administer taxonomy',
    ];
    foreach ($revertPermissions as $permission) {
      if ($account->hasPermission($permission)) {
        $hasRevertPermission = TRUE;
        break;
      }
    }

    return $hasRevertPermission;
  }

  /**
   * Checks if account can delete a given taxonomy vocabularie.
   *
   * @param string $vocabularie
   *   Vocabularie to check permission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account to check for permissions.
   *
   * @return bool
   *   TRUE if account can delete a given vocabularie type, otherwise FALSE.
   */
  protected function accountHasDeletePermission($vocabularie, AccountInterface $account) {
    $hasRevertPermission = FALSE;
    $revertPermissions = [
      "delete $vocabularie revisions",
      'delete all taxonomy revisions',
      'administer taxonomy',
    ];
    foreach ($revertPermissions as $permission) {
      if ($account->hasPermission($permission)) {
        $hasRevertPermission = TRUE;
        break;
      }
    }

    return $hasRevertPermission;
  }

}
