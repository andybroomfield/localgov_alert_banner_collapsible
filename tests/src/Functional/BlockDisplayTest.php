<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_alert_banner_collapsible\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\block\Entity\Block;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test collapsible block displays when positioned.
 *
 * @group localgov_alert_banner_collapsible
 */
final class BlockDisplayTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'path',
    'options',
    'localgov_alert_banner',
    'localgov_alert_banner_collapsible',
  ];

  /**
   * Test callback.
   */
  public function testBlockDisplays(): void {
    $admin_user = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($admin_user);
    $block = $this->drupalPlaceBlock('localgov_alert_banner_collapsible');
    $this->drupalLogout();

    // Check hide link is shown when remove_hide_link is not set.
    $this->drupalGet('<front>');

    // Assert page displays ok.
    // @See https://github.com/localgovdrupal/localgov_alert_banner_collapsible/issues/21
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

}
