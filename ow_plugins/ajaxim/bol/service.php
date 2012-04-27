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
 * @package ow_plugins.ajaxim.bol
 */
class AJAXIM_BOL_Service
{
    /**
     *
     * @var AJAXIM_BOL_MessageDao
     */
    private $messageDao;
    /**
     *
     * @var AJAXIM_BOL_LastRosterDao
     */
    private $rosterDao;
    /**
     * Class instance
     *
     * @var AJAXIM_BOL_Service
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        $this->messageDao = AJAXIM_BOL_MessageDao::getInstance();
        $this->rosterDao = AJAXIM_BOL_RosterDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return AJAXIM_BOL_Service
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function save( AJAXIM_BOL_Message $msg )
    {
        $this->messageDao->save($msg);
    }

    public function findLastMessages( $userId, $rosterId, $lastMessageTimestamp, $count = 10, $omit_last_message = 0 )
    {
        $result_msg_list = array();
        $msg_list = $this->messageDao->findLastMessages($userId, $rosterId, $lastMessageTimestamp, $count);

        foreach ( $msg_list as $id => $msg )
        {
            if ( $omit_last_message == 1 && ($id == (count($msg_list) - 1)) )
            {
                continue;
            }
            else
            {
                $msg->setMessage(UTIL_HtmlTag::autoLink($msg->getMessage()));
                $msg->setRead(UTIL_DateTime::formatDate($msg->getTimestamp()));
                $result_msg_list[$id] = $msg;
            }
        }

        return $result_msg_list;
    }

    public function getSessionActiveList()
    {
        $active_list = OW::getSession()->get('ajaxim.active_list');
        if ( empty($active_list) )
        {
            $active_list = array();
        }

        return $active_list;
    }

    public function getSessionRosterList()
    {
        $roster_list = OW::getSession()->get('ajaxim.roster_list');

        if ( empty($roster_list) )
        {
            $roster_list = array();
        }

        return $roster_list;
    }

    /**
     *
     * @param BOL_User $user
     */
    public function getRosterList( $user )
    {
        $rosterList = $this->rosterDao->findRosters($user->getId());
        $rosterIdList = array();
        $list = array();
        if ( !empty($rosterList) )
        {

            foreach ( $rosterList as $roster )
            {
                $rosterIdList[] = $roster->getRosterId();
            }


            $a = BOL_UserService::getInstance()->findOnlineStatusForUserList($rosterIdList);

            foreach ( $a as $id => $isOnline )
            {
                if ( !$isOnline )
                    continue;

                $list[$id] = BOL_UserService::getInstance()->findUserById($id);
            }
        }

        return $list;
    }

    public function addRoster( $userId, $rosterId )
    {
        //exit('addRoster');
        $roster = $this->rosterDao->findRoster($userId, $rosterId);
        if ( empty($roster) )
        {
            $roster = new AJAXIM_BOL_Roster();
            $roster->setUserId($userId);
            $roster->setRosterId($rosterId);

            $this->rosterDao->save($roster);
        }

        $roster = $this->rosterDao->findRoster($rosterId, $userId);
        if ( empty($roster) )
        {
            $reverseRoster = new AJAXIM_BOL_Roster();
            $reverseRoster->setUserId($rosterId);
            $reverseRoster->setRosterId($userId);

            $this->rosterDao->save($reverseRoster);
        }
    }

    public function deleteRoster( $userId, $rosterId )
    {
        $this->rosterDao->deleteRosterItem($userId, $rosterId);
        $this->rosterDao->deleteRosterItem($rosterId, $userId);
    }

