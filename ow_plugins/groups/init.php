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
$plugin = OW::getPluginManager()->getPlugin('groups');

//Admin Routs
OW::getRouter()->addRoute(new OW_Route('groups-admin-widget-panel', 'admin/plugins/groups', 'GROUPS_CTRL_Admin', 'panel'));
OW::getRouter()->addRoute(new OW_Route('groups-admin-additional-features', 'admin/plugins/groups/additional', 'GROUPS_CTRL_Admin', 'additional'));
OW::getRouter()->addRoute(new OW_Route('groups-admin-uninstall', 'admin/plugins/groups/uninstall', 'GROUPS_CTRL_Admin', 'uninstall'));

//Frontend Routs
OW::getRouter()->addRoute(new OW_Route('groups-create', 'groups/create', 'GROUPS_CTRL_Groups', 'create'));
OW::getRouter()->addRoute(new OW_Route('groups-edit', 'groups/:groupId/edit', 'GROUPS_CTRL_Groups', 'edit'));
OW::getRouter()->addRoute(new OW_Route('groups-view', 'groups/:groupId', 'GROUPS_CTRL_Groups', 'view'));
OW::getRouter()->addRoute(new OW_Route('groups-join', 'groups/:groupId/join', 'GROUPS_CTRL_Groups', 'join'));
OW::getRouter()->addRoute(new OW_Route('groups-customize', 'groups/:groupId/customize', 'GROUPS_CTRL_Groups', 'customize'));
OW::getRouter()->addRoute(new OW_Route('groups-most-popular', 'groups/most-popular', 'GROUPS_CTRL_Groups', 'mostPopularList'));
OW::getRouter()->addRoute(new OW_Route('groups-latest', 'groups/latest', 'GROUPS_CTRL_Groups', 'latestList'));
OW::getRouter()->addRoute(new OW_Route('groups-invite-list', 'groups/invitations', 'GROUPS_CTRL_Groups', 'inviteList'));
OW::getRouter()->addRoute(new OW_Route('groups-my-list', 'groups/my', 'GROUPS_CTRL_Groups', 'myGroupList'));

OW::getRouter()->addRoute(new OW_Route('groups-index', 'groups', 'GROUPS_CTRL_Groups', 'index'));
OW::getRouter()->addRoute(new OW_Route('groups-user-groups', 'users/:user/groups', 'GROUPS_CTRL_Groups', 'userGroupList'));
OW::getRouter()->addRoute(new OW_Route('groups-leave', 'groups/:groupId/leave', 'GROUPS_CTRL_Groups', 'leave'));

OW::getRouter()->addRoute(new OW_Route('groups-user-list', 'groups/:groupId/users', 'GROUPS_CTRL_Groups', 'userList'));
OW::getRouter()->addRoute(new OW_Route('groups-private-group', 'groups/:groupId/private', 'GROUPS_CTRL_Groups', 'privateGroup'));

OW::getRegistry()->addToArray(BASE_CMP_AddNewContent::REGISTRY_DATA_KEY,
    array(
        BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_comment',
        BASE_CMP_AddNewContent::DATA_KEY_URL => OW::getRouter()->urlForRoute('groups-create'),
        BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('groups', 'add_new_label')
));

function groups_on_add_new_content( BASE_CLASS_EventCollector $event )
{
    if (GROUPS_BOL_Service::getInstance()->isCurrentUserCanCreate())
    {
        $event->add(array(
            BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_comment',
            BASE_CMP_AddNewContent::DATA_KEY_URL => OW::getRouter()->urlForRoute('groups-create'),
            BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('groups', 'add_new_label')
        ));
    }
}
OW::getEventManager()->bind(BASE_CMP_AddNewContent::EVENT_NAME, 'groups_on_add_new_content');

/* Events */

function groups_delete_on_group( OW_Event $event )
{
    $params = $event->getParams();
    $groupId = $params['groupId'];

    $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
    $fileName = GROUPS_BOL_Service::getInstance()->getGroupImagePath($group);

    if ( $fileName !== null )
    {
        OW::getStorage()->removeFile($fileName);
    }
}
OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_ON_DELETE, 'groups_delete_on_group');

