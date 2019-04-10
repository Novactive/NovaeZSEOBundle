<?php
/**
 * NovaeZSEOBundle Extension.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class NovaeZSEOExtension extends Extension implements PrependExtensionInterface
{
    public function getAlias(): string
    {
        return 'nova_ezseo';
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('assetic', ['bundles' => ['NovaeZSEOBundle']]);

        $configs = [
            'ez_field_templates.yml'          => 'ezpublish',
            'variations.yml'                  => 'ezpublish',
            'admin_ui/ez_field_templates.yml' => 'ezpublish',
        ];

        foreach ($configs as $fileName => $extensionName) {
            $configFile = __DIR__.'/../Resources/config/'.$fileName;
            $config     = Yaml::parse(file_get_contents($configFile));
            $container->prependExtensionConfig($extensionName, $config);
            $container->addResource(new FileResource($configFile));
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('default_settings.yml');
        $loader->load('admin_ui/services.yml');

        $processor = new ConfigurationProcessor($container, 'nova_ezseo');
        $processor->mapSetting('fieldtype_metas_identifier', $config);
        $processor->mapSetting('fieldtype_metas', $config);
        $processor->mapSetting('google_verification', $config);
        $processor->mapSetting('google_gatracker', $config);
        $processor->mapSetting('google_anonymizeIp', $config);
        $processor->mapSetting('bing_verification', $config);
        $processor->mapSetting('limit_to_rootlocation', $config);
        $processor->mapConfigArray('fieldtype_metas', $config, ContextualizerInterface::MERGE_FROM_SECOND_LEVEL);
        $processor->mapConfigArray('default_metas', $config);
        $processor->mapConfigArray('default_links', $config);
        $processor->mapConfigArray('sitemap_excludes', $config, ContextualizerInterface::MERGE_FROM_SECOND_LEVEL);
        $processor->mapConfigArray('robots_disallow', $config);
    }
}
