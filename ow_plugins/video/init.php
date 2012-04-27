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
$plugin = OW::getPluginManager()->getPlugin('video');

$classesToAutoload = array(
    'VideoProviders' => $plugin->getRootDir() . 'classes' . DS . 'video_providers.php'
);

OW::getAutoloader()->addClassArray($classesToAutoload);

OW::getRouter()->addRoute(
    new OW_Route(
        'video_view_list',
        'video/viewlist/:listType/',
        'VIDEO_CTRL_Video',
        'viewList',
        array('listType' => array('default' => 'latest'))
    )
);

OW::getRouter()->addRoute(new OW_Route('video_list_index', 'video/', 'VIDEO_CTRL_Video', 'viewList'));

OW::getRouter()->addRoute(new OW_Route('view_clip', 'video/view/:id/', 'VIDEO_CTRL_Video', 'view'));
OW::getRouter()->addRoute(new OW_Route('edit_clip', 'video/edit/:id/', 'VIDEO_CTRL_Video', 'edit'));
OW::getRouter()->addRoute(new OW_Route('view_list', 'video/viewlist/:listType/', 'VIDEO_CTRL_Video', 'viewList'));
OW::getRouter()->addRoute(new OW_Route('view_tagged_list_st', 'video/viewlist/tagged/', 'VIDEO_CTRL_Video', 'viewTaggedList'));
OW::getRouter()->addRoute(new OW_Route('view_tagged_list', 'video/viewlist/tagged/:tag', 'VIDEO_CTRL_Video', 'viewTaggedList'));
OW::getRouter()->addRoute(new OW_Route('video_user_video_list', 'video/user-video/:user', 'VIDEO_CTRL_Video', 'viewUserVideoList'));

OW::getRouter()->addRoute(new OW_Route('video_admin_config', 'admin/video/', 'VIDEO_CTRL_Admin', 'index'));

OW::getThemeManager()->addDecorator('video_list_item', $plugin->getKey());

function video_elst_add_new_content_item( BASE_CLASS_EventCollector $event )
{
    if ( !OW::getUser()->isAuthorized('video', 'add') )
    {
        return;
    }
    
    $resultArray = array(
        BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_video',
        BASE_CMP_AddNewContent::DATA_KEY_URL => OW::getRouter()->urlFor('VIDEO_CTRL_Add', 'index'),
        BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('video', 'video')
    );

    $event->add($resultArray);
}

OW::getEventManager()->bind(BASE_CMP_AddNewContent::EVENT_NAME, 'video_elst_add_new_content_item');


function video_delete_user_content( OW_Event $event )
{
    $params = $event->getParams();

    if ( !isset($params['deleteContent']) || !(bool) $params['deleteContent'] )
    {
        return;
    }

    $userId = (int) $params['userId'];

    if ( $userId > 0 )
    {
        VIDEO_BOL_ClipService::getInstance()->deleteUserClips($userId);
    }
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'video_delete_user_content');


function video_on_notify_actions( BASE_CLASS_EventCollector $e )
{
    $e->add(array(
        'section' => 'video',
        'action' => 'video-add_comment',
        'description' => OW::getLanguage()->text('video', 'email_notifications_setting_comment'),
        'sectionIcon' => 'ow_ic_video',
        'sectionLabel' => OW::getLanguage()->text('video', 'email_notifications_section_label'),
        'selected' => true
    ));
}
OW::getEventManager()->bind('base.notify_actions', 'video_on_notify_actions');

function video_add_comment_notification( OW_Event $event )
{
    $params = $event->getParams();

    if ( empty($params['entityType']) || $params['entityType'] !== 'video_comments' )
    {
        return;
    }

    $entityId = $params['entityId'];
    $userId = $params['userId'];
    $commentId = $params['commentId'];

    $clipService = VIDEO_BOL_ClipService::getInstance();
    $userService = BOL_UserService::getInstance();

    $clip = $clipService->findClipById($entityId);

    if ( $clip->userId != $userId )
    {
        $comment = BOL_CommentService::getInstance()->findComment($commentId);
        $url = OW::getRouter()->urlForRoute('view_clip', array('id' => $entityId));

        $event = new OW_Event('base.notify', array(
                'plugin' => 'video',
                'pluginIcon' => 'ow_ic_video',
                'action' => 'video-add_comment',
                'userId' => $clip->userId,
                'string' => OW::getLanguage()->text('video', 'email_notifications_comment', array(
                    'userName' => $userService->getDisplayName($userId),
                    'userUrl' => $userService->getUserUrl($userId),
                    'videoUrl' => $url,
                    'videoTitle' => strip_tags($clip->title)
                )),
                'content' => $comment->getMessage(),
                'url' => $url
            ));

        OW::getEventManager()->trigger($event);
    }
}
OW::getEventManager()->bind('base_add_comment', 'video_add_comment_notification');

