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
 * Forum group action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.controllers
 * @since 1.0
 */
class FORUM_CTRL_Group extends OW_ActionController
{
    /**
     * @var FORUM_BOL_ForumService
     */
    private $forumService;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->forumService = FORUM_BOL_ForumService::getInstance();

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'forum', 'forum');
        }
    }

    /**
     * Controller's default action
     *
     * @param array $params
     */
    public function index( array $params )
    {
        if ( !isset($params['groupId']) || !($groupId = (int) $params['groupId']) )
        {
            throw new Redirect404Exception();
        }

        $groupInfo = $this->forumService->getGroupInfo($groupId);
        $forumSection = $this->forumService->findSectionById($groupInfo->sectionId);

        $isHidden = $forumSection->isHidden;

        $userId = OW::getUser()->getId();

        if ( $isHidden )
        {
            $isModerator = OW::getUser()->isAuthorized($forumSection->entity);

            $event = new OW_Event('forum.can_view', array(
                'entity' => $forumSection->entity,
                'entityId' => $groupInfo->entityId
            ), true);
            OW::getEventManager()->trigger($event);

            $canView = $event->getData();

            //check permissions
            $canEdit = OW::getUser()->isAuthorized($forumSection->entity, 'add_topic', $userId);

            $event = new OW_Event('forum.check_permissions', array('entity' => $forumSection->entity, 'entityId' => $groupInfo->entityId));
            OW::getEventManager()->trigger($event);

            $canPost = $event->getData();
            $canEdit = $canEdit && $canPost || $isModerator ? true : false;
        }
        else
        {
            $isModerator = OW::getUser()->isAuthorized('forum');

            $canView = OW::getUser()->isAuthorized('forum', 'view');
            $canEdit = OW::getUser()->isAuthorized('forum', 'edit');

            $canEdit = $canEdit || $isModerator ? true : false;
        }

        if ( $groupInfo->isPrivate )
        {
            if ( !$userId )
            {
                $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
                return;
            }
            else if ( !$isModerator )
            {
                if ( !$this->forumService->isPrivateGroupAvailable($userId, json_decode($groupInfo->roles)) )
                {
                    $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
                    return;
                }
            }
        }

        if (!$canView)
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }
        //$this->assign('canView', $canView);

        $page = !empty($_GET['page']) && (int) $_GET['page'] ? abs((int) $_GET['page']) : 1;

        if ( !$groupInfo )
        {
            $forumUrl = OW::getRouter()->urlForRoute('forum-default');
            $this->redirect($forumUrl);
        }

        $topicList = $this->forumService->getGroupTopicList($groupId, $page);
        $topicCount = $this->forumService->getGroupTopicCount($groupId);

        // get usernames list
        $userIds = array();

        $topicIds = array();

        foreach ( $topicList as $topic )
        {
            array_push($topicIds, $topic['id']);

            if ( isset($topic['lastPost']) && !in_array($topic['lastPost']['userId'], $userIds) )
            {
                array_push($userIds, $topic['lastPost']['userId']);
            }
        }

        $attachments = FORUM_BOL_PostAttachmentService::getInstance()->getAttachmentsCountByTopicIdList($topicIds);
        $this->assign('attachments', $attachments);

        $usernames = BOL_UserService::getInstance()->getUserNamesForList($userIds);
        $this->assign('usernames', $usernames);

        $displayNames = BOL_UserService::getInstance()->getDisplayNamesForList($userIds);
        $this->assign('displayNames', $displayNames);

        $perPage = $this->forumService->getTopicPerPageConfig();
        $pageCount = ($topicCount) ? ceil($topicCount / $perPage) : 1;
        $paging = new BASE_CMP_Paging($page, $pageCount, $perPage);
        $this->assign('paging', $paging->render());

        $addTopicUrl = OW::getRouter()->urlForRoute('add-topic', array('groupId' => $groupId));
        $this->assign('addTopicUrl', $addTopicUrl);

        $this->assign('canEdit', $canEdit);
        $this->assign('groupId', $groupId);
        $this->assign('topicList', $topicList);
        $this->assign('isHidden', $isHidden);

        $groupName = htmlspecialchars($groupInfo->name);
        OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'forum_page_heading', array('forum' => $groupName)));
        OW::getDocument()->setHeadingIconClass('ow_ic_forum');

        OW::getDocument()->setTitle($groupName);
        OW::getDocument()->setDescription(
            OW::getLanguage()->text('forum', 'group_meta_description', array('group' => $groupName))
        );

        if ( $isHidden )
        {
            $event = new OW_Event('forum.find_forum_caption', array('entity' => $forumSection->entity, 'entityId' => $groupInfo->entityId));
            OW::getEventManager()->trigger($event);

            $eventData = $event->getData();
            $componentForumCaption = $eventData['component'];
            if (!empty($componentForumCaption))
            {
                $this->assign('componentForumCaption', $componentForumCaption->render());
            }
            else
            {
                $componentForumCaption = false;
                $this->assign('componentForumCaption', $componentForumCaption);
            }

            OW::getNavigation()->deactivateMenuItems(OW_Navigation::MAIN);
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, $forumSection->entity, $eventData['key']);
        }
        else
        {
            $bcItems = array(
                array(
                    'href' => OW::getRouter()->urlForRoute('forum-default'),
                    'label' => OW::getLanguage()->text('forum', 'forum_index')
                ),
                array(
                    'href' => OW::getRouter()->urlForRoute('forum-default') . '#section-' . $groupInfo->sectionId,
                    'label' => $forumSection->name
                ),
                array(
                    'label' => $groupInfo->name
                )
            );

            $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems);
            $this->addComponent('breadcrumb', $breadCrumbCmp);
        }

        $this->addComponent('search', new FORUM_CMP_ForumSearch(array('scope' => 'group', 'groupId' => $groupId)));
    }
}
