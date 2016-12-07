<?php
/**
 * NovaeZSEOBundle FunctionnalCrawlerTest
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Tests;

/**
 * Class FunctionalCrawlerTest
 */
class FunctionalCrawlerTest extends NovaeZSEOBundleTestCase
{
    /**
     * Test Default Metas
     */
    public function testDefaultMetasIndex()
    {
        $client  = static::createClient();
        $crawler = $client->request( 'GET', '/' );
        $this->assertTrue( $crawler->filter( 'html meta' )->count() > 0 );
        $container      = $client->getContainer();
        $configResolver = $container->get( "ezpublish.config.resolver" );
        $defaultMetas   = $configResolver->getParameter( "default_metas", "nova_ezseo" );
        foreach ( $defaultMetas as $metaName => $metaContent )
        {
            if ( !$metaContent )
            {
                continue;
            }
            $metaTagsHtml = $crawler->filter( "html meta[name='{$metaName}']" );
            $this->assertTrue( $metaTagsHtml->count() > 0, "Meta {$metaName} not found" );
            $findValue = false;
            foreach ( $metaTagsHtml as $meta )
            {
                if ( $meta->getAttribute( 'content' ) == $metaContent )
                {
                    $findValue = true;
                }
            }
            $this->assertTrue( $findValue, "Meta {$metaName} with content {$metaContent} not found." );
        }
    }

    /**
     * Test Default Links
     */
    public function testDefaultLinksIndex()
    {
        $client  = static::createClient();
        $crawler = $client->request( 'GET', '/' );
        $this->assertTrue( $crawler->filter( 'html meta' )->count() > 0 );
        $container      = $client->getContainer();
        $configResolver = $container->get( "ezpublish.config.resolver" );
        $defaultLinks = $configResolver->getParameter( "default_links", "nova_ezseo" );
        foreach ( $defaultLinks as $linkRel => $linkConfig )
        {
            $linkTagsHtml = $crawler->filter( "html link[rel='{$linkRel}']" );
            $this->assertTrue( $linkTagsHtml->count() > 0, "Link {$linkRel} not found" );
            $findValue = false;
            $title = isset( $linkConfig['title'] ) ? $linkConfig['title'] : false;
            $type = isset( $linkConfig['type'] ) ? $linkConfig['type'] : false;
            foreach ( $linkTagsHtml as $link )
            {
                if ( $title )
                {
                    if ( $link->getAttribute( 'title' ) == $title )
                    {
                        $findValue = true;
                    }
                }
                if ( $type )
                {
                    if ( $link->getAttribute( 'type' ) == $type )
                    {
                        $findValue = true;
                    }
                }
            }
            $this->assertTrue(
                $findValue,
                "Link {$linkRel} not found title:{$title} or type:{$type}."
            );
        }
    }

    /**
     * Test Default Robots.txt
     */
    public function testRobots()
    {
        $client = static::createClient();
        $client->request( 'GET', '/robots.txt' );
        $content = $client->getResponse()->getContent();
        $this->assertRegExp( "/User-agent: \\*/uis", $content );
        $this->assertRegExp( "/Disallow: \\//uis", $content );
    }

    /**
     * Test Default Google Verification
     */
    public function testGoogleVerification()
    {
        $client = static::createClient();
        $client->request( 'GET', '/google1234567890.html' );
        $content = $client->getResponse()->getContent();
        $this->assertEquals( $content, "google-site-verification: google1234567890.html" );
    }

    /**
     * Test Basics Sitemap.xml
     */
    public function testSitemap()
    {
        $client  = static::createClient();
        $crawler = $client->request( 'GET', '/sitemap.xml' );
        $count   = $crawler->filter( 'urlset > url' )->count();
        $this->assertTrue( $count > 0 );
        $this->assertEquals( $crawler->filter( 'urlset > url > loc ' )->count(), 1 );
        $this->assertEquals( $crawler->filter( 'urlset > url > lastmod ' )->count(), 1 );
        $lastmod = $crawler->filter( 'urlset > url' )->getNode( rand( 0, $count - 1 ) )->getElementsByTagName(
            'lastmod'
        )->item( 0 )->nodeValue;
        $this->assertRegExp( '/(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})/', $lastmod );
    }
}
