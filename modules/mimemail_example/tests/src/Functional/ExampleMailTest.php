<?php

namespace Drupal\Tests\mimemail_example\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests operation of the Mime Mail Example module.
 *
 * @group mimemail
 */
class ExampleMailTest extends BrowserTestBase {
  use AssertMailTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mimemail', 'mimemail_example'];

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Authenticated but unprivileged user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $unprivUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create our test users.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'access content',
    ]);
    $this->unprivUser = $this->drupalCreateUser();
  }

  /**
   * Tests module permissions / access to configuration page.
   */
  public function testUserAccess() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Test as anonymous user.
    $this->drupalGet('example/mimemail_example');
    $assert->statusCodeEquals(403);
    $assert->pageTextContains('Access denied');
    $assert->pageTextContains('You are not authorized to access this page.');

    // Test as authenticated but unprivileged user.
    $this->drupalLogin($this->unprivUser);
    $this->drupalGet('example/mimemail_example');
    $assert->statusCodeEquals(403);
    $this->drupalLogout();

    // Test as admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('example/mimemail_example');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Use this form to send a HTML message to an e-mail address. No spamming!');
    $this->drupalLogout();
  }

}
