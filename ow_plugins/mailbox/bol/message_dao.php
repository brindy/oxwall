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
 * Data Access Object for `mailbox_message` table.
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.bol
 * @since 1.0
 */
class MAILBOX_BOL_MessageDao extends OW_BaseDao
{

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
     * @var MAILBOX_BOL_MessageDao
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return MAILBOX_BOL_MessageDao
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
     * @see MAILBOX_BOL_MessageDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'MAILBOX_BOL_Message';
    }

    /**
     * @see MAILBOX_BOL_MessageDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'mailbox_message';
    }

    /**
     * Deletes conversation's messages
     *
     * @param int $conversationId
     * @return int
     */
    public function deleteByConversationId( $conversationId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('conversationId', (int) $conversationId);
        return $this->deleteByExample($example);
    }

    /**
     * Returns conversation's messages list
     *
     * @param int $conversationId
     * @return array
     */
    public function findListByConversationId( $conversationId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('conversationId', (int) $conversationId);
        $example->setOrder('timeStamp');
        return $this->findListByExample($example);
    }

    /**
     * Returns conversation's messages count
     *
     * @param int $conversationId
     * @return int
     */
    public function findCountByConversationId( $conversationId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('conversationId', (int) $conversationId);
        return $this->countByExample($example);
    }

    /**
     * don't call this function
     * This is a temporary method used for mailbox plugin update.
     * 
     * @param int $messageId
     * @param int $limit
     * @return MAILBOX_BOL_Message
     */
    public function findNotUpdatedMessages( $messageId, $limit = 100 )
    {
        $example = new OW_Example();
        $example->andFieldGreaterThan('id', (int) $messageId);
        $example->setOrder(" id ");

        return $this->findListByExample($example);
    }
}