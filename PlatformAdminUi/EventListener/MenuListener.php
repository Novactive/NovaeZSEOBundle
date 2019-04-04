<?php

/**
 * NovaeZSEOBundle MenuListener or Admin UI
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2019 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\PlatformAdminUi\EventListener;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MainMenuBuilder;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuListener implements EventSubscriberInterface
{
    const ITEM_REDIRECT = 'main__redirect';

    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    protected $authorizationChecker;


    /**
     * MenuListener constructor.
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigureMenuEvent::MAIN_MENU => ['onMenuConfigure', 0],
        ];
    }

    /**
     * This method adds Redirect menu items to eZ Platform admin interface.
     *
     * @param ConfigureMenuEvent $event
     */
    public function onMenuConfigure(ConfigureMenuEvent $event)
    {
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        $this->addRedirectSubMenu($event->getMenu());
    }

    /**
     * Adds the Redirect submenu to eZ Platform admin interface.
     *
     * @param ItemInterface $menu
     * @return ItemInterface
     */
    public function addRedirectSubMenu(ItemInterface $menu)
    {
        // your customizations
        $menu = $menu[MainMenuBuilder::ITEM_CONTENT];
        $menu
            ->addChild('nova_create_redirect', [
                'route' => 'novactive_platform_admin_ui.list'
            ])
            ->setLabel('menu.main_menu.header')
            ->setExtra('translation_domain', 'redirect');

        return $menu;
    }
}
