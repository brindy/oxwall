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
 * @package ow.ow_plugins.ajaxim.controllers
 * @since 1.0
 */
class AJAXIM_CTRL_Action extends OW_ActionController
{

    public function bind()
    {
        if ( empty($_SESSION['lastRequestTimestamp']) )
        {
            $_SESSION['lastRequestTimestamp'] = (int)$_POST['lastRequestTimestamp'];
        }

        if ( ((int)$_POST['lastRequestTimestamp'] - (int) $_SESSION['lastRequestTimestamp']) < 3 )
        {
            exit('{error: "too much requests"}');
        }

        $_SESSION['lastRequestTimestamp'] = (int)$_POST['lastRequestTimestamp'];


        $this->checkPermissions();

        $active_list = AJAXIM_BOL_Service::getInstance()->getSessionActiveList();
        $roster_list = AJAXIM_BOL_Service::getInstance()->getSessionRosterList();

        $onlinePeople = AJAXIM_BOL_Service::getInstance()->getOnlinePeople(OW::getUser());

        if ( !empty($_POST['lastMessageTimestamps']) )
        {
            $jsOnlineList = array_keys($_POST['lastMessageTimestamps']);
        }
        else
        {
            $jsOnlineList = array();
        }

        $onlineInfo = array();
        /* @var $rosterItem BOL_User */
        foreach ( $onlinePeople['users'] as $rosterItem )
        {
            if ( !OW::getAuthorization()->isUserAuthorized($rosterItem->getId(), 'ajaxim', 'chat') && !OW::getAuthorization()->isUserAuthorized($rosterItem->getId(), 'ajaxim') )
            {
                continue;
            }

            if ( !array_key_exists($rosterItem->getId(), $roster_list) || !in_array($rosterItem->getId(), $jsOnlineList) )
            {
                $friendship = OW::getEventManager()->call('plugin.friends.check_friendship', array('userId' => OW::getUser()->getId(), 'friendId' => $rosterItem->getId()));
                $roster = $this->getUserInfoByNode($rosterItem, $active_list, $friendship);
                $roster['type'] = 'online';
                $onlineInfo[] = $roster;
                $roster_list[$rosterItem->getId()] = $rosterItem;
            }
        }

        /* @var $rosterItem BOL_User */
        foreach ( $roster_list as $rosterItem )
        {
            if ( !array_key_exists($rosterItem->getId(), $onlinePeople['users']) && in_array($rosterItem->getId(), $jsOnlineList) )
            {
                $friendship = OW::getEventManager()->call('plugin.friends.check_friendship', array('userId' => OW::getUser()->getId(), 'friendId' => $rosterItem->getId()));
                $roster = $this->getUserInfoByNode($rosterItem, $active_list, $friendship);
                $roster['type'] = 'offline';
                $onlineInfo[] = $roster;
                unset($roster_list[$rosterItem->getId()]);
            }
        }

        OW::getSession()->set('ajaxim.roster_list', $roster_list);


        switch ( $_POST['action'] )
        {
            case "online":

                break;
            case "get":
                $getResult = array();
                if ( !empty($onlineInfo) )
                {
                    $friends = array();
                    $others = array();
                    foreach ( $onlineInfo as $i )
                    {
                        if ( $i['is_friend'] )
                        {
                            $friends[$i['username']] = $i;
                        }
                        else
                        {
                            $others[$i['username']] = $i;
                        }
                    }
                    ksort($friends);
                    ksort($others);
                    $onlineInfo = array_merge($friends, $others);

                    $sortedOnlineInfo = array();

                    foreach ( $onlineInfo as $user )
                    {
                        $sortedOnlineInfo[] = $user;
                    }

                    $getResult['presenceList'] = $sortedOnlineInfo;
                }

                if ( $onlinePeople['total'] != $_POST['onlineCount'] )
                {
                    $getResult['onlineCount'] = $onlinePeople['total'];
                }

                if ( !empty($_POST['lastMessageTimestamps']) )
                {
                    $messageList = AJAXIM_BOL_Service::getInstance()->findUnreadMessages(OW::getUser(), $_POST['lastMessageTimestamps']);
                    if ( !empty($messageList) )
                    {
                        $getResult['messageList'] = $messageList;
                        $getResult['messageListLength'] = count($messageList);
                    }
                }

                exit(json_encode($getResult));
                break;
        }
    }