function groups_delete_group_complete( OW_Event $event )
{
    $params = $event->getParams();

    $groupId = $params['groupId'];

    BOL_ComponentEntityService::getInstance()->onEntityDelete(GROUPS_BOL_Service::WIDGET_PANEL_NAME, $groupId);
    BOL_CommentService::getInstance()->deleteEntityComments(GROUPS_BOL_Service::ENTITY_TYPE_WAL, $groupId);

    BOL_FlagService::getInstance()->deleteByTypeAndEntityId(GROUPS_BOL_Service::ENTITY_TYPE_GROUP, $groupId);

    OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array(
        'entityType' => GROUPS_BOL_Service::FEED_ENTITY_TYPE,
        'entityId' => $groupId
    )));
}
OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_DELETE_COMPLETE, 'groups_delete_group_complete');

function groups_on_user_unregister( OW_Event $event )
{
    $params = $event->getParams();
    $userId = (int) $params['userId'];

    GROUPS_BOL_Service::getInstance()->onUserUnregister( $userId, !empty($params['deleteContent']) );
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'groups_on_user_unregister');

function groups_on_notify_actions( BASE_CLASS_EventCollector $e )
{
    $sectionLabel = OW::getLanguage()->text('groups', 'email_notification_section_label');

    $e->add(array(
        'section' => 'groups',
        'sectionIcon' => 'ow_ic_files',
        'sectionLabel' => OW::getLanguage()->text('groups', 'email_notification_section_label'),
        'action' => 'groups-add_comment',
        'description' => OW::getLanguage()->text('groups', 'email_notification_comment_setting')
    ));

    $e->add(array(
        'section' => 'groups',
        'action' => 'groups-invitation',
        'sectionIcon' => 'ow_ic_files',
        'sectionLabel' => $sectionLabel,
        'description' => OW::getLanguage()->text('groups', 'notifications_new_message')
    ));
}
OW::getEventManager()->bind('base.notify_actions', 'groups_on_notify_actions');

function groups_add_comment( OW_Event $e )
{
    $params = $e->getParams();

    if ( empty($params['entityType']) || $params['entityType'] != GROUPS_BOL_Service::ENTITY_TYPE_WAL )
    {
        return;
    }

    $entityId = $params['entityId'];
    $userId = $params['userId'];
    $commentId = $params['commentId'];
    $group = GROUPS_BOL_Service::getInstance()->findGroupById($entityId);

    $comment = BOL_CommentService::getInstance()->findComment($commentId);
    $groupUrl = OW::getRouter()->urlForRoute('groups-view', array('groupId' => $group->id));

    $groupImage = GROUPS_BOL_Service::getInstance()->getGroupImageUrl($group);

    $string = OW::getLanguage()->text('groups', 'email_notification_comment', array(
            'userName' => BOL_UserService::getInstance()->getDisplayName($userId),
            'userUrl' => BOL_UserService::getInstance()->getUserUrl($userId),
            'url' => $groupUrl,
            'title' => strip_tags($group->title)
        ));

    $params = array(
        'plugin' => 'groups',
        'action' => 'groups-add_comment',
        'string' => $string,
        'avatar' => $groupImage,
        'content' => $comment->getMessage(),
        'url' => $groupUrl,
        'time' => time()
    );

    $userIds = GROUPS_BOL_Service::getInstance()->findGroupUserIdList($group->id);

    foreach ( $userIds as $uid )
    {
        if ( $uid == $userId )
        {
            continue;
        }

        $params['userId'] = $uid;

        $event = new OW_Event('base.notify', $params);
        OW::getEventManager()->trigger($event);
    }
}
OW::getEventManager()->bind('base_add_comment', 'groups_add_comment');

