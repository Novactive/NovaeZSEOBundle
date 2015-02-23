<?php
/**
 * NovaeZSEOBundle MetaTest Case
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Tests;

use Novactive\Bundle\eZSEOBundle\Core\Meta;

/**
 * Class MetaTest
 */
class MetaTest extends NovaeZSEOBundleTestCase
{

    /**
     * Provide some metas examples
     *
     * @return array
     */
    public function metasSampleProvider()
    {
        return [
            [ 'title', 'There is the title' ],
            [ 'description', 'There is the description' ],
            [ 'keywords', '' ],
        ];
    }

    /**
     * Test the Meta Method
     *
     * @param string $name
     * @param string $content
     *
     * @dataProvider metasSampleProvider
     */
    public function testMetaObject( $name, $content )
    {
        $meta = new Meta( $name, $content );

        $this->assertEquals( $name, $meta->getName() );
        $this->assertEquals( $content, $meta->getContent() );

        $this->assertEquals( $name, $meta->attribute( 'name' ) );
        $this->assertEquals( $content, $meta->attribute( 'content' ) );

        $this->assertTrue( $meta->hasAttribute( 'name' ) );
        $this->assertTrue( $meta->hasAttribute( 'content' ) );

        $this->assertTrue( ( $content == '' ) ? $meta->isEmpty() : !$meta->isEmpty() );
    }
}
