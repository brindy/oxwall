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
 * Forum edit topic action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.controllers
 * @since 1.0
 */
class FORUM_CTRL_Search extends OW_ActionController
{
    private $forumService;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->forumService = FORUM_BOL_ForumService::getInstance();
    }
    

    /**
     * Controller's default action
     * 
     * @param array $params
     */
    public function inForums( array $params = null )
    {
        $plugin = OW::getPluginManager()->getPlugin('forum');
        $this->setTemplate($plugin->getCtrlViewDir() . 'search_result.html');

        if ( !empty($_GET['q']) )
        {
            $token = urldecode(htmlspecialchars(trim($_GET['q'])));
            $this->assign('token', $token);
            
            $page = !empty($_GET['page']) && (int) $_GET['page'] ? abs((int) $_GET['page']) : 1;
            
            if ( OW::getUser()->isAuthorized('forum') )
            {
                $excludeGroupIdList = array();
            }
            else 
            {
                $excludeGroupIdList = $this->forumService->getPrivateUnavailableGroupIdList(OW::getUser()->getId());
            }
            
            $topics = $this->forumService->searchInGroups($token, $page, $excludeGroupIdList);
            
            $authors = array();
            if ( $topics )
            {
                foreach ( $topics as &$topic )
                {
                    $topic['toolbar'] = array();
                    if ( !isset($topic['posts']) )
                    {
                        continue;
                    }
                    foreach ( $topic['posts'] as $post )
                    {
                        if ( !in_array($post['userId'], $authors) )
                        {
                            array_push($authors, $post['userId']);
                        }
                    }
                }
            }
            $this->assign('topics', $topics);
            
            // Paging
            $total = $this->forumService->countFoundTopicsInGroups($token, $excludeGroupIdList);
            $perPage = $this->forumService->getTopicPerPageConfig();
            $pages = (int) ceil($total / $perPage);
            $paging = new BASE_CMP_Paging($page, $pages, $perPage);
            $this->assign('paging', $paging->render());
            
            $this->assign('avatars', BOL_AvatarService::getInstance()->getDataForUserAvatars($authors));
        }
        else 
        {
            $this->redirect(OW::getRouter()->urlForRoute('forum-default'));
        }
        
        $this->addComponent('search', new FORUM_CMP_ForumSearch(
            array('scope' => 'all_forum', 'token' => $token))
        );
        
        OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'search_page_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_forum');
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'forum', 'forum');
    }
    
    public function inGroup( array $params = null )
    {
        $plugin = OW::getPluginManager()->getPlugin('forum');
        $this->setTemplate($plugin->getCtrlViewDir() . 'search_result.html');

        if ( !empty($_GET['q']) )
        {
            $token = urldecode(htmlspecialchars(trim($_GET['q'])));
            $this->assign('token', $token);
            
            $page = !empty($_GET['page']) && (int) $_GET['page'] ? abs((int) $_GET['page']) : 1;
            $groupId = (int)$params['groupId'];
            $forumGroup = $this->forumService->findGroupById($groupId);
            
            $userId = OW::getUser()->getId();

            $isHidden = $this->forumService->groupIsHidden($groupId);
            
            if ( $isHidden )
            {
                $forumSection = $this->forumService->findSectionById($forumGroup->sectionId);
                $isModerator = OW::getUser()->isAuthorized($forumSection->entity);
                
                $event = new OW_Event('forum.find_forum_caption', array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId));
                OW::getEventManager()->trigger($event);
    
                $eventData = $event->getData();
                $componentForumCaption = $eventData['component'];
    
                $this->addComponent('componentForumCaption', $componentForumCaption);
            }
            else
            {
                $isModerator = OW::getUser()->isAuthorized('forum');
            }
            
            if ( $forumGroup->isPrivate )
            {
                if ( !$userId )
                {
                    $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
                    return;
                } 
                else if ( !$isModerator )
                {
                    if ( !$this->forumService->isPrivateGroupAvailable($userId, json_decode($forumGroup->roles)) )
                    {
                        $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
                        return;
                    }
                }
            }
            
            $topics = $this->forumService->searchInGroup($token, $page, $groupId, $isHidden);
            
            $authors = array();
            if ( $topics )
            {
                foreach ( $topics as &$topic )
                {
                    if ( !in_array($topic['userId'], $authors) )
                    {
                        array_push($authors, $topic['userId']);
                    }
                        
                    if ( !isset($topic['posts']) )
                    {
                        continue;
                    }
                    foreach ( $topic['posts'] as $post )
                    {
                        if ( !in_array($post['userId'], $authors) )
                        {
                            array_push($authors, $post['userId']);
                        }
                    }
                }
                
                $this->assign('avatars', BOL_AvatarService::getInstance()->getDataForUserAvatars($authors));
            }
            
            $this->assign('topics', $topics);
            
            // Paging
            $total = $this->forumService->countFoundTopicsInGroup($token, $groupId, $isHidden);
            $perPage = $this->forumService->getTopicPerPageConfig();
            $pages = (int) ceil($total / $perPage);
            $paging = new BASE_CMP_Paging($page, $pages, $perPage);
            $this->assign('paging', $paging->render());
        }
        else
        {
            $this->redirect(OW::getRouter()->urlForRoute('forum-default'));
        }
        
        $this->addComponent('search', new FORUM_CMP_ForumSearch(
            array('scope' => 'group', 'token' => $token, 'groupId' => $groupId))
        );
        
        OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'search_page_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_forum');
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'forum', 'forum');
    }
    
    public function inTopic( array $params = null )
    {
        $plugin = OW::getPluginManager()->getPlugin('forum');
        $this->setTemplate($plugin->getCtrlViewDir() . 'search_result.html');

        if ( !empty($_GET['q']) )
        {
            $token = urldecode(htmlspecialchars(trim($_GET['q'])));
            $this->assign('token', $token);
            
            $topicId = (int)$params['topicId'];
            $userId = OW::getUser()->getId();
            
            $page = !empty($_GET['page']) && (int) $_GET['page'] ? abs((int) $_GET['page']) : 1;
            
            $topic = $this->forumService->findTopicById($topicId);
            $forumGroup = $this->forumService->findGroupById($topic->groupId);
            $forumSection = $this->forumService->findSectionById($forumGroup->sectionId);
            
            if ( $forumSection && $forumSection->isHidden )
            {
                $event = new OW_Event('forum.find_forum_caption', array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId));
                OW::getEventManager()->trigger($event);
    
                $eventData = $event->getData();
                $componentForumCaption = $eventData['component'];
    
                $this->addComponent('componentForumCaption', $componentForumCaption);
                
                $isModerator = OW::getUser()->isAuthorized($forumSection->entity);
            }
            else 
            {
                $isModerator = OW::getUser()->isAuthorized('forum');
            }
            
            if ( $forumGroup->isPrivate )
            {
                if ( !$userId )
                {
                    $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
                    return;
                } 
                else if ( !$isModerator )
                {
                    if ( !$this->forumService->isPrivateGroupAvailable($userId, json_decode($forumGroup->roles)) )
                    {
                        $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
                        return;
                    }
                }
            }
            
            $topics = $this->forumService->searchInTopic($token, $topicId, $page);
            
            if ( $topics )
            {
                $authors = array();
                foreach ( $topics as &$topic )
                {
                    if ( !in_array($topic['userId'], $authors) )
                    {
                        array_push($authors, $topic['userId']);
                    }
                        
                    if ( !isset($topic['posts']) )
                    {
                        continue;
                    }
                    foreach ( $topic['posts'] as $post )
                    {
                        if ( !in_array($post['userId'], $authors) )
                        {
                            array_push($authors, $post['userId']);
                        }
                    }
                }
                
                $this->assign('avatars', BOL_AvatarService::getInstance()->getDataForUserAvatars($authors));
            }
            
            $this->assign('topics', $topics);
        }
        else 
        {
            $this->redirect(OW::getRouter()->urlForRoute('forum-default'));
        }
        
        $this->addComponent('search', new FORUM_CMP_ForumSearch(
            array('scope' => 'topic', 'token' => $token, 'topicId' => $topicId))
        );
        
        OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'search_page_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_forum');
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'forum', 'forum');
    }
}