function groups_check_permissions( OW_Event $event )
{
    $params = $event->getParams();

    if ( !isset($params['entityId']) || !isset($params['entity']) )
    {
        return;
    }

    if ( $params['entity'] == 'groups' )
    {
        $groupService = GROUPS_BOL_Service::getInstance();

        if ( $groupService->findUser($params['entityId'], OW::getUser()->getId()) )
        {
            $event->setData(true);
        }
        else
        {
            $event->setData(false);
        }
    }
}
OW::getEventManager()->bind('forum.check_permissions', 'groups_check_permissions');

function groups_on_find_forum_caption( OW_Event $event )
{

    $params = $event->getParams();
    if ( !isset($params['entity']) || !isset($params['entityId']) )
    {
        return;
    }

    if ( $params['entity'] == 'groups' )
    {
        $component = new GROUPS_CMP_BriefInfo($params['entityId']);
        $eventData['component'] = $component;
        $eventData['key'] = 'main_menu_list';
        $event->setData($eventData);
    }
}
OW::getEventManager()->bind('forum.find_forum_caption', 'groups_on_find_forum_caption');

function groups_is_forum_active( BASE_CLASS_EventCollector $event )
{
    $is_forum_connected = OW::getConfig()->getValue('groups', 'is_forum_connected');

    if ( $is_forum_connected && !OW::getPluginManager()->isPluginActive('forum') )
    {
        $language = OW::getLanguage();

        $event->add($language->text('groups', 'error_forum_disconnected', array('url' => OW::getRouter()->urlForRoute('admin_plugins_installed'))));
    }
}
OW::getEventManager()->bind('admin.add_admin_notification', 'groups_is_forum_active');

function groups_uninstall_forum_plugin( OW_Event $event )
{
    $config = OW::getConfig();

    if ( $config->getValue('groups', 'is_forum_connected') )
    {
        $event = new OW_Event('forum.delete_section', array('entity' => 'groups'));
        OW::getEventManager()->trigger($event);

        $event = new OW_Event('forum.delete_widget');
        OW::getEventManager()->trigger($event);

        $config->saveConfig('groups', 'is_forum_connected', 0);

        $actionId = BOL_AuthorizationActionDao::getInstance()->getIdByName('add_topic');

        BOL_AuthorizationService::getInstance()->deleteAction($actionId);
    }
}
OW::getEventManager()->bind('forum.uninstall_plugin', 'groups_uninstall_forum_plugin');

function groups_activate_forum_plugin( OW_Event $event )
{
    $is_forum_connected = OW::getConfig()->getValue('groups', 'is_forum_connected');

    // Add latest topic widget if forum plugin is connected
    if ( $is_forum_connected )
    {
        $event->setData(array('forum_connected' => true, 'place' => 'group', 'section' => BOL_ComponentAdminService::SECTION_RIGHT));
    }
}
OW::getEventManager()->bind('forum.activate_plugin', 'groups_activate_forum_plugin');


//Feed
function groups_after_group_create( OW_Event $event )
{
    $params = $event->getParams();
    $groupId = (int) $params['groupId'];

    $event = new OW_Event('feed.action', array(
        'entityType' => GROUPS_BOL_Service::FEED_ENTITY_TYPE,
        'entityId' => $groupId,
        'pluginKey' => 'groups'
    ));

    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_CREATE, 'groups_after_group_create');

