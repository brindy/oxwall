<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 *  Comment Service.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
final class BOL_CommentService
{
    const CONFIG_COMMENTS_ON_PAGE = 'comments_on_page';
    const CONFIG_ALLOWED_TAGS = 'allowed_tags';
    const CONFIG_ALLOWED_ATTRS = 'allowed_attrs';

    /**
     * @var BOL_CommentDao
     */
    private $commentDao;
    /**
     * @var BOL_CommentEntityDao;
     */
    private $commentEntityDao;
    /**
     * @var array
     */
    private $configs;
    /**
     * Singleton instance.
     *
     * @var BOL_CommentDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return BOL_CommentService
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
     * Constructor.
     */
    private function __construct()
    {
        $this->commentDao = BOL_CommentDao::getInstance();
        $this->commentEntityDao = BOL_CommentEntityDao::getInstance();

        $this->configs[self::CONFIG_COMMENTS_ON_PAGE] = 10;
        //$this->configs[self::CONFIG_ALLOWED_TAGS] = array('a', 'b', 'i', 'span', 'u', 'strong', 'br');
        //$this->configs[self::CONFIG_ALLOWED_ATTRS] = array('style', 'href');
    }

    /**
     * Returns comments list for entity item.
     *
     * @param string $entityType
     * @param integer $entityId
     * @param integer $page
     * @return array
     */
    public function findCommentList( $entityType, $entityId, $page = null, $count = null )
    {
        $page = ( $page === null ) ? 1 : (int) $page;
        $count = ( (int) $count === 0 ) ? $this->configs[self::CONFIG_COMMENTS_ON_PAGE] : (int) $count;
        $first = ( $page - 1 ) * $count;

        return $this->commentDao->findCommentList($entityType, $entityId, $first, $count);
    }

    /**
     * Returns full comments list for entity item.
     *
     * @param string $entityType
     * @param integer $entityId
     * @return array
     */
    public function findFullCommentList( $entityType, $entityId )
    {
        return $this->commentDao->findFullCommentList($entityType, $entityId);
    }

    /**
     * Returns comments count for entity item.
     *
     * @param integer $entityId
     * @param string $entityType
     * @return array
     */
    public function findCommentCount( $entityType, $entityId )
    {
        return (int) $this->commentDao->findCommentCount($entityType, $entityId);
    }

    /**
     * Returns entity item comment pages count.
     *
     * @param integer $entityId
     * @param string $entityType
     * @return integer
     */
    public function findCommentPageCount( $entityType, $entityId, $count = null )
    {
        $count = ( (int) $count === 0 ) ? $this->configs[self::CONFIG_COMMENTS_ON_PAGE] : (int) $count;
        $commentCount = $this->findCommentCount($entityType, $entityId);

        if ( $commentCount === 0 )
        {
            return 1;
        }

        return ( ( $commentCount - ( $commentCount % $count ) ) / $count ) + ( ( $commentCount % $count > 0 ) ? 1 : 0 );
    }

    /**
     * Returns comment item.
     *
     * @param integer $commentId
     * @return BOL_Comment
     */
    public function findComment( $id )
    {
        return $this->commentDao->findById($id);
    }

    /**
     * @param integer $id
     * @return BOL_CommentEntity
     */
    public function findCommentEntityById( $id )
    {
        return $this->commentEntityDao->findById($id);
    }

    /**
     * @return BOL_Comment
     */
    public function addComment( $entityType, $entityId, $pluginKey, $userId, $message, $attachment = null )
    {
        $commentEntity = $this->commentEntityDao->findByEntityTypeAndEntityId($entityType, $entityId);

        if ( $commentEntity === null )
        {
            $commentEntity = new BOL_CommentEntity();
            $commentEntity->setEntityType(trim($entityType));
            $commentEntity->setEntityId((int) $entityId);
            $commentEntity->setPluginKey($pluginKey);

            $this->commentEntityDao->save($commentEntity);
        }

        //$message = UTIL_HtmlTag::stripTags($message, $this->configs[self::CONFIG_ALLOWED_TAGS], $this->configs[self::CONFIG_ALLOWED_ATTRS]);
        //$message = UTIL_HtmlTag::stripJs($message);
        //$message = UTIL_HtmlTag::stripTags($message, array('frame', 'style'), array(), true);
        if( $attachment !== null && strlen($message) == 0 )
        {
            $message = '&nbsp;';
        }
        else
        {
            $message = UTIL_HtmlTag::autoLink(nl2br(htmlspecialchars($message)));
        }

        $comment = new BOL_Comment();
        $comment->setCommentEntityId($commentEntity->getId());
        $comment->setMessage(trim($message));
        $comment->setUserId($userId);
        $comment->setCreateStamp(time());

        if( $attachment !== null )
        {
            $comment->setAttachment($attachment);
        }

        $this->commentDao->save($comment);

        return $comment;
    }

