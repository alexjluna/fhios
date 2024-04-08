<?php

namespace Drupal\smart_content\Decision;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\ObjectWithPluginCollectionInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\smart_content\Decision\Storage\DecisionStorageInterface;
use Drupal\smart_content\Event\AttachDecisionSettingsEvent;
use Drupal\smart_content\Reaction\ReactionInterface;
use Drupal\smart_content\Reaction\ReactionManager;
use Drupal\smart_content\Reaction\ReactionPluginCollection;
use Drupal\smart_content\SegmentSetStorage\SegmentSetStorageInterface;
use Drupal\smart_content\SegmentSetStorage\SegmentSetStorageManager;
use Drupal\smart_content\SegmentSetStorage\SegmentSetStoragePluginCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

// @todo: should we use Drupal\Core\Plugin\Factory\ContainerFactory

/**
 * Base class for ReactionSet storage plugins.
 */
abstract class DecisionBase extends PluginBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface, DecisionInterface, ConfigurableInterface, PluginFormInterface, ObjectWithPluginCollectionInterface {
  // todo: confirm that we use this.
  use MessengerTrait;

  use ContextAwarePluginTrait;

  /**
   * The segment set storage plugin manager.
   *
   * @var \Drupal\smart_content\SegmentSetStorage\SegmentSetStorageManager
   */
  protected $segmentSetStorageManager;

  /**
   * The reaction plugin manager.
   *
   * @var \Drupal\smart_content\Reaction\ReactionManager
   */
  protected $reactionManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The uuid generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * An array of reaction settings.
   *
   * @var array
   */
  protected $reactions;

  /**
   * An array of segment storage settings.
   *
   * @var array
   */
  protected $segmentStorage;

  /**
   * A Uuid representing a unique instance of this plugin.
   *
   * @var string
   */
  protected $token;

  /**
   * The plugin ID of the decision storage.
   *
   * @var string
   */
  protected $storageId;

  /**
   * The plugin collection that holds the block plugin for this entity.
   *
   * @var \Drupal\smart_content\Reaction\ReactionPluginCollection
   */
  protected $reactionCollection;


  /**
   * The plugin collection that holds the block plugin for this entity.
   *
   * @var \Drupal\smart_content\Reaction\ReactionPluginCollection
   */
  protected $segmentStorageCollection;

  /**
   * Constructs a DecisionBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\smart_content\SegmentSetStorage\SegmentSetStorageManager $segmentSetStorageManager
   *   The segment set storage plugin manager.
   * @param \Drupal\smart_content\Reaction\ReactionManager $reactionManager
   *   The reaction plugin manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Component\Uuid\UuidInterface $uuidGenerator
   *   The uuid generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SegmentSetStorageManager $segmentSetStorageManager, ReactionManager $reactionManager, EventDispatcherInterface $eventDispatcher, UuidInterface $uuidGenerator) {
    $this->segmentSetStorageManager = $segmentSetStorageManager;
    $this->reactionManager = $reactionManager;
    $this->eventDispatcher = $eventDispatcher;
    $this->uuidGenerator = $uuidGenerator;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);

    // If no token is set, generate a new one.
    if (!$this->hasToken()) {
      $this->refreshToken();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.smart_content.segment_set_storage'),
      $container->get('plugin.manager.smart_content.reaction'),
      $container->get('event_dispatcher'),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getReactions() {
    return $this->getReactionPluginCollection();
  }

  /**
   * {@inheritdoc}
   */
  public function getReaction($id) {
    return $this->getReactionPluginCollection()->get($id);
  }

  /**
   * {@inheritdoc}
   */
  public function hasReaction($id) {
    return $this->getReactionPluginCollection()->has($id);
  }

