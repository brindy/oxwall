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
OW::getRouter()->addRoute(new OW_Route('virtual_gifts_templates', 'admin/plugin/virtual-gifts/templates', 'VIRTUALGIFTS_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('virtual_gifts_categories', 'admin/plugin/virtual-gifts/categories', 'VIRTUALGIFTS_CTRL_Admin', 'categories'));
OW::getRouter()->addRoute(new OW_Route('virtual_gifts_uninstall', 'admin/plugin/virtual-gifts/uninstall', 'VIRTUALGIFTS_CTRL_Admin', 'uninstall'));
OW::getRouter()->addRoute(new OW_Route('virtual_gifts_private_list', 'virtual-gifts/my-gifts', 'VIRTUALGIFTS_CTRL_MyGifts', 'index'));
OW::getRouter()->addRoute(new OW_Route('virtual_gifts_user_list', 'virtual-gifts/user/:userName', 'VIRTUALGIFTS_CTRL_Gifts', 'userGifts'));
OW::getRouter()->addRoute(new OW_Route('virtual_gifts_view_gift', 'virtual-gifts/view/:giftId', 'VIRTUALGIFTS_CTRL_Gifts', 'view'));

function virtualgifts_send_gift_action_tool( BASE_CLASS_EventCollector $event )
{
    if ( !OW::getUser()->isAuthenticated() )
    {
        return;
    }

    $params = $event->getParams();

    if ( empty($params['userId']) || $params['userId'] == OW::getUser()->getId() )
    {
        return;
    }

    $linkId = 'gi' . rand(10, 1000000);
    $user = BOL_UserService::getInstance()->getUserName((int) $params['userId']);

    if ( BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), (int) $params['userId']) )
    {
        $script = "\$('#" . $linkId . "').click(function(){
            
            window.OW.error('" . OW::getLanguage()->text('base', 'user_block_message') . "');
            
        });";

        OW::getDocument()->addOnloadScript($script);
    }
    else
    {

        $script = '$("#' . $linkId . '").click(function(){
        sendGiftFloatBox = OW.ajaxFloatBox("VIRTUALGIFTS_CMP_SendGift", {recipientId: ' . $params['userId'] . ' } , {width:580, iconClass: "ow_ic_heart", title: "' . OW::getLanguage()->text('virtualgifts', 'send_gift_to', array('user' => $user)) . '"});
    });';

        OW::getDocument()->addOnloadScript($script);
    }

    $resultArray = array(
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LABEL => OW::getLanguage()->text('virtualgifts', 'profile_toolbar_item_send_gift'),
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_HREF => 'javascript://',
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_ID => $linkId
    );

    $event->add($resultArray);
}
OW::getEventManager()->bind(BASE_CMP_ProfileActionToolbar::EVENT_NAME, 'virtualgifts_send_gift_action_tool');

function virtualgifts_on_notify_actions( BASE_CLASS_EventCollector $e )
{
    $e->add(array(
        'section' => 'virtualgifts',
        'action' => 'virtualgifts-send_gift',
        'sectionIcon' => 'ow_ic_heart',
        'sectionLabel' => OW::getLanguage()->text('virtualgifts', 'email_notifications_section_label'),
        'description' => OW::getLanguage()->text('virtualgifts', 'email_notifications_setting_send_gift'),
        'selected' => true
    ));
}
OW::getEventManager()->bind('base.notify_actions', 'virtualgifts_on_notify_actions');

