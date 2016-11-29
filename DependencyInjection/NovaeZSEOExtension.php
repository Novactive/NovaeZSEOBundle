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

use Symfony\Component\Config\Resource\FileResource;
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
        $container->prependExtensionConfig('assetic', array('bundles' => array('NovaeZSEOBundle')));

        $config = Yaml::parse( __DIR__ . '/../Resources/config/ez_field_templates.yml' );
        $container->prependExtensionConfig( 'ezpublish', $config );
        $config_variations = Yaml::parse( __DIR__ . '/../Resources/config/variations.yml' );
        $container->prependExtensionConfig( 'ezpublish', $config_variations );

        $this->prependYui($container);
        $this->prependCss($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function prependYui(ContainerBuilder $container)
    {
        $container->setParameter(
            'ezseobundle.public_dir',
            'bundles/novaezseo'
        );
        $yuiConfigFile = __DIR__ . '/../Resources/config/yui.yml';
        $config = Yaml::parse(file_get_contents($yuiConfigFile));
        $container->prependExtensionConfig('ez_platformui', $config);
        $container->addResource(new FileResource($yuiConfigFile));
    }

    /**
     * @param ContainerBuilder $container
     */
    private function prependCss(ContainerBuilder $container)
    {
        $container->setParameter(
            'ezseobundle.public_dir',
            'bundles/novaezseo'
        );
        $cssConfigFile = __DIR__ . '/../Resources/config/css.yml';
        $config = Yaml::parse(file_get_contents($cssConfigFile));
        $container->prependExtensionConfig('ez_platformui', $config);
        $container->addResource(new FileResource($cssConfigFile));
    }

    /**
     * {@inheritdoc}
     */
    public function load( array $configs, ContainerBuilder $container )
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration( $configuration, $configs );

        $loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__ . '/../Resources/config' ) );
        $loader->load( 'services.yml' );
        $loader->load( 'fieldtypes.yml' );
        $loader->load( 'default_settings.yml' );

        $processor = new ConfigurationProcessor( $container, 'novae_zseo' );
        $processor->mapSetting( 'fieldtype_metas_identifier', $config );
        $processor->mapSetting( 'fieldtype_metas', $config );
        $processor->mapSetting( 'google_verification', $config );
        $processor->mapSetting( 'google_gatracker', $config );
        $processor->mapConfigArray( 'fieldtype_metas', $config, ContextualizerInterface::MERGE_FROM_SECOND_LEVEL );
        $processor->mapConfigArray( 'default_metas', $config );
        $processor->mapConfigArray( 'default_links', $config );
        $processor->mapConfigArray( 'sitemap_excludes', $config, ContextualizerInterface::MERGE_FROM_SECOND_LEVEL );
        $processor->mapConfigArray( 'robots_disallow', $config );
    }
}
