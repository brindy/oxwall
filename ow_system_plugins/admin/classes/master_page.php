<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * Master page class for admin controllers.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.admin.classes
 * @since 1.0
 */
class ADMIN_CLASS_MasterPage extends OW_MasterPage
{

    /**
     * @see OW_MasterPage::init()
     */
    protected function init()
    {
        $language = OW::getLanguage();

        OW::getThemeManager()->setCurrentTheme(BOL_ThemeService::getInstance()->getThemeObjectByName(BOL_ThemeService::DEFAULT_THEME));

        $menuTypes = array(
            BOL_NavigationService::MENU_TYPE_ADMIN, BOL_NavigationService::MENU_TYPE_APPEARANCE, BOL_NavigationService::MENU_TYPE_PRIVACY,
            BOL_NavigationService::MENU_TYPE_PAGES, BOL_NavigationService::MENU_TYPE_PLUGINS, BOL_NavigationService::MENU_TYPE_SETTINGS,
            BOL_NavigationService::MENU_TYPE_USERS
        );

        $menuItems = BOL_NavigationService::getInstance()->findMenuItemsForMenuList($menuTypes);

        $khzArray = array();

        $menu = new ADMIN_CMP_AdminMenu($menuItems[BOL_NavigationService::MENU_TYPE_ADMIN]);
        $this->addMenu(BOL_NavigationService::MENU_TYPE_ADMIN, $menu);
        $this->addComponent('menu_admin', $menu);
        $menuItem = $menu->getFirstElement();
        $khzArray['menu_admin'] = array('url' => ( $menuItem === null ? null : $menuItem->getUrl() ));

        $menu = new ADMIN_CMP_AdminMenu($menuItems[BOL_NavigationService::MENU_TYPE_APPEARANCE]);
        $this->addMenu(BOL_NavigationService::MENU_TYPE_APPEARANCE, $menu);
        $this->addComponent('menu_appearance', $menu);
        $menuItem = $menu->getFirstElement();
        $khzArray['menu_appearance'] = array('url' => ( $menuItem === null ? null : $menuItem->getUrl() ));

        $menu = new ADMIN_CMP_AdminMenu($menuItems[BOL_NavigationService::MENU_TYPE_PRIVACY]);
        $this->addMenu(BOL_NavigationService::MENU_TYPE_PRIVACY, $menu);
        $this->addComponent('menu_privacy', $menu);
        $menuItem = $menu->getFirstElement();

        $menu->onBeforeRender();
        $khzArray['menu_privacy'] = array('url' => ( $menuItem === null ? null : $menuItem->getUrl() ));

        $menu = new ADMIN_CMP_AdminMenu($menuItems[BOL_NavigationService::MENU_TYPE_PAGES]);
        $this->addMenu(BOL_NavigationService::MENU_TYPE_PAGES, $menu);
        $this->addComponent('menu_pages', $menu);
        $menuItem = $menu->getFirstElement();
        $khzArray['menu_pages'] = array('url' => ( $menuItem === null ? null : $menuItem->getUrl() ));

        $menu = new ADMIN_CMP_AdminMenu($menuItems[BOL_NavigationService::MENU_TYPE_PLUGINS]);
        $this->addMenu(BOL_NavigationService::MENU_TYPE_PLUGINS, $menu);
        $this->addComponent('menu_plugins', $menu);
        $menuItem = $menu->getFirstElement();
        $khzArray['menu_plugins'] = array('url' => ( $menuItem === null ? null : $menuItem->getUrl() ));

        $menu = new ADMIN_CMP_AdminMenu($menuItems[BOL_NavigationService::MENU_TYPE_SETTINGS]);
        $this->addMenu(BOL_NavigationService::MENU_TYPE_SETTINGS, $menu);
        $this->addComponent('menu_settings', $menu);
        $menuItem = $menu->getFirstElement();
        $khzArray['menu_settings'] = array('url' => ( $menuItem === null ? null : $menuItem->getUrl() ));

        $menu = new ADMIN_CMP_AdminMenu($menuItems[BOL_NavigationService::MENU_TYPE_USERS]);
        $this->addMenu(BOL_NavigationService::MENU_TYPE_USERS, $menu);
        $this->addComponent('menu_users', $menu);
        $menuItem = $menu->getFirstElement();

        $khzArray['menu_users'] = array('url' => ( $menuItem === null ? null : $menuItem->getUrl() ));

        $this->assign('back_to_site_url', OW_URL_HOME);
        $this->assign('globalMenuData', $khzArray);
        $this->assign('activeMenuName', 'fake');

        // admin notifications
        $adminNotifications = array();

        if ( !defined('OW_PLUGIN_XP') && OW::getConfig()->getValue('base', 'update_soft') )
        {
            $adminNotifications[] = $language->text('admin', 'notification_soft_update', array('link' => OW::getRouter()->urlForRoute('admin_core_update_request')));
        }

        $pluginsCount = BOL_PluginService::getInstance()->getPluginsToUpdateCount();

        if ( !defined('OW_PLUGIN_XP') && $pluginsCount > 0 )
        {
            $adminNotifications[] = $language->text('admin', 'notification_plugins_to_update', array('link' => OW::getRouter()->urlForRoute('admin_plugins_installed'), 'count' => $pluginsCount));
        }

        $event = new BASE_CLASS_EventCollector('admin.add_admin_notification');
        OW::getEventManager()->trigger($event);

        $adminNotifications = array_merge($adminNotifications, $event->getData());

        $this->assign('notifications', $adminNotifications);

        $adminWarnings = array();

        if ( !defined('OW_PLUGIN_XP') && OW::getConfig()->configExists('base', 'cron_is_active') && (int) OW::getConfig()->getValue('base', 'cron_is_active') === 0 )
        {
            $adminWarnings[] = $language->text('admin', 'warning_cron_is_not_active', array('path' => OW_DIR_ROOT . 'ow_cron' . DS . 'run.php'));
        }

        $event = new BASE_CLASS_EventCollector('admin.add_admin_warning');
        OW::getEventManager()->trigger($event);

        $adminWarnings = array_merge($adminWarnings, $event->getData());
        $this->assign('warnings', $adminWarnings);

        // platform info
        $verString = OW::getLanguage()->text('admin', 'soft_version', array('version' => OW::getConfig()->getValue('base', 'soft_version'), 'build' => OW::getConfig()->getValue('base', 'soft_build')));
        $this->assign('version', OW::getConfig()->getValue('base', 'soft_version'));
        $this->assign('build', OW::getConfig()->getValue('base', 'soft_build'));
        $this->assign('softVersion', $verString);
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $this->setTemplate(OW::getThemeManager()->getMasterPageTemplate(OW_MasterPage::TEMPLATE_ADMIN));

        foreach ( $this->components as $key => $cmp )
        {
            /* @var $cmp ADMIN_CMP_AdminMenu */
            if ( $cmp instanceof ADMIN_CMP_AdminMenu )
            {
                $cmp->onBeforeRender();

                if ( $cmp->isActive() )
                {
                    $this->assign('activeMenuName', $key);

                    if ( $cmp->getElementsCount() > 1 )
                    {
                        $this->addComponent('submenu', $cmp);
                    }
                }

                if ( $cmp->getElementsCount() < 2 || $cmp->isActive() )
                {
                    unset($this->components[$key]);
                }
            }
        }
    }
}