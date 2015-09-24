<?php
/**
 * NovaeZSEOBundle Bundle
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */
namespace Novactive\Bundle\eZSEOBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Novactive\Bundle\eZSEOBundle\DependencyInjection\Compiler\CustomFallbackPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class NovaeZSEOBundle
 */
class NovaeZSEOBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build( ContainerBuilder $container )
    {
        parent::build( $container );

        $container->addCompilerPass( new CustomFallbackPass() );
    }
}
