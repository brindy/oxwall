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
$plugin = OW::getPluginManager()->getPlugin('links');

$key = strtoupper($plugin->getKey());

OW::getAutoloader()->addClass('Link', $plugin->getBolDir() . 'dto' . DS . 'link.php');
OW::getAutoloader()->addClass('LinkDao', $plugin->getBolDir() . 'dao' . DS . 'link_dao.php');
OW::getAutoloader()->addClass('LinkService', $plugin->getBolDir() . 'service' . DS . 'link_service.php');

OW::getRouter()->addRoute(new OW_Route('links', 'links', "{$key}_CTRL_List", 'index'));
OW::getRouter()->addRoute(new OW_Route('link', 'link/:id', "{$key}_CTRL_View", 'index'));

OW::getRouter()->addRoute(new OW_Route('link-save-new', 'links/new', "{$key}_CTRL_Save", 'index'));
OW::getRouter()->addRoute(new OW_Route('link-save-edit', 'links/edit/:id', "{$key}_CTRL_Save", 'index'));

OW::getRouter()->addRoute(new OW_Route('links-latest', 'links/latest', "{$key}_CTRL_List", 'index'));
OW::getRouter()->addRoute(new OW_Route('links-most-discussed', 'links/most-discussed', "{$key}_CTRL_List", 'index'));
OW::getRouter()->addRoute(new OW_Route('links-top-rated', 'links/top-rated', "{$key}_CTRL_List", 'index'));
OW::getRouter()->addRoute(new OW_Route('links-by-tag', 'links/browse-by-tag/', "{$key}_CTRL_List", 'index'));

OW::getRouter()->addRoute(new OW_Route('links-admin', 'admin/links', "{$key}_CTRL_Admin", 'index'));

function links_add_new_content_item( BASE_CLASS_EventCollector $event )
{
    if ( OW::getUser()->isAuthenticated() && OW::getUser()->isAuthorized('links', 'add') )
    {
        $resultArray = array(
            BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_link',
            BASE_CMP_AddNewContent::DATA_KEY_URL => OW::getRouter()->urlForRoute('link-save-new'),
            BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('links', 'add_new_link')
        );

        $event->add($resultArray);
    }
}
OW::getEventManager()->bind(BASE_CMP_AddNewContent::EVENT_NAME, 'links_add_new_content_item');

function links_on_user_delete( OW_Event $event )
{
    $params = $event->getParams();

    if ( empty($params['deleteContent']) )
    {
        return;
    }

    $userId = $params['userId'];

    $service = LinkService::getInstance();


    $count = (int) $service->countUserLinks($userId);

    if ( $count == 0 )
    {
        return;
    }

    $list = $service->findUserLinkList($userId, 0, $count);

    foreach ( $list as $link )
    {
        $service->delete($link);
    }
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'links_on_user_delete');

function links_on_link_comment_delete( OW_Event $event )
{
    $params = $event->getParams();

    if ( empty($params['entityType']) || $params['entityType'] !== 'link' )
        return;

    $entityId = $params['entityId'];
    $userId = $params['userId'];
    $commentId = (int) $params['commentId'];
}
//OW::getEventManager()->bind('base_delete_comment', 'links_on_link_comment_delete');

function links_on_notify_actions( BASE_CLASS_EventCollector $e )
{
    $e->add(array(
        'section' => 'links',
        'action' => 'links-add_comment',
        'sectionLabel' => OW::getLanguage()->text('links', 'notification_section_label'),
        'description' => OW::getLanguage()->text('links', 'email_notifications_setting_comment'),
        'selected' => 1,
        'sectionIcon' => 'ow_ic_write'
    ));
}
OW::getEventManager()->bind('base.notify_actions', 'links_on_notify_actions');

