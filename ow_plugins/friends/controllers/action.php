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
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow_plugins.friends.controllers
 * @since 1.0
 */
class FRIENDS_CTRL_Action extends OW_ActionController
{

    /**
     * Request new friendship controller
     * 
     * @param array $params 
     */
    public function request( $params )
    {
        if (!OW::getUser()->isAuthenticated())
        {
           throw new AuthenticateException();
        }
        
        $userService = BOL_UserService::getInstance();

        $requesterId = OW::getUser()->getId();

        $userId = (int) $params['id'];
        
        if ( BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $userId) )
        {
            throw new Redirect404Exception();
            return;
        }

        $user = $userService->findUserById($userId);

        $service = FRIENDS_BOL_Service::getInstance();

        if( $service->findFriendship($requesterId, $userId) === null )
        {
            $service->request($requesterId, $userId);

            $this->onRequest($userId);

            OW::getFeedback()->info(OW::getLanguage()->text('friends', 'feedback_request_was_sent'));
        }
        else
        {
            OW::getFeedback()->error(OW::getLanguage()->text('friends', 'feedback_request_already_sent_error_message'));
        }

        if ( $params['backUri'] )
        {
            $this->redirect($params['backUri']);
        }

        $this->redirect($_SERVER['HTTP_REFERER']);
    }
    
    private function onRequest( $userId )
    {
        $requesterId = OW::getUser()->getId();

        $reqr = BOL_UserService::getInstance()->findUserById($requesterId);
        $displayName = BOL_UserService::getInstance()->getDisplayName($reqr->getId());
        $userUrl = BOL_UserService::getInstance()->getUserUrl($reqr->getId());

        $event = new OW_Event('base.notify', array(
                'plugin' => 'friends',
                'action' => 'friends-request',
                'userId' => $userId,
                'string' => OW::getLanguage()->text('friends', 'notify_request_string', array('userUrl' => $userUrl, 'displayName' => $displayName)),
                'content' => OW::getLanguage()->text('friends', 'notify_request_content_string', array('url' => OW::getRouter()->urlForRoute('friends_lists', array('list' => 'got-requests')))),
                'time' => time(),
                'url' => OW::getRouter()->urlForRoute('friends_got_requests'),
                'avatar' => BOL_AvatarService::getInstance()->getAvatarUrl($reqr->getId())
            ));

        OW::getEventManager()->trigger($event);
    }

    private function onAccept( $userId, $requesterId, FRIENDS_BOL_Friendship $frendshipDto )
    {
        $se = BOL_UserService::getInstance();
    
        $names = $se->getDisplayNamesForList(array($requesterId, $userId));
        $unames = $se->getUserNamesForList(array($requesterId, $userId));
        $avatars = BOL_AvatarService::getInstance()->getAvatarsUrlList(array($requesterId, $userId));
        $uUrls = $se->getUserUrlsForList(array($requesterId, $userId));

        //Add Newsfeed activity action
        $event = new OW_Event('feed.action', array(
            'pluginKey' => 'friends',
            'entityType' => 'friend_add',
            'entityId' => $frendshipDto->id,
            'userId' => array($userId, $requesterId),
            'feedType' => 'user',
            'feedId' => $requesterId
        ), array(
            'line' => OW::getLanguage()->text('friends', 'activity_title', array(
                    'user_url' => $uUrls[$userId],
                    'name' => $names[$userId],
                    'requester_url' => $uUrls[$requesterId],
                    'requester_name' => $names[$requesterId]
            )),
            'content' => '<a href="' . $uUrls[$userId] . '"><img title="' . $names[$userId] . '" src="' . $avatars[$userId] . '" /></a>&nbsp
                <a href="' . $uUrls[$requesterId] . '"><img title="' . $names[$requesterId] . '" src="' . $avatars[$requesterId] . '" /></a>'
        ));
        OW::getEventManager()->trigger($event);


        //Send notification about accept of friendship request
        $user = BOL_UserService::getInstance()->findUserById($userId);

        $receiver = (OW::getConfig()->getValue('base', 'display_name_question') == 'username') ? $user->getUsername() : BOL_UserService::getInstance()->getDisplayName($user->getId());

        $event = new OW_Event('base.notify', array(
                'plugin' => 'friends',
                'action' => 'friends-accept',
                'userId' => $requesterId,
                'string' => OW::getLanguage()->text('friends', 'notify_accept', array('receiver' => $receiver)),
                'content' => '',
                'time' => time(),
                'url' => OW::getRouter()->urlForRoute('friends_list'),
                'avatar' => BOL_AvatarService::getInstance()->getAvatarUrl($user->getId())
        ));

        $event = new OW_Event('friends.request-accepted', array(
                'senderId' => $requesterId,
                'recipientId' => OW::getUser()->getId(),
                'time' => time()
        ));

        OW::getEventManager()->trigger($event);

    }

    /**
     * Accept new friendship request 
     * 
     * @param array $params 
     */
    public function accept( $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $requesterId = (int) $params['id'];
        $userId = OW::getUser()->getId();

        $userService = BOL_UserService::getInstance();

        $service = FRIENDS_BOL_Service::getInstance();

        $frendshipDto = $service->accept($userId, $requesterId);

        $this->onAccept($userId, $requesterId, $frendshipDto);

        OW::getFeedback()->info(OW::getLanguage()->text('friends', 'feedback_request_accepted'));

        if ( !empty($params['backUrl']) )
        {
            $this->redirect($params['backUrl']);
        }

        $this->redirect(OW::getRouter()->urlForRoute('friends_list'));
    }

    /**
     * Ignore new friendship request
     * 
     * @param array $params 
     */
    public function ignore( $params )
    {
        
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }        
        
        $requesterId = (int) OW::getUser()->getId();
        $userId = (int) $params['id'];

        $service = FRIENDS_BOL_Service::getInstance();

        $service->ignore($userId, $requesterId);

        OW::getFeedback()->info(OW::getLanguage()->text('friends', 'feedback_request_ignored'));

        $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * Cancel friendship
     * 
     * @param array $params 
     */
    public function cancel( $params )
    {

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }        
        
        $requesterId = (int) $params['id'];
        $userId = (int) OW::getUser()->getId();

        $event = new OW_Event('friends.cancelled', array(
                'senderId' => $requesterId,
                'recipientId' => $userId
        ));

        OW::getEventManager()->trigger($event);

        OW::getFeedback()->info(OW::getLanguage()->text('friends', 'feedback_cancelled'));

        if ( $params['backUrl'] )
        {
            $this->redirect($params['backUrl']);
        }

        $this->redirect($_SERVER['HTTP_REFERER']);
    }

    public function activate( $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $requesterId = (int) $params['id'];
        $userId = (int) OW::getUser()->getId();

        FRIENDS_BOL_Service::getInstance()->activate($userId, $requesterId);

        OW::getFeedback()->info(OW::getLanguage()->text('friends', 'new_friend_added'));
        $this->redirect($_SERVER['HTTP_REFERER']);
    }
}