function groups_feed_on_group_add( OW_Event $e )
{
    $params = $e->getParams();
    $data = $e->getData();

    if ( $params['entityType'] != GROUPS_BOL_Service::FEED_ENTITY_TYPE )
    {
        return;
    }

    $groupId = (int) $params['entityId'];
    $groupService = GROUPS_BOL_Service::getInstance();
    $group = $groupService->findGroupById($groupId);

    if ( $group === null )
    {
        return;
    }

    $thumbnail = $groupService->getGroupImageUrl($group);
    $url = $groupService->getGroupUrl($group);

    $content = UTIL_String::truncate(strip_tags($group->description), 150, '...');
    $title = UTIL_String::truncate(strip_tags($group->title), 100, '...');

    $private = $group->whoCanView == GROUPS_BOL_Service::WCV_INVITE;
    $visibility = $private
            ? 4 + 8 // Visible for autor (4) and current feed (8)
            : 15; // Visible for all (15)


    $data = array(
        'params' => array(
            'feedType' => 'groups',
            'feedId' => $groupId,
            'visibility' => $visibility,
            'postOnUserFeed' => !$private
        ),
        'ownerId' => $group->userId,
        'time' => (int) $group->timeStamp,
        'string' => OW::getLanguage()->text('groups', 'feed_create_string'),
        'content' => '<div class="clearfix"><div class="ow_newsfeed_item_picture">
            <a href="' . $url . '"><img src="' . $thumbnail . '" /></a>
        </div><div class="ow_newsfeed_item_content">
        <a class="ow_newsfeed_item_title" href="' . $url . '">' . $title . '</a><div class="ow_remark ow_smallmargin">' . $content . '</div><div class="ow_newsfeed_action_activity groups_newsfeed_activity">[ph:activity]</div></div></div>',
        'view' => array(
            'iconClass' => 'ow_ic_files'
        )
    );

    $e->setData($data);
}
OW::getEventManager()->bind('feed.on_entity_add', 'groups_feed_on_group_add');

function groups_after_group_edit( OW_Event $event )
{
    $params = $event->getParams();
    $data = $event->getData();
    $groupId = (int) $params['groupId'];

    $groupService = GROUPS_BOL_Service::getInstance();
    $group = $groupService->findGroupById($groupId);

    $url = $groupService->getGroupUrl($group);
    $thumbnail = $groupService->getGroupImageUrl($group);
    $content = UTIL_String::truncate(strip_tags($group->description), 150, '...');
    $title = UTIL_String::truncate(strip_tags($group->title), 100, '...');
    $url = $groupService->getGroupUrl($group);

    $private = $group->whoCanView == GROUPS_BOL_Service::WCV_INVITE;
    $visibility = $private
            ? 2 + 4 + 8 // Visible for follows(2), autor (4) and current feed (8)
            : 15; // Visible for all (15)

    $data['params']['visibility'] = $visibility;
    $data['params']['postOnUserFeed'] = !$private;

    $data['content'] =
    '<div class="clearfix"><div class="ow_newsfeed_item_picture">
    <a href="' . $url . '"><img src="' . $thumbnail . '" /></a>
    </div><div class="ow_newsfeed_item_content">
    <a class="ow_newsfeed_item_title" href="' . $url . '">' . $title . '</a><div class="ow_remark ow_smallmargin">' . $content . '</div><div class="ow_newsfeed_action_activity groups_newsfeed_activity">[ph:activity]</div></div></div>';

    $event = new OW_Event('feed.action', array(
        'entityType' => GROUPS_BOL_Service::FEED_ENTITY_TYPE,
        'entityId' => $groupId,
        'pluginKey' => 'groups'
    ), $data);

    OW::getEventManager()->trigger($event);

    if ( $private )
    {
        $users = $groupService->findGroupUserIdList($groupId);
        $follows = OW::getEventManager()->call('feed.get_all_follows', array(
            'feedType' => 'groups',
            'feedId' => $groupId
        ));

        foreach ( $follows as $follow )
        {
            if ( in_array($follow['userId'], $users) )
            {
                continue;
            }

            OW::getEventManager()->call('feed.remove_follow', array(
                'feedType' => 'groups',
                'feedId' => $groupId,
                'userId' => $follow['userId']
            ));
        }
    }
}
OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_EDIT, 'groups_after_group_edit');

