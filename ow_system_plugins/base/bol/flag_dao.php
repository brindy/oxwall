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
class BOL_FlagDao extends OW_BaseDao
{
    /**
     * 
     * @var BOL_FlagDao
     */
    private static $classInstance;

    /**
     * Enter description here...
     *
     * @return BOL_FlagDao
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
        return OW_DB_PREFIX . 'base_flag';
    }

    public function getDtoClassName()
    {
        return 'BOL_Flag';
    }

    public function flag( $type, $entityId, $reason, $title, $url, $userId, $langKey )
    {
        $dto = new BOL_Flag();
        $this->save(
                $dto->setEntityId($entityId)
                ->setReason($reason)
                ->setUserId($userId)
                ->setType($type)
                ->setTitle($title)
                ->setUrl($url)
                ->setLangKey($langKey)
        );
    }

    public function find( $type, $entityId, $userId )
    {
        $ex = new OW_Example();

        $ex->andFieldEqual('type', $type)
            ->andFieldEqual('entityId', $entityId)
            ->andFieldEqual('userId', $userId);
        $ex->setLimitClause(0, 1);

        return $this->findObjectByExample($ex);
    }

    public function count( $type )
    {
        $q = "SELECT *
		FROM `{$this->getTableName()}`
		WHERE `type` =?
		ORDER BY `entityId`";

        return $this->dbo->queryForColumn($q, array($type));
    }

    public function findList( $first, $count, $type )
    {
        $q = "SELECT `f`.*,
			(
				select count(*) 
				FROM `{$this->getTableName()}` as `f2`
				where `f2`.`entityId` = `f`.`entityId` and `f2`.`reason` = 'spam'   
			) as `spamC`,
			(
				select count(*) 
				FROM `{$this->getTableName()}` as `f2`
				where `f2`.`entityId` = `f`.`entityId` and `f2`.`reason` = 'illegal'   
			) as `illegalC`,
			(
				select count(*) 
				FROM `{$this->getTableName()}` as `f2`
				where `f2`.`entityId` = `f`.`entityId` and `f2`.`reason` = 'offence'   
			) as `offenceC`
		FROM `{$this->getTableName()}` as `f`  
		WHERE `f`.`type` = ?
		GROUP BY `f`.`entityId`
		ORDER BY `offenceC` + `illegalC` + `spamC` DESC
		LIMIT ?, ?";
        
        return $this->dbo->queryForList($q, array($type, $first, $count));
    }

    public function countFlaggedItems( $type )
    {
        $q = "SELECT count(DISTINCT `entityId`) FROM `{$this->getTableName()}` WHERE `type`=?";

        return $this->dbo->queryForColumn($q, array($type));
    }

    public function findTypeList()
    {
        $q = "SELECT `type`, `langKey` FROM `{$this->getTableName()}` GROUP BY `type` ORDER BY `type` ASC";

        return $this->dbo->queryForList($q);
    }

    public function findFlaggedUserIdList( $type, $entityId, $reason )
    {
        $q = "SELECT `userId` FROM `{$this->getTableName()}` WHERE `type`= ? AND `entityId`= ? AND `reason`= ?";

        return $this->dbo->queryForColumnList($q, array($type, $entityId, $reason));
    }

    public function deleteByTypeAndEntityId( $type, $entityId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('type', $type)
            ->andFieldEqual('entityId', $entityId);

        $this->deleteByExample($ex);
    }

    public function findLangKey( $type )
    {
        $q = "select `langKey` from `{$this->getTableName()}` where `type` = ? order by `timestamp` DESC limit 1";

        return $this->dbo->queryForColumn($q, array($type));
    }
    
    public function deleteByType( $type )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('type', $type);

        $this->deleteByExample($ex);
    }    
}