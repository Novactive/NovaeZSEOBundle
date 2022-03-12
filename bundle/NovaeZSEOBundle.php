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

namespace Novactive\Bundle\eZSEOBundle;

use LogicException;
use Novactive\Bundle\eZSEOBundle\DependencyInjection\Compiler\CustomFallbackPass;
use Novactive\Bundle\eZSEOBundle\DependencyInjection\Security\PolicyProvider\PolicyProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NovaeZSEOBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CustomFallbackPass());
        $eZExtension = $container->getExtension('ibexa');
        $eZExtension->addPolicyProvider(new PolicyProvider());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $extension = $this->createContainerExtension();

            if (null !== $extension) {
                if (!$extension instanceof ExtensionInterface) {
                    $fqdn = \get_class($extension);
                    $message = 'Extension %s must implement %s.';
                    throw new LogicException(sprintf($message, $fqdn, ExtensionInterface::class));
                }
                $this->extension = $extension;
            } else {
                $this->extension = false;
            }
        }

        if ($this->extension) {
            return $this->extension;
        }
    }
}
