<?php

namespace Drupal\smart_content\Cache;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides temporary solve for cacheable ajax requests.
 *
 * @link https://www.drupal.org/project/drupal/issues/956186.
 *
 * @see Drupal\smart_content\Cache\CacheableAjaxResponse
 */
class CacheableAjaxResponseAttachmentsProcessor implements AttachmentsResponseProcessorInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The original service.
   *
   * @var \Drupal\Core\Render\AttachmentsResponseProcessorInterface
   */
  protected $originalService;

  /**
   * Constructs a AjaxResponseAttachmentsProcessor decorator.
   *
   * @param \Drupal\Core\Render\AttachmentsResponseProcessorInterface $service
   *   The service we are decorating.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(AttachmentsResponseProcessorInterface $service, RequestStack $request_stack) {
    $this->originalService = $service;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function processAttachments(AttachmentsInterface $response) {
    if (!$response instanceof AjaxResponse) {
      throw new \InvalidArgumentException('\Drupal\Core\Ajax\AjaxResponse instance expected.');
    }
    // Get the current request.
    $request = $this->requestStack->getCurrentRequest();
    // Check if the query string has 'ajax_page_state' property.
    $ajax_page_state = $request->query->all('ajax_page_state');
    if ($ajax_page_state && !$request->request->has('ajax_page_state')) {
      // Temporarily set query into request data.
      $request->request->set('ajax_page_state', $ajax_page_state);
      // Process same as POST.
      $response = $this->originalService->processAttachments($response);
      // Remove query from request data.
      $request->request->remove('ajax_page_state');
    }
    else {
      $response = $this->originalService->processAttachments($response);
    }
    return $response;
  }

}
