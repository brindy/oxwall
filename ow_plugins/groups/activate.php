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
$navigation = OW::getNavigation();

$navigation->addMenuItem(
    OW_Navigation::MAIN,
    'groups-index',
    'groups',
    'main_menu_list',
    OW_Navigation::VISIBLE_FOR_ALL);



$widgetService = BOL_ComponentAdminService::getInstance();

$widget = $widgetService->addWidget('GROUPS_CMP_UserGroupsWidget', false);
$widgetPlace = $widgetService->addWidgetToPlace($widget, BOL_ComponentService::PLACE_PROFILE);
$widgetService->addWidgetToPosition($widgetPlace, BOL_ComponentService::SECTION_LEFT);

$widget = $widgetService->addWidget('GROUPS_CMP_UserGroupsWidget', false);
$widgetPlace = $widgetService->addWidgetToPlace($widget, BOL_ComponentService::PLACE_DASHBOARD);
$widgetService->addWidgetToPosition($widgetPlace, BOL_ComponentService::SECTION_RIGHT);

$widget = $widgetService->addWidget('GROUPS_CMP_GroupsWidget', false);
$widgetPlace = $widgetService->addWidgetToPlace($widget, BOL_ComponentService::PLACE_INDEX);
$widgetService->addWidgetToPosition($widgetPlace, BOL_ComponentService::SECTION_LEFT);

$event = new OW_Event('feed.install_widget', array(
    'place' => 'group',
    'section' => BOL_ComponentService::SECTION_RIGHT,
    'order' => 0
));

OW::getEventManager()->trigger($event);

if ( OW::getConfig()->getValue('groups', 'is_forum_connected') )
{
    if ( !OW::getConfig()->configExists('groups', 'restore_groups_forum') )
    {
        OW::getConfig()->addConfig('groups', 'restore_groups_forum', 1);
    }
}
OW::getEventManager()->trigger($event);


require_once dirname(__FILE__) . DS .  'classes' . DS . 'credits.php';
$credits = new GROUPS_CLASS_Credits();
$credits->triggerCreditActionsAdd();