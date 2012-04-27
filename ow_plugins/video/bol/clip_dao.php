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
 * Data Access Object for `video_clip` table.  
 * 
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.video.bol
 * @since 1.0
 * 
 */
class VIDEO_BOL_ClipDao extends OW_BaseDao
{
    /**
     * Class instance
     *
     * @var VIDEO_BOL_ClipDao
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
     * @return VIDEO_BOL_ClipDao
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     * @return string
     */
    public function getDtoClassName()
    {
        return 'VIDEO_BOL_Clip';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     * @return string
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'video_clip';
    }

    /**
     * Get clips list (featured|latest|toprated)
     *
     * @param string $listtype
     * @param int $page
     * @param int $limit
     * @return array of VIDEO_BOL_Clip
     */
    public function getClipsList( $listtype, $page, $limit )
    {
        $first = ($page - 1 ) * $limit;

        switch ( $listtype )
        {
            case 'featured':
                $clipFeaturedDao = VIDEO_BOL_ClipFeaturedDao::getInstance();

                $query = "
                    SELECT `c`.*
                    FROM `" . $this->getTableName() . "` AS `c`
                    LEFT JOIN `" . $clipFeaturedDao->getTableName() . "` AS `f` ON (`f`.`clipId`=`c`.`id`)
                    WHERE `c`.`status` = 'approved' AND `c`.`privacy` = 'everybody' AND `f`.`id` IS NOT NULL
                    ORDER BY `c`.`addDatetime` DESC
                    LIMIT :first, :limit
                ";

                $qParams = array('first' => $first, 'limit' => $limit);

                return $this->dbo->queryForObjectList($query, 'VIDEO_BOL_Clip', $qParams);

                break;

            case 'latest':
                $example = new OW_Example();

                $example->andFieldEqual('status', 'approved');
                $example->andFieldEqual('privacy', 'everybody');
                $example->setOrder('`addDatetime` DESC');
                $example->setLimitClause($first, $limit);

                return $this->findListByExample($example);

                break;
        }
    }

    /**
     * Get user video clips list
     *
     * @param int $userId
     * @param int $itemsNum
     * @param int $exclude 
     * @return array of VIDEO_BOL_Clip
     */
    public function getUserClipsList( $userId, $page, $itemsNum, $exclude )
    {
        $first = ($page - 1 ) * $itemsNum;

        $example = new OW_Example();

        $example->andFieldEqual('status', 'approved');
        $example->andFieldEqual('userId', $userId);

        if ( $exclude )
        {
            $example->andFieldNotEqual('id', $exclude);
        }

        $example->setOrder('`addDatetime` DESC');
        $example->setLimitClause($first, $itemsNum);

        return $this->findListByExample($example);
    }

    /**
     * Counts clips
     *
     * @param string $listtype
     * @return int
     */
    public function countClips( $listtype )
    {
        switch ( $listtype )
        {
            case 'featured':
                $featuredDao = VIDEO_BOL_ClipFeaturedDao::getInstance();

                $query = "
                    SELECT COUNT(`c`.`id`)       
                    FROM `" . $this->getTableName() . "` AS `c`
                    LEFT JOIN `" . $featuredDao->getTableName() . "` AS `f` ON ( `c`.`id` = `f`.`clipId` )
                    WHERE `c`.`status` = 'approved' AND `c`.`privacy` = 'everybody' AND `f`.`id` IS NOT NULL
                ";

                return $this->dbo->queryForColumn($query);

                break;

            case 'latest':
                $example = new OW_Example();

                $example->andFieldEqual('status', 'approved');
                $example->andFieldEqual('privacy', 'everybody');

                return $this->countByExample($example);

                break;
        }
    }

    /**
     * Counts clips added by a user
     *
     * @param int $userId
     * @return int
     */
    public function countUserClips( $userId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('userId', $userId);
        $example->andFieldEqual('status', 'approved');

        return $this->countByExample($example);
    }
    
    public function findByUserId( $userId )
    {
        $example = new OW_Example();

        $example->andFieldEqual('userId', $userId);

        return $this->findIdListByExample($example);
    }
    
    public function updatePrivacyByUserId( $userId, $privacy )
    {
        $sql = "UPDATE `".$this->getTableName()."` SET `privacy` = :privacy 
            WHERE `userId` = :userId";
        
        $this->dbo->query($sql, array('privacy' => $privacy, 'userId' => $userId));
    }
}