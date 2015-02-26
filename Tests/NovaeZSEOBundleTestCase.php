<?php
/**
 * NovaeZSEOBundle NovaeZSEOBundle TestCase
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class NovaeZSEOBundleTestCase
 */
abstract class NovaeZSEOBundleTestCase extends WebTestCase
{
    /**
     * Force the env to behat
     *
     * @{@inheritdoc}
     */
    protected static function createClient( array $options = array(), array $server = array() )
    {
        $options['environment'] = 'behat';
        static::bootKernel( $options );
        $client = static::$kernel->getContainer()->get( 'test.client' );
        $client->setServerParameters( $server );

        return $client;
    }

    /**
     * Get the Input Stream
     *
     * @param string $input
     *
     * @return resource
     */
    protected function getInputStream( $input )
    {
        $stream = fopen( 'php://memory', 'r+', false );
        fputs( $stream, $input );
        rewind( $stream );

        return $stream;
    }
}