function video_feed_entity_add( OW_Event $e )
{
    $params = $e->getParams();
    $data = $e->getData();
    
    if ( $params['entityType'] != 'video_comments' )
    {
        return;
    }
    
    $videoService = VIDEO_BOL_ClipService::getInstance();
    $clip = $videoService->findClipById($params['entityId']);
    $thumb = $videoService->getClipThumbUrl($clip->id);
    
    $url = OW::getRouter()->urlForRoute('view_clip', array('id' => $clip->id));

    $content = UTIL_String::truncate(strip_tags($clip->description), 150, '...');
    $title = UTIL_String::truncate(strip_tags($clip->title), 100, '...');

    if ( $thumb == "undefined" )
    {
        $thumb = $videoService->getClipDefaultThumbUrl();

        $markup  = '<div class="clearfix"><div class="ow_newsfeed_item_picture">';
        $markup .= '<a style="display: block;" href="' . $url . '"><div style="width: 75px; height: 60px; background: url('.$thumb.') no-repeat center center;"></div></a>';
        $markup .= '</div><div class="ow_newsfeed_item_content"><a href="' . $url . '">' . $title . '</a><div class="ow_remark">'; 
        $markup .= $content . '</div></div></div>';       
    }
    else
    {
        $markup  = '<div class="clearfix ow_newsfeed_large_image"><div class="ow_newsfeed_item_picture">';
        $markup .= '<a href="' . $url . '"><img src="' . $thumb . '" /></a>';
        $markup .= '</div><div class="ow_newsfeed_item_content"><a href="' . $url . '">' . $title . '</a><div class="ow_remark">'; 
        $markup .= $content . '</div></div></div>';
    }
    
    $data = array(
        'time' => (int) $clip->addDatetime,
        'ownerId' => $clip->userId,
        'content' => '<div class="clearfix">' . $markup . '</div>',
        'view' => array(
            'iconClass' => 'ow_ic_video'
        )
    );
    
    $e->setData($data);
}

OW::getEventManager()->bind('feed.on_entity_add', 'video_feed_entity_add');


function video_ads_enabled( BASE_EventCollector $event )
{
    $event->add('video');
}

OW::getEventManager()->bind('ads.enabled_plugins', 'video_ads_enabled');


$credits = new VIDEO_CLASS_Credits();
OW::getEventManager()->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));


function video_add_auth_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(
        array(
            'video' => array(
                'label' => $language->text('video', 'auth_group_label'),
                'actions' => array(
                    'add' => $language->text('video', 'auth_action_label_add'),
                    'view' => $language->text('video', 'auth_action_label_view'),
                    'add_comment' => $language->text('video', 'auth_action_label_add_comment'),
                    'delete_comment_by_content_owner' => $language->text('video', 'auth_action_label_delete_comment_by_content_owner')
                )
            )
        )
    );
}

OW::getEventManager()->bind('admin.add_auth_labels', 'video_add_auth_labels');


function video_privacy_add_action( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $action = array(
        'key' => 'video_view_video',
        'pluginKey' => 'video',
        'label' => $language->text('video', 'privacy_action_view_video'),
        'description' => '',
        'defaultValue' => 'everybody'
    );

    $event->add($action);
}

OW::getEventManager()->bind('plugin.privacy.get_action_list', 'video_privacy_add_action');


function video_on_change_privacy( OW_Event $e )
{
    $params = $e->getParams();
    $userId = (int) $params['userId'];
    
    $actionList = $params['actionList'];
    
    if ( empty($actionList['video_view_video']) )
    {
        return;
    }
    
    VIDEO_BOL_ClipService::getInstance()->updateUserClipsPrivacy($userId, $actionList['video_view_video']);
}

OW::getEventManager()->bind('plugin.privacy.on_change_action_privacy', 'video_on_change_privacy');

function video_feed_collect_configurable_activity( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(array(
        'label' => $language->text('video', 'feed_content_label'),
        'activity' => '*:video_comments'
    ));
}

OW::getEventManager()->bind('feed.collect_configurable_activity', 'video_feed_collect_configurable_activity');

function video_feed_collect_privacy( BASE_CLASS_EventCollector $event )
{
    $event->add(array('create:video_comments', 'video_view_video'));
}

OW::getEventManager()->bind('feed.collect_privacy', 'video_feed_collect_privacy');