<?php

/**
 * NovaeZSEOBundle Metas list provider for platform UI
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */
namespace Novactive\Bundle\eZSEOBundle\ApplicationConfig\Providers\PlatformUI;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\PlatformUIBundle\ApplicationConfig\Provider;

class SeoMetas implements Provider
{
    /** @var  ConfigResolverInterface */
    protected $configResolver;

    /**
     * NovaEzSEOMetas constructor.
     * @param ConfigResolverInterface $configResolver
     */
    public function __construct(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    /**
     * @return mixed Anything that is serializable via json_encode()
     */
    public function getConfig()
    {
        $list = [];
        $metas = $this->configResolver->getParameter('fieldtype_metas', 'nova_ezseo');
        foreach ($metas as $metaIdentifier => $meta) {
            $meta['identifier'] = $metaIdentifier;
            $list[] = $meta;
        }
        return $list;
    }
}
