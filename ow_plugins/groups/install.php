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
$plugin = OW::getPluginManager()->getPlugin('groups');

$dbPrefix = OW_DB_PREFIX;

$sql =
    <<<EOT
CREATE TABLE IF NOT EXISTS `{$dbPrefix}groups_group` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `imageHash` varchar(32) default NULL,
  `timeStamp` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `privacy` varchar(100) NOT NULL default 'everybody',
  `whoCanView` varchar(100) NOT NULL default 'anyone',
  `whoCanInvite` varchar(100) NOT NULL default 'participant',
  PRIMARY KEY  (`id`),
  KEY `timeStamp` (`timeStamp`),
  KEY `userId` (`userId`),
  KEY `whoCanView` (`whoCanView`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE `{$dbPrefix}groups_group_user` (
  `id` int(11) NOT NULL auto_increment,
  `groupId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `timeStamp` int(11) NOT NULL,
  `privacy` varchar(100) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `groupId` (`groupId`,`userId`),
  KEY `timeStamp` (`timeStamp`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;


CREATE TABLE IF NOT EXISTS `{$dbPrefix}groups_invite` (
  `id` int(11) NOT NULL auto_increment,
  `groupId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `inviterId` int(11) NOT NULL,
  `timeStamp` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `inviteUniq` (`groupId`,`userId`,`inviterId`),
  KEY `timeStamp` (`timeStamp`),
  KEY `userId` (`userId`),
  KEY `groupId` (`groupId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `{$dbPrefix}base_place` (`id`, `name`, `editableByUser`) VALUES (4, 'group', 1);
EOT;

OW::getDbo()->query($sql);

OW::getPluginManager()->addPluginSettingsRouteName('groups', 'groups-admin-widget-panel');
OW::getPluginManager()->addUninstallRouteName('groups', 'groups-admin-uninstall');

// Add widgets
$widgetService = BOL_ComponentAdminService::getInstance();

$widget = $widgetService->addWidget('GROUPS_CMP_JoinButtonWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, 'group');
$widgetService->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_LEFT);

$widget = $widgetService->addWidget('GROUPS_CMP_BriefInfoWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, 'group');
$widgetService->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_TOP);

$widget = $widgetService->addWidget('GROUPS_CMP_UserListWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, 'group');
$widgetService->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_LEFT);

$widget = $widgetService->addWidget('GROUPS_CMP_LeaveButtonWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, 'group');
$widgetService->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_LEFT);

$widget = $widgetService->addWidget('GROUPS_CMP_WallWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, 'group');
$widgetService->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_RIGHT);

$widget = $widgetService->addWidget('GROUPS_CMP_InviteWidget', false);
$widgetPlace = $widgetService->addWidgetToPlace($widget, 'group');
$widgetService->addWidgetToPosition($widgetPlace, BOL_ComponentService::SECTION_LEFT);

$widget = $widgetService->addWidget('BASE_CMP_CustomHtmlWidget', true);
$placeWidget = $widgetService->addWidgetToPlace($widget, 'group');

$widget = $widgetService->addWidget('BASE_CMP_RssWidget', true);
$placeWidget = $widgetService->addWidgetToPlace($widget, 'group');

BOL_LanguageService::getInstance()->importPrefixFromZip($plugin->getRootDir() . 'langs.zip', 'groups');

$authorization = OW::getAuthorization();
$groupName = 'groups';
$authorization->addGroup($groupName);

$authorization->addAction($groupName, 'add_comment');
$authorization->addAction($groupName, 'create');
$authorization->addAction($groupName, 'delete_comment_by_content_owner');
$authorization->addAction($groupName, 'view', true);


$config = OW::getConfig();

if ( !$config->configExists('groups', 'is_forum_connected') )
{
    OW::getConfig()->addConfig('groups', 'is_forum_connected', 0, 'Add Forum to Groups plugin');
}