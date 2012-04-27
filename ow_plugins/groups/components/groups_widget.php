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
 * Group List Widget
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.groups.components
 * @since 1.0
 */
class GROUPS_CMP_GroupsWidget extends BASE_CLASS_Widget
{
    /**
     *
     * @var GROUPS_BOL_Service
     */
    private $service;

    /**
     * @return Constructor.
     */
    public function __construct( BASE_CLASS_WidgetParameter $paramObj )
    {
        parent::__construct();

        $this->service = GROUPS_BOL_Service::getInstance();

        $num = isset($paramObj->customParamList['count']) ? (int) $paramObj->customParamList['count'] : 8;

        $this->assign('showTitles', !empty($paramObj->customParamList['showTitles']));

        $latest = $this->service->findGroupList(GROUPS_BOL_Service::LIST_LATEST, 0, $num);
        $popular = $this->service->findGroupList(GROUPS_BOL_Service::LIST_MOST_POPULAR, 0, $num);

        $toolbars = self::getToolbar();

        $lang = OW::getLanguage();
        $menuItems = array();

        if ( $this->assignList('latest', $latest) )
        {
            $this->setSettingValue(self::SETTING_TOOLBAR, $toolbars['latest']);
            $menuItems[] = array(
                'label' => $lang->text('groups', 'group_list_menu_item_latest'),
                'id' => 'groups-widget-menu-latest',
                'contId' => 'groups-widget-latest',
                'active' => true
            );
        }

        if ( $this->assignList('popular', $popular) )
        {
            $menuItems[] = array(
                'label' => $lang->text('groups', 'group_list_menu_item_popular'),
                'id' => 'groups-widget-menu-popular',
                'contId' => 'groups-widget-popular',
                'active' => empty($menuItems)
            );
        }

        if ( empty($menuItems) && !$this->service->isCurrentUserCanCreate() )
        {
            $this->setVisible(false);

            return;
        }

        $this->assign('menuItems', $menuItems);

        if ( $paramObj->customizeMode )
        {
            $this->assign('menu', '');
        }
        else
        {
            $this->addComponent('menu', new BASE_CMP_WidgetMenu($menuItems));
        }

        $this->assign('toolbars', $toolbars);
        $this->assign('createUrl', OW::getRouter()->urlForRoute('groups-create'));
    }

    private function assignList( $listName, $list )
    {
        $tplList = array();
        foreach ( $list as $item )
        {
            /* @var $item GROUPS_BOL_Group */
            $tplList[] = array(
                'image' => $this->service->getGroupImageUrl($item),
                'title' => htmlspecialchars($item->title),
                'url' => OW::getRouter()->urlForRoute('groups-view', array('groupId' => $item->id)),
                'users' => $this->service->findUserListCount($item->id)
            );
        }

        $this->assign($listName, $tplList);

        return!empty($tplList);
    }

    private static function getToolbar()
    {
        $lang = OW::getLanguage();

        $toolbars['latest'] = array();
        $canCreate = GROUPS_BOL_Service::getInstance()->isCurrentUserCanCreate();

        if ( $canCreate )
        {
            $toolbars['latest'][] = array(
                'href' => OW::getRouter()->urlForRoute('groups-create'),
                'label' => $lang->text('groups', 'add_new')
            );
        }

        $toolbars['latest'][] = array(
            'href' => OW::getRouter()->urlForRoute('groups-latest'),
            'label' => $lang->text('base', 'view_all')
        );

        $toolbars['popular'] = array();

        if ( $canCreate )
        {
            $toolbars['popular'][] = array(
                'href' => OW::getRouter()->urlForRoute('groups-create'),
                'label' => $lang->text('groups', 'add_new')
            );
        }

        $toolbars['popular'][] = array(
            'href' => OW::getRouter()->urlForRoute('groups-most-popular'),
            'label' => $lang->text('base', 'view_all')
        );

        return $toolbars;
    }

    public static function getSettingList()
    {
        $settingList = array();

        $settingList['count'] = array(
            'presentation' => self::PRESENTATION_NUMBER,
            'label' => OW::getLanguage()->text('groups', 'widget_groups_count_setting'),
            'value' => 3
        );

        $settingList['showTitles'] = array(
            'presentation' => self::PRESENTATION_CHECKBOX,
            'label' => OW::getLanguage()->text('groups', 'widget_groups_show_titles_setting'),
            'value' => true
        );

        return $settingList;
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_TITLE => OW::getLanguage()->text('groups', 'widget_groups_title'),
            self::SETTING_ICON => self::ICON_COMMENT,
            self::SETTING_SHOW_TITLE => true
        );
    }
}