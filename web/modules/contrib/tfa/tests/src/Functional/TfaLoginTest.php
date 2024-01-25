<?php

namespace Drupal\Tests\tfa\Functional;

/**
 * Tests for the tfa login process.
 *
 * @group tfa
 */
class TfaLoginTest extends TfaTestBase {

  /**
   * User doing the TFA Validation.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * Administrator to handle configurations.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Super administrator to edit other users TFA.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $superAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->webUser = $this->drupalCreateUser(['setup own tfa']);
    $this->adminUser = $this->drupalCreateUser(['admin tfa settings']);
    $this->superAdmin = $this->drupalCreateUser(
      ['administer tfa for other users', 'admin tfa settings', 'setup own tfa']
    );
    $this->canEnableValidationPlugin('tfa_test_plugins_validation');
  }

  /**
   * Tests the tfa login process.
   */
  public function testTfaLogin() {
    $assert_session = $this->assertSession();
    // Check that tfa is not presented if no roles selected.
    $this->drupalLogin($this->webUser);
    $assert_session->statusCodeEquals(200);
    $assert_session->addressEquals('user/' . $this->webUser->id());

    // Enable TFA for the webUser role only.
    $this->drupalLogin($this->adminUser);
    $web_user_roles = $this->webUser->getRoles(TRUE);
    $edit = [
      'tfa_required_roles[' . $web_user_roles[0] . ']' => TRUE,
    ];
    $this->drupalGet('admin/config/people/tfa');
    $this->submitForm($edit, 'Save configuration');
    $assert_session->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Check that tfa is presented.
    $this->drupalLogout();
    $edit = [
      'name' => $this->webUser->getAccountName(),
      'pass' => $this->webUser->passRaw,
    ];
    $this->drupalGet('user/login');
    $this->submitForm($edit, 'Log in');
    $assert_session->statusCodeEquals(200);
    $assert_session->addressMatches('/\/tfa\/' . $this->webUser->id() . '/');

    // Ensure that if no roles are required, a user with tfa enabled still
    // gets prompted with tfa.
    // Disable TFA for all roles.
    $this->drupalLogin($this->adminUser);
    $roles = user_role_names(TRUE);
    $edit = [];
    foreach ($roles as $role_id => $role_name) {
      $edit['tfa_required_roles[' . $role_id . ']'] = FALSE;
    }
    $edit['tfa_required_roles[authenticated]'] = FALSE;
    $this->drupalGet('admin/config/people/tfa');
    $this->submitForm($edit, 'Save configuration');
    $assert_session->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    // Enable tfa for a single user.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('user/' . $this->webUser->id() . '/security/tfa');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains('Currently there are no enabled plugins.');
    $this->clickLink('Set up test application');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Enter your current password to continue.');
    $edit = [
      'current_pass' => $this->webUser->passRaw,
    ];
    $this->submitForm($edit, 'Confirm');
    $assert_session->statusCodeEquals(200);
    $edit = [
      'expected_field' => 'Expected field content',
    ];
    $this->submitForm($edit, 'Verify and save');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('TFA setup complete.');
    $assert_session->pageTextContains('Status: TFA enabled');
    $assert_session->linkExists('Reset test application');
    $assert_session->pageTextContains('Number of times validation skipped: 0 of 3');
    // Check that tfa is presented.
    $this->drupalLogout();
    $edit = [
      'name' => $this->webUser->getAccountName(),
      'pass' => $this->webUser->passRaw,
    ];
    $this->drupalGet('user/login');
    $this->submitForm($edit, 'Log in');
    $assert_session->statusCodeEquals(200);
    $assert_session->addressMatches('/\/tfa\/' . $this->webUser->id() . '/');

    // Check tfa setup as another user.
    $another_user = $this->createUser();
    $this->drupalLogin($this->superAdmin);
    $this->drupalGet('user/' . $another_user->id() . '/security/tfa');
    $assert_session->statusCodeEquals(200);
    $this->clickLink('Set up test application');
    $edit = [
      'current_pass' => $this->superAdmin->passRaw,
    ];
    $this->submitForm($edit, 'Confirm');
    $assert_session->pageTextContains('TFA Setup for ' . $another_user->getDisplayName());
  }

}
