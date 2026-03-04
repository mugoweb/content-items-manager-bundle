<?php

namespace MugoWeb\ContentItemsManagerBundle\EventListener;

use Ibexa\AdminUi\Menu\Event\ConfigureMenuEvent;
use Ibexa\AdminUi\Menu\MainMenuBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MenuListener implements EventSubscriberInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMenuEvent::MAIN_MENU => 'onMenuConfigure',
        ];
    }

    public function onMenuConfigure(ConfigureMenuEvent $event): void
    {
        $menu = $event->getMenu();
        $request = $this->requestStack->getCurrentRequest();
        // Check if the current route is your custom page
        if ($request && $request->attributes->get('_route') === 'mugo_content_items_list') {
            $contentMenu = $menu->getChild(MainMenuBuilder::ITEM_CONTENT);
            if ($contentMenu) {
                $contentGroupSettings = $contentMenu->getChild(MainMenuBuilder::ITEM_CONTENT_GROUP_SETTINGS);
                if ($contentGroupSettings) {
                    $contentTypesItem = $contentGroupSettings->getChild(MainMenuBuilder::ITEM_ADMIN__CONTENT_TYPES);
                    if ($contentTypesItem) {
                        // Mark the item as current. This triggers the 'active' CSS class
                        // and the 'expanded' state in Ibexa's JS-driven sidebar.
                        $contentTypesItem->setCurrent(true);
                    }
                }
            }
        }
    }
}