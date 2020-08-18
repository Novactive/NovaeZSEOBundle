<?php

/**
 * NovaeZSEOBundle CustomFallbackPass.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\DependencyInjection\Compiler;

use Novactive\Bundle\eZSEOBundle\Twig\NovaeZSEOExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CustomFallbackPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig('nova_ezseo');

        //@todo: How to do that by SiteAccess
        if (isset($configs[0]['system']['default']['custom_fallback_service'])) {
            $fallbackService = $configs[0]['system']['default']['custom_fallback_service'];
            if (null !== $fallbackService) {
                $container->getDefinition(NovaeZSEOExtension::class)->addMethodCall(
                    'setCustomFallbackService',
                    [new Reference($fallbackService)]
                );
            }
        }
    }
}
