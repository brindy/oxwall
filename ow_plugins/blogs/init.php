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
$plugin = OW::getPluginManager()->getPlugin('blogs');

$key = strtoupper($plugin->getKey());

OW::getAutoloader()->addClass('Post', $plugin->getBolDir() . 'dto' . DS . 'post.php');
OW::getAutoloader()->addClass('PostDao', $plugin->getBolDir() . 'dao' . DS . 'post_dao.php');
OW::getAutoloader()->addClass('PostService', $plugin->getBolDir() . 'service' . DS . 'post_service.php');


OW::getRouter()->addRoute(new OW_Route('blogs-uninstall', 'admin/blogs/uninstall', 'BLOGS_CTRL_Admin', 'uninstall'));


OW::getRouter()->addRoute(new OW_Route('post-save-new', 'blogs/post/new', "{$key}_CTRL_Save", 'index'));
OW::getRouter()->addRoute(new OW_Route('post-save-edit', 'blogs/post/edit/:id', "{$key}_CTRL_Save", 'index'));

OW::getRouter()->addRoute(new OW_Route('post', 'blogs/post/:id', "{$key}_CTRL_View", 'index'));

OW::getRouter()->addRoute(new OW_Route('post-part', 'blogs/post/:id/:part', "{$key}_CTRL_View", 'index'));

OW::getRouter()->addRoute(new OW_Route('user-blog', 'blogs/user/:user', "{$key}_CTRL_UserBlog", 'index'));

OW::getRouter()->addRoute(new OW_Route('user-post', 'blogs/:id', "{$key}_CTRL_View", 'index'));

OW::getRouter()->addRoute(new OW_Route('blogs', 'blogs', "{$key}_CTRL_Blog", 'index', array('list' => array(OW_Route::PARAM_OPTION_HIDDEN_VAR => 'latest'))));
OW::getRouter()->addRoute(new OW_Route('blogs.list', 'blogs/list/:list', "{$key}_CTRL_Blog", 'index'));

OW::getRouter()->addRoute(new OW_Route('blog-manage-posts', 'blogs/my-published-posts/', "{$key}_CTRL_ManagementPost", 'index'));
OW::getRouter()->addRoute(new OW_Route('blog-manage-drafts', 'blogs/my-drafts/', "{$key}_CTRL_ManagementPost", 'index'));
OW::getRouter()->addRoute(new OW_Route('blog-manage-comments', 'blogs/my-incoming-comments/', "{$key}_CTRL_ManagementComment", 'index'));

OW::getRouter()->addRoute(new OW_Route('blogs-admin', 'admin/blogs', "{$key}_CTRL_Admin", 'index'));

OW::getEventManager()->bind(OW_EventManager::ON_USER_SUSPEND, array(PostService::getInstance(), 'onAuthorSuspend'));

function blogs_add_new_content_item( BASE_CLASS_EventCollector $event )
{
    
    if ( OW::getUser()->isAuthenticated() && OW::getUser()->isAuthorized('blogs', 'add') )
    {
        $resultArray = array(
            BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_write',
            BASE_CMP_AddNewContent::DATA_KEY_URL => OW::getRouter()->urlForRoute('post-save-new'),
            BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('blogs', 'add_new_link')
        );

        $event->add($resultArray);    
    }
    
}
OW::getEventManager()->bind(BASE_CMP_AddNewContent::EVENT_NAME, 'blogs_add_new_content_item');

function blogs_on_notify_actions( BASE_CLASS_EventCollector $e )
{
    $e->add(array(
        'section' => 'blogs',
        'action' => 'blogs-add_comment',
        'description' => OW::getLanguage()->text('blogs', 'email_notifications_setting_comment'),
        'selected' => true,
        'sectionLabel' => OW::getLanguage()->text('blogs', 'notification_section_label'),
        'sectionIcon' => 'ow_ic_write'
    ));
}
OW::getEventManager()->bind('base.notify_actions', 'blogs_on_notify_actions');

