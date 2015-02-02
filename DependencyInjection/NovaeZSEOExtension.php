<?php
/**
 * NovaeZSEOBundle Extension
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */
namespace Novactive\Bundle\eZSEOBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Yaml\Yaml;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class NovaeZSEOExtension extends Extension implements PrependExtensionInterface
{

    /**
     * Add configuration
     *
     * @param ContainerBuilder $container
     */
    public function prepend( ContainerBuilder $container )
    {
        $config = Yaml::parse( __DIR__ . '/../Resources/config/ez_field_templates.yml' );
        $container->prependExtensionConfig( 'ezpublish', $config );
    }

    /**
     * {@inheritdoc}
     */
    public function load( array $configs, ContainerBuilder $container )
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration( $configuration, $configs );

        $loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__ . '/../Resources/config' ) );
        $loader->load( 'services.yml' );
        $loader->load( 'fieldtypes.yml' );
        $loader->load( 'default_settings.yml' );

        $processor = new ConfigurationProcessor( $container, 'novae_zseo' );
        $processor->mapConfig(
            $config,
            function ( $scopeSettings, $currentScope, ContextualizerInterface $contextualizer )
            {
                $contextualizer->setContextualParameter( 'fieldtype_metas', $currentScope, $scopeSettings['fieldtype_metas'] );
                $contextualizer->setContextualParameter( 'google_verification', $currentScope, $scopeSettings['google_verification'] );
                $contextualizer->setContextualParameter( 'robots_disallow', $currentScope, $scopeSettings['robots_disallow'] );
            }
        );
    }
}
