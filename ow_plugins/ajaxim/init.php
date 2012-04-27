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

OW::getRouter()->addRoute(new OW_Route('ajaxim_settings', 'admin/ajaxim/settings', 'AJAXIM_CTRL_Admin', 'settings'));


// add IM item to member console
function ajaxim_add_console_item( BASE_CLASS_EventCollector $e )
{
    if ( !OW::getUser()->isAuthenticated() || !OW::getConfig()->getValue('ajaxim', 'is_configured') || !OW::getAuthorization()->isUserAuthorized(OW::getUser()->getId(), 'ajaxim', 'chat') )
    {
        return;
    }

    $e->add(
        array(
            BASE_CMP_Console::DATA_KEY_URL => 'javascript://',
            BASE_CMP_Console::DATA_KEY_ICON_CLASS => 'new_mail ow_ic_chat',
            BASE_CMP_Console::DATA_KEY_BLOCK_CLASS => 'main_im_tab_container',
            BASE_CMP_Console::DATA_KEY_BLOCK => true,
            BASE_CMP_Console::DATA_KEY_ID => 'main_im_tab',
            BASE_CMP_Console::DATA_KEY_ITEMS_LABEL => OW::getLanguage()->text('ajaxim', 'chat')
        )
    );
}
OW::getEventManager()->bind(BASE_CMP_Console::EVENT_NAME, 'ajaxim_add_console_item');

//--

function ajaxim_plugin_init()
{
    if ( !OW::getUser()->isAuthenticated() || !OW::getConfig()->getValue('ajaxim', 'is_configured') || !OW::getAuthorization()->isUserAuthorized(OW::getUser()->getId(), 'ajaxim', 'chat')  )
    {
        return;
    }
    $friendsPluginActive = OW::getEventManager()->call('plugin.friends');

    $im_toolbar = new AJAXIM_CMP_Toolbar();
    OW::getDocument()->appendBody($im_toolbar->render());
}
OW::getEventManager()->bind(OW_EventManager::ON_FINALIZE, 'ajaxim_plugin_init');

function ajaxim_add_auth_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(
        array(
            'ajaxim' => array(
                'label' => $language->text('ajaxim', 'auth_group_label'),
                'actions' => array(
                    'chat' => $language->text('ajaxim', 'auth_action_label_chat')
                )
            )
        )
    );
}
OW::getEventManager()->bind('admin.add_auth_labels', 'ajaxim_add_auth_labels');


function ajaxim_privacy_add_action( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $action = array(
        'key' => 'ajaxim_invite_to_chat',
        'pluginKey' => 'ajaxim',
        'label' => $language->text('ajaxim', 'privacy_action_invite_to_chat'),
        'description' => '',
        'defaultValue' => 'everybody'
    );
    $event->add($action);
}
OW::getEventManager()->bind('plugin.privacy.get_action_list', 'ajaxim_privacy_add_action');

function ajaxim_online_now_button( OW_Event $event )
{
    $params = $event->getParams();

    if ( !empty($params['userId']) && OW::getAuthorization()->isUserAuthorized($params['userId'], 'ajaxim', 'chat') )
    {
        return true;
    }
    else
    {
        return false;
    }
}
OW::getEventManager()->bind('base.online_now_click', 'ajaxim_online_now_button');