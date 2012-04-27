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

OW::getRouter()->addRoute(new OW_Route('newsfeed_admin_settings', 'admin/plugins/newsfeed', 'NEWSFEED_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('newsfeed_admin_customization', 'admin/plugins/newsfeed/customization', 'NEWSFEED_CTRL_Admin', 'customization'));

$eventHandler = NEWSFEED_CLASS_EventHandler::getInstance();

OW::getEventManager()->bind('feed.action', array($eventHandler, 'action'));
OW::getEventManager()->bind('feed.activity', array($eventHandler, 'activity'));

OW::getEventManager()->bind('feed.delete_activity', array($eventHandler, 'removeActivity'));
OW::getEventManager()->bind('feed.get_all_follows', array($eventHandler, 'getAllFollows'));
OW::getEventManager()->bind('feed.install_widget', array($eventHandler, 'installWidget'));
OW::getEventManager()->bind('feed.delete_item', array($eventHandler, 'deleteAction'));
OW::getEventManager()->bind('feed.get_status', array($eventHandler, 'getStatus'));
OW::getEventManager()->bind('feed.remove_follow', array($eventHandler, 'removeFollow'));
OW::getEventManager()->bind('feed.is_follow', array($eventHandler, 'isFollow'));
OW::getEventManager()->bind('feed.after_status_update', array($eventHandler, 'statusUpdate'));
OW::getEventManager()->bind('feed.after_like_added', array($eventHandler, 'addLike'));
OW::getEventManager()->bind('feed.after_like_removed', array($eventHandler, 'removeLike'));
OW::getEventManager()->bind('feed.add_follow', array($eventHandler, 'addFollow'));
OW::getEventManager()->bind('feed.on_entity_add', array($eventHandler, 'entityAdd'));
OW::getEventManager()->bind('feed.on_item_render', array($eventHandler, 'itemRender'));
OW::getEventManager()->bind('feed.on_activity', array($eventHandler, 'onActivity'));
OW::getEventManager()->bind('feed.after_activity', array($eventHandler, 'afterActivity'));

OW::getEventManager()->bind('feed.clear_cache', array($eventHandler, 'deleteActionSet'));

//OW::getEventManager()->bind('base.notify_actions', array($eventHandler, 'collectNotifyActions'));
OW::getEventManager()->bind('plugin.privacy.on_change_action_privacy', array($eventHandler, 'onPrivacyChange'));

OW::getEventManager()->bind('base_add_comment', array($eventHandler, 'addComment'));
OW::getEventManager()->bind('base_delete_comment', array($eventHandler, 'deleteComment'));
OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_PLUGIN_DEACTIVATE, array($eventHandler, 'onPluginDeactivate'));
OW::getEventManager()->bind(OW_EventManager::ON_AFTER_PLUGIN_ACTIVATE, array($eventHandler, 'onPluginActivate'));
OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_PLUGIN_UNINSTALL, array($eventHandler, 'onPluginUninstall'));
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, array($eventHandler, 'userUnregister'));
OW::getEventManager()->bind(OW_EventManager::ON_USER_BLOCK, array($eventHandler, 'userBlocked'));
OW::getEventManager()->bind(OW_EventManager::ON_PLUGINS_INIT, array($eventHandler, 'afterAppInit'));
//OW::getEventManager()->bind('base.on_get_user_status', array($eventHandler, 'getUserStatus'));

function newsfeed_on_collect_profile_actions( BASE_CLASS_EventCollector $event )
{
    $params = $event->getParams();
    $userId = $params['userId'];

    if ( OW::getUser()->getId() == $userId )
    {
        return;
    }

    $urlParams = array(
        'userId' => $userId,
        'backUri' => OW::getRouter()->getUri()
    );
    $linkId = 'follow' . rand(10, 1000000);

    if ( NEWSFEED_BOL_Service::getInstance()->isFollow(OW::getUser()->getId(), 'user', $userId) )
    {
        $url = OW::getRouter()->urlFor('NEWSFEED_CTRL_Feed', 'unFollow');
        $url = OW::getRequest()->buildUrlQueryString($url, $urlParams);
        $label = OW::getLanguage()->text('newsfeed', 'unfollow_button');
    }
    else
    {
        if ( BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $userId) )
        {
            $script = "\$('#" . $linkId . "').click(function(){

            window.OW.error('" . OW::getLanguage()->text('base', 'user_block_message') . "');

        });";

            OW::getDocument()->addOnloadScript($script);
            $url = 'javascript://';
        }
        else
        {
            $url = OW::getRouter()->urlFor('NEWSFEED_CTRL_Feed', 'follow');
            $url = OW::getRequest()->buildUrlQueryString($url, $urlParams);
        }

        $label = OW::getLanguage()->text('newsfeed', 'follow_button');
    }

    $event->add(array(
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LABEL => $label,
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_HREF => $url,
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_ID => $linkId
    ));
}
OW::getEventManager()->bind(BASE_CMP_ProfileActionToolbar::EVENT_NAME, 'newsfeed_on_collect_profile_actions');

