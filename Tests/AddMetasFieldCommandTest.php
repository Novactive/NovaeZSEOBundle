<?php
/**
 * NovaeZSEOBundle AddMetasFieldCommandTest
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */
namespace Novactive\Bundle\eZSEOBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Novactive\Bundle\eZSEOBundle\Command\AddNovaSEOMetasFieldTypeCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class AddMetasFieldCommandTest
 */
class AddMetasFieldCommandTest extends NovaeZSEOBundleTestCase
{
    /**
     * Run provider
     *
     * @return array
     */
    public function runProvider()
    {
        return [
            [ [ ], [ ], 'no' ],
            [ [ "--identifier" => "article" ], [ '/Selected Content Type/', '/Article/' ], "no" ],
            [ [ "--identifier" => "folder" ], [ '/Selected Content Type/', '/Folder/' ], "no" ],
            [ [ "--identifier" => "file" ], [ '/Selected Content Type/', '/File/' ], "no" ],
            [ [ "--identifiers" => "file,image,article" ], [ '/Selected Content Type/', '/File/', '/Image/', '/Article/' ], "no" ],
            [ [ "--group_identifier" => "Content" ], [ '/Selected Content Type/', '/Article/', '/Comment/' ], "no" ],
        ];
    }

    /**
     * Test different kind of arguments
     *
     * @param $arguments
     * @param $regexps
     * @param $interactAnswer
     * @dataProvider runProvider
     */
    public function testRunning( $arguments, $regexps, $interactAnswer )
    {
        $app = new Application( static::createClient()->getKernel() );
        $app->add( new AddNovaSEOMetasFieldTypeCommand() );
        $command = $app->find( 'novae_zseo:addnovaseometasfieldtype' );
        $tester  = new CommandTester( $command );
        $input   = [ 'command' => $command->getName() ];
        if ( count( $arguments ) > 0 ) {
            $input = array_merge( $input, $arguments );
        }
        $helper = $command->getHelper( 'question' );
        $helper->setInputStream( $this->getInputStream( $interactAnswer . '\\n' ) );
        $tester->execute( $input );
        foreach( $regexps as $regexp ) {
            $this->assertRegExp( $regexp, $tester->getDisplay() );
        }

        if ( $interactAnswer == "no" )
        {
            $this->assertRegExp( '/Nothing to do/', $tester->getDisplay() );
        }

        if ( $interactAnswer == "yes" )
        {
            $this->assertRegExp( '/FieldType added/', $tester->getDisplay() );
        }
    }
}
