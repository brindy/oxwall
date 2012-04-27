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
$plugin = OW::getPluginManager()->getPlugin('ajaxim');

BOL_LanguageService::getInstance()->importPrefixFromZip($plugin->getRootDir() . 'langs.zip', 'ajaxim');

$dbPrefix = OW_DB_PREFIX;

$sql =
    <<<EOT
CREATE TABLE `{$dbPrefix}ajaxim_message` (
  `id` INTEGER(11) NOT NULL AUTO_INCREMENT,
  `from` INTEGER(11) NOT NULL,
  `to` INTEGER(11) NOT NULL,
  `message` VARCHAR(255) COLLATE utf8_general_ci DEFAULT NULL,
  `timestamp` INTEGER(11) NOT NULL,
  `read` INTEGER(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
)ENGINE=MyISAM
CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
EOT;

OW::getDbo()->query($sql);

$sql =
    <<<EOT
CREATE TABLE `{$dbPrefix}ajaxim_roster` (
  `id` INTEGER(11) NOT NULL AUTO_INCREMENT,
  `userId` INTEGER(11) NOT NULL,
  `rosterId` INTEGER(11) NOT NULL,
  PRIMARY KEY (`id`)
)ENGINE=MyISAM
CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';
EOT;

OW::getDbo()->query($sql);

$authorization = OW::getAuthorization();
$groupName = 'ajaxim';
$authorization->addGroup($groupName, false);

$authorization->addAction($groupName, 'chat');

OW::getConfig()->addConfig('ajaxim', 'is_configured', 1);
OW::getConfig()->addConfig('ajaxim', 'ping_interval', 3);

/* Update to 3555 */

OW::getConfig()->addConfig('ajaxim', 'friends_only', 0);

$ajaxim_user_preference_section = BOL_PreferenceService::getInstance()->findSection('ajaxim');
if ( empty($ajaxim_user_preference_section) )
{
    $ajaxim_user_preference_section = new BOL_PreferenceSection();
    $ajaxim_user_preference_section->name = 'ajaxim';
    $ajaxim_user_preference_section->sortOrder = 0;
    BOL_PreferenceService::getInstance()->savePreferenceSection($ajaxim_user_preference_section);
}

$ajaxim_user_preference = BOL_PreferenceService::getInstance()->findPreference('ajaxim_user_settings_enable_sound');
if ( empty($ajaxim_user_preference) )
{
    $ajaxim_user_preference = new BOL_Preference();
    $ajaxim_user_preference->key = 'ajaxim_user_settings_enable_sound';
    $ajaxim_user_preference->defaultValue = true;
    $ajaxim_user_preference->sectionName = 'ajaxim';
    $ajaxim_user_preference->sortOrder = 0;
    BOL_PreferenceService::getInstance()->savePreference($ajaxim_user_preference);
}

OW::getPluginManager()->addPluginSettingsRouteName('ajaxim', 'ajaxim_settings');