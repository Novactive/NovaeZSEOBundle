<?php

/**
 * NovaeZSEOBundle MenuListener or Admin UI.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2019 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\PlatformAdminUi\EventListener;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MainMenuBuilder;

class AdminTopMenu implements EventSubscriberInterface
{
    public function onMenuConfigure(ConfigureMenuEvent $event): void
    {
        $menu        = $event->getMenu();
        $contentMenu = $menu[MainMenuBuilder::ITEM_CONTENT];
        $contentMenu
            ->addChild(
                'nova_create_redirect',
                [
                    'route' => 'novactive_platform_admin_ui.list',
                ]
            )
            ->setLabel('menu.main_menu.header')
            ->setExtra('translation_domain', 'redirect');

        return $menu;
    }
}
