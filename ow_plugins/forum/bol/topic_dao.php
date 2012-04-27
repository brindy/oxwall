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
 * Data Access Object for `forum_topic` table.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.bol
 * @since 1.0
 */
class FORUM_BOL_TopicDao extends OW_BaseDao
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
     * @var FORUM_BOL_TopicDao
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return FORUM_BOL_TopicDao
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
        return 'FORUM_BOL_Topic';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'forum_topic';
    }

    /**
     * Returns forum group's topic count
     * 
     * @param int 
     * @return int $groupId
     */
    public function findGroupTopicCount( $groupId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('groupId', (int) $groupId);

        return $this->countByExample($example);
    }

    /**
     * Returns forum group's post count
     * 
     * @param int $groupId
     * @return int
     */
    public function findGroupPostCount( $groupId )
    {
        $query = "
		SELECT COUNT(`p`.`id`) FROM `" . $this->getTableName() . "` AS `t`
		LEFT JOIN `" . FORUM_BOL_PostDao::getInstance()->getTableName() . "` AS `p` 
		ON ( `t`.`id` = `p`.`topicId` )
		WHERE `t`.`groupId` = ?
		";

        $postCount = $this->dbo->queryForColumn($query, array($groupId));

        return (int) $postCount;
    }

    /**
     * Returns forum group's topic list
     * 
     * @param int $groupId
     * @return array 
     */
    public function findGroupTopicList( $groupId, $first, $count )
    {
        $query = "
		SELECT `t`.*, COUNT(`p`.`id`) AS `postCount`, MAX(`p`.`createStamp`) AS `createStamp` 
		FROM `" . $this->getTableName() . "` AS `t`
		LEFT JOIN `" . FORUM_BOL_PostDao::getInstance()->getTableName() . "` AS `p`
		ON (`t`.`id` = `p`.`topicId`)
		WHERE `t`.`groupId` = ?
		GROUP BY `p`.`topicId`
		ORDER BY `t`.`sticky` DESC, `createStamp` DESC
		LIMIT ?, ?
		";

        return $this->dbo->queryForList($query, array($groupId, $first, $count));
    }

    public function findLastTopicList( $limit )
    {
        $postDao = FORUM_BOL_PostDao::getInstance();
        $groupDao = FORUM_BOL_GroupDao::getInstance();
        $sectionDao = FORUM_BOL_SectionDao::getInstance();


        $query = "
            SELECT `t`.*, COUNT(`p`.`id`) AS `postCount`, MAX(`p`.`createStamp`) AS `createStamp` 
            FROM `" . $this->getTableName() . "` AS `t`
            LEFT JOIN `" . $groupDao->getTableName() . "` AS `g`
            ON (`t`.`groupId` = `g`.`id`)
            LEFT JOIN `" . $sectionDao->getTableName() . "` AS `s`
            ON (`s`.`id` = `g`.`sectionId`)
            LEFT JOIN `" . $postDao->getTableName() . "` AS `p`
            ON (`t`.`id` = `p`.`topicId`)
            WHERE `s`.`isHidden` = 0
            GROUP BY `p`.`topicId`
            ORDER BY `createStamp` DESC
            LIMIT ?
        ";

        return $this->dbo->queryForList($query, array($limit));
    }

    public function findUserTopicList( $userId )
    {
        $query = "
            SELECT * FROM `" . $this->getTableName() . "` WHERE `userId` = ?
        ";

        return $this->dbo->queryForList($query, array($userId));
    }

    /**
     * Returns forum topic info
     * 
     * @param int $topicId
     * @return array 
     */
    public function findTopicInfo( $topicId )
    {
        $query = "
		SELECT `t`.*, `g`.`id` AS `groupId`, `g`.`name` AS `groupName`, `s`.`name` AS `sectionName`, `s`.`id` AS `sectionId` 
		FROM `" . $this->getTableName() . "` AS `t`
		LEFT JOIN `" . FORUM_BOL_GroupDao::getInstance()->getTableName() . "` AS `g` 
		ON (`t`.`groupId` = `g`.`id`)
		LEFT JOIN `" . FORUM_BOL_SectionDao::getInstance()->getTableName() . "` AS `s`
		ON (`g`.`sectionId` = `s`.`id`)
		WHERE `t`.`id` = ?
		";

        return $this->dbo->queryForRow($query, array($topicId));
    }

    /**
     * Returns topic id list
     * 
     * @param array $groupIds
     * @return array 
     */
    public function findIdListByGroupIds( $groupIds )
    {
        $example = new OW_Example();
        $example->andFieldInArray('groupId', $groupIds);

        $query = "
    	SELECT `id` FROM `" . $this->getTableName() . "`
    	" . $example;

        return $this->dbo->queryForColumnList($query);
    }

    public function getTopicIdListForDelete( $limit )
    {
        $example = new OW_Example();
        $example->setOrder('`id` ASC');
        $example->setLimitClause(0, $limit);

        return $this->findIdListByExample($example);
    }
}