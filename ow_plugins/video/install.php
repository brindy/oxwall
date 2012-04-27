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

$config = OW::getConfig();

if ( !$config->configExists('video', 'player_width') )
{
    $config->addConfig('video', 'player_width', 619, 'Main video player width');
}

if ( !$config->configExists('video', 'player_height') )
{
    $config->addConfig('video', 'player_height', 464, 'Main video player height');
}

if ( !$config->configExists('video', 'user_quota') )
{
    $config->addConfig('video', 'user_quota', 500, 'Maximum number of videos per user');
}

if ( !$config->configExists('video', 'videos_per_page') )
{
    $config->addConfig('video', 'videos_per_page', 20, 'Videos per page');
}

$dbPref = OW_DB_PREFIX;

$sql = "CREATE TABLE IF NOT EXISTS `".$dbPref."video_clip` (
  `id` int(11) NOT NULL auto_increment,
  `userId` int(11) NOT NULL,
  `code` text NOT NULL,
  `title` varchar(128) NOT NULL default '',
  `description` text,
  `addDatetime` int(11) NOT NULL default '0',
  `provider` varchar(32) NOT NULL default '',
  `status` enum('approval','approved','blocked') NOT NULL default 'approved',
  `privacy` varchar(50) NOT NULL default 'everybody',
  PRIMARY KEY  (`id`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `".$dbPref."video_clip_featured` (
  `id` int(11) NOT NULL auto_increment,
  `clipId` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

OW::getPluginManager()->addPluginSettingsRouteName('video', 'video_admin_config');

$authorization = OW::getAuthorization();
$groupName = 'video';
$authorization->addGroup($groupName);
$authorization->addAction($groupName, 'add');
$authorization->addAction($groupName, 'view', true);
$authorization->addAction($groupName, 'add_comment');
$authorization->addAction($groupName, 'delete_comment_by_content_owner');

$path = OW::getPluginManager()->getPlugin('video')->getRootDir() . 'langs.zip';
BOL_LanguageService::getInstance()->importPrefixFromZip($path, 'video');