    /**
     *
     * @param BOL_User $user
     */
    public function getOnlinePeople( $user )
    {
        $listCount = 15;
        $onlineList = array();
        $totalCount = 0;
        $friendsOnlineCount = 0;
        $roster_list = AJAXIM_BOL_Service::getInstance()->getSessionRosterList();

        $count = (int) OW::getEventManager()->call('plugin.friends.count_friends', array('userId' => $user->getId()));
        $idList = OW::getEventManager()->call('plugin.friends.get_friend_list', array('userId' => $user->getId(), 'count' => $count));

        if ( !empty($idList) )
        {
            $a = BOL_UserService::getInstance()->findOnlineStatusForUserList($idList);

            foreach ( $a as $id => $isOnline )
            {
                if ( !$isOnline )
                {
                    continue;
                }
                $friendsOnlineCount++;
                $onlineList[$id] = BOL_UserService::getInstance()->findUserById($id);
            }
        }

        $userRoster = $this->getRosterList($user);
        $friendsOnlineCount += count($userRoster);
        $onlineList += $userRoster;        
        
        if ( $friendsOnlineCount < $listCount )
        {
            if ( OW::getConfig()->getValue('ajaxim', 'friends_only') )
            {
                return array('users' => $onlineList, 'total' => $friendsOnlineCount);
            }
            else
            {
                $count = (int) BOL_UserService::getInstance()->countOnline();

                $otherOnlineList = BOL_UserService::getInstance()->findOnlineList(0, $count);
                
                foreach ( $otherOnlineList as $id => $item )
                {
                    if ( $item->getId() == OW::getUser()->getId() || in_array($item, $onlineList) || in_array($item, $roster_list) )
                    {
                        continue;
                    }
                    
                    $eventParams = array(
                        'action' => 'ajaxim_invite_to_chat',
                        'ownerId' => $item->getId(),
                        'viewerId' => OW::getUser()->getId()
                    );

                    try
                    {
                        OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
                    }
                    catch ( RedirectException $e )
                    {
                        unset($otherOnlineList[$id]);
                        $count--;
                    }
                }
                $result = array();
                $addCount = $listCount - $friendsOnlineCount;
                $addCounted = 0;
                foreach ( $otherOnlineList as $item )
                {
                    if ( $addCounted == $addCount )
                    {
                        return array('users' => $onlineList, 'total' => $count - 1);
                    }
                    if ( $item->getId() == OW::getUser()->getId() || in_array($item, $onlineList) )
                    {
                        continue;
                    }
                    $addCounted++;
                    $onlineList[$item->getId()] = $item;
                }

                return array('users' => $onlineList, 'total' => $count - 1);
            }
        }
    }

    public function getLastMessageTimestamp( $userId, $rosterId )
    {
        $message = $this->messageDao->findLastMessage($userId, $rosterId);

        return (!empty($message)) ? $message->getTimestamp() : 0;
    }

    public function findUnreadMessages( $user, $lastMessageTimeStamps )
    {
        $messages = array();

        foreach ( $lastMessageTimeStamps as $rosterId => $timestamp )
        {
            $messages = array_merge($messages, $this->messageDao->findUnreadMessages($rosterId, $user->getId(), $timestamp));
        }

        return $messages;
    }

    public function findMessages( $user, $lastMessageId=null )
    {
        return $this->messageDao->findMessages($user, $lastMessageId);
    }

    /**
     *
     * @param string $name
     * @return Form
     */
    public function getUserSettingsForm()
    {
        $form = new Form('user_settings_form');
        $form->setAjax(true);
        $form->setAction(OW::getRouter()->urlFor('AJAXIM_CTRL_Action', 'processUserSettingsForm'));
        $form->setAjaxResetOnSuccess(false);
        $form->bindJsFunction(Form::BIND_SUCCESS, "function(data){OW.info(data.message); window.ajaximUserSettingsForm.close(); ajaximSetSoundEnabled(data.ajaxim_enable_sound); }");


        $enableSound = new CheckboxField('ajaxim_enable_sound');
        $user_preference_enable_sound = BOL_PreferenceService::getInstance()->getPreferenceValue('ajaxim_user_settings_enable_sound', OW::getUser()->getId());
        $enableSound->setValue($user_preference_enable_sound);
        $enableSound->setLabel(OW::getLanguage()->text('ajaxim', 'enable_sound_label'));
        $form->addElement($enableSound);

        /*        $statusInvisible = new CheckboxField('ajaxim_status_invisible');
          $user_preference_status_invisible = BOL_PreferenceService::getInstance()->getPreferenceValue('ajaxim_user_settings_status_invisible', OW::getUser()->getId());
          $statusInvisible->setValue($user_preference_status_invisible);
          $statusInvisible->setLabel(OW::getLanguage()->text('ajaxim', 'status_invisible_label'));
          $form->addElement($statusInvisible);
         */
        $submit = new Submit('submit');
        $submit->setValue(OW::getLanguage()->text('ajaxim', 'user_settings_submit_label'));
        $form->addElement($submit);

        $userIdHidden = new HiddenField('user_id');
        $form->addElement($userIdHidden);


        return $form;
    }
}