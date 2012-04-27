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
$exArr = array();

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'ajaxim');

$moduleName = OW::getPluginManager()->getPlugin('ajaxim')->getModuleName();

try
{
    if ( !Updater::getConfigService()->configExists('ajaxim', 'friends_only') )
    {
        Updater::getConfigService()->addConfig('ajaxim', 'friends_only', 0);    
    }
}
catch ( Exception $e )
{
    $exArr[] = $e;
}

try
{
    Updater::getDbo()->update("REPLACE INTO `".OW_DB_PREFIX."base_preference_section` ( `name`, `sortOrder`) VALUES ('ajaxim', 0)");
}
catch ( Exception $e )
{
    $exArr[] = $e;
}
try
{
    Updater::getDbo()->update("REPLACE INTO `".OW_DB_PREFIX."base_preference` ( `key`, `defaultValue`, `sectionName`, `sortOrder`) VALUES ( 'ajaxim_user_settings_enable_sound', '1', 'ajaxim', 0 )");
}
catch ( Exception $e )
{
    $exArr[] = $e;
}
try
{
    OW::getPluginManager()->addPluginSettingsRouteName('ajaxim', 'ajaxim_settings');
}
catch ( Exception $e )
{
    $exArr[] = $e;
}
