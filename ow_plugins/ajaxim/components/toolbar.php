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
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow.ow_system_plugins.base.controllers
 * @since 1.0
 */
class AJAXIM_CMP_Toolbar extends OW_Component
{

    public function render()
    {
        if ( !OW::getConfig()->getValue('ajaxim', 'is_configured') )
        {
            $this->setVisible(false);
            return;
        }

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('ajaxim')->getStaticJsUrl() . 'audio-player.js');
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('ajaxim')->getStaticJsUrl() . 'ajaxim.js');
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('ajaxim')->getStaticCssUrl() . 'im.css');

//~~
        $node = OW::getUser()->getId();
        $username = BOL_UserService::getInstance()->getDisplayName($node);

        $avatar = BOL_AvatarService::getInstance()->getAvatarUrl(OW::getUser()->getId());
        if ( empty($avatar) )
        {
            $avatar = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
        }

        $active_list = AJAXIM_BOL_Service::getInstance()->getSessionActiveList();
        $roster_list = AJAXIM_BOL_Service::getInstance()->getSessionRosterList();

        foreach ( $roster_list as $roster )
        {
            $friendship = OW::getEventManager()->call('plugin.friends.check_friendship', array('userId' => OW::getUser()->getId(), 'friendId' => $roster->getId()));
            if ( ( empty($friendship) || $friendship->getStatus() == 'pending' ) && !isset($active_list[$roster->getId()]) )
            {
                AJAXIM_BOL_Service::getInstance()->deleteRoster(OW::getUser()->getId(), $roster->getId());
                unset($roster_list[$roster->getId()]);
            }
        }

        OW::getSession()->set('ajaxim.roster_list', $roster_list);
        $activeContact = '';
        $active_listJs = '[ ';

        if ( !empty($active_list) )
        {
            foreach ( $active_list as $contactName => $isActive )
            {
                $active_listJs .= "{$contactName}, ";
                if ( $isActive )
                {
                    $activeContact = $contactName;
                }
            }
        }

        if ( $active_listJs == '[ ' )
        {
            $active_listJs .= ' ]';
        }
        else
        {
            $active_listJs = substr($active_listJs, 0, -2) . ' ]';
        }
        $jsGenerator = UTIL_JsGenerator::newInstance();
        $jsGenerator->setVariable('ajaxim_activeContact', $activeContact);
        $jsGenerator->setVariable('ajaxim_oldTitle', OW::getDocument()->getTitle());
        $jsGenerator->setVariable('ajaxim_ping', 3000);
        //$jsGenerator->setVariable('ajaxim_ping', OW::getConfig()->getValue('ajaxim', 'ping_interval') * 1000);
        $jsGenerator->setVariable('ajaxim_sound_url', OW::getPluginManager()->getPlugin('ajaxim')->getStaticUrl() . 'sound/receive.mp3');
        $jsGenerator->setVariable('ajaxim_sound_swf', OW::getPluginManager()->getPlugin('ajaxim')->getStaticUrl() . 'js/player.swf');
        $this->assign('ajaxim_sound_url', OW::getPluginManager()->getPlugin('ajaxim')->getStaticUrl() . 'sound/receive.mp3');
        $jsGenerator->setVariable('ajaxim_sound_enabled', (bool) BOL_PreferenceService::getInstance()->getPreferenceValue('ajaxim_user_settings_enable_sound', OW::getUser()->getId()));

        $jsGenerator->setVariable('window.ajaxim_logMsgUrl', OW::getRouter()->urlFor('AJAXIM_CTRL_Action', 'logMsg'));
        $jsGenerator->setVariable('window.ajaxim_getLogUrl', OW::getRouter()->urlFor('AJAXIM_CTRL_Action', 'getLog'));
        $jsGenerator->setVariable('window.ajaxim_bindUrl', OW::getRouter()->urlFor('AJAXIM_CTRL_Action', 'bind'));
        $jsGenerator->setVariable('window.ajaxim_updateUserInfoUrl', OW::getRouter()->urlFor('AJAXIM_CTRL_Action', 'updateUserInfo'));

        $variables = $jsGenerator->generateJs();

        $ajaximMy = array(
            'node' => $node,
            'username' => $username,
            'avatar' => $avatar
        );

        OW::getDocument()->addScriptDeclaration("window.ajaxim_my = " . json_encode($ajaximMy) . ";\n ajaxim_active_list = $active_listJs;\n " . $variables);

        $userSettingsForm = AJAXIM_BOL_Service::getInstance()->getUserSettingsForm();
        $this->addForm($userSettingsForm);
        $userSettingsForm->getElement('user_id')->setValue(OW::getUser()->getId());
        OW::getDocument()->addOnloadScript("$('#user_settings').click(function(){window.ajaximUserSettingsForm = new OW_FloatBox({\$title:'" . OW::getLanguage()->text('ajaxim', 'user_settings_title') . "', \$contents: $('#user_settings_form'), width: '450px', height: '200px'})});");


        $avatar_proto_data = array('url' => 1, 'src' => BOL_AvatarService::getInstance()->getDefaultAvatarUrl(), 'class' => 'talk_box_avatar');
        $this->assign('no_avatar_url', BOL_AvatarService::getInstance()->getDefaultAvatarUrl());
        $this->assign('avatar_proto_data', $avatar_proto_data);
        $this->assign('friend_list_url', OW::getRouter()->urlForRoute('friends_list'));
        $this->assign('online_list_url', OW::getRouter()->urlForRoute('base_user_lists', array('list' => 'online')));

        $privacyPluginActive = OW::getEventManager()->call('plugin.privacy');
        $this->assign('privacyPluginActive', $privacyPluginActive);
        if ( $privacyPluginActive )
        {
            $this->assign('privacy_settings_url', OW::getRouter()->urlForRoute('privacy_index'));
        }

        /* Instant Chat DEBUG MODE */
        $jsGenerator->setVariable('im_debug_mode', false);
        $this->assign('debug_mode', false);

        return parent::render();
    }
}