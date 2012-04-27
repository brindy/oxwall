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
 * Singleton. 'Flag' Data Access Object
 *
 * @author Aybat Duyshokov <duyshokov@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
class BOL_FlagService
{
    /*
     * @type BOL_FlagDao
     */
    private $dao;
    /**
     * 
     * @var BOL_FlagService
     */
    private static $classInstance;

    /**
     * Enter description here...
     *
     * @return BOL_FlagService
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function __construct()
    {
        $this->dao = BOL_FlagDao::getInstance();
    }

    public function flag( $type, $entityId, $reason, $title, $url, $langKey, $userId=null )
    {

        if ( $userId == null && !OW::getUser()->isAuthenticated() )
        {
            throw new InvalidArgumentException("Can't be flagged by guest");
        }

        $userId = OW::getUser()->getId();

        $this->dao->flag($type, $entityId, $reason, $title, $url, $userId, $langKey);
    }

    public function isFlagged( $type, $entityId, $userId )
    {
        return null !== $this->dao->find($type, $entityId, $userId);
    }

    public function count( $type )
    {
        return $this->dao->count($type);
    }

    public function findList( $first, $count, $type )
    {
        return $this->dao->findList($first, $count, $type);
    }

    public function findTypeList()
    {
        return $this->dao->findTypeList();
    }

    public function countFlaggedItems( $type )
    {
        return $this->dao->countFlaggedItems($type);
    }

    public function findFlaggedUserIdList( $type, $entityId, $reason )
    {
        return $this->dao->findFlaggedUserIdList($type, $entityId, $reason);
    }

    public function deleteById( $id )
    {
        $this->dao->deleteById($id);
    }

    public function deleteByTypeAndEntityId( $type, $entityId )
    {
        $this->dao->deleteByTypeAndEntityId($type, $entityId);
    }

    /**
     *
     * @param string $type
     */
    public function findLangKey( $type )
    {
        return $this->dao->findLangKey($type);
    }
    
    
    public function deleteByType($type)
    {
    	$this->dao->deleteByType($type);
    }
}