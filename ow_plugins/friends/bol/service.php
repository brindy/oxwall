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
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_plugins.friends.bol
 * @since 1.0
 */
class FRIENDS_BOL_Service
{
    const STATUS_ACTIVE = FRIENDS_BOL_FriendshipDao::VAL_STATUS_ACTIVE;
    const STATUS_PENDING = FRIENDS_BOL_FriendshipDao::VAL_STATUS_PENDING;
    const STATUS_IGNORED = FRIENDS_BOL_FriendshipDao::VAL_STATUS_IGNORED;

    /**
     * @var FRIENDS_BOL_FriendshipDao
     */
    private $friendshipDao;
    /**
     * Class instance
     *
     * @var FRIENDS_BOL_Service
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        $this->friendshipDao = FRIENDS_BOL_FriendshipDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return FRIENDS_BOL_Service
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * Save new friendship request
     * 
     * @param integer $requesterId
     * @param integer $userId 
     */
    public function request( $requesterId, $userId )
    {
        $this->friendshipDao->request($requesterId, $userId);
    }

    /**
     * Accept new friendship request
     * 
     * @param integer $userId
     * @param integer $requesterId
     * @return FRIENDS_BOL_Friendship 
     */
    public function accept( $userId, $requesterId )
    {
        return $this->friendshipDao->accept($userId, $requesterId);
    }

        
    /**
     * Ignore new friendship request
     * 
     * @param integer $requesterId
     * @param integer $userId 
     */
    public function ignore( $requesterId, $userId )
    {
        $this->friendshipDao->ignore($requesterId, $userId);
    }

    /**
     * Cancel friendship
     *  
     * @param integer $requesterId
     * @param integer $userId 
     */
    public function cancel( $requesterId, $userId )
    {
        $this->friendshipDao->cancel($requesterId, $userId);
    }

    /**
     * Activate friendship
     * 
     * @param integer $requesterId
     * @param integer $userId 
     */
    public function activate( $requesterId, $userId )
    {
        $this->friendshipDao->activate($requesterId, $userId);
    }

    public function findFriendship( $userId, $user2Id )
    {
        return $this->friendshipDao->findFriendship($userId, $user2Id);
    }

    public function findFriendIdList( $userId, $first, $count, $type = 'friends' )
    {

        switch ( $type )
        {
            case 'friends':
                return $this->friendshipDao->findFriendIdList($userId, $first, $count);


            case 'sent-requests':
                return $this->friendshipDao->findRequestedUserIdList($userId, $first, $count);

            case 'got-requests':

                return $this->friendshipDao->findRequesterUserIdList($userId, $first, $count);
        }

        return array(array(), 0);
    }

    public function count( $userId=null, $friendId=null, $status= FRIENDS_BOL_Service::STATUS_ACTIVE, $orStatus=null )
    {
        return $this->friendshipDao->count($userId, $friendId, $status, $orStatus);
    }

    public function countFriends( $userId )
    {
        return $this->friendshipDao->findUserFriendsCount($userId);
    }

    public function deleteUserFriendships( $userId )
    {
        $this->friendshipDao->deleteUserFriendships($userId);
    }

    public function findAllActiveFriendships()
    {
        return $this->friendshipDao->findAllActiveFriendships();
    }

    public function findActiveFriendships( $first, $count )
    {
        return $this->friendshipDao->findActiveFriendships($first, $count);
    }
    /* -------------------- */

    public function findUserFriendsInList( $userId, $first, $count, $userIdList = null )
    {
        return $this->friendshipDao->findFriendIdList($userId, $first, $count, $userIdList);
    }

    public function findCountOfUserFriendsInList( $userId, $userIdList = null )
    {
        return $this->friendshipDao->findUserFriendsCount($userId, $userIdList);
    }
}