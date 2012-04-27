<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2009, Skalfa LLC
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
OW::getRouter()->addRoute(new OW_Route('friends_list', 'friends', 'FRIENDS_CTRL_List', 'index', array('list' => array(OW_Route::PARAM_OPTION_HIDDEN_VAR => 'friends'))));
OW::getRouter()->addRoute(new OW_Route('friends_lists', 'friends/:list', 'FRIENDS_CTRL_List', 'index'));
OW::getRouter()->addRoute(new OW_Route('friends_user_friends', 'friends/user/:user', 'FRIENDS_CTRL_List', 'index', array('list' => array(OW_Route::PARAM_OPTION_HIDDEN_VAR => 'user-friends'))));

/**
 * Prepeare actions for tool on the profile view page
 * 
 * @param BASE_CLASS_EventCollector $event
 */
function friends_user_action_tool( BASE_CLASS_EventCollector $event )
{
    $params = $event->getParams();

    if ( empty($params['userId']) )
    {
        return;
    }

    $userId = (int) $params['userId'];

    if ( !OW::getUser()->isAuthenticated() || OW::getUser()->getId() == $userId || !OW::getUser()->isAuthorized('friends', 'add_friend') )
    {
        return;
    }

    $service = FRIENDS_BOL_Service::getInstance();
    $language = OW::getLanguage();
    $router = OW::getRouter();
    $dto = $service->findFriendship($userId, OW::getUser()->getId());
    $linkId = 'friendship' . rand(10, 1000000);
    if ( $dto === null )
    {
        if ( BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $userId) )
        {
            $script = "\$('#" . $linkId . "').click(function(){
            
            window.OW.error('" . OW::getLanguage()->text('base', 'user_block_message') . "');
            
        });";

            OW::getDocument()->addOnloadScript($script);
            $href = 'javascript://';
        }
        else
        {
            $href = OW::getRouter()->urlFor('FRIENDS_CTRL_Action', 'request', array('id' => $userId));
        }    
        
        $label = OW::getLanguage()->text('friends', 'add_to_friends');        
    }
    else
    {
        switch ( $dto->getStatus() )
        {
            case FRIENDS_BOL_Service::STATUS_ACTIVE:
                $label = $language->text('friends', 'remove_from_friends');
                $href = $router->urlFor('FRIENDS_CTRL_Action', 'cancel', array('id' => $userId));
                break;

            case FRIENDS_BOL_Service::STATUS_PENDING:

                if ( $dto->getUserId() == OW::getUser()->getId() )
                {
                    $label = $language->text('friends', 'remove_from_friends');
                    $href = $router->urlFor('FRIENDS_CTRL_Action', 'cancel', array('id' => $userId));
                }
                else
                {
                    $label = $language->text('friends', 'add_to_friends');
                    $href = $router->urlFor('FRIENDS_CTRL_Action', 'accept', array('id' => $userId));
                }
                break;

            case FRIENDS_BOL_Service::STATUS_IGNORED:

                if ( $dto->getUserId() == OW::getUser()->getId() )
                {
                    $label = $language->text('friends', 'remove_from_friends');
                    $href = $router->urlFor('FRIENDS_CTRL_Action', 'cancel', array('id' => $userId));
                }
                else
                {
                    $label = $language->text('friends', 'add_to_friends');
                    $href = $router->urlFor('FRIENDS_CTRL_Action', 'activate', array('id' => $userId));
                }
        }
    }

    $resultArray = array(
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LABEL => $label,
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_HREF => $href,
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_ID => $linkId
    );

    $event->add($resultArray);
}
OW::getEventManager()->bind(BASE_CMP_ProfileActionToolbar::EVENT_NAME, 'friends_user_action_tool');

function friends_on_user_delete( OW_Event $event )
{
    $params = $event->getParams();

    $userId = $params['userId'];

    $service = FRIENDS_BOL_Service::getInstance();
    $service->deleteUserFriendships($userId);
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'friends_on_user_delete');

//---
function friends_on_notify_actions( BASE_CLASS_EventCollector $e )
{
    $sectionLabel = OW::getLanguage()->text('friends', 'notification_section_label');

    $e->add(array(
        'section' => 'friends',
        'action' => 'friends-request',
        'description' => OW::getLanguage()->text('friends', 'email_notifications_setting_request'),
        'selected' => true,
        'sectionLabel' => $sectionLabel,
        'sectionIcon' => 'ow_ic_write'
    ));

    $e->add(array(
        'section' => 'friends',
        'action' => 'friends-accept',
        'description' => OW::getLanguage()->text('friends', 'email_notifications_setting_accept'),
        'selected' => true,
        'sectionLabel' => $sectionLabel,
        'sectionIcon' => 'ow_ic_write'
    ));
}
OW::getEventManager()->bind('base.notify_actions', 'friends_on_notify_actions');