function blogs_on_notify( OW_Event $event )
{
    $params = $event->getParams();

    if ( empty($params['entityType']) || $params['entityType'] !== 'blog-post' )
        return;

    $entityId = $params['entityId'];
    $userId = $params['userId'];
    $commentId = $params['commentId'];

    $postService = PostService::getInstance();

    $post = $postService->findById($entityId);

    $title = $post->getTitle();

    $actor = array(
        'name' => BOL_UserService::getInstance()->getDisplayName($userId),
        'url' => BOL_UserService::getInstance()->getUserUrl($userId)
    );

    $ownerId = $post->getAuthorId();

    $comment = BOL_CommentService::getInstance()->findComment($commentId);

    $data = array(
        'string' => OW::getLanguage()->text('blogs', 'comment_notification_string',
            array(
                'actor' => $actor['name'],
                'actorUrl' => $actor['url'],
                'title' => $post->getTitle(),
                'url' => OW::getRouter()->urlForRoute('post', array('id' => $post->getId()))
            )
        ),
        'content' => $comment->getMessage(),
        'commentId' => (int) $comment->getId()
    );

    $event = new OW_Event('base.notify', array(
            'plugin' => 'blogs',
            'action' => 'blogs-add_comment',
            'userId' => $ownerId,
            'string' => $data['string'],
            'content' => $data['content'],
            'url' => OW::getRouter()->urlForRoute('post', array('id' => $post->getId())),
            'avatar' => BOL_AvatarService::getInstance()->getAvatarUrl($userId),
        ));

    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind('base_add_comment', 'blogs_on_notify');

function blogs_on_post_comment_delete( OW_Event $event )
{
    $params = $event->getParams();

    if ( empty($params['entityType']) || $params['entityType'] !== 'blog-post' )
        return;

    $entityId = $params['entityId'];
    $userId = $params['userId'];
    $commentId = (int) $params['commentId'];
}

//OW::getEventManager()->bind('base_delete_comment', 'blogs_on_post_comment_delete');

function blogs_on_user_delete( OW_Event $event )
{
    $params = $event->getParams();

    if ( empty($params['deleteContent']) )
    {
        return;
    }

    $userId = $params['userId'];

    $service = PostService::getInstance();

    $count = (int) $service->countUserPost($userId);

    if ( $count == 0 )
    {
        return;
    }

    $list = $service->findUserPostList($userId, 0, $count);

    foreach ( $list as $post )
    {
        $service->delete($post);
    }
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'blogs_on_user_delete');

function blogs_feed_entity_add( OW_Event $e )
{
    $params = $e->getParams();
    $data = $e->getData();

    if ( $params['entityType'] != 'blog-post' )
    {
        return;
    }

    $service = PostService::getInstance();
    $post = $service->findById($params['entityId']);

    $url = OW::getRouter()->urlForRoute('post', array('id' => $post->id));

    $content = nl2br( UTIL_String::truncate(strip_tags($post->post), 150, '...') );
    $title = UTIL_String::truncate(strip_tags($post->title), 100, '...');

    $data = array(
        'time' => (int) $post->timestamp,
        'ownerId' => $post->authorId,
        'content' => '<a href="' . $url . '">' . $title . '</a>
            <div class="ow_remark" style="paddig-top: 4px">' . $content . '</div>',
        'view' => array(
            'iconClass' => 'ow_ic_write'
        )
    );

    $e->setData($data);
}
OW::getEventManager()->bind('feed.on_entity_add', 'blogs_feed_entity_add');

function blogs_ads_enabled( BASE_EventCollector $event )
{
    $event->add('blogs');
}
OW::getEventManager()->bind('ads.enabled_plugins', 'blogs_ads_enabled');

$credits = new BLOGS_CLASS_Credits();
OW::getEventManager()->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));

function blogs_add_auth_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(
        array(
            'blogs' => array(
                'label' => $language->text('blogs', 'auth_group_label'),
                'actions' => array(
                    'add' => $language->text('blogs', 'auth_action_label_add'),
                    'view' => $language->text('blogs', 'auth_action_label_view'),
                    'add_comment' => $language->text('blogs', 'auth_action_label_add_comment'),
                    'delete_comment_by_content_owner' => $language->text('blogs', 'auth_action_label_delete_comment_by_content_owner')
                )
            )
        )
    );
}
OW::getEventManager()->bind('admin.add_auth_labels', 'blogs_add_auth_labels');

function blogs_feed_collect_configurable_activity( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(array(
        'label' => $language->text('blogs', 'feed_content_label'),
        'activity' => '*:blog-post'
    ));
}
OW::getEventManager()->bind('feed.collect_configurable_activity', 'blogs_feed_collect_configurable_activity');


function blogs_feed_collect_privacy( BASE_CLASS_EventCollector $event )
{
    $event->add(array('*:blog-post', PostService::PRIVACY_ACTION_VIEW_BLOG_POSTS));
}
OW::getEventManager()->bind('feed.collect_privacy', 'blogs_feed_collect_privacy');

function blogs_privacy_add_action( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $action = array(
        'key' => PostService::PRIVACY_ACTION_VIEW_BLOG_POSTS,
        'pluginKey' => 'blogs',
        'label' => $language->text('blogs', 'privacy_action_view_blog_posts'),
        'description' => '',
        'defaultValue' => 'everybody'
    );

    $event->add($action);

    $action = array(
        'key' => PostService::PRIVACY_ACTION_COMMENT_BLOG_POSTS,
        'pluginKey' => 'blogs',
        'label' => $language->text('blogs', 'privacy_action_comment_blog_posts'),
        'description' => '',
        'defaultValue' => 'everybody'
    );

    $event->add($action);
}
OW::getEventManager()->bind('plugin.privacy.get_action_list', 'blogs_privacy_add_action');

function blogs_privacy_on_change_action_privacy( OW_Event $event )
{
    $params = $event->getParams();

    $userId = (int) $params['userId'];
    $actionList = $params['actionList'];
    $actionList = is_array($actionList) ? $actionList : array();

    if ( empty($actionList[PostService::PRIVACY_ACTION_VIEW_BLOG_POSTS]) )
    {
        return;
    }

    PostService::getInstance()->updateBlogsPrivacy($userId, $actionList[PostService::PRIVACY_ACTION_VIEW_BLOG_POSTS]);
}
OW::getEventManager()->bind('plugin.privacy.on_change_action_privacy', 'blogs_privacy_on_change_action_privacy');
