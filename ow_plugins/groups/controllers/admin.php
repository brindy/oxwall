<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Group Admin
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.groups.controllers
 * @since 1.0
 */
class GROUPS_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    public function getMenu()
    {
        $item[0] = new BASE_MenuItem(array());

        $item[0]->setLabel(OW::getLanguage()->text('groups', 'general_settings'));
        $item[0]->setIconClass('ow_ic_dashboard');
        $item[0]->setKey('1');

        $item[0]->setUrl(
            OW::getRouter()->urlForRoute('groups-admin-widget-panel')
        );

        $item[0]->setOrder(1);

        $item[1] = new BASE_MenuItem(array());

        $item[1]->setLabel(OW::getLanguage()->text('groups', 'additional_features'));
        $item[1]->setIconClass('ow_ic_files');
        $item[1]->setKey('2');
        $item[1]->setUrl(
            OW::getRouter()->urlForRoute('groups-admin-additional-features')
        );

        $item[1]->setOrder(2);

        return new BASE_CMP_ContentMenu($item);
    }

    public function panel()
    {
        
        if ( OW::getConfig()->getValue('groups', 'restore_groups_forum') )
        {
            // Add forum section
            $event = new OW_Event('forum.create_section', array('name' => 'Groups', 'entity' => 'groups', 'isHidden' => true));
            OW::getEventManager()->trigger($event);

            // Add widget
            $event = new OW_Event('forum.add_widget', array('place' => 'group', 'section' => BOL_ComponentAdminService::SECTION_RIGHT));
            OW::getEventManager()->trigger($event);

            $groupsService = GROUPS_BOL_Service::getInstance();

            $groupList = $groupsService->findGroupList('all');
            foreach ( $groupList as $group )
            {
                // Add forum group
                $event = new OW_Event('forum.create_group', array('entity' => 'groups', 'name' => $group->title, 'description' => $group->description, 'entityId' => $group->getId()));
                OW::getEventManager()->trigger($event);
            }

            OW::getConfig()->saveConfig('groups', 'restore_groups_forum', 0);
        }


        $componentService = BOL_ComponentAdminService::getInstance();

        $this->setPageHeading(OW::getLanguage()->text('groups', 'widgets_panel_heading'));
        $this->setPageHeadingIconClass('ow_ic_dashboard');

        $place = GROUPS_BOL_Service::WIDGET_PANEL_NAME;

        $dbSettings = $componentService->findAllSettingList();

        $dbPositions = $componentService->findAllPositionList($place);

        $dbComponents = $componentService->findPlaceComponentList($place);
        $activeScheme = $componentService->findSchemeByPlace($place);
        $schemeList = $componentService->findSchemeList();

        if ( empty($activeScheme) && !empty($schemeList) )
        {
            $activeScheme = reset($schemeList);
        }

        $componentPanel = new ADMIN_CMP_DragAndDropAdminPanel($place, $dbComponents);
        $componentPanel->setPositionList($dbPositions);
        $componentPanel->setSettingList($dbSettings);
        $componentPanel->setSchemeList($schemeList);


        if ( !empty($activeScheme) )
        {
            $componentPanel->setScheme($activeScheme);
        }

        $menu = $this->getMenu();

        $this->addComponent('menu', $menu);

        $this->assign('componentPanel', $componentPanel->render());
    }

    public function connect_forum()
    {
        $config = OW::getConfig();
        $language = OW::getLanguage();

        if ( $_GET['isForumConnected'] === 'yes' && !OW::getConfig()->getValue('groups', 'is_forum_connected') )
        {
            // Add forum section
            $event = new OW_Event('forum.create_section', array('name' => 'Groups', 'entity' => 'groups', 'isHidden' => true));
            OW::getEventManager()->trigger($event);

            // Add widget
            $event = new OW_Event('forum.add_widget', array('place' => 'group', 'section' => BOL_ComponentAdminService::SECTION_RIGHT));
            OW::getEventManager()->trigger($event);

            $groupsService = GROUPS_BOL_Service::getInstance();

            $groupList = $groupsService->findGroupList('all');
            foreach ( $groupList as $group )
            {
                // Add forum group
                $event = new OW_Event('forum.create_group', array('entity' => 'groups', 'name' => $group->title, 'description' => $group->description, 'entityId' => $group->getId()));
                OW::getEventManager()->trigger($event);
            }

            $config->saveConfig('groups', 'is_forum_connected', 1);
            OW::getFeedback()->info($language->text('groups', 'forum_connected'));
        }

        $redirectURL = OW::getRouter()->urlForRoute('groups-admin-widget-panel');
        $this->redirect($redirectURL);
    }

    public function additional()
    {
        $this->setPageHeading(OW::getLanguage()->text('groups', 'widgets_panel_heading'));
        $this->setPageHeadingIconClass('ow_ic_dashboard');

        $is_forum_connected = OW::getConfig()->getValue('groups', 'is_forum_connected');

        if ( OW::getPluginManager()->isPluginActive('forum') || $is_forum_connected )
        {
            $this->assign('isForumConnected', $is_forum_connected);
            $this->assign('isForumAvailable', true);
        }
        else
        {
            $this->assign('isForumAvailable', false);
        }

        $menu = $this->getMenu();
        $this->addComponent('menu', $menu);
    }

    public function uninstall()
    {
        $config = OW::getConfig();

        if ( !$config->configExists('groups', 'uninstall_inprogress') )
        {
            $config->addConfig('groups', 'uninstall_inprogress', 0);
        }

        if ( isset($_POST['action']) && $_POST['action'] == 'delete_content' )
        {
            $config->saveConfig('groups', 'uninstall_inprogress', 1);
            OW::getFeedback()->info(OW::getLanguage()->text('groups', 'plugin_set_for_uninstall'));

            OW::getApplication()->setMaintenanceMode(true);

            $this->redirect();
        }

        $this->setPageHeading(OW::getLanguage()->text('groups', 'page_title_uninstall'));
        $this->setPageHeadingIconClass('ow_ic_delete');

        $inprogress = $config->getValue('groups', 'uninstall_inprogress');
        $this->assign('inprogress', $inprogress);

        $js = new UTIL_JsGenerator();
        $js->jQueryEvent('#btn-delete-content', 'click', 'if ( !confirm("' . OW::getLanguage()->text('groups', 'confirm_delete_groups') . '") ) return false;');

        OW::getDocument()->addOnloadScript($js);
    }
}