//~~


function friends_ads_enabled( BASE_EventCollector $event )
{
    $event->add('friends');
}
OW::getEventManager()->bind('ads.enabled_plugins', 'friends_ads_enabled');

function friends_plugin_is_active()
{
    return true;
}
OW::getEventManager()->bind('plugin.friends', 'friends_plugin_is_active');

function friends_get_friend_list( OW_Event $event )
{
    $params = $event->getParams();

    if ( !empty($params['userId']) )
    {
        $userId = (int) $params['userId'];
    }
    else
    {
        return null;
    }

    $first = 0;

    if ( !empty($params['first']) )
    {
        $first = (int) $params['first'];
    }

    $count = 1000;

    if ( !empty($params['count']) )
    {
        $count = (int) $params['count'];
    }

    $paramsUserIdList = null;

    if ( !empty($params['idList']) && is_array($params['idList']) )
    {
        $paramsUserIdList = $params['idList'];
    }

    return FRIENDS_BOL_Service::getInstance()->findUserFriendsInList($userId, $first, $count, $paramsUserIdList);
}
OW::getEventManager()->bind('plugin.friends.get_friend_list', 'friends_get_friend_list');

function friends_check_friendship( OW_Event $event )
{
    $params = $event->getParams();

    if ( empty($params['userId']) || empty($params['friendId']) )
    {
        return null;
    }

    return FRIENDS_BOL_Service::getInstance()->findFriendship((int) $params['userId'], (int) $params['friendId']);
}
OW::getEventManager()->bind('plugin.friends.check_friendship', 'friends_check_friendship');

function friends_count_friends( OW_Event $event )
{
    $params = $event->getParams();

    if ( !empty($params['userId']) )
    {
        $userId = (int) $params['userId'];
    }
    else
    {
        return null;
    }

    $paramsUserIdList = null;

    if ( !empty($params['idList']) && is_array($params['idList']) )
    {
        $paramsUserIdList = $params['idList'];
    }

    return FRIENDS_BOL_Service::getInstance()->findCountOfUserFriendsInList($userId, $paramsUserIdList);
}
OW::getEventManager()->bind('plugin.friends.count_friends', 'friends_count_friends');

function friends_find_all_active_friendships( OW_Event $event )
{
    $params = $event->getParams();

    return FRIENDS_BOL_Service::getInstance()->findAllActiveFriendships();
}
OW::getEventManager()->bind('plugin.friends.find_all_active_friendships', 'friends_find_all_active_friendships');

function friends_find_active_friendships( OW_Event $event )
{
    $params = $event->getParams();

    if ( !isset($params['first']) || !isset($params['count']) )
    {
        return null;
    }

    return FRIENDS_BOL_Service::getInstance()->findActiveFriendships((int) $params['first'], (int) $params['count']);
}
OW::getEventManager()->bind('plugin.friends.find_active_friendships', 'friends_find_active_friendships');

function friends_feed_collect_follow( BASE_CLASS_EventCollector $e )
{
    $friends = FRIENDS_BOL_Service::getInstance()->findAllActiveFriendships();
    foreach ( $friends as $item )
    {
        $e->add(array(
            'feedType' => 'user',
            'feedId' => $item->getUserId(),
            'userId' => $item->getFriendId(),
            'permission' => 'friends_only'
        ));

        $e->add(array(
            'feedType' => 'user',
            'feedId' => $item->getFriendId(),
            'userId' => $item->getUserId(),
            'permission' => 'friends_only'
        ));
    }
}
OW::getEventManager()->bind('feed.collect_follow', 'friends_feed_collect_follow');


$credits = new FRIENDS_CLASS_Credits();
OW::getEventManager()->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));

// add item to member console
function friends_add_console_item( BASE_EventCollector $e )
{
    if ( !OW::getUser()->isAuthenticated() )
    {
        return;
    }

    $count = FRIENDS_BOL_Service::getInstance()->count(null, OW::getUser()->getId(), FRIENDS_BOL_Service::STATUS_PENDING);

    if ( $count > 0 )
    {
        $e->add(
            array(
                BASE_CMP_Console::DATA_KEY_URL => OW::getRouter()->urlForRoute('friends_lists', array('list' => 'got-requests')),
                BASE_CMP_Console::DATA_KEY_ICON_CLASS => 'new_mail ow_ic_user',
                BASE_CMP_Console::DATA_KEY_ITEMS_LABEL => $count,
                BASE_CMP_Console::DATA_KEY_BLOCK => true,
                BASE_CMP_Console::DATA_KEY_BLOCK_CLASS => 'ow_mild_green'
            )
        );
    }
}
OW::getEventManager()->bind(BASE_CMP_Console::EVENT_NAME, 'friends_add_console_item');

