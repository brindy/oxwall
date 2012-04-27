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
$plugin = OW::getPluginManager()->getPlugin('forum');

OW::getAutoloader()->addClass('ForumSelectBox', $plugin->getRootDir() . 'classes' . DS . 'forum_select_box.php');
OW::getAutoloader()->addClass('ForumStringValidator', $plugin->getRootDir() . 'classes' . DS . 'forum_string_validator.php');

OW::getRouter()->addRoute(new OW_Route('forum-default', 'forum', 'FORUM_CTRL_Index', 'index'));
OW::getRouter()->addRoute(new OW_Route('customize-default', 'forum/customize', 'FORUM_CTRL_Customize', 'index'));
OW::getRouter()->addRoute(new OW_Route('group-default', 'forum/:groupId', 'FORUM_CTRL_Group', 'index'));
OW::getRouter()->addRoute(new OW_Route('topic-default', 'forum/topic/:topicId', 'FORUM_CTRL_Topic', 'index'));

OW::getRouter()->addRoute(new OW_Route('add-topic-default', 'forum/addTopic', 'FORUM_CTRL_AddTopic', 'index'));
OW::getRouter()->addRoute(new OW_Route('add-topic', 'forum/addTopic/:groupId', 'FORUM_CTRL_AddTopic', 'index'));

OW::getRouter()->addRoute(new OW_Route('sticky-topic', 'forum/stickyTopic/:topicId/:page', 'FORUM_CTRL_Topic', 'stickyTopic'));
OW::getRouter()->addRoute(new OW_Route('lock-topic', 'forum/lockTopic/:topicId/:page', 'FORUM_CTRL_Topic', 'lockTopic'));
OW::getRouter()->addRoute(new OW_Route('delete-topic', 'forum/deleteTopic/:topicId', 'FORUM_CTRL_Topic', 'deleteTopic'));
OW::getRouter()->addRoute(new OW_Route('get-post', 'forum/getPost/:postId', 'FORUM_CTRL_Topic', 'getPost'));
OW::getRouter()->addRoute(new OW_Route('edit-post', 'forum/edit-post/:id', 'FORUM_CTRL_EditPost', 'index'));
OW::getRouter()->addRoute(new OW_Route('edit-topic', 'forum/edit-topic/:id', 'FORUM_CTRL_EditTopic', 'index'));
OW::getRouter()->addRoute(new OW_Route('move-topic', 'forum/moveTopic', 'FORUM_CTRL_Topic', 'moveTopic'));
OW::getRouter()->addRoute(new OW_Route('subscribe-topic', 'forum/subscribe-topic/:id', 'FORUM_CTRL_Topic', 'subscribeTopic'));
OW::getRouter()->addRoute(new OW_Route('unsubscribe-topic', 'forum/unsubscribe-topic/:id', 'FORUM_CTRL_Topic', 'unsubscribeTopic'));

