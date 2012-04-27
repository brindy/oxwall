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
 * Data Access Object for `newsfeed_action` table.
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.bol
 * @since 1.0
 */
class NEWSFEED_BOL_ActionDao extends OW_BaseDao
{
    const CACHE_TIMESTAMP_PREFERENCE = 'newsfeed_generate_action_set_timestamp';
    const CACHE_TIMEOUT = 300; // 5 min
    /**
     * Singleton instance.
     *
     * @var NEWSFEED_BOL_ActionDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return NEWSFEED_BOL_ActionDao
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
        return 'NEWSFEED_BOL_Action';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'newsfeed_action';
    }

    /**
     *
     * @param $entityType
     * @param $entityId
     * @return NEWSFEED_BOL_Action
     */
    public function findAction( $entityType, $entityId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('entityType', $entityType);
        $example->andFieldEqual('entityId', $entityId);

        return $this->findObjectByExample($example);
    }

    public function findByPluginKey( $pluginKey )
    {
        $example = new OW_Example();
        $example->andFieldEqual('pluginKey', $pluginKey);

        return $this->findListByExample($example);
    }

    public function setStatusByPluginKey( $pluginKey, $status )
    {
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $query = "UPDATE " . $this->getTableName() . " action
            INNER JOIN " . $activityDao->getTableName() . " activity ON action.id = activity.actionId
            SET activity.`status`=:s
            WHERE activity.activityType=:ca AND action.pluginKey=:pk";

        $this->dbo->query($query, array(
            's' => $status,
            'pk' => $pluginKey,
            'ca' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
        ));
    }