function friends_add_auth_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(
        array(
            'friends' => array(
                'label' => $language->text('friends', 'auth_group_label'),
                'actions' => array(
                    'add_friend' => $language->text('friends', 'auth_action_label_add_friend')
                )
            )
        )
    );
}
OW::getEventManager()->bind('admin.add_auth_labels', 'friends_add_auth_labels');

function friends_add_privacy( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $params = $event->getParams();

    $event->add(array(
        'key' => 'friends_only',
        'label' => $language->text('friends', 'privacy_friends_only'),
        'sortOrder' => 2
    ));
}
OW::getEventManager()->bind('plugin.privacy.get_privacy_list', 'friends_add_privacy');

function friends_permission_friends_only( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $params = $event->getParams();

    if ( !empty($params['privacy']) && $params['privacy'] == 'friends_only' )
    {
        $ownerId = (int) $params['ownerId'];
        $viewerId = (int) $params['viewerId'];

        $privacy = array();
        $privacy = array(
            'friends_only' => array(
                'blocked' => true,
            ));

        $friendship = FRIENDS_BOL_Service::getInstance()->findFriendship($ownerId, $viewerId);

        if ( $ownerId > 0 && $viewerId > 0 && ( (!empty($friendship) && $friendship->getStatus() == 'active' ) || $ownerId === $viewerId ) )
        {
            $privacy = array(
                'friends_only' => array(
                    'blocked' => false
                ));
        }

        $event->add($privacy);
    }
}
OW::getEventManager()->bind('plugin.privacy.check_permission', 'friends_permission_friends_only');

function friends_on_request_accept( OW_Event $e )
{
    $params = $e->getParams();
    $recipientId = $params['recipientId'];
    $senderId = $params['senderId'];

    $eventParams = array(
        'userId' => $recipientId,
        'feedType' => 'user',
        'feedId' => $senderId
    );
    OW::getEventManager()->trigger(new OW_Event('feed.add_follow', $eventParams));

    $eventParams = array(
        'userId' => $senderId,
        'feedType' => 'user',
        'feedId' => $recipientId
    );
    OW::getEventManager()->trigger(new OW_Event('feed.add_follow', $eventParams));
}
OW::getEventManager()->bind('friends.request-accepted', 'friends_on_request_accept');

function friends_on_cancel( OW_Event $e )
{
    $params = $e->getParams();
    $recipientId = $params['recipientId'];
    $senderId = $params['senderId'];

    $service = FRIENDS_BOL_Service::getInstance();
    $service->cancel($recipientId, $senderId);

    $eventParams = array(
        'userId' => $recipientId,
        'feedType' => 'user',
        'feedId' => $senderId
    );
    OW::getEventManager()->trigger(new OW_Event('feed.remove_follow', $eventParams));

    $eventParams = array(
        'userId' => $senderId,
        'feedType' => 'user',
        'feedId' => $recipientId
    );
    OW::getEventManager()->trigger(new OW_Event('feed.remove_follow', $eventParams));
}
OW::getEventManager()->bind('friends.cancelled', 'friends_on_cancel');

function friends_on_user_block( OW_Event $e )
{
    $params = $e->getParams();
    
    $event = new OW_Event('friends.cancelled', array(
            'senderId' => $params['userId'],
            'recipientId' => $params['blockedUserId']
    ));
    
    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_BLOCK, 'friends_on_user_block');

function friends_feed_collect_follow_permissions( BASE_CLASS_EventCollector $e )
{
    $params = $e->getParams();

    if ( $params['feedType'] != 'user' )
    {
        return;
    }

    $dto = FRIENDS_BOL_Service::getInstance()->findFriendship($params['feedId'], $params['userId']);
    if ( $dto === null || $dto->status != 'active' )
    {
        return;
    }

    $e->add('friends_only');
}
OW::getEventManager()->bind('feed.collect_follow_permissions', 'friends_feed_collect_follow_permissions');

function friends_privacy_add_action( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $action = array(
        'key' => 'friends_view',
        'pluginKey' => 'friends',
        'label' => $language->text('friends', 'privacy_action_view_friends'),
        'description' => '',
        'defaultValue' => 'everybody'
    );

    $event->add($action);
}
OW::getEventManager()->bind('plugin.privacy.get_action_list', 'friends_privacy_add_action');

function friends_feed_collect_privacy( BASE_CLASS_EventCollector $event )
{
    $event->add(array('create:friend_add', 'friends_view'));
}
OW::getEventManager()->bind('feed.collect_privacy', 'friends_feed_collect_privacy');

function friends_feed_collect_configurable_activity( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(array(
        'label' => $language->text('friends', 'feed_content_label'),
        'activity' => '*:friend_add'
    ));
}
OW::getEventManager()->bind('feed.collect_configurable_activity', 'friends_feed_collect_configurable_activity');