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
 * Groups Service
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.groups.bol
 * @since 1.0
 */
class GROUPS_BOL_Service
{
    const WIDGET_PANEL_NAME = 'group';

    const EVENT_ON_DELETE = 'groups_on_group_delete';
    const EVENT_DELETE_COMPLETE = 'groups_group_delete_complete';
    const EVENT_CREATE = 'groups_group_create_complete';
    const EVENT_BEFORE_CREATE = 'groups_group_before_create';
    const EVENT_EDIT = 'groups_group_edit_complete';
    const EVENT_USER_ADDED = 'groups_user_signed';
    const EVENT_USER_BEFORE_ADDED = 'groups_before_user_signed';
    const EVENT_USER_DELETED = 'groups_user_left';
    const EVENT_DELETE_FORUM = 'forum.delete_group';

    const WCV_ANYONE = 'anyone';
    const WCV_INVITE = 'invite';

    const WCI_CREATOR = 'creator';
    const WCI_PARTICIPANT = 'participant';

    const PRIVACY_EVERYBODY = 'everybody';
    const PRIVACY_ACTION_VIEW_MY_GROUPS = 'view_my_groups';

    const LIST_MOST_POPULAR = 'most_popular';
    const LIST_LATEST = 'latest';

    const LIST_ALL = 'all';

    const ENTITY_TYPE_WAL = 'groups_wal';
    const ENTITY_TYPE_GROUP = 'groups';
    const FEED_ENTITY_TYPE = 'group';

    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return GROUPS_BOL_Service
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     *
     * @var GROUPS_BOL_InviteDao
     */
    private $inviteDao;

    /**
     *
     * @var GROUPS_BOL_GroupDao
     */
    private $groupDao;

