<?php

/**
 * NovaeZSEOBundle.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core;

use Doctrine\Bundle\DoctrineBundle\Mapping\ContainerEntityListenerResolver;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\Persistence\ManagerRegistry as Registry;
use Ibexa\Bundle\Core\ApiLoader\RepositoryConfigurationProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class SiteAccessAwareEntityManagerFactory
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var RepositoryConfigurationProvider
     */
    private $repositoryConfigurationProvider;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var ContainerEntityListenerResolver
     */
    private $resolver;

    public function __construct(
        Registry $registry,
        RepositoryConfigurationProvider $repositoryConfigurationProvider,
        ContainerEntityListenerResolver $resolver,
        array $settings
    ) {
        $this->registry = $registry;
        $this->repositoryConfigurationProvider = $repositoryConfigurationProvider;
        $this->settings = $settings;
        $this->resolver = $resolver;
    }

    private function getConnectionName(): string
    {
        $config = $this->repositoryConfigurationProvider->getRepositoryConfig();

        return $config['storage']['connection'] ?? 'default';
    }

    public function get(): EntityManagerInterface
    {
        $connectionName = $this->getConnectionName();
        // If it is the default connection then we don't bother we can directly use the default entity Manager
        if ('default' === $connectionName) {
            return $this->registry->getManager();
        }

        $connection = $this->registry->getConnection($connectionName);

        /** @var \Doctrine\DBAL\Connection $connection */
        $cache = new ArrayAdapter();
        $config = new Configuration();
        $config->setMetadataCacheImpl(DoctrineProvider::wrap($cache));
        $driverImpl = $config->newDefaultAnnotationDriver(__DIR__.'/../Entity', false);
        $config->setMetadataDriverImpl($driverImpl);
        $config->setQueryCacheImpl(DoctrineProvider::wrap($cache));
        $config->setProxyDir($this->settings['cache_dir'].'/eZSEOBundle/');
        $config->setProxyNamespace('eZSEOBundle\Proxies');
        $config->setAutoGenerateProxyClasses($this->settings['debug']);
        $config->setEntityListenerResolver($this->resolver);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());

        return EntityManager::create($connection, $config);
    }
}