    public function updateComment( BOL_Comment $comment )
    {
        $this->commentDao->save($comment);
    }

        /**
     * Deletes comment item.
     *
     * @param integer $id
     */
    public function deleteComment( $id )
    {
        $this->commentDao->deleteById($id);
    }

    public function deleteCommentEntity( $id )
    {
        $this->commentEntityDao->deleteById($id);
    }

    /**
     * Deletes entity comments.
     *
     * @param integer $entityId
     * @param string $entityType
     */
    public function deleteEntityComments( $entityType, $entityId )
    {
        $commentEntity = $this->commentEntityDao->findByEntityTypeAndEntityId($entityType, $entityId);

        if ( $commentEntity === null )
        {
            return;
        }

        $this->commentDao->deleteByCommentEntityId($commentEntity->getId());
        $this->commentEntityDao->delete($commentEntity);
    }

    /**
     * @param string $entityType
     * @param integer $entityId
     * @param boolean $status
     */
    public function setEntityStatus( $entityType, $entityId, $status = true )
    {
        $commentEntity = $this->commentEntityDao->findByEntityTypeAndEntityId($entityType, $entityId);

        if ( $commentEntity === null )
        {
            return;
        }

        $commentEntity->setActive(($status ? 1 : 0));
        $this->commentEntityDao->save($commentEntity);
    }

    /**
     * @param integer $entityType
     * @param array $idList
     * @return array
     */
    public function findCommentCountForEntityList( $entityType, array $idList )
    {
        $commentCountArray = $this->commentDao->findCommentCountForEntityList($entityType, $idList);

        $commentCountAssocArray = array();

        $resultArray = array();

        foreach ( $commentCountArray as $value )
        {
            $commentCountAssocArray[$value['id']] = $value['commentCount'];
        }

        foreach ( $idList as $value )
        {
            $resultArray[$value] = ( array_key_exists($value, $commentCountAssocArray) ) ? $commentCountAssocArray [$value] : 0;
        }

        return $resultArray;
    }

    /**
     * Finds most commented entities.
     *
     * @param string $entityType
     * @param integer $first
     * @param integer $count
     * @return array<BOL_CommentEntity>
     */
    public function findMostCommentedEntityList( $entityType, $first, $count )
    {
        $resultArray = $this->commentDao->findMostCommentedEntityList($entityType, $first, $count);

        $resultList = array();

        foreach ( $resultArray as $item )
        {
            $resultList[$item['id']] = $item;
        }

        return $resultList;
    }

    /**
     * Finds comments count for entity type.
     *
     * @param string $entityType
     * @return integer
     */
    public function findCommentedEntityCount( $entityType )
    {
        return $this->commentEntityDao->findCommentedEntityCount($entityType);
    }

    /**
     * Deletes all user comments.
     *
     * @param integer $userId
     */
    public function deleteUserComments( $userId )
    {
        $this->commentDao->deleteByUserId($userId);
    }

    /**
     * Deletes comments for provided entity type.
     *
     * @param string $entityType
     */
    public function deleteEntityTypeComments( $entityType )
    {
        $entityType = trim($entityType);
        $this->commentDao->deleteEntityTypeComments($entityType);
        $this->commentEntityDao->deleteByEntityType($entityType);
    }

    /**
     * Deletes all plugin entities comments.
     *
     * @param string $pluginKey
     */
    public function deletePluginComments( $pluginKey )
    {
        $pluginKey = trim($pluginKey);
        $this->commentDao->deleteByPluginKey($pluginKey);
        $this->commentEntityDao->deleteByPluginKey($pluginKey);
    }

    /**
     * Finds comment entity object for provided entity type and id.
     *
     * @param string $entityType
     * @param integer $entityId
     * @return BOL_CommentEntity
     */
    public function findCommentEntity( $entityType, $entityId )
    {
        return $this->commentEntityDao->findByEntityTypeAndEntityId($entityType, $entityId);
    }
}