function links_notify_on_post_comment_add( OW_Event $event )
{
    $params = $event->getParams();
    if ( empty($params['entityType']) || $params['entityType'] !== 'link' )
        return;

    $entityId = $params['entityId'];
    $userId = $params['userId'];
    $commentId = $params['commentId'];

    $linkService = LinkService::getInstance();

    $link = $linkService->findById($entityId);

    $title = $link->getTitle();

    $actor = array(
        'name' => BOL_UserService::getInstance()->getDisplayName($userId),
        'url' => BOL_UserService::getInstance()->getUserUrl($userId),
        'avatar-url' => BOL_AvatarService::getInstance()->getAvatarUrl($userId),
    );

    $comment = BOL_CommentService::getInstance()->findComment($commentId);

    $data = array(
        'string' => OW::getLanguage()->text('links', 'comment_notification_string',
            array(
                'actor' => $actor['name'],
                'actorUrl' => $actor['url'],
                'title' => $link->getTitle(),
                'url' => OW::getRouter()->urlForRoute('link', array('id' => $link->getId()))
            )
        ),
        'content' => ( mb_strlen($comment->getMessage()) > 30 ) ? nl2br(mb_substr($comment->getMessage(), 0, 30)) . '...' : $comment->getMessage(),
        'commentId' => (int) $comment->getId(),
        'avatar' => $actor['avatar-url']
    );

    $event = new OW_Event('base.notify', array(
            'plugin' => 'links',
            'action' => 'links-add_comment',
            'userId' => $link->getUserId(),
            'string' => $data['string'],
            'content' => $data['content'],
            'url' => OW::getRouter()->urlForRoute('link', array('id' => $link->getId())),
            'avatar' => BOL_AvatarService::getInstance()->getAvatarUrl($userId),
        ));

    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind('base_add_comment', 'links_notify_on_post_comment_add');

function links_feed_entity_add( OW_Event $e )
{
    $params = $e->getParams();
    $data = $e->getData();

    if ( $params['entityType'] != 'link' )
    {
        return;
    }

    $linkService = LinkService::getInstance();
    $link = $linkService->findById($params['entityId']);

    $url = OW::getRouter()->urlForRoute('link', array('id' => $link->id));

    $title = UTIL_String::truncate(strip_tags($link->title), 100, '...');
    $description = UTIL_String::truncate(strip_tags($link->description), 150, '...');

    $data = array(
        'time' => $link->timestamp,
        'ownerId' => $link->userId,
        'string' => $title,
        'content' => '<a href="' . $link->url . '">' . $link->url . '</a>
            <div class="ow_remark" style="paddig-top: 4px">' . $description . '</div>',
        'view' => array(
            'iconClass' => 'ow_ic_link'
        ),
        'toolbar' => array(array(
                'href' => $url,
                'label' => OW::getLanguage()->text('links', 'feed_toolbar_permalink')
        ))
    );

    $e->setData($data);
}
OW::getEventManager()->bind('feed.on_entity_add', 'links_feed_entity_add');

function links_ads_enabled( BASE_EventCollector $event )
{
    $event->add('links');
}
OW::getEventManager()->bind('ads.enabled_plugins', 'links_ads_enabled');


$credits = new LINKS_CLASS_Credits();
OW::getEventManager()->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));

function links_add_auth_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(
        array(
            'links' => array(
                'label' => $language->text('links', 'auth_group_label'),
                'actions' => array(
                    'add' => $language->text('links', 'auth_action_label_add'),
                    'view' => $language->text('links', 'auth_action_label_view'),
                    'add_comment' => $language->text('links', 'auth_action_label_add_comment'),
                    'delete_comment_by_content_owner' => $language->text('links', 'auth_action_label_delete_comment_by_content_owner')
                )
            )
        )
    );
}

OW::getEventManager()->bind('admin.add_auth_labels', 'links_add_auth_labels');

function links_feed_collect_configurable_activity( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(array(
        'label' => $language->text('links', 'feed_content_label'),
        'activity' => '*:link'
    ));
}

OW::getEventManager()->bind('feed.collect_configurable_activity', 'links_feed_collect_configurable_activity');


function links_privacy_add_action( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $action = array(
        'key' => LinkService::PRIVACY_ACTION_VIEW_LINKS,
        'pluginKey' => 'links',
        'label' => $language->text('links', 'privacy_action_view_links'),
        'description' => '',
        'defaultValue' => 'everybody'
    );

    $event->add($action);

    $action = array(
        'key' => LinkService::PRIVACY_ACTION_COMMENT_LINKS,
        'pluginKey' => 'links',
        'label' => $language->text('links', 'privacy_action_comment_links'),
        'description' => '',
        'defaultValue' => 'everybody'
    );

    $event->add($action);

}
OW::getEventManager()->bind('plugin.privacy.get_action_list', 'links_privacy_add_action');

function links_privacy_on_change_action_privacy( OW_Event $event )
{
    $params = $event->getParams();

    $userId = (int) $params['userId'];
    $actionList = $params['actionList'];
    $actionList = is_array($actionList) ? $actionList : array();

    if ( empty($actionList[LinkService::PRIVACY_ACTION_VIEW_LINKS]) )
    {
        return;
    }

    LinkService::getInstance()->updateLinksPrivacy($userId, $actionList[LinkService::PRIVACY_ACTION_VIEW_LINKS]);
}
OW::getEventManager()->bind('plugin.privacy.on_change_action_privacy', 'links_privacy_on_change_action_privacy');
