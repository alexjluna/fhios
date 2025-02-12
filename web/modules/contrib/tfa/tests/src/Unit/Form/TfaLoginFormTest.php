<?php

declare(strict_types=1);

namespace Drupal\Tests\tfa\Unit\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Tests\UnitTestCase;
use Drupal\tfa\Form\TfaLoginForm;
use Drupal\tfa\TfaLoginContext;
use Drupal\tfa\TfaLoginContextFactory;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserFloodControlInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

require_once DRUPAL_ROOT . '/core/modules/user/user.module';

/**
 * Test the TfaLoginForm class.
 *
 * @covers \Drupal\tfa\Form\TfaLoginForm
 *
 * @group tfa
 */
class TfaLoginFormTest extends UnitTestCase {

  /**
   * The Tfa Login Context Mock.
   *
   * @var \Drupal\tfa\TfaLoginContext&\PHPUnit\Framework\MockObject\MockObject
   */
  protected TfaLoginContext&MockObject $loginContextMock;

  /**
   * The Memory Cache Backend Mock.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected CacheBackendInterface&MockObject $memoryCacheMock;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->loginContextMock = $this->createMock(TfaLoginContext::class);
    $this->memoryCacheMock = $this->createMock(CacheBackendInterface::class);
  }

  /**
   * Helper method to instantiate the test fixture.
   *
   * @return \Drupal\tfa\Form\TfaLoginForm
   *   The TFA Login Form.
   */
  public function getFixture(): TfaLoginForm {
    $container = new ContainerBuilder();

    // Global container services.
    $request_stack_mock = $this->createMock(RequestStack::class);
    $request_stack_mock->method('getCurrentRequest')->willReturn(new Request());
    $messenger_mock = $this->createMock(MessengerInterface::class);

    // Required for user_login_finalized().
    $current_user_mock = $this->createMock(AccountProxyInterface::class);
    $logger_factory_mock = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory_mock
      ->method('get')
      ->willReturn($this->createMock(LoggerChannelInterface::class));
    $date_time_mock = $this->createMock(TimeInterface::class);
    $session_mock = $this->createMock(SessionInterface::class);
    $module_handler_mock = $this->createMock(ModuleHandlerInterface::class);

    $global_config = [
      'tfa.settings' => [
        'users_without_tfa_redirect' => FALSE,
      ],
    ];

    $container->set('request_stack', $request_stack_mock);
    $container->set('config.factory', $this->getConfigFactoryStub($global_config));
    $container->set('messenger', $messenger_mock);
    $container->set('string_translation', $this->getStringTranslationStub());

    // Required for user_login_finalized().
    $container->set('current_user', $current_user_mock);
    $container->set('logger.factory', $logger_factory_mock);
    $container->set('datetime.time', $date_time_mock);
    $container->set('session', $session_mock);
    $container->set('module_handler', $module_handler_mock);

    // Parent class services.
    $flood_mock = $this->createMock(UserFloodControlInterface::class);
    $user_auth_mock = $this->createMock(UserAuthInterface::class);
    $render_mock = $this->createMock(RendererInterface::class);
    $bare_render_mock = $this->createMock(BareHtmlPageRendererInterface::class);

    $user_mock = $this->createMock(UserInterface::class);
    $user_mock->method('id')->willReturn('3');
    $user_storage_mock = $this->createMock(UserStorageInterface::class);
    $user_storage_mock->method('load')->with($this->identicalTo(3))->willReturn($user_mock);
    $entity_type_manager_mock = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager_mock->method('getStorage')->with('user')->willReturn($user_storage_mock);

    $container->set('user.flood_control', $flood_mock);
    $container->set('entity_type.manager', $entity_type_manager_mock);
    $container->set('user.auth', $user_auth_mock);
    $container->set('renderer', $render_mock);
    $container->set('bare_html_page_renderer', $bare_render_mock);

    // Our services.
    $destination_mock = $this->createMock(RedirectDestinationInterface::class);
    $login_context_factory = $this->createMock(TfaLoginContextFactory::class);
    $login_context_factory->method('createContextFromUser')->willReturn($this->loginContextMock);

    $temp_store_mock = $this->createMock(PrivateTempStore::class);
    $temp_store_factory_mock = $this->createMock(PrivateTempStoreFactory::class);
    $temp_store_factory_mock->method('get')->with('tfa')->willReturn($temp_store_mock);

    $this->loginContextMock->method('getUser')->willReturn($user_mock);

    $container->set('redirect.destination', $destination_mock);
    $container->set('tempstore.private', $temp_store_factory_mock);
    $container->set('tfa.login_context_factory', $login_context_factory);
    $container->set('cache.tfa_memcache', $this->memoryCacheMock);

    // Set the global container.
    \Drupal::setContainer($container);

    return TfaLoginForm::create($container);
  }

  /**
   * Ensures that the TFA Complete flag is set.
   *
   * @param int $count
   *   Number of times Memory Cache is expected to be called.
   * @param callable $setup
   *   Sets up test environment. Called with $this for only param.
   *
   * @dataProvider providerSetCompleteFlag
   */
  public function testSetCompleteFlag(int $count, callable $setup): void {
    $this->memoryCacheMock
      ->expects($this->exactly($count))
      ->method('set')
      ->with(
        self::identicalTo('tfa_complete'),
        self::identicalTo(3),
        Cache::PERMANENT,
        [],
      );

    $setup($this);
    $fixture = $this->getFixture();
    $form_state = new FormState();
    $form = [];
    $form_state->set('uid', 3);
    $fixture->submitForm($form, $form_state);
  }

  /**
   * Provider for testSetCompleteFlag()
   *
   * @return \Generator
   *   Test data.
   */
  public static function providerSetCompleteFlag(): \Generator {

    yield 'TFA disabled' => [
      1,
      function (self $context) {
        $context->loginContextMock->method('isTfaDisabled')->willReturn(TRUE);
      },
    ];

    yield 'Can log-in without tfa' => [
      1,
      function (self $context) {
        $context->loginContextMock->method('isTfaDisabled')->willReturn(FALSE);
        $context->loginContextMock->method('canLoginWithoutTfa')->willReturn(TRUE);
      },
    ];

    yield 'TFA Required, Login Plugin allowed' => [
      1,
      function (self $context) {
        $context->loginContextMock->method('isTfaDisabled')->willReturn(FALSE);
        $context->loginContextMock->method('isReady')->willReturn(TRUE);
        $context->loginContextMock->method('canLoginWithoutTfa')->willReturn(FALSE);
        $context->loginContextMock->method('pluginAllowsLogin')->willReturn(TRUE);
      },
    ];

    yield 'TFA Required, Redirected to entry form' => [
      0,
      function (self $context) {
        $context->loginContextMock->method('isTfaDisabled')->willReturn(FALSE);
        $context->loginContextMock->method('isReady')->willReturn(TRUE);
        $context->loginContextMock->method('canLoginWithoutTfa')->willReturn(FALSE);
        $context->loginContextMock->method('pluginAllowsLogin')->willReturn(FALSE);
      },
    ];

  }

}