function newsfeed_is_feed_inited()
{
    return true;
}
OW::getEventManager()->bind('feed.is_inited', 'newsfeed_is_feed_inited');

function newsfeed_ads_enabled( BASE_EventCollector $event )
{
    $event->add('newsfeed');
}

OW::getEventManager()->bind('ads.enabled_plugins', 'newsfeed_ads_enabled');

$credits = new NEWSFEED_CLASS_Credits();
OW::getEventManager()->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));

function newsfeed_add_auth_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(
        array(
            'newsfeed' => array(
                'label' => $language->text('newsfeed', 'auth_group_label'),
                'actions' => array(
                    'add_comment' => $language->text('newsfeed', 'auth_action_label_add_comment')
                )
            )
        )
    );
}

OW::getEventManager()->bind('admin.add_auth_labels', 'newsfeed_add_auth_labels');

$onceInited = OW::getConfig()->getValue('newsfeed', 'is_once_initialized');
if ( $onceInited === null )
{
    if ( OW::getConfig()->configExists('newsfeed', 'is_once_initialized') )
    {
        OW::getConfig()->saveConfig('newsfeed', 'is_once_initialized', 1);
    }
    else
    {
        OW::getConfig()->addConfig('newsfeed', 'is_once_initialized', 1);
    }

    $event = new OW_Event('feed.after_first_init', array('pluginKey' => 'newsfeed'));
    OW::getEventManager()->trigger($event);
}

function newsfeed_privacy_add_action( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $action = array(
        'key' => NEWSFEED_BOL_Service::PRIVACY_ACTION_VIEW_MY_FEED,
        'pluginKey' => 'newsfeed',
        'label' => $language->text('newsfeed', 'privacy_action_view_my_feed'),
        'description' => '',
        'defaultValue' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
        'sortOrder' => 1001
    );

    $event->add($action);
}

OW::getEventManager()->bind('plugin.privacy.get_action_list', 'newsfeed_privacy_add_action');
/*$a1 = array('qq' => '123', 're' => 222, 'q' => array(1,2) );
$a2 = array('q' => array(1,2), 'qq' => '123', 're' => 222 );
printVar($a1);
printVar($a2);
printVar($a1 === $a2);*/
/*
$k1 = array('create:user_status', 'create:video_comments');
$k2 = 'qqq:qqqs,create.1:video_comments.12:22,create:video_comments';
printVar(NEWSFEED_BOL_Service::getInstance()->testActivityKey( 'create:video_comments', $k2, true));
exit;*/

/*printVar(NEWSFEED_BOL_Service::getInstance()->findActivity( '*:friend_add', '*:*:1'));
exit;*/

//require_once 'update/xxx/update.php';
/*
$c = new NEWSFEED_Cron();
$c->run();exit;*/
/*$query = 'SELECT FROM %tablena_me% FRCGBDSF %tablena_me2% afsas fasdf dasfds';
printVar(preg_replace('/%(.*?)%/', 'grey_' . '$1', $query)); exit;*/

//require_once dirname(__FILE__) . DS . 'update' . DS . 'xxx' . DS . 'update.php'; exit;

//OW::getEventManager()->trigger(new OW_Event('birthdays.today_birthday_user_list', array('userIdList' => array(137, 1))));

/*$k2 = 'create.1:video_comments.12:22';
printVar(NEWSFEED_BOL_Service::getInstance()->testActivityKey( 'create.1:video_comments.12:22', 'create:video_comments,create:grey'));
exit;*/

//create.137:photo_comments.604:137
/*$k2 = array('create:photo_comments','create:multiple_photo_upload');
printVar(NEWSFEED_BOL_Service::getInstance()->testActivityKey( 'create.137:photos_comments.604:137', $k2));
exit;*/

/*$e = new OW_Event('feed.delete_activity', array(
    'activityUniqId' => 4,
));

OW::getEventManager()->trigger($e);*/