OW::getRouter()->addRoute(new OW_Route('add-post', 'forum/addPost/:topicId', 'FORUM_CTRL_Topic', 'addPost'));
OW::getRouter()->addRoute(new OW_Route('delete-post', 'forum/deletePost/:topicId/:postId', 'FORUM_CTRL_Topic', 'deletePost'));
OW::getRouter()->addRoute(new OW_Route('forum_delete_attachment', 'forum/deleteAttachment/', 'FORUM_CTRL_Topic', 'ajaxDeleteAttachment'));
OW::getRouter()->addRoute(new OW_Route('forum_admin_config', 'admin/plugins/forum', 'FORUM_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('forum_uninstall', 'admin/forum/uninstall', 'FORUM_CTRL_Admin', 'uninstall'));
OW::getRouter()->addRoute(new OW_Route('forum_search', 'forum/search/', 'FORUM_CTRL_Search', 'inForums'));
OW::getRouter()->addRoute(new OW_Route('forum_search_group', 'forum/:groupId/search/', 'FORUM_CTRL_Search', 'inGroup'));
OW::getRouter()->addRoute(new OW_Route('forum_search_topic', 'forum/topic/:topicId/search/', 'FORUM_CTRL_Search', 'inTopic'));

function forum_elst_add_new_content_item( BASE_CLASS_EventCollector $event )
{
    if ( !OW::getUser()->isAuthorized('forum', 'edit') )
    {
        return;
    }

    $resultArray = array(
        BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_files',
        BASE_CMP_AddNewContent::DATA_KEY_URL => OW::getRouter()->urlForRoute('add-topic-default'),
        BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('forum', 'discussion')
    );

    $event->add($resultArray);
}
OW::getEventManager()->bind(BASE_CMP_AddNewContent::EVENT_NAME, 'forum_elst_add_new_content_item');

function forum_delete_user_content( OW_Event $event )
{
    $params = $event->getParams();

    if ( !isset($params['deleteContent']) || !(bool) $params['deleteContent'] )
    {
        return;
    }

    $userId = (int) $params['userId'];

    if ( $userId > 0 )
    {
        $forumService = FORUM_BOL_ForumService::getInstance();

        $forumService->deleteUserTopics($userId);
        $forumService->deleteUserPosts($userId);
    }
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'forum_delete_user_content');

function forum_create_section( OW_Event $event )
{
    $params = $event->getParams();

    if ( !isset($params['name']) || !isset($params['entity']) || !isset($params['isHidden']) )
    {
        return;
    }

    $forum_service = FORUM_BOL_ForumService::getInstance();

    $sectionDto = $forum_service->findSectionByEntity($params['entity']);

    if ( !isset($sectionDto) )
    {
        $sectionDto = new FORUM_BOL_Section();
        $sectionDto->name = $params['name'];
        $sectionDto->entity = $params['entity'];
        $sectionDto->isHidden = $params['isHidden'];
        $sectionDto->order = $forum_service->getNewSectionOrder();

        $forum_service->saveOrUpdateSection($sectionDto);
    }

    try
    {
        OW::getAuthorization()->addAction($params['entity'], 'add_topic');
    }
    catch ( Exception $e )
    {

    }
}
OW::getEventManager()->bind('forum.create_section', 'forum_create_section');

function forum_delete_section( OW_Event $event )
{
    $params = $event->getParams();

    if ( !isset($params['name']) && !isset($params['entity']) )
    {
        return;
    }

    $forum_service = FORUM_BOL_ForumService::getInstance();

    if ( isset($params['name']) )
    {
        $section = $forum_service->getSection($params['name']);
    }

    if ( isset($params['entity']) )
    {
        $section = $forum_service->findSectionByEntity($params['entity']);
    }

    if ( !empty($section) )
    {
        $forum_service->deleteSection($section->getId());
    }
}
OW::getEventManager()->bind('forum.delete_section', 'forum_delete_section');

function forum_add_widget( OW_Event $event )
{
    $params = $event->getParams();

    if ( !isset($params['place']) || !isset($params['section']) )
    {
        return;
    }

    try
    {
        $widgetService = BOL_ComponentAdminService::getInstance();
        $widget = $widgetService->addWidget('FORUM_CMP_LatestTopicsWidget', false);
        $placeWidget = $widgetService->addWidgetToPlace($widget, $params['place']);
        $widgetService->addWidgetToPosition($placeWidget, $params['section'], 0);
    }
    catch ( Exception $e )
    {

    }
}
OW::getEventManager()->bind('forum.add_widget', 'forum_add_widget');

function forum_delete_widget( OW_Event $event )
{
    BOL_ComponentAdminService::getInstance()->deleteWidget('FORUM_CMP_LatestTopicsWidget');
}
OW::getEventManager()->bind('forum.delete_widget', 'forum_delete_widget');

function forum_create_group( OW_Event $event )
{
    $params = $event->getParams();

    if ( !$params['entity'] || !isset($params['name']) || !isset($params['description']) || !isset($params['entityId']) )
    {
        return;
    }

    $forumService = FORUM_BOL_ForumService::getInstance();

    $forumGroup = $forumService->findGroupByEntityId($params['entity'], $params['entityId']);

    if ( !isset($forumGroup) )
    {
        $section = $forumService->findSectionByEntity($params['entity']);

        $forumGroup = new FORUM_BOL_Group();
        $forumGroup->sectionId = $section->getId();
        $forumGroup->order = $forumService->getNewGroupOrder($section->getId());

        $forumGroup->name = $params['name'];
        $forumGroup->description = $params['description'];
        $forumGroup->entityId = $params['entityId'];

        $forumService->saveOrUpdateGroup($forumGroup);
    }
}
OW::getEventManager()->bind('forum.create_group', 'forum_create_group');

function forum_delete_group( OW_Event $event )
{
    $params = $event->getParams();

    if ( !isset($params['entityId']) || !isset($params['entity']) )
    {
        return;
    }

    $forumService = FORUM_BOL_ForumService::getInstance();
    $group = $forumService->findGroupByEntityId($params['entity'], $params['entityId']);

    if ( !empty($group) )
    {
        $forumService->deleteGroup($group->getId());
    }
}
OW::getEventManager()->bind('forum.delete_group', 'forum_delete_group');

function forum_on_notify_actions( BASE_CLASS_EventCollector $e )
{
    $e->add(array(
        'section' => 'forum',
        'action' => 'forum-add_post',
        'sectionIcon' => 'ow_ic_forum',
        'sectionLabel' => OW::getLanguage()->text('forum', 'email_notifications_section_label'),
        'description' => OW::getLanguage()->text('forum', 'email_notifications_setting_post'),
        'selected' => true
    ));
}
OW::getEventManager()->bind('base.notify_actions', 'forum_on_notify_actions');

function forum_add_post( OW_Event $e )
{
    $params = $e->getParams();

    $postId = $params['postId'];

    $forumService = FORUM_BOL_ForumService::getInstance();
    $post = $forumService->findPostById($postId);

    $userIds = FORUM_BOL_SubscriptionService::getInstance()->findTopicSubscribers($post->topicId);
    if ( empty($userIds) )
    {
        return;
    }

    $postUrl = $forumService->getPostUrl($post->topicId, $postId);
    $userAvatar = BOL_AvatarService::getInstance()->getAvatarUrl($post->userId, 1);
    $topic = $forumService->findTopicById($post->topicId);
    $topicUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $post->topicId));

    $string = OW::getLanguage()->text('forum', 'email_notification_post', array(
            'userName' => BOL_UserService::getInstance()->getDisplayName($post->userId),
            'userUrl' => BOL_UserService::getInstance()->getUserUrl($post->userId),
            'postUrl' => $postUrl,
            'topicUrl' => $topicUrl,
            'title' => strip_tags($topic->title)
        ));

    $params = array(
        'plugin' => 'forum',
        'action' => 'forum-add_post',
        'string' => $string,
        'avatar' => $userAvatar,
        'content' => strip_tags($post->text),
        'url' => $postUrl,
        'time' => time()
    );

    foreach ( $userIds as $uid )
    {
        if ( $uid == $post->userId )
        {
            continue;
        }

        $params['userId'] = $uid;

        $event = new OW_Event('base.notify', $params);
        OW::getEventManager()->trigger($event);
    }
}
OW::getEventManager()->bind('forum.add_post', 'forum_add_post');

