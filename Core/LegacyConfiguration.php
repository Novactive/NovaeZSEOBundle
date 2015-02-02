<?php
/**
 * NovaeZSEOBundle Legacy Injection
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core;

use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LegacyConfiguration
 */
class LegacyConfiguration implements EventSubscriberInterface
{
    /**
     * ConfigResolver useful to get the config
     *
     * @var ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * Constructor
     *
     * @param ConfigResolverInterface $configResolver
     */
    public function __construct( ConfigResolverInterface $configResolver )
    {
        $this->configResolver = $configResolver;
    }

    /**
     * Subscribe events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            LegacyEvents::PRE_BUILD_LEGACY_KERNEL => array( "onBuildKernel", 128 )
        );
    }

    /**
     * Adds settings to the parameters that will be injected into the legacy kernel
     *
     * @param PreBuildKernelEvent $event
     */
    public function onBuildKernel( PreBuildKernelEvent $event )
    {
        $settings['novaseo.ini/Settings/Metas'] = $this->configResolver->getParameter( 'fieldtype_metas', 'novae_zseo' );
        $event->getParameters()->set(
            "injected-settings",
            $settings + (array)$event->getParameters()->get( "injected-settings" )
        );
    }
}
