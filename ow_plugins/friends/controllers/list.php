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
class FRIENDS_CTRL_List extends OW_ActionController
{
    private $params;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get list of friendships
     * 
     * @param array $params 
     */
    public function index( $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
            return;
        }
        
        $this->params = $params;

        $language = OW::getLanguage();

        $userService = BOL_UserService::getInstance();

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;

        $rpp = (int) OW::getConfig()->getValue('base', 'users_count_on_page');

        $first = ($page - 1) * $rpp;
        $count = $rpp;

        $listType = $params['list'];

        if ( $listType == 'user-friends' )
        {
            $this->setPageHeading(OW::getLanguage()->text('friends', 'user_friends_page_heading', array('user' => $params['user'])));
            $this->setPageTitle(OW::getLanguage()->text('friends', 'user_friends_page_title', array('user' => $params['user'])));
        }
        else
        {
            $this->setPageHeading(OW::getLanguage()->text('friends', 'my_friends_page_heading'));
            $this->setPageTitle(OW::getLanguage()->text('friends', 'my_friends_page_title'));
            $this->addComponent('menu', $this->getMenu());
        }

        $this->setPageHeadingIconClass('ow_ic_user');

        $this->assign('case', $listType);

        list($list, $itemCount) = $this->getInfo($first, $count, $listType);

        $this->addComponent('paging', new BASE_CMP_Paging($page, ceil($itemCount / $rpp), 5));

        $idList = array();

        $userList = array();

        foreach ( $list as $dto )
        {
            $userList[] = array(
                'dto' => $dto
            );

            $idList[] = $dto->getId();
        }

        $questionList = array();
        $onlineInfo = array();
        $avatarArr = array();

        $qs = array();

        $qBdate = BOL_QuestionService::getInstance()->findQuestionByName('birthdate', 'sex');

        if ( $qBdate->onView )
            $qs[] = 'birthdate';

        $qSex = BOL_QuestionService::getInstance()->findQuestionByName('sex');

        if ( $qSex->onView )
            $qs[] = 'sex';

        if ( !empty($idList) )
        {
            $avatarArr = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList);
            $questionList = BOL_QuestionService::getInstance()->getQuestionData($idList, $qs);

            if ( $listType != 'online' )
            {
                $onlineInfo = BOL_UserService::getInstance()->findOnlineStatusForUserList($idList);
            }
        }

        $this->assign('questionList', $questionList);
        $this->assign('avatars', $avatarArr);
        $this->assign('onlineInfo', $onlineInfo);
        $this->assign('list', $userList);
    }

    /**
     * Get info about list of friends or friend requests
     * 
     * @param integer $first
     * @param integer $count
     * @param string $listType
     * @return array( $userList, $count )
     */
    private function getInfo( $first, $count, $listType )
    {
        $service = FRIENDS_BOL_Service::getInstance();
        $userService = BOL_UserService::getInstance();

        $userId = OW::getUser()->getId();

        switch ( $listType )
        {
            case 'friends':
                $idList = $service->findUserFriendsInList($userId, $first, $count);

                return array(
                    $userService->findUserListByIdList($idList),
                    $service->countFriends($userId)
                );

            case 'sent-requests':

                $idList = $service->findFriendIdList($userId, $first, $count, 'sent-requests');

                return array(
                    $userService->findUserListByIdList($idList),
                    $service->count($userId, null, FRIENDS_BOL_Service::STATUS_PENDING, FRIENDS_BOL_Service::STATUS_IGNORED)
                );

            case 'got-requests':

                $idList = $service->findFriendIdList($userId, $first, $count, 'got-requests');

                return array(
                    $userService->findUserListByIdList($idList),
                    $service->count(null, $userId, FRIENDS_BOL_Service::STATUS_PENDING)
                );

            case 'user-friends':

                $eventParams = array(
                    'action' => 'friends_view',
                    'ownerId' => $userId,
                    'viewerId' => OW::getUser()->getId()
                );

                OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);

                $user = BOL_UserService::getInstance()->findByUsername($this->params['user']);
                $userId = $user->getId();

                $idList = $service->findUserFriendsInList($userId, $first, $count);

                return array(
                    $userService->findUserListByIdList($idList),
                    $service->countFriends($userId)
                );
        }

        return array(array(), 0);
    }

    /**
     * Get submenu for friendship lists
     * 
     * @return BASE_CMP_ContentMenu 
     */
    private function getMenu()
    {
        $items = array();
        $language = OW::getLanguage();
        $userId = OW::getUser()->getId();

        $count = FRIENDS_BOL_Service::getInstance()->countFriends($userId);
        $item = new BASE_MenuItem();
        $item->setLabel($language->text('friends', 'friends_tab', array('count' => ($count > 0) ? "({$count})" : '')));
        $item->setKey('friends');
        $item->setUrl(OW::getRouter()->urlForRoute('friends_list'));
        $item->setOrder(1);
        $item->setIconClass('ow_ic_clock');
        $items[] = $item;

        $count = FRIENDS_BOL_Service::getInstance()->count($userId, null, FRIENDS_BOL_Service::STATUS_PENDING, FRIENDS_BOL_Service::STATUS_IGNORED);
        $item = new BASE_MenuItem();
        $item->setLabel($language->text('friends', 'sent_requests_tab', array('count' => ($count > 0) ? "({$count})" : '')));
        $item->setKey('sent_requests');
        $item->setUrl(OW::getRouter()->urlForRoute('friends_lists', array('list' => 'sent-requests')));
        $item->setOrder(2);
        $item->setIconClass('ow_ic_push_pin');
        $items[] = $item;

        $count = FRIENDS_BOL_Service::getInstance()->count(null, $userId, FRIENDS_BOL_Service::STATUS_PENDING);
        $item = new BASE_MenuItem();
        $item->setLabel($language->text('friends', 'got_requests_tab', array('count' => ($count > 0) ? "({$count})" : '')));
        $item->setKey('got_requests');
        $item->setUrl(OW::getRouter()->urlForRoute('friends_lists', array('list' => 'got-requests')));
        $item->setOrder(3);
        $item->setIconClass('ow_ic_push_pin');
        $items[] = $item;

        return new BASE_CMP_ContentMenu($items);
    }
}