function forum_ads_enabled( BASE_EventCollector $event )
{
    $event->add('forum');
}
OW::getEventManager()->bind('ads.enabled_plugins', 'forum_ads_enabled');


$credits = new FORUM_CLASS_Credits();
OW::getEventManager()->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));

function forum_add_auth_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(
        array(
            'forum' => array(
                'label' => $language->text('forum', 'auth_group_label'),
                'actions' => array(
                    'edit' => $language->text('forum', 'auth_action_label_edit'),
                    'view' => $language->text('forum', 'auth_action_label_view'),
                    'subscribe' => $language->text('forum', 'auth_action_label_subscribe'),
                    'move_topic_to_hidden' => $language->text('forum', 'auth_action_label_move_topic_to_hidden')
                )
            )
        )
    );
}
OW::getEventManager()->bind('admin.add_auth_labels', 'forum_add_auth_labels');

//Feed
function forum_feed_on_entity_add( OW_Event $e )
{
    $params = $e->getParams();
    $data = $e->getData();

    if ( $params['entityType'] != 'forum-topic' )
    {
        return;
    }

    $topicId = (int) $params['entityId'];
    $service = FORUM_BOL_ForumService::getInstance();
    $topicDto = $service->findTopicById($topicId);
    $postDto = $service->findTopicFirstPost($topicId);
    $groupDto = $service->findGroupById($topicDto->groupId);
    $sectionDto = $service->findSectionById($groupDto->sectionId);
    $isHidden = (bool) $sectionDto->isHidden;

    if ( $postDto === null )
    {
        return;
    }

    if ( $groupDto->isPrivate )
    {
        return;
    }

    $topicUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicDto->id));
    $content = UTIL_String::truncate(strip_tags($postDto->text), 150, '...');
    $title = UTIL_String::truncate(strip_tags($topicDto->title), 100, '...');

    $data = array(
        'features' => array('likes'),
        'ownerId' => $topicDto->userId,
        'time' => (int) $postDto->createStamp,
        'content' => '<div class="ow_newsfeed_item_content" style="float: none">
                <a class="ow_newsfeed_item_title" href="' . $topicUrl . '">' . $title . '</a>
                <div class="ow_remark ow_smallmargin">' . $content . '</div>
                <div class="ow_newsfeed_action_activity forum_newsfeed_activity">[ph:activity]</div>
            </div>',
        'view' => array(
            'iconClass' => 'ow_ic_forum'
        ),
        'toolbar' => array(array(
                'href' => $topicUrl,
                'label' => OW::getLanguage()->text('forum', 'feed_toolbar_discuss')
        ))
    );

    if ( $isHidden )
    {
        $data['params']['feedType'] = $sectionDto->entity;
        $data['params']['feedId'] = $groupDto->entityId;
        $data['params']['visibility'] = 2 + 4 + 8; // Visible for follows(2), autor (4) and current feed (8)
        $data['params']['postOnUserFeed'] = false;
        $data['contextFeedType'] = $data['params']['feedType'];
        $data['contextFeedId'] = $data['params']['feedId'];
    }

    $e->setData($data);
}
OW::getEventManager()->bind('feed.on_entity_add', 'forum_feed_on_entity_add');

