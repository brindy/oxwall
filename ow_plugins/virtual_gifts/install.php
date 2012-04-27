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

if ( !$config->configExists('virtualgifts', 'uninstall_inprogress') )
{
    $config->addConfig('virtualgifts', 'uninstall_inprogress', 0, 'Plugin is being uninstalled');
}

if ( !$config->configExists('virtualgifts', 'uninstall_cron_busy') )
{
    $config->addConfig('virtualgifts', 'uninstall_cron_busy', 0, 'Uninstall queue is busy');
}

if ( !$config->configExists('virtualgifts', 'maintenance_mode_state') )
{
    $state = (int) $config->getValue('base', 'maintenance');
    $config->addConfig('virtualgifts', 'maintenance_mode_state', $state, 'Stores site maintenance mode config before plugin uninstallation');
}


$sql = "CREATE TABLE IF NOT EXISTS `".OW_DB_PREFIX."virtualgifts_category` (
  `id` int(11) NOT NULL auto_increment,
  `order` int(11) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `".OW_DB_PREFIX."virtualgifts_template` (
  `id` int(11) NOT NULL auto_increment,
  `categoryId` int(11) default NULL,
  `extension` varchar(10) NOT NULL,
  `uploadTimestamp` int(11) NOT NULL default '0',
  `price` int(10) default '0',
  PRIMARY KEY  (`id`),
  KEY `categoryId` (`categoryId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `".OW_DB_PREFIX."virtualgifts_user_gift` (
  `id` int(11) NOT NULL auto_increment,
  `templateId` int(11) NOT NULL,
  `senderId` int(11) NOT NULL,
  `recipientId` int(11) NOT NULL,
  `sendTimestamp` int(11) NOT NULL,
  `message` text,
  `private` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `senderId` (`senderId`),
  KEY `recipientId` (`recipientId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

try {
    $storage = OW::getStorage();
    $userfilesDir = OW_DIR_USERFILES . 'plugins' . DS . 'virtual_gifts' . DS;
    
    for ( $i = 1; $i <= 17; $i++ )
    {
        $defaultPath = dirname(__FILE__) . DS .  'static' . DS . 'default' . DS . 'gift' . $i . '.jpg';
        if ( !file_exists($defaultPath) )
        {
            continue;
        }
        
        $time = time();
        $sql = "INSERT INTO `".OW_DB_PREFIX."virtualgifts_template` (`extension`, `uploadTimestamp`) VALUES ('jpg', :ts)";
        $id = OW::getDbo()->insert($sql, array('ts' => $time));
        if ( $id )
        {
            $imagePath = $userfilesDir . 'gift_' . $id . '_' . $time . '.jpg';
            if ( !$storage->copyFile($defaultPath, $imagePath) )
            {
                $sql = "DELETE FROM `".OW_DB_PREFIX."virtualgifts_template` WHERE `id` = :id";
                OW::getDbo()->query($sql, array('id' => $id));
            }
        }
    }
}
catch ( Exception $e ) { }

OW::getPluginManager()->addPluginSettingsRouteName('virtualgifts', 'virtual_gifts_templates');
OW::getPluginManager()->addUninstallRouteName('virtualgifts', 'virtual_gifts_uninstall');

$authorization = OW::getAuthorization();
$groupName = 'virtualgifts';
$authorization->addGroup($groupName);
$authorization->addAction($groupName, 'send_gift');

$path = OW::getPluginManager()->getPlugin('virtualgifts')->getRootDir() . 'langs.zip';
OW::getLanguage()->importPluginLangs($path, 'virtualgifts');