function groups_feed_on_user_join( OW_Event $e )
{
    $params = $e->getParams();

    $groupId = (int) $params['groupId'];
    $userId = (int) $params['userId'];
    $groupUserId = (int) $params['groupUserId'];

    $groupService = GROUPS_BOL_Service::getInstance();
    $group = $groupService->findGroupById($groupId);

    if ( $group->userId == $userId )
    {
        return;
    }

    OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
        'activityType' => 'groups-join',
        'activityId' => $userId,
        'entityId' => $group->id,
        'entityType' => GROUPS_BOL_Service::FEED_ENTITY_TYPE,
        'userId' => $userId,
        'pluginKey' => 'groups',
        'feedType' => 'groups',
        'feedId' => $group->id
    ), array(
        'groupId' => $group->id,
        'userId' => $userId,
        'groupUserId' => $groupUserId
    )));

    $url = $groupService->getGroupUrl($group);
    $title = UTIL_String::truncate(strip_tags($group->title), 100, '...');

    $data = array(
        'time' => time(),
        'string' => OW::getLanguage()->text('groups', 'feed_join_string',
            array(
                'groupTitle' => $title,
                'groupUrl' => $url
            )
        ),
        'view' => array(
            'iconClass' => 'ow_ic_add'
        ),
        'data' => array(
            'joinUsersId' => $userId
        )
    );

    $event = new OW_Event('feed.action', array(
        'feedType' => 'groups',
        'feedId' => $group->id,
        'entityType' => 'groups-join',
        'entityId' => $groupUserId,
        'pluginKey' => 'groups',
        'userId' => $userId,
        'visibility' => 8,
        'postOnUserFeed' => false
    ), $data);

    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_USER_ADDED, 'groups_feed_on_user_join');

function groups_newsfeed_collect_widgets( BASE_CLASS_EventCollector $e )
{
    $e->add(array(
        'place' => 'group',
        'section' => BOL_ComponentService::SECTION_RIGHT,
        'order' => 0
    ));
}
OW::getEventManager()->bind('feed.collect_widgets', 'groups_newsfeed_collect_widgets');


function groups_newsfeed_on_widget_construct( OW_Event $e )
{
    $params = $e->getParams();

    if ( $params['feedType'] != 'groups' )
    {
        return;
    }

    $groupId = (int) $params['feedId'];
    $userId = OW::getUser()->getId();
    $data = $e->getData();

    $userDto = GROUPS_BOL_Service::getInstance()->findUser($groupId, $userId);
    $data['statusForm'] = $userDto !== null;

    $e->setData($data);
}
OW::getEventManager()->bind('feed.on_widget_construct', 'groups_newsfeed_on_widget_construct');

function groups_on_toolbar_collect( BASE_CLASS_EventCollector $e )
{
    if ( !OW::getUser()->isAuthenticated() )
    {
        return;
    }

    $params = $e->getParams();
    $backUri = OW::getRequest()->getRequestUri();

    if ( OW::getEventManager()->call('feed.is_inited') )
    {
        $url = OW::getRouter()->urlFor('GROUPS_CTRL_Groups', 'follow');

        $eventParams = array(
            'userId' => OW::getUser()->getId(),
            'feedType' => GROUPS_BOL_Service::ENTITY_TYPE_GROUP,
            'feedId' => $params['groupId']
        );

        if ( !OW::getEventManager()->call('feed.is_follow', $eventParams) )
        {
            $e->add(array(
                'label' => OW::getLanguage()->text('groups', 'feed_group_follow'),
                'href' => OW::getRequest()->buildUrlQueryString($url, array(
                    'backUri' => $backUri,
                    'groupId' => $params['groupId'],
                    'command' => 'follow'))
            ));
        }
        else
        {
            $e->add(array(
                'label' => OW::getLanguage()->text('groups', 'feed_group_unfollow'),
                'href' => OW::getRequest()->buildUrlQueryString($url, array(
                    'backUri' => $backUri,
                    'groupId' => $params['groupId'],
                    'command' => 'unfollow'))
            ));
        }
    }
}
OW::getEventManager()->bind('groups.on_toolbar_collect', 'groups_on_toolbar_collect');

function groups_ads_enabled( BASE_EventCollector $event )
{
    $event->add('groups');
}

OW::getEventManager()->bind('ads.enabled_plugins', 'groups_ads_enabled');

function groups_find_all_groups_users( OW_Event $e )
{
    $out = GROUPS_BOL_Service::getInstance()->findAllGroupsUserList();
    $e->setData($out);

    return $out;
}
OW::getEventManager()->bind('groups.get_all_group_users', 'groups_find_all_groups_users');

