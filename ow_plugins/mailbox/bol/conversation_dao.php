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
 * Data Access Object for `mailbox_conversation` table.
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.bol
 * @since 1.0
 */
class MAILBOX_BOL_ConversationDao extends OW_BaseDao
{
    const READ_NONE = 0;
    const READ_INITIATOR = 1;
    const READ_INTERLOCUTOR = 2;
    const READ_ALL = 3;

    const DELETED_NONE = 0;
    const DELETED_INITIATOR = 1;
    const DELETED_INTERLOCUTOR = 2;
    const DELETED_ALL = 3;

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }
    /**
     * Class instance
     *
     * @var MAILBOX_BOL_ConversationDao
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return MAILBOX_BOL_ConversationDao
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
     * @see MAILBOX_BOL_ConversationDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'MAILBOX_BOL_Conversation';
    }

    /**
     * @see MAILBOX_BOL_ConversationDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'mailbox_conversation';
    }

    /**
     * Returns sent conversations list by $userId
     *
     * @param int $userId
     * @param int $first
     * @param int $count
     * @return array
     */
    public function getSentConversationList( $userId, $first, $count )
    {
        $sql = " SELECT `conv`.*, `last_m`.*, `mess`.* FROM `" . $this->getTableName() . "` AS `conv`
        
				 INNER JOIN `" . MAILBOX_BOL_LastMessageDao::getInstance()->getTableName() . "` AS `last_m`
					 ON ( `last_m`.`conversationId` = `conv`.`id` )
				 
				 INNER JOIN `" . MAILBOX_BOL_MessageDao::getInstance()->getTableName() . "` AS `mess`
				 	ON ( `conv`.`id` = `mess`.conversationId )
				 
				 WHERE ( `conv`.`initiatorId` = :user AND `conv`.`deleted` != " . self::DELETED_INITIATOR . " AND `mess`.`id` = `last_m`.`initiatorMessageId` )
					 	OR ( `conv`.`interlocutorId` = :user AND `conv`.`deleted` != "  . self::DELETED_INTERLOCUTOR .  " AND `mess`.`id` = `last_m`.`interlocutorMessageId` )
                 
				 ORDER BY `mess`.`timeStamp` DESC
                 
				 LIMIT :first, :count ";
        return $this->dbo->queryForList($sql, array('user' => $userId, 'first' => $first, 'count' => $count));
    }

    /**
     * Returns inbox conversations list by $userId
     *
     * @param int $userId
     * @param int $first
     * @param int $count
     * @return array
     */
    public function getInboxConversationList( $userId, $first, $count )
    {
        $sql = " SELECT `conv`.*, `last_m`.*, `mess`.* FROM `" . $this->getTableName() . "` AS `conv`
        
				 INNER JOIN `" . MAILBOX_BOL_LastMessageDao::getInstance()->getTableName() . "` AS `last_m`
					 ON ( `last_m`.`conversationId` = `conv`.`id` )
				 
				 INNER JOIN `" . MAILBOX_BOL_MessageDao::getInstance()->getTableName() . "` AS `mess`
				 	ON ( `conv`.`id` = `mess`.conversationId )
				 
				 WHERE ( `conv`.`initiatorId` = :user AND `conv`.`deleted` != " . self::DELETED_INITIATOR . " AND `mess`.`id` = `last_m`.`interlocutorMessageId` )
					 	OR ( `conv`.`interlocutorId` = :user AND `conv`.`deleted` != "  . self::DELETED_INTERLOCUTOR .  " AND `mess`.`id` = `last_m`.`initiatorMessageId` )
                 
				 ORDER BY `mess`.`timeStamp` DESC

				 LIMIT :first, :count ";

        return $this->dbo->queryForList($sql, array('user' => $userId, 'first' => $first, 'count' => $count));
    }

    /**
     * Returns inbox conversations count
     *
     * @param int $userId
     * @return int
     */
    public function getInboxConversationCount( $userId )
    {
        $sql = " SELECT COUNT(`conv`.`id`) FROM `" . $this->getTableName() . "` AS `conv`
        
				 INNER JOIN `" . MAILBOX_BOL_LastMessageDao::getInstance()->getTableName() . "` AS `last_m`
					 ON `last_m`.`conversationId` = `conv`.`id`
					 	AND ( `conv`.`initiatorId` = :user AND `last_m`.`interlocutorMessageId` > 0 AND `conv`.`deleted` != " . self::DELETED_INITIATOR . "
					 	OR `conv`.`interlocutorId` = :user AND `conv`.`deleted` != " . self::DELETED_INTERLOCUTOR . " )";

        return $this->dbo->queryForColumn($sql, array('user' => $userId));
    }

    /**
     * Returns new inbox conversations count
     *
     * @param int $userId
     * @return int
     */
    public function getNewInboxConversationCount( $userId )
    {
        $sql = " SELECT COUNT(`conv`.`id`) FROM `" . $this->getTableName() . "` AS `conv`
        
				 INNER JOIN `" . MAILBOX_BOL_LastMessageDao::getInstance()->getTableName() . "` AS `last_m`
					 ON `last_m`.`conversationId` = `conv`.`id`
					 	AND ( ( `conv`.`initiatorId` = :user AND `last_m`.`interlocutorMessageId` > 0 AND `conv`.`deleted` != " . self::DELETED_INITIATOR . " AND NOT `conv`.`read` & " . (self::READ_INITIATOR) . " )
					 	OR ( `conv`.`interlocutorId` = :user AND `conv`.`deleted` != " . self::DELETED_INTERLOCUTOR . "  AND  NOT `conv`.`read` & " . (self::READ_INTERLOCUTOR) . " ) )";

        return (int) $this->dbo->queryForColumn($sql, array('user' => $userId));
    }

    /**
     * Returns sent conversations count
     *
     * @param int $userId
     * @return int
     */
    public function getSentConversationCount( $userId )
    {
        $sql = " SELECT COUNT(`conv`.`id`) FROM `" . $this->getTableName() . "` AS `conv`
        
				 INNER JOIN `" . MAILBOX_BOL_LastMessageDao::getInstance()->getTableName() . "` AS `last_m`
					 ON `last_m`.`conversationId` = `conv`.`id`
					 	AND ( `conv`.`initiatorId` = :user AND `conv`.`deleted` != " . self::DELETED_INITIATOR . "
					 	OR `conv`.`interlocutorId` = :user AND `last_m`.`interlocutorMessageId` > 0 AND `conv`.`deleted` != " . self::DELETED_INTERLOCUTOR . " )";

        return $this->dbo->queryForColumn($sql, array('user' => $userId));
    }

    /**
     * Returns new sent conversations count
     *
     * @param int $userId
     * @return int
     */
    public function getNewSentConversationCount( $userId )
    {
        $sql = " SELECT COUNT(`conv`.`id`) FROM `" . $this->getTableName() . "` AS `conv`
        
				 INNER JOIN `" . MAILBOX_BOL_LastMessageDao::getInstance()->getTableName() . "` AS `last_m`
					 ON `last_m`.`conversationId` = `conv`.`id`
					 	AND ( (`conv`.`initiatorId` = :user AND `conv`.`deleted` != " . self::DELETED_INITIATOR . " AND NOT `conv`.`read` & " . (self::READ_INITIATOR) . " )
					 	OR ( `conv`.`interlocutorId` = :user AND `last_m`.`interlocutorMessageId` > 0 AND `conv`.`deleted` != " . self::DELETED_INTERLOCUTOR . "  AND NOT `conv`.`read` & " . (self::READ_INTERLOCUTOR) . " ) )";
        return $this->dbo->queryForColumn($sql, array('user' => $userId));
    }

    /**
     * Returns conversations list by $userId
     *
     * @param int $userId
     * @param int $first
     * @param int $count
     * @return array
     */
    public function getConversationListByUserId( $userId, $first, $count )
    {
        $sql = " SELECT `conv`.* FROM `" . $this->getTableName() . "` AS `conv`
            WHERE `conv`.`initiatorId` = :userId LIMIT :start, :count";

        return $this->dbo->queryForList($sql, array('userId' => $userId, 'start' => $first, 'count' => $count));
    }

    public function getAffectedRows()
    {
        return $this->dbo->getAffectedRows();
    }

    public function findConversationList( $initiatorId, $interlocutorId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('initiatorId', $initiatorId);
        $ex->andFieldEqual('interlocutorId', $interlocutorId);
        $ex->setOrder('id');

        return $this->findListByExample($ex);
    }
}