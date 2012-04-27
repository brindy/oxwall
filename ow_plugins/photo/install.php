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

if ( !$config->configExists('photo', 'accepted_filesize') )
{
    $config->addConfig('photo', 'accepted_filesize', 32, 'Maximum accepted file size');
}

if ( !$config->configExists('photo', 'main_image_width') )
{
    $config->addConfig('photo', 'main_image_width', 609, 'Main image width');
}

if ( !$config->configExists('photo', 'main_image_height') )
{
    $config->addConfig('photo', 'main_image_height', 500, 'Main image height');
}

if ( !$config->configExists('photo', 'preview_image_width') )
{
    $config->addConfig('photo', 'preview_image_width', 140, 'Preview image width');
}

if ( !$config->configExists('photo', 'preview_image_height') )
{
    $config->addConfig('photo', 'preview_image_height', 140, 'Preview image height');
}

if ( !$config->configExists('photo', 'photos_per_page') )
{
    $config->addConfig('photo', 'photos_per_page', 20, 'Photos per page');
}

if ( !$config->configExists('photo', 'album_quota') )
{
    $config->addConfig('photo', 'album_quota', 400, 'Maximum number of photos per album');
}

if ( !$config->configExists('photo', 'user_quota') )
{
    $config->addConfig('photo', 'user_quota', 5000, 'Maximum number of photos per user');
}

if ( !$config->configExists('photo', 'store_fullsize') )
{
    $config->addConfig('photo', 'store_fullsize', 1, 'Store full-size photos');
}

if ( !$config->configExists('photo', 'uninstall_inprogress') )
{
    $config->addConfig('photo', 'uninstall_inprogress', 0, 'Plugin is being uninstalled');
}

if ( !$config->configExists('photo', 'uninstall_cron_busy') )
{
    $config->addConfig('photo', 'uninstall_cron_busy', 0, 'Uninstall queue is busy');
}

if ( !$config->configExists('photo', 'maintenance_mode_state') )
{
    $state = (int) $config->getValue('base', 'maintenance');
    $config->addConfig('photo', 'maintenance_mode_state', $state, 'Stores site maintenance mode config before plugin uninstallation');
}

if ( !$config->configExists('photo', 'advanced_upload_enabled') )
{
    $config->addConfig('photo', 'advanced_upload_enabled', 1, 'Enables advanced multiple file flash uploader');
}

if ( !$config->configExists('photo', 'fullsize_resolution') )
{
    $config->addConfig('photo', 'fullsize_resolution', 1024, 'Full-size photo resolution');
}


$dbPref = OW_DB_PREFIX;

$sql = "CREATE TABLE IF NOT EXISTS `".$dbPref."photo` (
  `id` int(11) NOT NULL auto_increment,
  `albumId` int(11) NOT NULL,
  `description` text,
  `addDatetime` int(10) default NULL,
  `status` enum('approval','approved','blocked') NOT NULL default 'approved',
  `hasFullsize` tinyint(1) NOT NULL default '1',
  `privacy` varchar(50) NOT NULL default 'everybody',
  PRIMARY KEY  (`id`),
  KEY `albumId` (`albumId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `".$dbPref."photo_album` (
  `id` int(11) NOT NULL auto_increment,
  `userId` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `createDatetime` int(10) default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `".$dbPref."photo_featured` (
  `id` int(11) NOT NULL auto_increment,
  `photoId` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `".OW_DB_PREFIX."photo_temporary` (
  `id` int(11) NOT NULL auto_increment,
  `userId` int(11) NOT NULL,
  `addDatetime` int(11) NOT NULL,
  `hasFullsize` tinyint(1) NOT NULL default '0',
  `order` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

OW::getPluginManager()->addPluginSettingsRouteName('photo', 'photo_admin_config');
OW::getPluginManager()->addUninstallRouteName('photo', 'photo_uninstall');

$authorization = OW::getAuthorization();
$groupName = 'photo';
$authorization->addGroup($groupName);
$authorization->addAction($groupName, 'upload');
$authorization->addAction($groupName, 'view', true);
$authorization->addAction($groupName, 'add_comment');
$authorization->addAction($groupName, 'delete_comment_by_content_owner');

$path = OW::getPluginManager()->getPlugin('photo')->getRootDir() . 'langs.zip';
OW::getLanguage()->importPluginLangs($path, 'photo');
