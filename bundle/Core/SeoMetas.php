<?php

/**
 * NovaeZSEOBundle Metas list provider for Admin UI.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core;

use EzSystems\EzPlatformAdminUi\UI\Config\ProviderInterface;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

class SeoMetas implements ProviderInterface
{
    /**
     * @var ConfigResolverInterface
     */
    protected $configResolver;

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
