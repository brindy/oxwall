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
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.controllers
 * @since 1.0
 */
class NEWSFEED_CTRL_Feed extends OW_ActionController
{
    /**
     * 
     * @var NEWSFEED_BOL_Service
     */
    private $service;
    
    public function __construct()
    {
        $this->service = NEWSFEED_BOL_Service::getInstance();
    }
    
    public function follow()
    {
        $userId = (int) $_GET['userId'];
        $backUri = $_GET['backUri'];
        
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }
        
        if ( empty($userId) )
        {
            throw new InvalidArgumentException('Invalid parameter `userId`');
        }
        
        $eventParams = array(
            'userId' => OW::getUser()->getId(),
            'feedType' => 'user',
            'feedId' => $userId
        );
        
        OW::getEventManager()->trigger( new OW_Event('feed.add_follow', $eventParams) );
        
        $backUrl = OW_URL_HOME . $backUri;
        $username = BOL_UserService::getInstance()->getDisplayName($userId);
        OW::getFeedback()->info(OW::getLanguage()->text('newsfeed', 'follow_complete_message', array('username' => $username)));
        
        $this->redirect($backUrl);
    }
    
    public function unFollow()
    {
        $userId = (int) $_GET['userId'];
        $backUri = $_GET['backUri'];
        
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }
        
        if ( empty($userId) )
        {
            throw new InvalidArgumentException('Invalid parameter `userId`');
        }
        
        $this->service->removeFollow(OW::getUser()->getId(), 'user', $userId);
        
        $backUrl = OW_URL_HOME . $backUri;
        $username = BOL_UserService::getInstance()->getDisplayName($userId);
        OW::getFeedback()->info(OW::getLanguage()->text('newsfeed', 'unfollow_complete_message', array('username' => $username)));
        
        $this->redirect($backUrl);
    }
    
    public function like()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }
        
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }
        
        $entityType = $_GET['entityType'];
        $entityId = (int) $_GET['entityId'];
        
        $this->service->addLike(OW::getUser()->getId(), $entityType, $entityId);
        
        $event = new OW_Event('feed.after_like_added', array(
            'entityType' => $entityType,
            'entityId' => $entityId,
            'userId' => OW::getUser()->getId()
        ));
        
        OW::getEventManager()->trigger($event);
        
        $cmp = new NEWSFEED_CMP_Likes($entityType, $entityId);
        
        echo $cmp->render();
        
        exit;
    }
    
    public function statusUpdate()
    {
        if ( !OW::getRequest()->isPost() )
        {
            throw new Redirect404Exception();
        }
        
        $isAjax = OW::getRequest()->isAjax();
        
        if ( !isset($_POST['status']) )
        {
            if ( $isAjax )
            {
                echo "0";
                exit;
            }
            else
            {
                $this->redirect(OW_URL_HOME . $_GET['backUri']);
            }
        }
        
        if ( trim($_POST['status']) )
        {
            $status = strip_tags($_POST['status']);
            
            $statusDto = NEWSFEED_BOL_Service::getInstance()->saveStatus($_POST['feedType'], (int) $_POST['feedId'], $status);
            
            $event = new OW_Event('feed.after_status_update', array(
                'feedType' => $_POST['feedType'],
                'feedId' =>  $_POST['feedId'],
                'visibility' => (int) $_POST['visibility']
            ), array(
                'status' => $status,
                'statusId' => (int) $statusDto->id 
            ));
            
            OW::getEventManager()->trigger($event);
        }
        else
        {
            NEWSFEED_BOL_Service::getInstance()->removeStatus($_POST['feedType'], (int) $_POST['feedId']);
            
            $event = new OW_Event('feed.after_status_remove', array(
                'feedType' => $_POST['feedType'],
                'feedId' =>  $_POST['feedId']
            ));
            
            OW::getEventManager()->trigger($event);
        }
        
        if ( $isAjax )
        {
            echo '1';
            exit;
        }
        else
        {
            $this->redirect(OW_URL_HOME . $_GET['backUri']);
        }
    }
    
    public function remove()
    {
        if ( empty($_GET['actionId']) )
        {
            throw new Redirect404Exception();    
        }
        
        $id = (int) $_GET['actionId'];
        
        $this->service->removeActionById($id);
        
        OW::getFeedback()->info(OW::getLanguage()->text('newsfeed', 'feed_action_deleted_message'));
        
        $this->redirect(OW_URL_HOME . $_GET['backUri']);
    }
    
    public function loadItem()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }
        
        $this->service->getAction();
    }
}