  /**
   * {@inheritdoc}
   */
  public function setReaction($instance_id, ReactionInterface $reaction) {
    $this->getReactionPluginCollection()->set($instance_id, $reaction);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function appendReaction(ReactionInterface $reaction) {
    $this->getReactionPluginCollection()->add($reaction);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeReaction($id) {
    $this->getReactionPluginCollection()->removeInstanceId($id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'reactions' => $this->getReactionPluginCollection(),
      'segmentStorage' => $this->getSegmentStoragePluginCollection(),
    ];
  }

  /**
   * Encapsulates the creation of the reactions's LazyPluginCollection.
   *
   * @return \Drupal\smart_content\Reaction\ReactionPluginCollection
   *   The reaction's plugin collection.
   */
  protected function getReactionPluginCollection() {
    if (!$this->reactionCollection) {
      $this->reactionCollection = new ReactionPluginCollection($this->reactionManager,
        (array) $this->reactions);
      foreach ($this->reactionCollection as $reaction) {
        // TODO: determine how to handle serialized layout builder classes.
        $this->mapContextsToChildPlugin($reaction);
      }
    }
    return $this->reactionCollection;
  }

  /**
   * Encapsulates the creation of the segment storage's LazyPluginCollection.
   *
   * @return \Drupal\smart_content\Reaction\ReactionPluginCollection
   *   The block's plugin collection.
   */
  protected function getSegmentStoragePluginCollection() {
    if (!$this->segmentStorageCollection && isset($this->segmentStorage['id'])) {
      $this->segmentStorageCollection = new SegmentSetStoragePluginCollection($this->segmentSetStorageManager, $this->segmentStorage['id'], (array) $this->segmentStorage);
    }
    return $this->segmentStorageCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'segmentStorage' => [],
      'reactions' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = $this->defaultConfiguration();
    // Loop through all plugin collections and set them to configuration array.
    foreach ($this->getPluginCollections() as $plugin_config_key => $plugin_collection) {
      // Check if plugin collection exists, in some cases it's not set defined.
      if ($plugin_collection) {
        $configuration[$plugin_config_key] = $plugin_collection->getConfiguration();
      }
    }
    $configuration['token'] = $this->getToken();
    $configuration['storage_id'] = $this->getStorageId();
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration = $configuration + $this->defaultConfiguration();
    $configuration = $this->fixConfigurationSegmentStorageKey($configuration);
    // Loop through all plugin collections and set them.
    foreach ($this->getPluginCollections() as $plugin_config_key => $plugin_collection) {
      if (isset($configuration[$plugin_config_key])) {
        $this->set($plugin_config_key, $configuration[$plugin_config_key]);
      }
    }
    if (isset($configuration['token'])) {
      $this->token = $configuration['token'];
    }
    if (isset($configuration['storage_id'])) {
      $this->storageId = $configuration['storage_id'];
    }
    return $this;
  }

  /**
   * Fix configuration segment storage mismatch.
   *
   * This is a temporary means to fix segment storage key mismatches.  We will
   * deprecate it in the future.  It is necessary because of serialized
   * tempstore data that we can't write an update hook for.
   *
   * @param array $configuration
   *   The configuration array.
   *
   * @return array
   *   The corrected configuration array.
   */
  protected function fixConfigurationSegmentStorageKey(array $configuration) {
    // Adding a fix for mismatched property information.  We can't update this
    // in an update hook because of data serialized in layout builder.
    if (!empty($configuration['segments_storage']) && empty($configuration['segmentStorage'])) {
      $configuration['segmentStorage'] = $configuration['segments_storage'];
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setSegmentSetStorage(SegmentSetStorageInterface $segment_set_storage) {
    $this->set('segmentStorage', $segment_set_storage->getConfiguration());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSegmentSetStorage() {
    if ($this->getSegmentStoragePluginCollection()) {
      $instance_ids = $this->getSegmentStoragePluginCollection()
        ->getInstanceIds();
      return $this->getSegmentStoragePluginCollection()
        ->get(reset($instance_ids));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(ReactionInterface $reaction) {
    $response = new AjaxResponse();
    if ($this->hasReaction($reaction->getSegmentDependencyId())) {
      $response = $this->getReaction($reaction->getSegmentDependencyId())
        ->getResponse($this);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array $element) {
    if ($this->getSegmentSetStorage()) {
      if ($settings = $this->getAttachedSettings()) {
        $element['#attached'] = [
          'drupalSettings' => [
            'smartContent' => $this->getAttachedSettings(),
          ],
        ];

        if ($libraries = $this->getLibraries()) {
          $element['#attached']['library'] = $libraries;
        }
        if ($this->getSegmentSetStorage() instanceof CacheableDependencyInterface) {
          \Drupal::service('renderer')->addCacheableDependency($element, $this->getSegmentSetStorage());
        }
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings() {
    $settings = [];
    if ($this->getStorageId() && $this->getSegmentSetStorage()) {
      $settings = $this->getSegmentSetStorage()->getAttachedSettings();
      $decision_settings = [
        'token' => $this->getToken(),
        'storage' => $this->getStorageId(),
      ];
      // Loop through each segment, keeping them in order based on weight.
      foreach ($this->getSegmentSetStorage()->getSegmentSet()->getSegments() as $segment) {
        // Only add segment if it has a reaction set.
        if ($this->hasReaction($segment->getUuid())) {
          $reaction = $this->getReaction($segment->getUuid());
          $this->mapContextsToChildPlugin($reaction);
          $decision_settings['reactions'][$reaction->getSegmentDependencyId()] = $reaction->getAttachedSettings();
        }
      }
      $settings['decisions'][$this->getToken()] = $decision_settings;
    }
    // Dispatch an event so other modules can alter settings.
    $this->eventDispatcher->dispatch(new AttachDecisionSettingsEvent($settings),
      AttachDecisionSettingsEvent::EVENT_NAME);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    $libraries = [];
    if ($storage = $this->getSegmentSetStorage()) {
      $libraries = $storage->getLibraries();
      $libraries[] = 'smart_content/smart_content';
    }
    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshToken() {
    $this->token = $this->uuidGenerator->generate();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->token;
  }

  /**
   * {@inheritdoc}
   */
  public function hasToken() {
    return isset($this->token);
  }

  /**
   * {@inheritdoc}
   */
  public function setStorage(DecisionStorageInterface $decision_storage) {
    $this->storageId = $decision_storage->getPluginId();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageId() {
    return $this->storageId;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $keys_to_unset = [];
    if ($this instanceof ObjectWithPluginCollectionInterface) {
      // Get the plugin collections first, so that the properties are
      // initialized in $vars and can be found later.
      $plugin_collections = $this->getPluginCollections();
      $vars = get_object_vars($this);
      foreach ($plugin_collections as $plugin_config_key => $plugin_collection) {
        if ($plugin_collection) {
          $this->set($plugin_config_key, $plugin_collection->getConfiguration());
        }
        // Save any changes to the plugin configuration to the entity.
        // If the plugin collections are stored as properties on the entity,
        // mark them to be unset.
        $keys_to_unset += array_filter($vars, function ($value) use ($plugin_collection) {
          return $plugin_collection === $value;
        });
      }
    }

    $vars = parent::__sleep();

    if (!empty($keys_to_unset)) {
      $vars = array_diff($vars, array_keys($keys_to_unset));
    }
    return $vars;
  }

  /**
   * Provides simple getter.
   */
  public function get($property_name) {
    return isset($this->{$property_name}) ? $this->{$property_name} : NULL;
  }

  /**
   * Provides setter that handles lazy PluginCollection.
   */
  public function set($property_name, $value) {
    if ($this instanceof ObjectWithPluginCollectionInterface) {
      $plugin_collections = $this->getPluginCollections();
      if (isset($plugin_collections[$property_name])) {
        // If external code updates the settings, pass it along to the plugin.
        $plugin_collections[$property_name]->setConfiguration($value);
        $plugin_collections[$property_name]->clear();
      }
    }

    $this->{$property_name} = $value;

    return $this;
  }

  /**
   * Generates a unique ID from the decision token.
   *
   * @param string|null $suffix
   *   Optional suffix.
   *
   * @return string
   *   An id from the decision token.
   */
  public function getUniqueFormId($suffix = NULL) {
    return ($suffix) ? $this->getToken() . '-' . $suffix : $this->getToken();
  }

  /**
   * Maps decision context to child.
   *
   * @param \Drupal\Core\Plugin\ContextAwarePluginInterface $child_plugin
   *   The child plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function mapContextsToChildPlugin(ContextAwarePluginInterface $child_plugin) {
    $definitions = $this->getContextDefinitions();
    $child_definitions = $child_plugin->getContextDefinitions();
    $keys = array_keys(array_intersect_key($definitions, $child_definitions));
    foreach ($keys as $key) {
      $child_plugin->setContextValue($key, $this->getContextValue($key));
    }
  }

}
