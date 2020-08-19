<?php

/**
 * NovaeZSEOBundle Bundle.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

declare(strict_types=1);

namespace Novactive\Bundle\eZSEOBundle\Tests;

use Novactive\eZPlatform\Bundles\Tests\BrowserHelper;
use Novactive\eZPlatform\Bundles\Tests\PantherTestCase;

class SEOControllerPantherTest extends PantherTestCase
{
    public function testRobotTxt(): void
    {
        $helper = new BrowserHelper($this->getPantherClient());
        $helper->get('/robots.txt');
        $source = $helper->client()->getPageSource();

        $this->assertStringContainsString('User-agent: *', $source);
        $this->assertStringContainsString('Disallow: /', $source);
    }

    public function testGoogleVerification(): void
    {
        $helper = new BrowserHelper($this->getPantherClient());
        $helper->get('/googleplop2test42.html');
        $source = $helper->client()->getPageSource();

        $this->assertStringContainsString('google-site-verification', $source);
        $this->assertStringContainsString('googleplop2test42.html', $source);
    }

    public function testBingSiteAuth(): void
    {
        $helper = new BrowserHelper($this->getPantherClient());
        $crawler = $helper->get('/BingSiteAuth.xml');
        $this->assertEquals(1, $crawler->filter('users')->count());
    }
}
