<?php

namespace Drupal\mymodule\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for ArticleController.
 */
class ArticleController extends ControllerBase {

  /**
   * Store content aticle content type.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Symfony request object.
   */
  public function store(Request $request) : JsonResponse {
    $content = $request->getContent();
    $json = Json::decode($content);
    $entity_type_manager = $this->entityTypeManager();
    $node_storage = $entity_type_manager->getStorage('node');
    $article = $node_storage->create([
      'type' => 'article',
      'title' => $json['title'],
      'body' => $json['body'],
    ]);
    $article->save();
    $article_url = $article->toUrl()->setAbsolute()->toString();
    return new JsonResponse(
      $article->toArray(),
      201,
      ['Location' => $article_url]
    );
  }

}