    /**
     *
     * @var GROUPS_BOL_GroupUserDao
     */
    private $groupUserDao;

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        $this->groupDao = GROUPS_BOL_GroupDao::getInstance();
        $this->groupUserDao = GROUPS_BOL_GroupUserDao::getInstance();
        $this->inviteDao = GROUPS_BOL_InviteDao::getInstance();
    }

    public function saveGroup( GROUPS_BOL_Group $groupDto )
    {
        $this->groupDao->save($groupDto);
    }

    public function deleteGroup( $groupId )
    {
        $event = new OW_Event(self::EVENT_ON_DELETE, array('groupId' => $groupId));

        OW::getEventManager()->trigger($event);

        $this->groupDao->deleteById($groupId);
        $this->groupUserDao->deleteByGroupId($groupId);
        $this->inviteDao->deleteByGroupId($groupId);

        $is_forum_connected = OW::getConfig()->getValue('groups', 'is_forum_connected');
        // Delete forum group
        if ( $is_forum_connected )
        {
            $event = new OW_Event(self::EVENT_DELETE_FORUM, array('entityId' => $groupId, 'entity' => 'groups'));
            OW::getEventManager()->trigger($event);
        }

        $event = new OW_Event(self::EVENT_DELETE_COMPLETE, array('groupId' => $groupId));

        OW::getEventManager()->trigger($event);
    }

    public function deleteUser( $groupId, $userId )
    {
        $this->groupUserDao->deleteByGroupAndUserId($groupId, $userId);

        $event = new OW_Event(self::EVENT_USER_DELETED, array(
                'groupId' => $groupId,
                'userId' => $userId
            ));

        OW::getEventManager()->trigger($event);
    }

    public function onUserUnregister( $userId, $withContent )
    {
        if ( $withContent )
        {
            $groups = $this->groupDao->findAllUserGroups($userId);

            foreach ( $groups as $groups )
            {
                GROUPS_BOL_Service::getInstance()->deleteGroup($groups->id);
            }
        }

        $this->inviteDao->deleteByUserId($groupId);
        $this->groupUserDao->deleteByUserId($userId);
    }

    public function findUserGroupList( $userId, $first = null, $count = null )
    {
        return $this->groupDao->findByUserId($userId, $first, $count);
    }



    public function findUserGroupListCount( $userId )
    {
        return $this->groupDao->findCountByUserId($userId);
    }

    /**
     *
     * @param $groupId
     * @return GROUPS_BOL_Group
     */
    public function findGroupById( $groupId )
    {
        return $this->groupDao->findById((int) $groupId);
    }

    public function findGroupList( $listType, $first=null, $count=null )
    {
        switch ( $listType )
        {
            case self::LIST_MOST_POPULAR:
                return $this->groupDao->findMostPupularList($first, $count);

            case self::LIST_LATEST:
                return $this->groupDao->findOrderedList($first, $count);

            case self::LIST_ALL:
                return $this->groupDao->findAll();
        }

        throw new InvalidArgumentException('Undefined list type');
    }

    public function findGroupListCount( $listType )
    {
        switch ( $listType )
        {
            case self::LIST_MOST_POPULAR:
            case self::LIST_LATEST:
                return $this->groupDao->findAllCount();
        }

        throw new InvalidArgumentException('Undefined list type');
    }

    public function findInvitedGroups( $userId, $first=null, $count=null )
    {
        return $this->groupDao->findUserInvitedGroups($userId, $first, $count);
    }

    public function findInvitedGroupsCount( $userId )
    {
        return $this->groupDao->findUserInvitedGroupsCount($userId);
    }

    public function findMyGroups( $userId, $first=null, $count=null )
    {
        return $this->groupDao->findMyGroups($userId, $first, $count);
    }

    public function findMyGroupsCount( $userId )
    {
        return $this->groupDao->findMyGroupsCount($userId);
    }

    public function findAllGroupCount()
    {
        return $this->groupDao->findAll();
    }

    public function findByTitle( $title )
    {
        return $this->groupDao->findByTitle($title);
    }

    public function findLimitedList( $count )
    {
        return $this->groupDao->findLimitedList($count);
    }

    public function findUserListCount( $groupId )
    {
        return $this->groupUserDao->findCountByGroupId($groupId);
    }

    public function findUserList( $groupId, $first, $count )
    {
        $groupUserList = $this->groupUserDao->findListByGroupId($groupId, $first, $count);
        $idList = array();
        foreach ( $groupUserList as $groupUser )
        {
            $idList[] = $groupUser->userId;
        }

        return BOL_UserService::getInstance()->findUserListByIdList($idList);
    }

    public function findGroupUserIdList( $groupId, $privacy = null )
    {
        $groupUserList = $this->groupUserDao->findByGroupId($groupId, $privacy);
        $idList = array();
        foreach ( $groupUserList as $groupUser )
        {
            $idList[] = $groupUser->userId;
        }

        return $idList;
    }

    public function addUser( $groupId, $userId )
    {
        $dto = $this->findUser($groupId, $userId);
        if ( $dto !== null )
        {
            return true;
        }

        $dto = new GROUPS_BOL_GroupUser();
        $dto->timeStamp = time();

        $dto->groupId = $groupId;
        $dto->userId = $userId;

        $event = new OW_Event(self::EVENT_USER_BEFORE_ADDED, array(
            'groupId' => $groupId,
            'userId' => $userId
        ), (array) $dto);

        OW::getEventManager()->trigger($event);
        $data = $event->getData();

        foreach ( $data as $k => $v )
        {
            $dto->$k = $v;
        }

        $this->groupUserDao->save($dto);

        $this->inviteDao->deleteByUserIdAndGroupId($groupId, $userId);

        $event = new OW_Event(self::EVENT_USER_ADDED, array(
                'groupId' => $groupId,
                'userId' => $userId,
                'groupUserId' => $dto->id
            ));

        OW::getEventManager()->trigger($event);
    }

    public function findUser( $groupId, $userId )
    {
        return $this->groupUserDao->findGroupUser($groupId, $userId);
    }

    public function getGroupImageFileName( GROUPS_BOL_Group $group )
    {
        if ( empty($group->imageHash) )
        {
            return null;
        }

        return 'group-' . $group->id . '-' . $group->imageHash . '.jpg';
    }

    public function getGroupImageUrl( GROUPS_BOL_Group $group )
    {
        $path = $this->getGroupImagePath($group);
        $noPictureUrl = OW::getPluginManager()->getPlugin('groups')->getStaticUrl() . 'images/no-picture.gif';
        return empty($path) ? $noPictureUrl : OW::getStorage()->getFileUrl($path);
    }

    public function getGroupImagePath( GROUPS_BOL_Group $group )
    {
        $fileName = $this->getGroupImageFileName($group);

        return empty($fileName) ? null : OW::getPluginManager()->getPlugin('groups')->getUserFilesDir() . $fileName;
    }

    public function getGroupUrl( GROUPS_BOL_Group $group )
    {
        return OW::getRouter()->urlForRoute('groups-view', array('groupId' => $group->id));
    }

    public function isCurrentUserCanEdit( GROUPS_BOL_Group $group )
    {
        return OW::getUser()->isAuthorized('groups', 'create', $group->userId) || OW::getUser()->isAuthorized('groups');
    }

    public function isCurrentUserCanCreate()
    {
        return OW::getUser()->isAuthorized('groups', 'create');
    }

    public function isCurrentUserCanView( $ownerId )
    {
        return $ownerId == OW::getUser()->getId() || OW::getUser()->isAuthorized('groups', 'view');
    }

    public function isCurrentUserInvite( $groupId )
    {
        $userId = OW::getUser()->getId();

        if ( empty($userId) )
        {
            return false;
        }

        $group = $this->findGroupById($groupId);

        if ( $group->whoCanInvite == self::WCI_CREATOR )
        {
            return $group->userId == $userId;
        }

        if ( $group->whoCanInvite == self::WCI_PARTICIPANT  )
        {
            return $this->findUser($groupId, $userId) !== null;
        }

        return false;
    }

    public function inviteUser( $groupId, $userId, $inviterId )
    {
        $invite = $this->inviteDao->findInvite($groupId, $userId, $inviterId);

        if ( $invite !== null  )
        {
            return;
        }

        $invite = new GROUPS_BOL_Invite();
        $invite->userId = $userId;
        $invite->groupId = $groupId;
        $invite->inviterId = $inviterId;
        $invite->timeStamp = time();

        $this->inviteDao->save($invite);
    }

    public function findInvite( $groupId, $userId, $inviterId = null )
    {
        return $this->inviteDao->findInvite($groupId, $userId, $inviterId);
    }

    public function findAllInviteList( $groupId )
    {
        return $this->inviteDao->findInviteList($groupId);
    }

    public function findInvitedUserIdList( $groupId, $inviterId )
    {
        $list = $this->inviteDao->findListByGroupIdAndInviterId($groupId, $inviterId);
        $out = array();
        foreach ( $list as $item )
        {
            $out[] = $item->userId;
        }

        return $out;
    }

    public function findUserInvitedGroupsCount( $userId )
    {
        return $this->groupDao->findUserInvitedGroupsCount($userId);
    }

    public function findAllGroupsUserList()
    {
        $users = $this->groupUserDao->findAll();

        $out = array();
        foreach ( $users as $user )
        {
            /* @var $user GROUPS_BOL_GroupUser */
            $out[$user->groupId][] = $user->userId;
        }

        return $out;
    }

    public function setGroupsPrivacy( $ownerId, $privacy )
    {
        $this->groupDao->setPrivacy($ownerId, $privacy);
    }

    public function setGroupUserPrivacy( $userId, $privacy )
    {
        $this->groupUserDao->setPrivacy($userId, $privacy);
    }
}