function groups_feed_collect_follow( BASE_CLASS_EventCollector $e )
{
    $groupUsers = GROUPS_BOL_Service::getInstance()->findAllGroupsUserList();
    foreach ( $groupUsers as $groupId => $users )
    {
        foreach ( $users as $userId )
        {
            $e->add(array(
                'feedType' => 'groups',
                'feedId' => $groupId,
                'userId' => $userId
            ));
        }
    }
}
OW::getEventManager()->bind('feed.collect_follow', 'groups_feed_collect_follow');

function groups_feed_add_follow( OW_Event $event )
{
    $params = $event->getParams();

    $groupId = $params['groupId'];
    $userId = $params['userId'];

    OW::getEventManager()->call('feed.add_follow', array(
        'feedType' => 'groups',
        'feedId' => $groupId,
        'userId' => $userId
    ));
}

OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_USER_ADDED, 'groups_feed_add_follow');

function groups_feed_on_status_add( OW_Event $event )
{
    $params = $event->getParams();
    $data = $event->getData();

    if ( $params['entityType'] != 'groups-status' )
    {
        return;
    }

    $service = GROUPS_BOL_Service::getInstance();
    $group = $service->findGroupById($params['feedId']);
    $url = $service->getGroupUrl($group);
    $title = UTIL_String::truncate(strip_tags($group->title), 100, '...');

    $data['context'] = array(
        'label' => $title,
        'url' => $url
    );

    $event->setData($data);
}
OW::getEventManager()->bind('feed.on_entity_add', 'groups_feed_on_status_add');

function groups_feed_on_item_render( OW_Event $event )
{
    $params = $event->getParams();
    $data = $event->getData();

    $groupActions = array(
        'groups-status'
    );

    if ( in_array($params['action']['entityType'], $groupActions) && $params['feedType'] == 'groups' )
    {
        $data['context'] = null;
    }

    if ( $params['action']['entityType'] == 'forum-topic' && isset($data['contextFeedType'])
            && $data['contextFeedType'] == 'groups' && $data['contextFeedType'] != $params['feedType'] )
    {
        $service = GROUPS_BOL_Service::getInstance();
        $group = $service->findGroupById($data['contextFeedId']);
        $url = $service->getGroupUrl($group);
        $title = UTIL_String::truncate(strip_tags($group->title), 100, '...');

        $data['context'] = array(
            'label' => $title,
            'url' => $url
        );
    }

    $event->setData($data);
}
OW::getEventManager()->bind('feed.on_item_render', 'groups_feed_on_item_render');

function groups_feed_on_item_render_activity( OW_Event $event )
{
    $params = $event->getParams();
    $data = $event->getData();

    if ( $params['action']['entityType'] != GROUPS_BOL_Service::FEED_ENTITY_TYPE || $params['feedType'] == 'groups')
    {
        return;
    }

    $groupId = $params['action']['entityId'];
    $usersCount = GROUPS_BOL_Service::getInstance()->findUserListCount($groupId);

    if ( $usersCount == 1 )
    {
        return;
    }

    $users = GROUPS_BOL_Service::getInstance()->findGroupUserIdList($groupId, GROUPS_BOL_Service::PRIVACY_EVERYBODY);
    $activityUserIds = array();

    foreach ( $params['activity'] as $activity )
    {
        if ( $activity['activityType'] == 'groups-join')
        {
            $activityUserIds[] = $activity['data']['userId'];
        }
    }

    $lastUserId = reset($activityUserIds);
    $follows = array_intersect($activityUserIds, $users);
    $notFollows = array_diff($users, $activityUserIds);
    $idlist = array_merge($follows, $notFollows);

    $avatarList = new BASE_CMP_MiniAvatarUserList( array_slice($idlist, 0, 5) );
    $avatarList->setEmptyListNoRender(true);

    if ( count($idlist) > 5 )
    {
        $avatarList->setViewMoreUrl(OW::getRouter()->urlForRoute('groups-user-list', array('groupId' => $groupId)));
    }

    $language = OW::getLanguage();
    $content = $avatarList->render();

    if ( $lastUserId )
    {
        $userName = BOL_UserService::getInstance()->getDisplayName($lastUserId);
        $userUrl = BOL_UserService::getInstance()->getUserUrl($lastUserId);
        $content .= $language->text('groups', 'feed_activity_joined', array('user' => '<a href="' . $userUrl . '">' . $userName . '</a>'));
    }

    $data['assign']['activity'] = array('template' => 'activity', 'vars' => array(
        'title' => $language->text('groups', 'feed_activity_users', array('usersCount' => $usersCount)),
        'content' => $content
    ));

    $event->setData($data);
}
OW::getEventManager()->bind('feed.on_item_render', 'groups_feed_on_item_render_activity');


