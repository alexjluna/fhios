<?php

namespace Drupal\smart_content_block\EventSubscriber;

use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\smart_content_block\BlockPreviewInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Builds render arrays and handles access for all block components.
 */
class LayoutBuilderComponentRenderArray implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['section_component.build.render_array'] = ['onBuildRender', 50];
    return $events;
  }

  /**
   * Builds preview render array when block is being previewed.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   The section component render event.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
    $block = $event->getPlugin();
    // If block doesn't implement preview interface, return.
    if (!$block instanceof BlockPreviewInterface) {
      return;
    }
    // If block is being previewed, override build.
    if ($event->inPreview()) {
      $build = $event->getBuild();
      $build['content'] = $block->buildPreview();
      $event->setBuild($build);
    }

  }

}