    public function getLog()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            exit('{error: "You need to sign in"}');
        }

        if ( !OW::getRequest()->isAjax() )
        {
            exit('{error: "Ajax request required"}');
        }

        $list = AJAXIM_BOL_Service::getInstance()->findLastMessages(OW::getUser()->getId(), $_POST['username'], $_POST['lastMessageTimestamp'], 10, $_POST['omit_last_message']);

        exit(json_encode($list));
    }

    public function logMsg()
    {
        $this->checkPermissions();

        if ( empty($_POST['to']) )
        {
            exit('{error: "Receiver is not defined"}');
        }

        if ( empty($_POST['message']) )
        {
            exit('{error: "Message is empty"}');
        }

        $receiver = BOL_UserService::getInstance()->findUserById($_POST['to']);
        $friendship = OW::getEventManager()->call('plugin.friends.check_friendship', array('userId' => OW::getUser()->getId(), 'friendId' => $receiver->getId()));

        $privacyFriendsOnly = false;

        $eventParams = array(
            'action' => 'ajaxim_invite_to_chat',
            'ownerId' => OW::getUser()->getId(),
            'viewerId' => $receiver->getId()
        );

        try
        {
            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        catch ( RedirectException $e )
        {
            $privacyFriendsOnly = true;
        }

        if ( (OW::getConfig()->getValue('ajaxim', 'friends_only') && (empty($friendship) || $friendship->getStatus() == 'pending' )) || $privacyFriendsOnly )
        {
            AJAXIM_BOL_Service::getInstance()->addRoster(OW::getUser()->getId(), $receiver->getId());
        }

        $dto = new AJAXIM_BOL_Message();

        $dto->setFrom(OW::getUser()->getId());
        $dto->setTo($_POST['to']);
        $dto->setMessage(UTIL_HtmlTag::stripTags(UTIL_HtmlTag::stripJs($_POST['message'])));
        $dto->setTimestamp(time());
        $dto->setRead(0);

        AJAXIM_BOL_Service::getInstance()->save($dto);

        $dto->setMessage(UTIL_HtmlTag::autoLink($dto->getMessage()));

        exit(json_encode($dto));
    }

    public function updateUserInfo()
    {
        $this->checkPermissions();

        $active_list = AJAXIM_BOL_Service::getInstance()->getSessionActiveList();
        $roster_list = AJAXIM_BOL_Service::getInstance()->getSessionRosterList();

        /* @var BOL_User $friend */
        $friend = null;
        $friendship = null;
        if ( !empty($_POST['click']) && $_POST['click'] == 'online_now_click' )
        {
            $friend = BOL_UserService::getInstance()->findUserById($_POST['username']);

            if ( !OW::getAuthorization()->isUserAuthorized($friend->getId(), 'ajaxim', 'chat') && !OW::getAuthorization()->isUserAuthorized($friend->getId(), 'ajaxim') )
            {
                $info = array(
                    'warning' => true,
                    'message' => OW::getLanguage()->text('ajaxim', 'user_is_not_authorized_chat', array('username' => BOL_UserService::getInstance()->getDisplayName($friend->getId()))),
                    'type' => 'warning'
                );
                exit(json_encode($info));
            }
            else
            {
                $eventParams = array(
                    'action' => 'ajaxim_invite_to_chat',
                    'ownerId' => $friend->getId(),
                    'viewerId' => OW::getUser()->getId()
                );

                try
                {
                    OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
                }
                catch ( RedirectException $e )
                {
                    $info = array(
                        'warning' => true,
                        'message' => OW::getLanguage()->text('ajaxim', 'warning_user_privacy_friends_only', array('displayname' => BOL_UserService::getInstance()->getDisplayName($friend->getId()))),
                        'type' => 'warning'
                    );
                    exit(json_encode($info));
                }

                exit(json_encode(array('node' => $friend->getId())));
            }
        }
        else
        {
            if ( !empty($_POST['username']) )
            {
                $friend = BOL_UserService::getInstance()->findUserById($_POST['username']);
            }
        }

        if ( empty($friend) )
        {
            exit('{error: "User not found"}');
        }

        $friendship = OW::getEventManager()->call('plugin.friends.check_friendship', array('userId' => OW::getUser()->getId(), 'friendId' => $friend->getId()));

        $info = '';

        switch ( $_POST['action'] )
        {
            case "open":

                if ( OW::getConfig()->getValue('ajaxim', 'friends_only') && (empty($friendship) || $friendship->getStatus() == 'pending') && empty($roster_list[$friend->getId()]) )
                {
                    AJAXIM_BOL_Service::getInstance()->addRoster(OW::getUser()->getId(), $friend->getId());
                }

                if ( !empty($active_list) )
                {
                    foreach ( $active_list as $contactName => $isActive )
                    {
                        $active_list[$contactName] = false;
                    }
                    $info['message'] = 'add to active list';
                }

                if ( !empty($_POST['option']) && $_POST['option'] == 'activate' )
                {
                    $active_list[$friend->getId()] = true;
                }
                else
                {
                    $active_list[$friend->getId()] = false;
                }
                $info = $this->getUserInfoByNode($friend, $active_list, $friendship);

                break;
            case "close":
                unset($active_list[$friend->getId()]);

                $info = array();
                break;
            case "min":
                $active_list[$friend->getId()] = false;

                break;
            case "clear":
                $active_list = array();
                break;
        }

        OW::getSession()->set('ajaxim.active_list', $active_list);

        exit(json_encode($info));
    }

    public function getUserInfoByNode( $user, $active_list, $friendship )
    {
        $this->checkPermissions();

        $info = array();

        $url = BOL_UserService::getInstance()->getUserUrl($user->getId());
        $avatar = BOL_AvatarService::getInstance()->getAvatarUrl($user->getId());
        if ( empty($avatar) )
        {
            $avatar = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
        }

        $is_active = false;
        if ( !empty($active_list[$user->getId()]) && $active_list[$user->getId()] )
        {
            $is_active = true;
        }

        $is_friend = false;
        if ( !empty($friendship) && $friendship->getStatus() == 'active' )
        {
            $is_friend = true;
        }

        $info = array(
            'node' => $user->getId(),
            'username' => BOL_UserService::getInstance()->getDisplayName($user->getId()),
            'user_avatar_src' => $avatar,
            'user_url' => $url,
            'is_friend' => $is_friend,
            'is_active' => $is_active,
            'last_message_timestamp' => AJAXIM_BOL_Service::getInstance()->getLastMessageTimestamp(OW::getUser()->getId(), $user->getId())
        );

        return $info;
    }

    public function checkPermissions()
    {

        if ( !OW::getUser()->isAuthenticated() )
        {
            exit('{error: "You need to sign in"}');
        }

        if ( !OW::getRequest()->isAjax() )
        {
            exit('{error: "Ajax request required"}');
        }
    }

    public function processUserSettingsForm()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        $form = AJAXIM_BOL_Service::getInstance()->getUserSettingsForm();

        if ( $form->isValid($_POST) )
        {
            $data = $form->getValues();

            BOL_PreferenceService::getInstance()->savePreferenceValue('ajaxim_user_settings_enable_sound', (bool) $data['ajaxim_enable_sound'], $data['user_id']);
            //BOL_PreferenceService::getInstance()->savePreferenceValue('ajaxim_user_settings_status_invisible', (bool)$data['ajaxim_status_invisible'], $data['user_id']);

            echo json_encode(
                array(
                    'message' => OW::getLanguage()->text('ajaxim', 'settings_saved'),
                    'ajaxim_enable_sound' => (bool) $data['ajaxim_enable_sound']
                )
            );
            exit;
        }
    }
}