$credits = new GROUPS_CLASS_Credits();
OW::getEventManager()->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));

function groups_add_auth_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(
        array(
            'groups' => array(
                'label' => $language->text('groups', 'auth_group_label'),
                'actions' => array(
                    'add_topic' => $language->text('groups', 'auth_action_label_add_topic'),
                    'create' => $language->text('groups', 'auth_action_label_create'),
                    'view' => $language->text('groups', 'auth_action_label_view'),
                    'add_comment' => $language->text('groups', 'auth_action_label_add_comment'),
                    'delete_comment_by_content_owner' => $language->text('groups', 'auth_action_label_delete_comment_by_content_owner')
                )
            )
        )
    );
}

OW::getEventManager()->bind('admin.add_auth_labels', 'groups_add_auth_labels');

function groups_feed_collect_configurable_activity( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(array(
        'label' => $language->text('groups', 'feed_content_label'),
        'activity' => '*:' . GROUPS_BOL_Service::FEED_ENTITY_TYPE
    ));
}

OW::getEventManager()->bind('feed.collect_configurable_activity', 'groups_feed_collect_configurable_activity');

function groups_privacy_add_action( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $action = array(
        'key' => GROUPS_BOL_Service::PRIVACY_ACTION_VIEW_MY_GROUPS,
        'pluginKey' => 'groups',
        'label' => $language->text('groups', 'privacy_action_view_my_groups'),
        'description' => '',
        'defaultValue' => GROUPS_BOL_Service::PRIVACY_EVERYBODY,
        'sortOrder' => 1000
    );

    $event->add($action);
}

OW::getEventManager()->bind('plugin.privacy.get_action_list', 'groups_privacy_add_action');

function groups_feed_collect_privacy( BASE_CLASS_EventCollector $event )
{
    $event->add(array('groups-join:*', GROUPS_BOL_Service::PRIVACY_ACTION_VIEW_MY_GROUPS));
    $event->add(array('create:groups-join', GROUPS_BOL_Service::PRIVACY_ACTION_VIEW_MY_GROUPS));
    $event->add(array('create:' . GROUPS_BOL_Service::FEED_ENTITY_TYPE, GROUPS_BOL_Service::PRIVACY_ACTION_VIEW_MY_GROUPS));
}

OW::getEventManager()->bind('feed.collect_privacy', 'groups_feed_collect_privacy');

function groups_privacy_on_change( OW_Event $e )
{
    $params = $e->getParams();

    $userId = (int) $params['userId'];
    $actionList = $params['actionList'];
    $actionList = is_array($actionList) ? $actionList : array();

    if ( empty($actionList[GROUPS_BOL_Service::PRIVACY_ACTION_VIEW_MY_GROUPS]) )
    {
        return;
    }

    GROUPS_BOL_Service::getInstance()->setGroupUserPrivacy($userId, $actionList[GROUPS_BOL_Service::PRIVACY_ACTION_VIEW_MY_GROUPS]);
    //GROUPS_BOL_Service::getInstance()->setGroupsPrivacy($userId, $actionList[GROUPS_BOL_Service::PRIVACY_ACTION_VIEW_MY_GROUPS]);
}
OW::getEventManager()->bind('plugin.privacy.on_change_action_privacy', 'groups_privacy_on_change');

