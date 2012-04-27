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
 * @package ow_plugins.links.bol.dao
 * @since 1.0
 */
class LinkDao extends OW_BaseDao
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
     * @var PostDao
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return PostDao
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
        return 'Link';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'links_link';
    }

    public function findList( $first, $count )
    {
        $ex = new OW_Example();
        $ex->setOrder('`timestamp` DESC')
            ->setLimitClause($first, $count)
            ->andFieldEqual('privacy', 'everybody');

        return $this->findListByExample($ex);
    }

    public function countLinks()
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('privacy', 'everybody');

        return $this->countByExample($ex);
    }

    public function findMostCommentedList( $first, $count )
    {
        $query = "
			SELECT p.*
			FROM `{$this->getTableName()}` as p
			LEFT JOIN `ow_base_comment` as c /*todo: 8aa*/
			ON( c.`entityType` = 'link' AND p.id = c.`entityId` )
			group by p.`id`
			ORDER BY count( c.id ) DESC
			LIMIT ?, ?";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function findTopRatedList( $first, $count )
    {
        $query = "
			SELECT l.*, IF(SUM(v.vote) IS NOT NULL, SUM(v.vote),0 ) as `t`
			FROM `ow_links_link` as l
			LEFT JOIN `ow_base_vote` as v 
			ON( v.`entityType` = 'link' AND l.id = v.`entityId` )
            GROUP BY l.id
            ORDER BY `t` DESC
			LIMIT ?, ?";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function findListByTag( $tag, $first, $count )
    {

        $query = "
			SELECT l.*
			FROM `ow_base_tag` as t
			INNER JOIN `ow_base_entity_tag` as `et`
				ON(`t`.`id` = `et`.`tagId` AND `et`.`entityType` = 'link')
			INNER JOIN `{$this->getTableName()}` as l
				ON(`et`.`entityId` = `l`.`id`)
			WHERE `t`.`label` = '{$tag}'
			LIMIT ?, ?";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function countByTag( $tag )
    {
        $query = "
			SELECT COUNT(*)
			FROM `ow_base_tag` as t
			INNER JOIN `ow_base_entity_tag` as `et`
				ON(`t`.`id` = `et`.`tagId` AND `et`.`entityType` = 'link')
			INNER JOIN `{$this->getTableName()}` as l
				ON(`et`.`entityId` = `l`.`id`)
			WHERE `t`.`label` = '{$tag}'";

        return $this->dbo->queryForColumn($query);
    }

    public function findListByIdList( $list )
    {
        $ex = new OW_Example();

        $ex->andFieldInArray('id', $list);
        $ex->andFieldEqual('privacy', 'everybody');

        return $this->findListByExample($ex);
    }

    public function countUserLinks( $userId )
    {
        $ex = new OW_Example();

        $ex->andFieldEqual('userId', $userId);

        return $this->countByExample($ex);
    }

    public function findUserLinkList( $userId, $first, $count )
    {
        $ex = new OW_Example();

        $ex->andFieldEqual('userId', $userId);
        $ex->setLimitClause($first, $count);

        return $this->findListByExample($ex);
    }

    public function updateLinksPrivacy( $userId, $privacy )
    {
        $sql = "UPDATE `" . $this->getTableName() . "` SET `privacy` = :privacy
            WHERE `userId` = :userId";

        $this->dbo->query($sql, array('privacy' => $privacy, 'userId' => $userId));
    }
}

?>