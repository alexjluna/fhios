<?php

namespace Drupal\vlsuite_modal\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\vlsuite_modal\VLSuiteModalHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides VLSuite - Modal event subscribers.
 */
class VLSuiteModalRouteSubscriber implements EventSubscriberInterface {

  /**
   * Close modal into ajax response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event.
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();

    if ($response instanceof AjaxResponse) {
      $close_dialog = FALSE;
      $layout_builder_condition = FALSE;
      $close_dialog_condition = FALSE;
      $commands = $response->getCommands();
      foreach ($commands as $command) {
        if (isset($command['selector']) && $command['selector'] === '#layout-builder') {
          $layout_builder_condition = TRUE;
        }
        elseif (isset($command['command']) && $command['command'] === 'closeDialog') {
          $close_dialog_condition = TRUE;
        }
        if ($layout_builder_condition && $close_dialog_condition) {
          $close_dialog = TRUE;
          break;
        }
      }
      if ($close_dialog) {
        $response->addCommand(new CloseDialogCommand('#' . VLSuiteModalHelper::DIALOG_TARGET));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => [['onResponse']],
    ];
  }

}
