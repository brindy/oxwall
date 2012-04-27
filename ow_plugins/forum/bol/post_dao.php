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
 * Data Access Object for `forum_post` table
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.bol
 * @since 1.0
 */
class FORUM_BOL_PostDao extends OW_BaseDao
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
     * @var FORUM_BOL_PostDao
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return FORUM_BOL_PostDao
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
        return 'FORUM_BOL_Post';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'forum_post';
    }

    /**
     * Returns topic's post list
     *
     * @param int $topicId
     * @param int $first
     * @param int $count
     * @return array of FORUM_BOL_Post
     */
    public function findTopicPostList( $topicId, $first, $count )
    {
        $example = new OW_Example();

        $example->andFieldEqual('topicId', $topicId);
        $example->setOrder('`id`');
        $example->setLimitClause($first, $count);

        return $this->findListByExample($example);
    }

    /**
     * Returns topic's post count
     *
     * @param int $topicId
     * @return int
     */
    public function findTopicPostCount( $topicId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('topicId', $topicId);

        return $this->countByExample($example);
    }

    /**
     * Returns post number in the topic
     *
     * @param int $topicId
     * @param int $postId
     * @return int
     */
    public function findPostNumber( $topicId, $postId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('topicId', $topicId);
        $example->andFieldLessOrEqual('id', $postId);

        return $this->countByExample($example);
    }

    /**
     * Finds previous post in the topic
     *
     * @param int $topicId
     * @param int $postId
     * @return FORUM_BOL_Post
     */
    public function findPreviousPost( $topicId, $postId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('topicId', $topicId);
        $example->andFieldLessThan('id', $postId);
        $example->setOrder('`id` DESC');
        $example->setLimitClause(0, 1);

        return $this->findObjectByExample($example);
    }

    /**
     * Finds topic post id list
     *
     * @param int $topicId
     * @return array
     */
    public function findTopicPostIdList( $topicId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('topicId', $topicId);

        $query = "
		SELECT `id` FROM `" . $this->getTableName() . "`
		" . $example;

        return $this->dbo->queryForColumnList($query);
    }

    public function findUserPostIdList( $userId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('userId', $userId);

        $query = "
            SELECT `id` FROM `" . $this->getTableName() . "` " . $example;

        return $this->dbo->queryForColumnList($query);
    }

    public function findTopicFirstPost( $topicId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('topicId', $topicId);
        $example->setOrder("`id`");
        $example->setLimitClause(0, 1);

        return $this->findObjectByExample($example);
    }
    
    public function findGroupLastPost( $groupId )
    {
        $topicDao = FORUM_BOL_TopicDao::getInstance();
        
        $sql = "SELECT `p`.*, `t`.`title` FROM `".$this->getTableName()."` AS `p`
            INNER JOIN `".$topicDao->getTableName()."` AS `t` ON(`p`.`topicId`=`t`.`id`)
            WHERE `t`.`groupId` = :groupId
            ORDER BY `p`.`createStamp` DESC LIMIT 1";
        
        return $this->dbo->queryForRow($sql, array('groupId' => $groupId));
    }
    
    public function searchInGroups( $token, $page, $limit, $excludeGroupIdList = null )
    {
        $excludeCond = $excludeGroupIdList ? ' AND `g`.`id` NOT IN ('.implode(',', $excludeGroupIdList).') = 1' : '';

        $limit = (int) $limit;
        $first = ( $page - 1 ) * $limit;
        
        $query = "SELECT `t`.*, `g`.`sectionId`, `s`.`name` AS `sectionName`, `g`.`name` AS `groupName`, 
            MATCH (`t`.`title`) AGAINST(:token), MATCH (`p`.`text`) AGAINST(:token) 
            FROM `".$this->getTableName()."` AS `p`
            INNER JOIN `".FORUM_BOL_TopicDao::getInstance()->getTableName()."` AS `t` ON (`t`.`id`=`p`.`topicId`)
            INNER JOIN `".FORUM_BOL_GroupDao::getInstance()->getTableName()."` AS `g` ON (`g`.`id`=`t`.`groupId`)
            INNER JOIN `".FORUM_BOL_SectionDao::getInstance()->getTableName()."` AS `s` ON (`s`.`id`=`g`.`sectionId`)
            WHERE (MATCH (`t`.`title`) AGAINST(:token) OR MATCH (`p`.`text`) AGAINST(:token))
            ".$excludeCond." AND `s`.`isHidden` = 0
            GROUP BY `t`.`id`
            ORDER BY MATCH (`t`.`title`) AGAINST(:token) DESC, MATCH (`p`.`text`) AGAINST(:token) DESC
            LIMIT :first, :limit";
        
        $params = array('token' => $token, 'first' => $first, 'limit' => $limit);
        
        return $this->dbo->queryForList($query, $params);
    }
    
    public function countFoundTopicsInGroups( $token, $excludeGroupIdList = null )
    {
        $excludeCond = $excludeGroupIdList ? ' AND `g`.`id` NOT IN ('.implode(',', $excludeGroupIdList).') = 1' : '';

        $query = "SELECT count(DISTINCT(`t`.`id`)) 
            FROM `".$this->getTableName()."` AS `p`
            INNER JOIN `".FORUM_BOL_TopicDao::getInstance()->getTableName()."` AS `t` ON (`t`.`id`=`p`.`topicId`)
            INNER JOIN `".FORUM_BOL_GroupDao::getInstance()->getTableName()."` AS `g` ON (`g`.`id`=`t`.`groupId`)
            INNER JOIN `".FORUM_BOL_SectionDao::getInstance()->getTableName()."` AS `s` ON (`s`.`id`=`g`.`sectionId`)
            WHERE (MATCH (`t`.`title`) AGAINST(:token) OR MATCH (`p`.`text`) AGAINST(:token))
            ".$excludeCond." AND `s`.`isHidden` = 0";
        
        return (int)$this->dbo->queryForColumn($query, array('token' => $token));
    }
    
    public function searchInGroup( $token, $page, $limit, $groupId, $isHidden = 0 )
    {
        $hiddenCond = $isHidden ? ' AND `s`.`isHidden` = 1' : ' AND `s`.`isHidden` = 0';

        $limit = (int) $limit;
        $first = ( $page - 1 ) * $limit;
        
        $query = "SELECT `t`.*, `g`.`sectionId`, `s`.`name` AS `sectionName`, `g`.`name` AS `groupName`, 
            MATCH (`t`.`title`) AGAINST(:token), MATCH (`p`.`text`) AGAINST(:token) 
            FROM `".$this->getTableName()."` AS `p`
            INNER JOIN `".FORUM_BOL_TopicDao::getInstance()->getTableName()."` AS `t` ON (`t`.`id`=`p`.`topicId`)
            INNER JOIN `".FORUM_BOL_GroupDao::getInstance()->getTableName()."` AS `g` ON (`g`.`id`=`t`.`groupId`)
            INNER JOIN `".FORUM_BOL_SectionDao::getInstance()->getTableName()."` AS `s` ON (`s`.`id`=`g`.`sectionId`)
            WHERE (MATCH (`t`.`title`) AGAINST(:token) OR MATCH (`p`.`text`) AGAINST(:token))
            AND `g`.`id` = :groupId " . $hiddenCond."
            GROUP BY `t`.`id`
            ORDER BY MATCH (`t`.`title`) AGAINST(:token) DESC, MATCH (`p`.`text`) AGAINST(:token) DESC
            LIMIT :first, :limit";
        
        $params = array('token' => $token, 'groupId' => $groupId, 'first' => $first, 'limit' => $limit);
        
        return $this->dbo->queryForList($query, $params);
    }
    
    public function countFoundTopicsInGroup( $token, $groupId, $isHidden = 0 )
    {
        $hiddenCond = $isHidden ? ' AND `s`.`isHidden` = 1' : ' AND `s`.`isHidden` = 0';

        $query = "SELECT count(DISTINCT(`t`.`id`)) 
            FROM `".$this->getTableName()."` AS `p`
            INNER JOIN `".FORUM_BOL_TopicDao::getInstance()->getTableName()."` AS `t` ON (`t`.`id`=`p`.`topicId`)
            INNER JOIN `".FORUM_BOL_GroupDao::getInstance()->getTableName()."` AS `g` ON (`g`.`id`=`t`.`groupId`)
            INNER JOIN `".FORUM_BOL_SectionDao::getInstance()->getTableName()."` AS `s` ON (`s`.`id`=`g`.`sectionId`)
            WHERE (MATCH (`t`.`title`) AGAINST(:token) OR MATCH (`p`.`text`) AGAINST(:token))
            AND `g`.`id` = :groupId " . $hiddenCond;
        
        $params = array('token' => $token, 'groupId' => $groupId);
        
        return (int)$this->dbo->queryForColumn($query, $params);
    }
    
    public function searchInTopic( $token, $topicId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('topicId', $topicId);
        $example->andFieldMatchAgainst(array('text'), $token);
        
        return $this->findListByExample($example);
    }
    
}