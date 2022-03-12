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

namespace Novactive\Bundle\eZSEOBundle\Listener;

use Ibexa\AdminUi\Menu\Event\ConfigureMenuEvent;

class AdminTopMenu
{
    public function onMenuConfigure(ConfigureMenuEvent $event): void
    {
        $menu = $event->getMenu();
        $menu->addChild(
            'nova_create_redirect'
        )
            ->setLabel('menu.main_menu.header')
            ->setExtra('translation_domain', 'redirect')
            ->setExtra('bottom_item', true)
            ->setExtra('icon', 'move')
            ->setExtra('orderNumber', 150)
            ->setAttribute('data-tooltip-placement', 'right')
            ->setAttribute('data-tooltip-extra-class', 'ibexa-tooltip--info-neon');

        $contentMenu = $menu['nova_create_redirect'];

        $contentMenu
            ->addChild(
                'nova_create_redirect_list',
                [
                    'route' => 'novaseo_redirect_list',
                ]
            )
            ->setLabel('menu.main_menu.list')
            ->setExtra('translation_domain', 'redirect');

        $contentMenu
            ->addChild(
                'nova_import_redirect_url',
                [
                  'route' => 'novactive_platform_admin_ui.import-redirect-url',
                ]
            )
            ->setLabel('menu.main_menu.import')
            ->setExtra('translation_domain', 'redirect');

        $contentMenu
            ->addChild(
                'nova_history_import_redirect_url',
                [
                    'route' => 'novactive_platform_admin_ui.history-import-redirect-url',
                ]
            )
            ->setLabel('menu.main_menu.history.import')
            ->setExtra('translation_domain', 'redirect')
        ;
    }
}
