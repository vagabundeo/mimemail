<?php

namespace Drupal\Tests\mimemail\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\mimemail\Utility\MimeMailFormatHelper;

/**
 * Tests that Mime Mail utility functions work properly.
 *
 * @coversDefaultClass \Drupal\mimemail\Utility\MimeMailFormatHelper
 *
 * @group mimemail
 */
class MimeMailFormatHelperTest extends KernelTestBase {
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'mailsystem',
    'mimemail',
    'system',
    'user',
  ];

  /**
   * Adminstrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userA;

  /**
   * A different authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userB;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Sets up an anonymous and two authenticated users.
    $this->adminUser = $this->setUpCurrentUser([
      'uid' => 1,
      'name' => 'username with spaces',
      'mail' => 'admin@test.example.com',
    ]);
    $this->userA = $this->createUser([], 'CapitaL', FALSE, ['mail' => 'namea@example.com']);
    $this->userB = $this->createUser([], '', FALSE, ['mail' => 'nameb@example.com']);
  }

  /**
   * Tests formatting an address string.
   *
   * @param string|array|\Drupal\user\UserInterface $address
   *   MimeMailFormatHelper::mimeMailAddress() accepts addresses in one of
   *   four different formats:
   *   - A text email address, e.g. someone@example.com.
   *   - An array where the values are each a text email address.
   *   - An associative array to represent one email address, containing keys:
   *     - mail: A text email address, as above.
   *     - (optional) name: A text name to accompany the email address,
   *       e.g. 'John Doe'.
   *   - A fully loaded object implementing \Drupal\user\UserInterface.
   * @param string $result
   *   Email address after formatting.
   * @param string $simplified_result
   *   Simplified email address after formatting.
   *
   * @dataProvider providerAddress
   * @dataProvider providerArrayOfAddresses
   * @dataProvider providerAssociativeAddressArray
   * @covers ::mimeMailAddress
   */
  public function testAddress($address, $result, $simplified_result) {
    // Test not simplified.
    $formatted = MimeMailFormatHelper::mimeMailAddress($address, $simplify = FALSE);
    $this->assertSame($result, $formatted);

    // Test simplified.
    $formatted = MimeMailFormatHelper::mimeMailAddress($address, $simplify = TRUE);
    $this->assertSame($simplified_result, $formatted);
  }

  /**
   * Provides test data for testAddress().
   *
   * Tests addresses provided as text email addresses, e.g. someone@example.com.
   */
  public function providerAddress() {
    // Format of each element is:
    // - address: Email address to test.
    // - result: Expected return value from
    //   MimeMailFormatHelper::mimeMailAddress($address, $simplify = FALSE).
    // - simplified_result: Expected return value from
    //   MimeMailFormatHelper::mimeMailAddress($address, $simplify = TRUE).
    $addresses = [
      'Encoded display-name' => [
        '=?utf-8?Q?Drupal=20Supporters?= <support@association.drupal.org>',
        '=?utf-8?Q?Drupal=20Supporters?= <support@association.drupal.org>',
        'support@association.drupal.org',
      ],
      'Display-name needing quotes' => [
        'Acme Industries, Inc. <no-reply@acme.example.com>',
        '"Acme Industries, Inc." <no-reply@acme.example.com>',
        'no-reply@acme.example.com',
      ],
      'UTF-8 display-name' => [
        '山田太郎 <taro@example.com>',
        '=?UTF-8?B?5bGx55Sw5aSq6YOO?= <taro@example.com>',
        'taro@example.com',
      ],
      'No display-name' => [
        'alpher@example.com',
        'alpher@example.com',
        'alpher@example.com',
      ],
      'No display-name, address between < and >' => [
        '<tr@202830.no-reply.drupal.org>',
        '<tr@202830.no-reply.drupal.org>',
        'tr@202830.no-reply.drupal.org',
      ],
    ];

    return $addresses;
  }

  /**
   * Provides test data for testAddress().
   *
   * Tests addresses provided as an array of text email addresses.
   */
  public function providerArrayOfAddresses() {
    // Format of each element is:
    // - address: Array of email addresses to test.
    // - result: Expected return value from
    //   MimeMailFormatHelper::mimeMailAddress($address, $simplify = FALSE).
    // - simplified_result: Expected return value from
    //   MimeMailFormatHelper::mimeMailAddress($address, $simplify = TRUE).
    $addresses = [
      'Array of address strings' => [
        [
          '=?utf-8?Q?Drupal=20Supporters?= <support@association.drupal.org>',
          'Acme Industries, Inc. <no-reply@acme.example.com>',
          '山田太郎 <taro@example.com>',
          'bethe@example.com',
          '<subscriber@example.com>',
        ],
        [
          '=?utf-8?Q?Drupal=20Supporters?= <support@association.drupal.org>',
          '"Acme Industries, Inc." <no-reply@acme.example.com>',
          '=?UTF-8?B?5bGx55Sw5aSq6YOO?= <taro@example.com>',
          'bethe@example.com',
          '<subscriber@example.com>',
        ],
        [
          'support@association.drupal.org',
          'no-reply@acme.example.com',
          'taro@example.com',
          'bethe@example.com',
          'subscriber@example.com',
        ],
      ],
    ];

    return $addresses;
  }

  /**
   * Provides test data for testAddress().
   *
   * Tests addresses provided as associative arrays containing keys:
   * - mail: A text email address, as above.
   * - (optional) name: A text name to accompany the email address,
   *   e.g. 'John Doe'.
   */
  public function providerAssociativeAddressArray() {
    // Format of each element is:
    // - address: Associative array of addresses, with 'mail' and 'name' keys.
    // - result: Expected return value from
    //   MimeMailFormatHelper::mimeMailAddress($address, $simplify = FALSE).
    // - simplified_result: Expected return value from
    //   MimeMailFormatHelper::mimeMailAddress($address, $simplify = TRUE).
    $addresses = [
      'Encoded display-name in array' => [
        ['name' => '=?utf-8?Q?Drupal=20Supporters?=', 'mail' => 'support@association.drupal.org'],
        '=?utf-8?Q?Drupal=20Supporters?= <support@association.drupal.org>',
        'support@association.drupal.org',
      ],
      'Display-name needing quotes in array' => [
        ['name' => 'Acme Industries, Inc.', 'mail' => 'no-reply@acme.example.com'],
        '"Acme Industries, Inc." <no-reply@acme.example.com>',
        'no-reply@acme.example.com',
      ],
      'UTF-8 display-name in array' => [
        ['name' => '山田太郎', 'mail' => 'taro@example.com'],
        '=?UTF-8?B?5bGx55Sw5aSq6YOO?= <taro@example.com>',
        'taro@example.com',
      ],
      'No display-name' => [
        ['name' => '', 'mail' => 'gamow@example.com'],
        'gamow@example.com',
        'gamow@example.com',
      ],
    ];

    return $addresses;
  }

  /**
   * Tests MimeMailFormatHelper::mimeMailAddress() with user objects.
   *
   * Tests addresses provided as fully loaded objects implementing
   * \Drupal\user\UserInterface. This can't be done in a data provider
   * function because the User module will not be set up at the time
   * the data provider is executed.
   *
   * @covers ::mimeMailAddress
   */
  public function testAddressUserObject() {
    // Format of each element is:
    // - address: Instance of a User object containing an email field.
    // - result: Expected return value from
    //   MimeMailFormatHelper::mimeMailAddress($address, $simplify = FALSE).
    // - simplified_result: Expected return value from
    //   MimeMailFormatHelper::mimeMailAddress($address, $simplify = TRUE).
    $addresses = [
      'User name with spaces' => [
        $this->adminUser,
        'username with spaces <admin@test.example.com>',
        'admin@test.example.com',
      ],
      'User name with capital letters' => [
        $this->userA,
        'CapitaL <namea@example.com>',
        'namea@example.com',
      ],
      'Random user name' => [
        $this->userB,
        $this->userB->getAccountName() . ' <nameb@example.com>',
        'nameb@example.com',
      ],
    ];

    foreach ($addresses as $address) {
      // Test not simplified.
      $formatted = MimeMailFormatHelper::mimeMailAddress($address[0], $simplify = FALSE);
      $this->assertSame($address[1], $formatted);

      // Test simplified.
      $formatted = MimeMailFormatHelper::mimeMailAddress($address[0], $simplify = TRUE);
      $this->assertSame($address[2], $formatted);
    }
  }

  /**
   * Tests helper function for formatting URLs.
   *
   * @param string $url
   *   URL to test.
   * @param bool $absolute
   *   Whether the URL is absolute.
   * @param string $expected
   *   URL after formatting.
   * @param string $message
   *   Description of the result we are expecting.
   *
   * @dataProvider providerTestUrl
   * @covers ::mimeMailUrl
   */
  public function testUrl($url, $absolute, $expected, $message) {
    $result = MimeMailFormatHelper::mimeMailUrl($url, $absolute);
    $this->assertSame($result, $expected, $message);
  }

  /**
   * Provides test data for testUrl().
   */
  public function providerTestUrl() {
    // Format of each element is:
    // - url: URL to test.
    // - absolute: Whether the URL is absolute.
    // - expected: URL after formatting.
    // - message: Description of the result we are expecting.
    return [
      [
        '#',
        FALSE,
        '#',
        'Hash mark URL without fragment left intact.',
      ],
      [
        '/sites/default/files/styles/thumbnail/public/image.jpg?itok=Wrl6Qi9U',
        TRUE,
        '/sites/default/files/styles/thumbnail/public/image.jpg',
        'Security token removed from styled image URL.',
      ],
      [
        $expected = 'public://' . $this->randomMachineName() . ' ' . $this->randomMachineName() . '.' . $this->randomMachineName(3),
        TRUE,
        $expected,
        'Space in the filename of the attachment left intact.',
      ],
    ];
  }

  /**
   * Tests the regular expression for extracting the mail address.
   *
   * @covers ::mimeMailHeaders
   */
  public function testHeaders() {
    $chars = ['-', '.', '+', '_'];
    $name = $this->randomString();
    $local = $this->randomMachineName() . $chars[array_rand($chars)] . $this->randomMachineName();
    $domain = $this->randomMachineName() . '-' . $this->randomMachineName() . '.' . $this->randomMachineName(rand(2, 4));
    $headers = MimeMailFormatHelper::mimeMailHeaders([], "$name <$local@$domain>");
    $result = $headers['Return-Path'];
    $expected = "<$local@$domain>";
    $this->assertSame($result, $expected, 'Return-Path header field correctly set.');
  }

}