function virtualgifts_on_send_gift( OW_Event $e )
{
    $params = $e->getParams();

    $giftId = (int) $params['giftId'];
    $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();

    if ( !$giftId || !$gift = $giftService->findUserGiftById($giftId) )
    {
        return;
    }

    $userService = BOL_UserService::getInstance();
    $giftUrl = OW::getRouter()->urlForRoute('virtual_gifts_view_gift', array('giftId' => $giftId));

    $event = new OW_Event('base.notify', array(
            'plugin' => 'virtualgifts',
            'action' => 'virtualgifts-send_gift',
            'userId' => $params['recipientId'],
            'string' => OW::getLanguage()->text('virtualgifts', 'email_notifications_send_gift', array(
                'senderName' => $userService->getDisplayName($params['senderId']),
                'senderUrl' => $userService->getUserUrl($params['senderId']),
                'giftUrl' => $giftUrl
            )),
            'content' => !empty($gift['dto']->message) ? $gift['dto']->message : '',
            'time' => time(),
            'url' => $giftUrl,
            'avatar' => $gift['imageUrl']
        ));

    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind('virtualgifts.send_gift', 'virtualgifts_on_send_gift');


$credits = new VIRTUALGIFTS_CLASS_Credits();
OW::getEventManager()->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));

function virtualgifts_after_inits()
{
    // Add user credits actions on first init
    if ( !OW::getConfig()->getValue('virtualgifts', 'is_once_initialized') )
    {
        if ( OW::getConfig()->configExists('virtualgifts', 'is_once_initialized') )
        {
            OW::getConfig()->saveConfig('virtualgifts', 'is_once_initialized', 1);
        }
        else
        {
            OW::getConfig()->addConfig('virtualgifts', 'is_once_initialized', 1);
        }

        $credits = new VIRTUALGIFTS_CLASS_Credits();
        $credits->triggerCreditActionsAdd();
    }
}
OW::getEventManager()->bind(OW_EventManager::ON_APPLICATION_INIT, 'virtualgifts_after_inits');

function virtualgifts_feed_entity_add( OW_Event $e )
{
    $params = $e->getParams();
    $data = $e->getData();

    if ( $params['entityType'] != 'user_gift' )
    {
        return;
    }

    $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
    $gift = $giftService->findUserGiftById($params['entityId']);

    if ( !$gift )
    {
        return;
    }

    $url = OW::getRouter()->urlForRoute('virtual_gifts_view_gift', array('giftId' => $params['entityId']));
    $userService = BOL_UserService::getInstance();

    $params = array(
        'recipientName' => $userService->getDisplayName($gift['dto']->recipientId),
        'recipientUrl' => $userService->getUserUrl($gift['dto']->recipientId)
    );

    $message = htmlspecialchars($gift['dto']->message);

    $data = array(
        'ownerId' => $gift['dto']->senderId,
        'string' => OW::getLanguage()->text('virtualgifts', 'feed_string', $params),
        'content' => '<div class="clearfix"><div class="ow_newsfeed_item_picture">
            <a href="' . $url . '"><img src="' . $gift['imageUrl'] . '" width="80" /></a>
        </div><div class="ow_newsfeed_item_content" style="padding: 5px;">' . $message . '</div></div>',
        'view' => array(
            'iconClass' => 'ow_ic_heart'
        )
    );

    $e->setData($data);
}
OW::getEventManager()->bind('feed.on_entity_add', 'virtualgifts_feed_entity_add');

function virtualgifts_ads_enabled( BASE_EventCollector $event )
{
    $event->add('virtualgifts');
}
OW::getEventManager()->bind('ads.enabled_plugins', 'virtualgifts_ads_enabled');

function virtualgifts_add_auth_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(
        array(
            'virtualgifts' => array(
                'label' => $language->text('virtualgifts', 'auth_group_label'),
                'actions' => array(
                    'send_gift' => $language->text('virtualgifts', 'auth_action_label_send_gift')
                )
            )
        )
    );
}
OW::getEventManager()->bind('admin.add_auth_labels', 'virtualgifts_add_auth_labels');

function virtualgifts_feed_collect_configurable_activity( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(array(
        'label' => $language->text('virtualgifts', 'feed_content_gift'),
        'activity' => 'create:user_gift'
    ));
}
OW::getEventManager()->bind('feed.collect_configurable_activity', 'virtualgifts_feed_collect_configurable_activity');