    public function findByFeed( $feedType, $feedId, $limit = null, $startTime = null )
    {
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $limitStr = '';
        if ( !empty($limit) )
        {
            $limitStr = "LIMIT " . intval($limit[0]) . ", " . intval($limit[1]);
        }

        $query = 'SELECT action.* FROM ' . $this->getTableName() . ' action
            INNER JOIN (

                SELECT DISTINCT cactivity.actionId FROM ' . $activityDao->getTableName() . ' cactivity
                INNER JOIN ' . $actionFeedDao->getTableName() . ' caction_feed ON cactivity.id=caction_feed.activityId
                WHERE cactivity.status=:s AND cactivity.activityType=:ac AND cactivity.timeStamp<:st AND cactivity.privacy=:peb AND caction_feed.feedType=:ft AND caction_feed.feedId=:fi AND cactivity.visibility & :v

            ) cactivity ON action.id = cactivity.actionId

            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId

            LEFT JOIN ' . $activityDao->getTableName() . ' pactivity ON activity.actionId = pactivity.actionId
                AND (pactivity.status=:s AND pactivity.activityType=:ac AND pactivity.privacy!=:peb AND pactivity.visibility & :v)

            WHERE pactivity.id IS NULL AND activity.status=:s AND activity.timeStamp<:st AND activity.privacy=:peb AND action_feed.feedType=:ft AND action_feed.feedId=:fi AND activity.visibility & :v
            GROUP BY action.id ORDER BY MAX(activity.timeStamp) DESC ' . $limitStr;

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'ft' => $feedType,
            'fi' => $feedId,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'v' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            'st' => empty($startTime) ? time() : $startTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
        ));
    }

    public function findCountByFeed( $feedType, $feedId )
    {
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $query = 'SELECT COUNT(DISTINCT activity.actionId) FROM ' . $activityDao->getTableName() . ' activity
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId

            LEFT JOIN ' . $activityDao->getTableName() . ' pactivity ON activity.actionId = pactivity.actionId
                AND (pactivity.status=:s AND pactivity.activityType=:ac AND pactivity.privacy!=:peb AND pactivity.visibility & :v)

            WHERE pactivity.id IS NULL AND activity.status=:s AND activity.activityType=:ac AND activity.privacy=:peb AND action_feed.feedType=:ft AND action_feed.feedId=:fi AND activity.visibility & :v';

        return (int) $this->dbo->queryForColumn($query, array(
            'ft' => $feedType,
            'fi' => $feedId,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'v' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
        ));
    }

    public function findByUser( $userId, $limit = null, $startTime = null )
    {
        $followDao = NEWSFEED_BOL_FollowDao::getInstance();
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();
        $actionSetDao = NEWSFEED_BOL_ActionSetDao::getInstance();

        $limitStr = '';
        if ( !empty($limit) )
        {
            $limitStr = "LIMIT " . intval($limit[0]) . ", " . intval($limit[1]);
        }

        $cacheTimeStamp = BOL_PreferenceService::getInstance()->getPreferenceValue(self::CACHE_TIMESTAMP_PREFERENCE, $userId);

        if ( (int) $cacheTimeStamp < time() - self::CACHE_TIMEOUT )
        {
            $actionSetDao->deleteActionSetUserId($userId);
            $actionSetDao->generateActionSet($userId, $startTime);

            BOL_PreferenceService::getInstance()->savePreferenceValue(self::CACHE_TIMESTAMP_PREFERENCE, $startTime, $userId);
        }

        $query = ' SELECT  b.`id`, b.`entityId`, b.`entityType`, b.`pluginKey`, b.`data` FROM
            ( SELECT  action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
            LEFT JOIN ' . $followDao->getTableName() . ' follow ON action_feed.feedId = follow.feedId AND action_feed.feedType = follow.feedType
            WHERE  cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND (
                    ( follow.userId=:u AND activity.visibility & :vf ) )

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . $this->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                WHERE  cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND (
                        ( activity.userId=:u AND activity.visibility & :va ) )

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . $this->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
                WHERE  cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st
                AND ( ( action_feed.feedId=:u AND action_feed.feedType="user" AND activity.visibility & :vfeed ) )

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . $this->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                INNER JOIN ' . $activityDao->getTableName() . ' subscribe ON activity.actionId=subscribe.actionId and subscribe.activityType=:as AND subscribe.userId=:u
                WHERE  cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st

                ) b

            GROUP BY b.`id` ORDER BY MAX(b.timeStamp) DESC ' . $limitStr;

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'u' => $userId,
            'va' => NEWSFEED_BOL_Service::VISIBILITY_AUTHOR,
            'vf' => NEWSFEED_BOL_Service::VISIBILITY_FOLLOW,
            'vfeed' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'st' => empty($startTime) ? time() : $startTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
            'as' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_SUBSCRIBE
        ));
    }

    public function findCountByUser( $userId )
    {
        $followDao = NEWSFEED_BOL_FollowDao::getInstance();
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();
        $actionSetDao = NEWSFEED_BOL_ActionSetDao::getInstance();

        $cacheTimeStamp = BOL_PreferenceService::getInstance()->getPreferenceValue(self::CACHE_TIMESTAMP_PREFERENCE, $userId);

        if ( (int) $cacheTimeStamp < time() - self::CACHE_TIMEOUT )
        {
            $startTime = time();
            $actionSetDao->deleteActionSetUserId($userId);
            $actionSetDao->generateActionSet($userId, $startTime);

            BOL_PreferenceService::getInstance()->savePreferenceValue(self::CACHE_TIMESTAMP_PREFERENCE, $startTime, $userId);
        }

        $query = 'SELECT COUNT(DISTINCT `id`) FROM ( SELECT action.`id` FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
            LEFT JOIN ' . $followDao->getTableName() . ' follow ON action_feed.feedId = follow.feedId AND action_feed.feedType = follow.feedType
            WHERE  cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND (
                    ( follow.userId=:u AND activity.visibility & :vf ) )

        UNION

        SELECT action.`id` FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            WHERE  cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND (
                    ( activity.userId=:u AND activity.visibility & :va ) )

        UNION

        SELECT action.`id` FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
            WHERE  cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st
            AND ( ( action_feed.feedId=:u AND action_feed.feedType="user" AND activity.visibility & :vfeed ) )

        UNION

        SELECT action.`id` FROM ' . $this->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            INNER JOIN ' . $activityDao->getTableName() . ' subscribe ON activity.actionId=subscribe.actionId and subscribe.activityType=:as AND subscribe.userId=:u
            WHERE  cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st ) a ';

        return $this->dbo->queryForColumn($query, array(
            'u' => $userId,
            'va' => NEWSFEED_BOL_Service::VISIBILITY_AUTHOR,
            'vf' => NEWSFEED_BOL_Service::VISIBILITY_FOLLOW,
            'vfeed' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'st' => empty($startTime) ? time() : $startTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
            'as' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_SUBSCRIBE
            ));
    }

    public function findSiteFeed( $limit = null, $startTime = null )
    {
        $limitStr = '';
        if ( !empty($limit) )
        {
            $limitStr = "LIMIT " . intval($limit[0]) . ", " . intval($limit[1]);
        }

        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $query = 'SELECT action.* FROM ' . $this->getTableName() . ' action '
            . 'INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId '
            . 'INNER JOIN ' . $activityDao->getTableName() . ' cactivity ON action.id = cactivity.actionId '
            . 'LEFT JOIN ' . $activityDao->getTableName() . ' pactivity ON cactivity.actionId = pactivity.actionId
                AND (pactivity.status=:s AND pactivity.activityType=:ac AND pactivity.privacy!=:peb AND pactivity.visibility & :v) '
            . 'WHERE
                pactivity.id IS NULL
                AND
                (cactivity.status=:s AND cactivity.activityType=:ac AND cactivity.privacy=:peb AND cactivity.visibility & :v)
                AND
                (activity.status=:s AND activity.privacy=:peb AND activity.visibility & :v AND activity.timeStamp < :st)
              GROUP BY action.id
              ORDER BY MAX(activity.timeStamp) DESC ' . $limitStr;

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'v' => NEWSFEED_BOL_Service::VISIBILITY_SITE,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'st' => empty($startTime) ? time() : $startTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
        ));
    }

    public function findSiteFeedCount()
    {
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $query = 'SELECT COUNT(DISTINCT activity.actionId) FROM ' . $activityDao->getTableName() . ' activity
                    LEFT JOIN ' . $activityDao->getTableName() . ' pactivity ON activity.actionId = pactivity.actionId
                        AND (pactivity.status=:s AND pactivity.activityType=:ac AND pactivity.privacy!=:peb AND pactivity.visibility & :v)
                    WHERE  pactivity.id IS NULL AND activity.status=:s AND activity.activityType=:ac AND activity.privacy=:peb AND activity.visibility & :v';

        return $this->dbo->queryForColumn($query, array(
            'v' => NEWSFEED_BOL_Service::VISIBILITY_SITE,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
        ));
    }

    public function findExpired( $inactivePeriod )
    {
        $example = new OW_Example();
        $example->andFieldLessThan('updateTime', time() - (int) $inactivePeriod);

        return $this->findObjectByExample($example);
    }

    public function findListByUserId( $userId )
    {
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $query = "SELECT DISTINCT action.* FROM " . $this->getTableName() . " action
            INNER JOIN " . $activityDao->getTableName() . " activity ON action.id=activity.actionId
            WHERE activity.activityType=:ca AND activity.userId=:u";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'ca' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
            'u' => $userId
        ));
    }

    public function setPrivacyByEntityType( $userId, array $entityTypes, $privacy )
    {
        if ( empty($entityTypes) )
        {
            return;
        }

        $query = "UPDATE " . $this->getTableName() . " SET privacy=:p WHERE userId=:u AND entityType IN (" . $this->dbo->mergeInClause($entityTypes) . ")";

        $this->dbo->query($query, array(
            'u' => $userId,
            'p' => $privacy
        ));
    }
}