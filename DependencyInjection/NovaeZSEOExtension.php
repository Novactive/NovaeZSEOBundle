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
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return "nova_ezseo";
    }

    /**
     * Add configuration
     *
     * @param ContainerBuilder $container
     */
    public function prepend( ContainerBuilder $container )
    {
        $container->prependExtensionConfig('assetic', array('bundles' => array('NovaeZSEOBundle')));

        $activatedBundles = array_keys($container->getParameter('kernel.bundles'));

        $configs = array(
            'ez_field_templates.yml' => 'ezpublish',
            'variations.yml' => 'ezpublish',
        );

         if (
            in_array('EzSystemsPlatformUIBundle', $activatedBundles, true) ||
            in_array('eZPlatformUIBundle', $activatedBundles, true)
        ) {
            $container->setParameter('ezseobundle.public_dir', 'bundles/novaezseo');

            $configs['platform_ui/yui.yml'] = 'ez_platformui';
            $configs['platform_ui/css.yml'] = 'ez_platformui';
        }

        if (in_array('EzPlatformAdminUiBundle', $activatedBundles, true)) {
            $configs['admin_ui/ez_field_templates.yml'] = 'ezpublish';
        }

        foreach ($configs as $fileName => $extensionName) {
            $configFile = __DIR__ . '/../Resources/config/' . $fileName;
            $config = Yaml::parse(file_get_contents($configFile));
            $container->prependExtensionConfig($extensionName, $config);
            $container->addResource(new FileResource($configFile));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load( array $configs, ContainerBuilder $container )
    {
        $activatedBundles = array_keys($container->getParameter('kernel.bundles'));

        $configuration = new Configuration();
        $config        = $this->processConfiguration( $configuration, $configs );

        $loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__ . '/../Resources/config' ) );
        $loader->load( 'services.yml' );
        $loader->load( 'fieldtypes.yml' );
        $loader->load( 'default_settings.yml' );

         if (
            in_array('EzSystemsPlatformUIBundle', $activatedBundles, true) ||
            in_array('eZPlatformUIBundle', $activatedBundles, true)
        ) {
            $loader->load('platform_ui/services.yml');
        }

        if (in_array('EzPlatformAdminUiBundle', $activatedBundles, true)) {
            $loader->load('admin_ui/services.yml');
        }

        $processor = new ConfigurationProcessor( $container, 'nova_ezseo' );
        $processor->mapSetting( 'fieldtype_metas_identifier', $config );
        $processor->mapSetting( 'fieldtype_metas', $config );
        $processor->mapSetting( 'google_verification', $config );
        $processor->mapSetting( 'google_gatracker', $config );
        $processor->mapSetting( 'google_anonymizeIp', $config );
        $processor->mapSetting( 'bing_verification', $config );
        $processor->mapSetting( 'is_sitemap_multi_rootlocation', $config );
        $processor->mapConfigArray( 'fieldtype_metas', $config, ContextualizerInterface::MERGE_FROM_SECOND_LEVEL );
        $processor->mapConfigArray( 'default_metas', $config );
        $processor->mapConfigArray( 'default_links', $config );
        $processor->mapConfigArray( 'sitemap_excludes', $config, ContextualizerInterface::MERGE_FROM_SECOND_LEVEL );
        $processor->mapConfigArray( 'robots_disallow', $config );
    }
}
