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

class SitemapControllerTest extends TestCase
{
    public function testSitemapIsXML(): void
    {
        $helper = new BrowserHelper($this->getPantherClient());
        $crawler = $helper->get('/sitemap.xml');
        $this->assertEquals(1, $crawler->filter('urlset')->count());
    }

    public function testSitemapPageIsXML(): void
    {
        $helper = new BrowserHelper($this->getPantherClient());
        $crawler = $helper->get('/sitemap-1.xml');
        $this->assertEquals(1, $crawler->filter('urlset')->count());
    }
}
