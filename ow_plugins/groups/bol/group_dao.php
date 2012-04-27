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
 * Data Access Object for `groups_group` table.
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.groups.bol
 * @since 1.0
 */
class GROUPS_BOL_GroupDao extends OW_BaseDao
{
    /**
     * Singleton instance.
     *
     * @var GROUPS_BOL_GroupDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return GROUPS_BOL_GroupDao
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
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'GROUPS_BOL_Group';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'groups_group';
    }

    //TODO Privacy filter
    public function findOrderedList( $first, $count )
    {
        $example = new OW_Example();
        $example->setOrder('`timeStamp` DESC');
        $example->setLimitClause($first, $count);

        if ( !OW::getUser()->isAuthorized('groups') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $example->andFieldEqual('whoCanView', GROUPS_BOL_Service::WCV_ANYONE);
        }

        return $this->findListByExample($example);
    }

    public function findLimitedList( $count )
    {
        $example = new OW_Example();
        $example->setLimitClause(0, $count);

        return $this->findListByExample($example);
    }

    public function findMostPupularList( $first, $count )
    {
        $groupUserTable = GROUPS_BOL_GroupUserDao::getInstance()->getTableName();

        $where = '';

        if ( !OW::getUser()->isAuthorized('groups') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $where = 'WHERE g.whoCanView="' . GROUPS_BOL_Service::WCV_ANYONE . '"';
        }

        $query = "SELECT `g`.* FROM `" . $this->getTableName() . "` AS `g`
            LEFT JOIN `" . $groupUserTable . "` AS `gu` ON `g`.`id` = `gu`.`groupId`
            $where
            GROUP BY `g`.`id` ORDER BY COUNT(`gu`.`id`) DESC LIMIT :f, :c";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'f' => $first,
            'c' => $count
        ));
    }

    public function findAllCount()
    {
        $example = new OW_Example();

        if ( !OW::getUser()->isAuthorized('groups') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $example->andFieldEqual('whoCanView', GROUPS_BOL_Service::WCV_ANYONE);
        }

        return $this->countByExample($example);
    }

    public function findByTitle( $title )
    {
        $example = new OW_Example();
        $example->andFieldEqual('title', $title);

        return $this->findObjectByExample($example);
    }

    public function findAllUserGroups( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);

        return $this->findListByExample($example);
    }

    public function findByUserId( $userId, $first = null, $count = null )
    {
        $groupUserDao = GROUPS_BOL_GroupUserDao::getInstance();

        $limit = '';
        if ( $first !== null && $count !== null )
        {
            $limit = "LIMIT $first, $count";
        }

        $wcvWhere = '1';

        if ( !OW::getUser()->isAuthorized('groups') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $wcvWhere = 'g.whoCanView="' . GROUPS_BOL_Service::WCV_ANYONE . '"';
        }

        $query = "SELECT g.* FROM " . $this->getTableName() . " g
            INNER JOIN " . $groupUserDao->getTableName() . " u ON g.id = u.groupId
            WHERE u.userId=:u AND $wcvWhere " . $limit;

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'u' => $userId
        ));
    }

    public function findCountByUserId( $userId )
    {
        $groupUserDao = GROUPS_BOL_GroupUserDao::getInstance();

        $wcvWhere = '1';

        if ( !OW::getUser()->isAuthorized('groups') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $wcvWhere = 'g.whoCanView="' . GROUPS_BOL_Service::WCV_ANYONE . '"';
        }

        $query = "SELECT COUNT(g.id) FROM " . $this->getTableName() . " g
            INNER JOIN " . $groupUserDao->getTableName() . " u ON g.id = u.groupId
            WHERE u.userId=:u AND $wcvWhere ";

        return (int) $this->dbo->queryForColumn($query, array(
            'u' => $userId
        ));
    }

    public function findMyGroups( $userId, $first = null, $count = null )
    {
        $groupUserDao = GROUPS_BOL_GroupUserDao::getInstance();

        $limit = '';
        if ( $first !== null && $count !== null )
        {
            $limit = "LIMIT $first, $count";
        }

        $query = "SELECT g.* FROM " . $this->getTableName() . " g
            INNER JOIN " . $groupUserDao->getTableName() . " u ON g.id = u.groupId
            WHERE u.userId=:u " . $limit;

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'u' => $userId
        ));
    }

    public function findMyGroupsCount( $userId )
    {
        $groupUserDao = GROUPS_BOL_GroupUserDao::getInstance();

        $query = "SELECT COUNT(g.id) FROM " . $this->getTableName() . " g
            INNER JOIN " . $groupUserDao->getTableName() . " u ON g.id = u.groupId
            WHERE u.userId=:u";

        return (int) $this->dbo->queryForColumn($query, array(
            'u' => $userId
        ));
    }

    public function setPrivacy( $userId, $privacy )
    {
        $query = 'UPDATE ' . $this->getTableName() . ' SET privacy=:p WHERE userId=:u';

        $this->dbo->query($query, array(
            'p' => $privacy,
            'u' => $userId
        ));
    }


    /**
     * @param integer $userId
     * @return array<GROUPS_BOL_Invite>
     */
    public function findUserInvitedGroups( $userId, $first, $count )
    {
        $query = "SELECT DISTINCT `g`.* FROM `" . $this->getTableName() . "` AS `g`
            INNER JOIN `" . GROUPS_BOL_InviteDao::getInstance()->getTableName() . "` AS `i` ON ( `g`.`id` = `i`.`groupId` )
            WHERE `i`.`userId` = :u
            ORDER BY `i`.`timeStamp` DESC LIMIT :f, :c";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'u' => (int) $userId,
            'f' => (int) $first,
            'c' => (int) $count
        ));
    }

    /**
     * @param integer $userId
     * @return integer
     */
    public function findUserInvitedGroupsCount( $userId )
    {
        $query = "SELECT COUNT(DISTINCT g.id) AS `count` FROM `" . $this->getTableName() . "` AS `g`
            INNER JOIN `" . GROUPS_BOL_InviteDao::getInstance()->getTableName() . "` AS `i` ON ( `g`.`id` = `i`.`groupId` )
            WHERE `i`.`userId` = :u";

        return $this->dbo->queryForColumn($query, array(
            'u' => (int) $userId
        ));
    }
}