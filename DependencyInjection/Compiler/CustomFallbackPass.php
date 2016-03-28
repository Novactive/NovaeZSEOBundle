<?php
/**
 * NovaeZSEOBundle CustomFallbackPass
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */
namespace Novactive\Bundle\eZSEOBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class CustomFallbackPass
 */
class CustomFallbackPass implements CompilerPassInterface
{
    /**
     * Process the configuration
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig('novae_zseo');

        //@todo: How to do that by SiteAccess
        if (isset($configs[0]['system']['default']['custom_fallback_service'])) {
            $fallbackService = $configs[0]['system']['default']['custom_fallback_service'];
            if ($fallbackService !== null) {
                $container->getDefinition('novactive.novaseobundle.twig_extension')->addMethodCall(
                    "setCustomFallbackService",
                    [new Reference($fallbackService)]
                );
            }
        }
    }
}
