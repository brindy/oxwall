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
 * @package ow.ow_system_plugins.base.controllers
 * @since 1.0
 */
class AJAXIM_BOL_MessageDao extends OW_BaseDao
{
    /**
     * Class instance
     *
     * @var AJAXIM_BOL_MessageDao
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns class instance
     *
     * @return AJAXIM_BOL_MessageDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function getTableName()
    {
        return OW_DB_PREFIX . 'ajaxim_message';
    }

    public function getDtoClassName()
    {
        return 'AJAXIM_BOL_Message';
    }

    public function findLastMessage( $userId, $rosterId )
    {
        $query = "SELECT * FROM `{$this->getTableName()}` WHERE  (`from`=? AND `to`=?) OR (`to`=? AND `from`=?) ORDER BY `timestamp` DESC LIMIT 0,1 ";

        return $this->dbo->queryForObject($query, $this->getDtoClassName(), array($userId, $rosterId, $userId, $rosterId));
    }

    public function findLastMessages( $userId, $rosterId, $lastMessageTimestamp, $count )
    {
        $q = "SELECT `tmp`.* FROM (SELECT * FROM {$this->getTableName()} WHERE (`from` = :user AND `to` = :user2) OR (`from` = :user2 AND `to` = :user ) AND `timestamp` <= :timestamp  ORDER BY `timestamp` DESC LIMIT :count) as `tmp` ORDER BY `tmp`.`timestamp` ASC";

        return $this->dbo->queryForObjectList($q, $this->getDtoClassName(), array(':user' => $userId, ':user2' => $rosterId, ':count' => $count, ':timestamp'=>$lastMessageTimestamp));
    }

    public function findMessages( $user, $lastMessageId=null )
    {
        if ( $lastMessageId === null )
        {
            $lastMessageId = 0;
        }

        $q = "SELECT * FROM `{$this->getTableName()}` WHERE  `to`=:user AND `id` > :mid ORDER BY `timestamp` ASC";

        return $this->dbo->queryForObjectList($q, $this->getDtoClassName(), array(':user' => $user, ':mid' => $lastMessageId));
    }

    public function findUnreadMessages( $userId, $rosterId, $timestamp )
    {
        $query = "SELECT * FROM `{$this->getTableName()}` WHERE ((`from`=:userId AND `to`=:rosterId) OR (`from`=:rosterId AND `to`=:userId )) AND `timestamp` > :timestamp ORDER BY `timestamp` ASC";
        
        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array('userId' => $userId, 'rosterId'=>$rosterId, 'timestamp'=>$timestamp));
    }
}

?>