function groups_on_before_user_join( OW_Event $event )
{
    $data = $event->getData();
    $params = $event->getParams();

    $userId = (int) $params['userId'];
    $privacy = GROUPS_BOL_Service::PRIVACY_EVERYBODY;

    $t = OW::getEventManager()->call('plugin.privacy.get_privacy', array(
        'ownerId' => $params['userId'],
        'action' => GROUPS_BOL_Service::PRIVACY_ACTION_VIEW_MY_GROUPS
    ));

    $data['privacy'] = empty($t) ? $privacy : $t;

    $event->setData($data);
}
OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_USER_BEFORE_ADDED, 'groups_on_before_user_join');


function groups_on_user_invite( OW_Event $e )
{
    $params = $e->getParams();

    $group = GROUPS_BOL_Service::getInstance()->findGroupById($params['groupId']);
    $groupUrl = OW::getRouter()->urlForRoute('groups-view', array('groupId' => $group->id));
    $groupImage = GROUPS_BOL_Service::getInstance()->getGroupImageUrl($group);

    $event = new OW_Event('base.notify', array(
            'plugin' => 'groups',
            'action' => 'groups-invitation',
            'userId' => $params['userId'],
            'string' => OW::getLanguage()->text('groups', 'email_notification_invite', array(
                'inviterUrl' => BOL_UserService::getInstance()->getUserUrl($params['inviterId']),
                'inviterName' => BOL_UserService::getInstance()->getDisplayName($params['inviterId']),
                'groupUrl' => $groupUrl,
                'groupName' => UTIL_String::truncate($group->title, 100, '...')
            )),
            'content' => UTIL_String::truncate(strip_tags($group->description), 200, '...'),
            'time' => time(),
            'url' => $groupUrl,
            'avatar' => $groupImage
        ));

    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind('groups.invite_user', 'groups_on_user_invite');


function groups_add_console_item( BASE_EventCollector $e )
{
    if ( !OW::getUser()->isAuthenticated() )
    {
        return;
    }

    $count = GROUPS_BOL_Service::getInstance()->findUserInvitedGroupsCount(OW::getUser()->getId());

    if ( $count > 0 )
    {
        $ntfBlockId = UTIL_HtmlTag::generateAutoId('groups_notification_block');

        $e->add(
            array(
                BASE_CMP_Console::DATA_KEY_URL => OW::getRouter()->urlForRoute('groups-invite-list'),
                BASE_CMP_Console::DATA_KEY_ICON_CLASS => 'new_mail ow_ic_files',
                BASE_CMP_Console::DATA_KEY_ITEMS_LABEL => $count,
                BASE_CMP_Console::DATA_KEY_BLOCK => true,
                BASE_CMP_Console::DATA_KEY_BLOCK_CLASS => 'ow_mild_green',
                BASE_CMP_Console::DATA_KEY_TITLE => OW::getLanguage()->text('groups', 'console_notification_label'),
                BASE_CMP_Console::DATA_KEY_BLOCK_ID => $ntfBlockId
            )
        );
    }
}
OW::getEventManager()->bind(BASE_CMP_Console::EVENT_NAME, 'groups_add_console_item');


function groups_forum_can_view( OW_Event $event )
{
    $params = $event->getParams();

    if ( !isset($params['entityId']) || !isset($params['entity']) )
    {
        return;
    }

    if ( $params['entity'] != 'groups' )
    {
        return;
    }


    $groupId = $params['entityId'];
    $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);

    if ( empty($group) )
    {
        return;
    }

    $privateUrl = OW::getRouter()->urlForRoute('groups-private-group', array(
        'groupId' => $group->id
    ));

    $canView = GROUPS_BOL_Service::getInstance()->isCurrentUserCanView($group->userId);

    if ( $group->whoCanView != GROUPS_BOL_Service::WCV_INVITE )
    {
        $event->setData($canView);

        return;
    }

    if ( !OW::getUser()->isAuthenticated() )
    {
        throw new RedirectException($privateUrl);
    }

    $isUser = GROUPS_BOL_Service::getInstance()->findUser($group->id, OW::getUser()->getId()) !== null;

    if ( !$isUser )
    {
        throw new RedirectException($privateUrl);
    }
}
OW::getEventManager()->bind('forum.can_view', 'groups_forum_can_view');