function forum_feed_on_post_add( OW_Event $e )
{
    $params = $e->getParams();

    $event = new OW_Event('feed.activity', array(
            'pluginKey' => 'forum',
            'entityType' => 'forum-topic',
            'entityId' => $params['topicId'],
            'userId' => $params['userId'],
            'activityType' => 'forum-post',
            'activityId' => $params['postId'],
            'subscribe' => true
            ), array(
            'postId' => $params['postId']
        ));
    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind('forum.add_post', 'forum_feed_on_post_add');

function forum_feed_on_item_render( OW_Event $event )
{
    $params = $event->getParams();
    $data = $event->getData();
    $language = OW::getLanguage();

    if ( $params['action']['entityType'] != 'forum-topic' )
    {
        return;
    }

    $service = FORUM_BOL_ForumService::getInstance();
    $postCount = $service->findTopicPostCount($params['action']['entityId']) - 1;

    if ( !$postCount )
    {
        return;
    }

    if ( is_array($data['toolbar']) )
    {
        $data['toolbar'][] = array(
            'label' => $language->text('forum', 'feed_toolbar_replies', array('postCount' => $postCount))
        );
    }

    $event->setData($data);

    $postIds = array();
    foreach ( $params['activity'] as $activity )
    {
        if ( $activity['activityType'] == 'forum-post' )
        {
            $postIds[] = $activity['data']['postId'];
        }
    }

    if ( empty($postIds) )
    {
        return;
    }

    $postDto = null;
    foreach ( $postIds as $pid )
    {
        $postDto = $service->findPostById($pid);
        if ( $postDto !== null )
        {
            break;
        }
    }

    if ( $postDto === null )
    {
        return;
    }

    $postUrl = $service->getPostUrl($postDto->topicId, $postDto->id);
    $postUrlEmbed = '...';
    $content = UTIL_String::truncate(strip_tags($postDto->text), 100, $postUrlEmbed);
    $usersData = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($postDto->userId), true, true, true, false);

    $avatarData = $usersData[$postDto->userId];
    $postUrl = $service->getPostUrl($postDto->topicId, $postDto->id);

    $ipcContent = OW::getThemeManager()->processDecorator('mini_ipc', array(
            'avatar' => $avatarData, 'profileUrl' => $avatarData['url'], 'displayName' => $avatarData['title'], 'content' => $content));

    $data['assign']['activity'] = array('template' => 'activity', 'vars' => array(
            'title' => $language->text('forum', 'feed_activity_last_reply', array('postUrl' => $postUrl)),
            'content' => $ipcContent
        ));

    $event->setData($data);
}
OW::getEventManager()->bind('feed.on_item_render', 'forum_feed_on_item_render');

function forum_feed_collect_configurable_activity( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(array(
        'label' => $language->text('forum', 'feed_content_label'),
        'activity' => 'create:forum-topic'
    ));

    $event->add(array(
        'label' => $language->text('forum', 'feed_content_replies_label'),
        'activity' => 'forum-post:forum-topic'
    ));
}
OW::getEventManager()->bind('feed.collect_configurable_activity', 'forum_feed_collect_configurable_activity');

function forum_subscribe_user( OW_Event $e )
{
    $params = $e->getParams();
    $userId = (int) $params['userId'];
    $topicId = (int) $params['topicId'];

    if ( !$userId || ! $topicId )
    {
        return false;
    }

    $service = FORUM_BOL_SubscriptionService::getInstance();

    if ( $service->isUserSubscribed($userId, $topicId) )
    {
        return true;
    }

    $subs = new FORUM_BOL_Subscription();
    $subs->userId = $userId;
    $subs->topicId = $topicId;

    $service->addSubscription($subs);

    return true;
}

OW::getEventManager()->bind('forum.subscribe_user', 'forum_subscribe_user');