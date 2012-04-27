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
 * Data Access Object for `user` table.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
class BOL_UserDao extends OW_BaseDao
{
    const EMAIL = 'email';
    const USERNAME = 'username';
    const PASSWORD = 'password';
    const JOIN_DATETIME = 'joinDatetime';
    const ACTIVITY_DATETIME = 'activityDatetime';

    /**
     * Singleton instance.
     *
     * @var BOL_UserDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return BOL_UserDao
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
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'BOL_User';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'base_user';
    }

    /**
     * Returns user for provided username/email.
     *
     * @param string $var
     * @param string $password
     * @return BOL_User
     */
    public function findUserByUsernameOrEmail( $var )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::USERNAME, trim($var));

        $result = $this->findObjectByExample($example);

        if ( $result !== null )
        {
            return $result;
        }

        $example = new OW_Example();
        $example->andFieldEqual(self::EMAIL, trim($var));

        $result = $this->findObjectByExample($example);

        return $result;
    }

    public function findByUserName( $username )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('username', $username);

        return $this->findObjectByExample($ex);
    }

    public function findByUseEmail( $email )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('email', $email);

        return $this->findObjectByExample($ex);
    }

    public function findList( $first, $count, $admin=false )
    {
        if ( $admin === true )
        {
            $ex = new OW_Example();
            $ex->setOrder('joinStamp DESC')
                ->setLimitClause($first, $count);

            return $this->findListByExample($ex);
        }

        $query = "SELECT `u`.*
    		FROM `{$this->getTableName()}` as `u`
    		LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s`
    			ON( `u`.`id` = `s`.`userId` )

    		LEFT JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . "` as `d`
    			ON( `u`.`id` = `d`.`userId` )

    		WHERE `s`.`id` IS NULL AND `d`.`id` IS NULL
    		ORDER BY `u`.`joinStamp` DESC
    		LIMIT ?,?
    		";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function findRecentlyActiveList( $first, $count, $admin = false )
    {
        $query = "SELECT `u`.*
            FROM `{$this->getTableName()}` as `u`"
            . ( $admin === true ? "" : "

                LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s`
                    ON( `u`.`id` = `s`.`userId` )

                LEFT JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . "` as `d`
                    ON( `u`.`id` = `d`.`userId` )

                WHERE `s`.`id` IS NULL AND `d`.`id` IS NULL" )
            . " ORDER BY `u`.`activityStamp` DESC
            LIMIT ?, ?
            ";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function count( $isAdmin = false )
    {
        if ( $isAdmin == true )
        {
            $query = "SELECT COUNT(*)
	    		FROM `{$this->getTableName()}`";
        }
        else
        {
            $query = "SELECT COUNT(*)
	    		FROM `{$this->getTableName()}` as `u`
	    		LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s`
	    			ON( `u`.`id` = `s`.`userId` )
	    		WHERE `s`.`id` IS NULL";
        }

        return $this->dbo->queryForColumn($query);
    }

    public function findFeaturedList( $first, $count )
    {
        $query = "
			SELECT `u`.* FROM `{$this->getTableName()}` AS `u`

			INNER JOIN `" . BOL_UserFeaturedDao::getInstance()->getTableName() . "` AS `f`
				ON( `u`.`id` = `f`.`userId` )

                        LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s`
                                ON( `u`.`id` = `s`.`userId` )

                        LEFT JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . "` as `d`
                                ON( `u`.`id` = `d`.`userId` )

                        WHERE `s`.`id` IS NULL AND `d`.`id` IS NULL

			LIMIT ?,?
			";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function countFeatured()
    {
        $query = "
			SELECT COUNT(*)
			FROM `{$this->getTableName()}` AS `u`

			INNER JOIN `" . BOL_UserFeaturedDao::getInstance()->getTableName() . "` AS `f`
				ON( `u`.`id` = `f`.`userId` )

    		LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s`
    			ON( `u`.`id` = `s`.`userId` )

    		LEFT JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . "` as `d`
    			ON( `u`.`id` = `d`.`userId` )

    		WHERE `s`.`id` IS NULL AND `d`.`id` IS NULL
			";

        return $this->dbo->queryForColumn($query);
    }

    public function findOnlineList( $first, $count )
    {
        $query = "
            SELECT `u`.*
            FROM `{$this->getTableName()}` AS `u`

            INNER JOIN `" . BOL_UserOnlineDao::getInstance()->getTableName() . "` AS `o`
                    ON(`u`.`id` = `o`.`userId`)

    		LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s`
    			ON( `u`.`id` = `s`.`userId` )

    		LEFT JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . "` as `d`
    			ON( `u`.`id` = `d`.`userId` )

    		WHERE `s`.`id` IS NULL AND `d`.`id` IS NULL

            ORDER BY `o`.`activityStamp` DESC
            LIMIT ?, ?
            ";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function countOnline()
    {
        $query = "
            SELECT  COUNT(*)
            FROM `{$this->getTableName()}` AS `u`

            INNER JOIN `" . BOL_UserOnlineDao::getInstance()->getTableName() . "` AS `o`
                    ON(`u`.`id` = `o`.`userId`)

    		LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s`
    			ON( `u`.`id` = `s`.`userId` )

    		LEFT JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . "` as `d`
    			ON( `u`.`id` = `d`.`userId` )

    		WHERE `s`.`id` IS NULL AND `d`.`id` IS NULL
            ";

        return $this->dbo->queryForColumn($query);
    }

    public function findSuspendedList( $first, $count )
    {
        $query = "SELECT `u`.*
            FROM `{$this->getTableName()}` as `u`
            LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s`
                ON( `u`.`id` = `s`.`userId` )
            WHERE `s`.`id` IS NOT NULL
            ORDER BY `u`.`activityStamp` DESC
            LIMIT ?,?
        ";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function countSuspended()
    {
        $query = "SELECT COUNT(*)
            FROM `{$this->getTableName()}` as `u`
            LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s`
                ON( `u`.`id` = `s`.`userId` )
            WHERE `s`.`id` IS NOT NULL
        ";

        return $this->dbo->queryForColumn($query);
    }

    public function findUnverifiedList( $first, $count )
    {
        $query = "
            SELECT `u`.*
            FROM `{$this->getTableName()}` AS `u`
            WHERE `u`.`emailVerify` = 0
            ORDER BY `u`.`activityStamp` DESC
            LIMIT ?, ?
        ";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function countUnverified()
    {
        $query = "SELECT COUNT(*)
            FROM `{$this->getTableName()}` as `u`
            WHERE `u`.`emailVerify` = 0
        ";

        return $this->dbo->queryForColumn($query);
    }

    public function findUnapprovedList( $first, $count )
    {
        $query = "SELECT `u`.*
            FROM `{$this->getTableName()}` as `u`
            LEFT JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . "` as `d`
                ON( `u`.`id` = `d`.`userId` )
            WHERE `d`.`id` IS NOT NULL
            ORDER BY `u`.`activityStamp` DESC
            LIMIT ?,?
        ";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function countUnapproved()
    {
        $query = "SELECT COUNT(*)
            FROM `{$this->getTableName()}` as `u`
            LEFT JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . "` as `d`
                ON( `u`.`id` = `d`.`userId` )
            WHERE `d`.`id` IS NOT NULL
        ";

        return $this->dbo->queryForColumn($query);
    }

    public function replaceAccountTypeForUsers( $oldType, $newType )
    {
        $sql = "UPDATE `{$this->getTableName()}` SET `accountType`=? WHERE `accountType`=?";
        $this->dbo->update($sql, array($newType, $oldType));
    }

    public function findMassMailingUsers( $start, $count, $ignoreUnsubscribe = false, $userRoles = array() )
    {
        $join = '';
        $where = '';

        if ( OW::getConfig()->getValue('base', 'mandatory_user_approve') == 1 )
        {
            $join .= " LEFT JOIN `" . (BOL_UserApproveDao::getInstance()->getTableName()) . "` AS `disapprov`
                        ON (`u`.`id` = `disapprov`.`userId`) ";
            $where .= " AND  ( `disapprov`.`id` IS NULL ) ";
        }

        if ( OW::getConfig()->getValue('base', 'confirm_email') == 1 )
        {
            $where .= " AND  u.emailVerify = 1 ";
        }

        if ( $ignoreUnsubscribe !== true )
        {            
            $join .= " LEFT JOIN `" . (BOL_PreferenceDataDao::getInstance()->getTableName()) . "` AS `preference`
                    ON (`u`.`id` = `preference`.`userId` AND `preference`.`key` = 'mass_mailing_subscribe') ";
            $where .= " AND  ( `preference`.`value` = 'true' OR `preference`.`id` IS NULL ) ";
        }

        if ( !empty($userRoles) && is_array($userRoles) )
        {
            $join .= " INNER JOIN `" . (BOL_AuthorizationUserRoleDao::getInstance()->getTableName()) . "` AS `userRole`
                    ON (`u`.`id` = `userRole`.`userId`)
                    INNER JOIN `" . (BOL_AuthorizationRoleDao::getInstance()->getTableName()) . "` AS `role`
                        ON (`userRole`.`roleId` = `role`.`id`) ";
            $where .= " AND  ( `role`.`name` IN ( " . OW::getDbo()->mergeInClause($userRoles) . " ) ) ";
        }

        $query = "
            SELECT  DISTINCT `u`.*
            FROM `{$this->getTableName()}` AS `u`
            LEFT JOIN `".(BOL_UserSuspendDao::getInstance()->getTableName())."` AS `suspend` ON ( u.id = `suspend`.userId )
            {$join}
            WHERE 1 {$where}  AND `suspend`.id IS NULL
            LIMIT :start, :count ";
            
        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array('start' => (int) $start, 'count' => (int) $count));
    }

    public function findMassMailingUserCount( $ignoreUnsubscribe = false, $userRoles = array() )
    {
        $join = '';
        $where = '';

        if ( OW::getConfig()->getValue('base', 'mandatory_user_approve') == 1 )
        {
            $join .= " LEFT JOIN `" . (BOL_UserApproveDao::getInstance()->getTableName()) . "` AS `disapprov`
                        ON (`u`.`id` = `disapprov`.`userId`) ";
            $where .= " AND  ( `disapprov`.`id` IS NULL ) ";
        }

        if ( OW::getConfig()->getValue('base', 'confirm_email') == 1 )
        {
            $where .= " AND  u.emailVerify = 1 ";
        }

        if ( $ignoreUnsubscribe !== true )
        {
            $join .= " LEFT JOIN `" . (BOL_PreferenceDataDao::getInstance()->getTableName()) . "` AS `preference`
                    ON (`u`.`id` = `preference`.`userId` AND `preference`.`key` = 'mass_mailing_subscribe') ";
            $where .= " AND  ( `preference`.`value` = 'true' OR `preference`.`id` IS NULL ) ";
        }

        if ( !empty($userRoles) && is_array($userRoles) )
        {
            $join .= " INNER JOIN `" . (BOL_AuthorizationUserRoleDao::getInstance()->getTableName()) . "` AS `userRole`
                    ON (`u`.`id` = `userRole`.`userId`)
                    INNER JOIN `" . (BOL_AuthorizationRoleDao::getInstance()->getTableName()) . "` AS `role` 
                        ON (`userRole`.`roleId` = `role`.`id`) ";
            $where .= " AND  ( `role`.`name` IN ( " . OW::getDbo()->mergeInClause($userRoles) . " ) ) ";
        }

        $query = "
            SELECT  COUNT( DISTINCT `u`.`id`)
            FROM `{$this->getTableName()}` AS `u` 
            LEFT JOIN `".(BOL_UserSuspendDao::getInstance()->getTableName())."` AS `suspend` ON ( u.id = `suspend`.userId )
            {$join}
            WHERE 1 {$where}  AND `suspend`.id IS NULL ";
            
        return $this->dbo->queryForColumn($query);
    }

    public function updateEmail( $userId, $email )
    {
        $userId = (int) $userId;
        $email = trim($email);

        $sql = " UPDATE `{$this->getTableName()}` SET email = ? WHERE id = ? LIMIT 1 ";
        $this->dbo->update($sql, array($email, $userId));
    }

    public function updatePassword( $userId, $password )
    {
        $userId = (int) $userId;

        $sql = " UPDATE `{$this->getTableName()}` SET password = ? WHERE id = ? LIMIT 1 ";
        $this->dbo->update($sql, array($password, $userId));
    }

    public function findListByRoleId( $roleId, $first, $count )
    {
        $query = "SELECT `u`.*
    		 FROM `{$this->getTableName()}` as `u`

    		 INNER JOIN `" . BOL_AuthorizationUserRoleDao::getInstance()->getTableName() . "` as `ur`
    		 	ON( `u`.`id` = `ur`.`userId` )
    		 WHERE `ur`.`roleId` = ?
    		 LIMIT ?, ?";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($roleId, $first, $count));
    }

    public function countByRoleId( $roleId )
    {
        $query = "SELECT COUNT(*)
    		 FROM `{$this->getTableName()}` as `u`

    		 INNER JOIN `" . BOL_AuthorizationUserRoleDao::getInstance()->getTableName() . "` as `ur`
    		 	ON( `u`.`id` = `ur`.`userId` )
    		 WHERE `ur`.`roleId` = ? ";

        return $this->dbo->queryForColumn($query, array($roleId));
    }

    public function findListByEmailList( $emailList )
    {
        $ex = new OW_Example();
        $ex->andFieldInArray('email', $emailList);

        return $this->findListByExample($ex);
    }

    public function findDisapprovedList( $first, $count )
    {
        $q = "SELECT `u`.* FROM `{$this->getTableName()}` as `u`
    		INNER JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . '` as `ud`
    			ON(`u`.`id` = `ud`.`userId`)
    		LIMIT ?, ?
    		';

        return $this->dbo->queryForObjectList($q, $this->getDtoClassName(), array((int)$first, (int)$count));
    }

    public function countDisapproved()
    {
        $q = "SELECT COUNT(*) FROM `{$this->getTableName()}` as `u`
    		INNER JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . '` as `ud`
    			ON(`u`.`id` = `ud`.`userId`)
    		';

        return (int) $this->dbo->queryForColumn($q);
    }

    public function findUnverifyStatusForUserList( $idList )
    {
        $query = "SELECT `id` FROM `" . $this->getTableName() . "`
            WHERE `emailVerify` = 0
            AND `id` IN (" . $this->dbo->mergeInClause($idList) . ")";

        return $this->dbo->queryForColumnList($query);
    }

    public function findUserListByQuestionValues( $questionValues, $first, $count, $isAdmin = false )
    {
        $userIdList = $this->findUserIdListByQuestionValues($questionValues, $first, $count, $isAdmin);

        if ( count($userIdList) === 0 )
        {
            return array();
        }

        $ex = new OW_Example();
        $ex->andFieldInArray('id', $userIdList);

        return $this->findListByExample($ex);
    }

    public function fcountUsersByQuestionValues( $questionValues, $isAdmin = false )
    {
        $questionNameList = array_keys($questionValues);

        $questions = BOL_QuestionService::getInstance()->findQuestionByNameList($questionNameList);
        $questionsList = array();

        $prefix = 'qd';
        $counter = 0;
        $innerJoin = '';
        $where = '';

        foreach ( $questions as $question )
        {
            if ( !empty($questionValues[$question->name]) && $question->name != 'password' )
            {
                if ( $question->base == 1 )
                {
                    $where .= ' AND `user`.`' . $this->dbo->escapeString($question->name) . '` LIKE \'' . $this->dbo->escapeString($questionValues[$question->name]) . '%\'';
                }
                else
                {
                    $questionString = $this->getQuestionWhileString($question, $questionValues[$question->name], $prefix . $counter);
                    if ( !empty($questionString) )
                    {
                        $innerJoin .= " INNER JOIN `" . BOL_QuestionDataDao::getInstance()->getTableName() . "` `" . $prefix . $counter . "`
                            ON ( `user`.`id` = `" . $prefix . $counter . "`.`userId` AND `" . $prefix . $counter . "`.`questionName` = '" . $this->dbo->escapeString($question->name) . "' AND " . $questionString . " ) ";
                        $counter++;
                    }
                }
            }
        }

        if ( !empty($questionValues['accountType']) )
        {
            $where .= " AND `user`.`accountType` = '" . $this->dbo->escapeString($questionValues['accountType']) . "' ";
        }

        $query = "SELECT DISTINCT COUNT(`user`.id) FROM `" . $this->getTableName() . "` `user`
            " . $innerJoin . "
            LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s` ON( `user`.`id` = `s`.`userId` )
            LEFT JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . "` as `d` ON( `user`.`id` = `d`.`userId` )

            WHERE `s`.`id` IS NULL AND `d`.`id` IS NULL " . $where;

        if ( $isAdmin === true )
        {
            $query = "SELECT DISTINCT COUNT(`user`.`id` ) FROM `" . $this->getTableName() . "` `user`
                " . $innerJoin . "
                WHERE 1 " . $where;
        }

        return $this->dbo->queryForColumn($query, $questionsList);
    }

    /**
     * Returns user for provided username/email.
     *
     * @param array $questionValues
     * @param int $first
     * @param int $count
     * @param boolean $isAdmin
     * @param boolean $type
     *
     * @return BOL_User
     */
    public function findUserIdListByQuestionValues( $questionValues, $first, $count, $isAdmin = false )
    {
        $questionNameList = array_keys($questionValues);

        $questions = BOL_QuestionService::getInstance()->findQuestionByNameList($questionNameList);

        $prefix = 'qd';
        $counter = 0;
        $innerJoin = '';
        $where = '';

        foreach ( $questions as $question )
        {
            if ( !empty($questionValues[$question->name]) && $question->name != 'password' )
            {
                if ( $question->base == 1 )
                {
                    $where .= ' AND `user`.`' . $this->dbo->escapeString($question->name) . '` LIKE \'' . $this->dbo->escapeString($questionValues[$question->name]) . '%\'';
                }
                else
                {
                    $questionString = $this->getQuestionWhileString($question, $questionValues[$question->name], $prefix . $counter);
                    if ( !empty($questionString) )
                    {
                        $innerJoin .= " INNER JOIN `" . BOL_QuestionDataDao::getInstance()->getTableName() . "` `" . $prefix . $counter . "`
                            ON ( `user`.`id` = `" . $prefix . $counter . "`.`userId` AND `" . $prefix . $counter . "`.`questionName` = '" . $this->dbo->escapeString($question->name) . "' AND " . $questionString . " ) ";
                        $counter++;
                    }
                }
            }
        }

        if ( !empty($questionValues['accountType']) )
        {
            $where .= " AND `user`.`accountType` = '" . $this->dbo->escapeString($questionValues['accountType']) . "' ";
        }

        $query = "SELECT DISTINCT `user`.id, `user`.`activityStamp` FROM `" . $this->getTableName() . "` `user`
                " . $innerJoin . "
                LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s` ON( `user`.`id` = `s`.`userId` )
                LEFT JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . "` as `d` ON( `user`.`id` = `d`.`userId` )

                WHERE `s`.`id` IS NULL AND `d`.`id` IS NULL " . $where . "
                ORDER BY `user`.`activityStamp` DESC
                LIMIT :first, :count ";

        if ( $isAdmin === true )
        {
            $query = "SELECT DISTINCT `user`.id FROM `" . $this->getTableName() . "` `user`
                " . $innerJoin . "
                WHERE 1 " . $where . "
                ORDER BY `user`.`activityStamp` DESC
                LIMIT :first, :count ";
        }
        
        return $this->dbo->queryForColumnList($query, array_merge(array('first' => $first, 'count' => $count)));
    }

    public function findSearchResultList( $listId, $first, $count )
    {
        $userIdList = BOL_SearchService::getInstance()->getUserIdList($listId, $first, $count);

        if ( empty($userIdList) )
        {
            return array();
        }

        $example = new OW_Example();
        $example->andFieldInArray('id', $userIdList);
        $example->setOrder(" `activityStamp` DESC ");

        return $this->findListByExample($example);
    }

    private function getQuestionWhileString( BOL_Question $question, $value, $prefix = '' )
    {
        $result = '';
        $prefix = $this->dbo->escapeString($prefix);

        switch ( $question->presentation )
        {
            case BOL_QuestionService::QUESTION_PRESENTATION_URL :
            case BOL_QuestionService::QUESTION_PRESENTATION_TEXT :
            case BOL_QuestionService::QUESTION_PRESENTATION_TEXTAREA :
                $result = " LCASE(`" . $prefix . "`.`textValue`) LIKE '" . $this->dbo->escapeString(strtolower($value)) . "%'";
                break;

            case BOL_QuestionService::QUESTION_PRESENTATION_CHECKBOX :
                $result = " `" . $prefix . "`.`intValue` = " . (boolean) $value;
                ;
                break;

            case BOL_QuestionService::QUESTION_PRESENTATION_RADIO :
            case BOL_QuestionService::QUESTION_PRESENTATION_SELECT :

                if ( isset($value) && is_array($value) )
                {
                    $result = ' `' . $this->dbo->escapeString($prefix) . '`.`intValue` IN ( ' . $this->dbo->mergeInClause($value) . ') ';
                }

                break;
            case BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX :
                $result = " `" . $prefix . "`.`intValue` & '" . $this->dbo->escapeString(array_sum($value)) . "'";
                break;

            case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE :
            case BOL_QuestionService::QUESTION_PRESENTATION_AGE :

                if ( isset($value['from']) && isset($value['to']) )
                {
                    $maxDate = ( date('Y') - (int) $value['from'] ) . '-12-31';
                    $minDate = ( date('Y') - (int) $value['to'] ) . '-01-01';

                    $result = " `" . $prefix . "`.`dateValue` BETWEEN  '" . $this->dbo->escapeString($minDate) . "' AND '" . $this->dbo->escapeString($maxDate) . "'";
                }

                break;

            case BOL_QuestionService::QUESTION_PRESENTATION_DATE :

                $dateFrom = UTIL_DateTime::parseDate($value['from']);
                $dateTo = UTIL_DateTime::parseDate($value['to']);

                if ( isset($dateFrom) )
                {
                    if ( UTIL_Validator::isDateValid($dateFrom[UTIL_DateTime::PARSE_DATE_MONTH], $dateFrom[UTIL_DateTime::PARSE_DATE_DAY], $dateFrom[UTIL_DateTime::PARSE_DATE_YEAR]) )
                    {
                        $valueFrom = $dateFrom[UTIL_DateTime::PARSE_DATE_YEAR] . '-' . $dateFrom[UTIL_DateTime::PARSE_DATE_MONTH] . '-' . $dateFrom[UTIL_DateTime::PARSE_DATE_DAY];
                    }
                }

                if ( isset($dateTo) )
                {
                    if ( UTIL_Validator::isDateValid($dateTo[UTIL_DateTime::PARSE_DATE_MONTH], $dateTo[UTIL_DateTime::PARSE_DATE_DAY], $dateTo[UTIL_DateTime::PARSE_DATE_YEAR]) )
                    {
                        $valueTo = $dateTo[UTIL_DateTime::PARSE_DATE_YEAR] . '-' . $dateTo[UTIL_DateTime::PARSE_DATE_MONTH] . '-' . $dateTo[UTIL_DateTime::PARSE_DATE_DAY];
                    }
                }

                if ( isset($valueFrom) && isset($valueTo) )
                {
                    $result = " `" . $prefix . "`.`dateValue` BETWEEN  '" . $valueFrom . "' AND '" . $valueTo . "'";
                }

                break;
        }

        return $result;
    }

    public function findUserIdListByPreferenceValues( $preferenceValues )
    {
        if ( empty($preferenceValues) || !is_array($preferenceValues) )
        {
            return array();
        }

        $sqlList = array();

        foreach ( $preferenceValues as $key => $value )
        {
            $sqlList[$key] = " SELECT d.userId FROM " . (BOL_PreferenceDao::getInstance()->getTableName()) . " p
                LEFT JOIN " . (BOL_PreferenceDataDao::getInstance()->getTableName()) . " d ON ( d.`key` = p.`key` )
                WHERE p.`key` = '" . $this->dbo->escapeString($key) . "' AND ( d.value = '" . $this->dbo->escapeString($value) . "' OR d.value IS NULL AND p.defaultValue = '" . $this->dbo->escapeString($value) . "' ) ";

            if ( !empty($value) && is_array($value) )
            {
                $sqlList[$key] = " SELECT d.userId FROM " . (BOL_PreferenceDao::getInstance()->getTableName()) . " p
                    LEFT JOIN " . (BOL_PreferenceDataDao::getInstance()->getTableName()) . " d ON ( d.`key` = p.`key` )
                    WHERE p.`key` = '" . $this->dbo->escapeString($key) . "' AND ( d.value IN " . $this->dbo->mergeInClause($value) . " OR d.value IS NULL AND p.defaultValue IN " . $this->dbo->mergeInClause($value) . " ) ";
            }
        }
        
        $sqlString = '';

        $queryNumber = 0;

        foreach( $sqlList as $sql )
        {
            if ( $queryNumber > 0 )
            {
                $sqlString .= ' UNION ';
            }

            $queryNumber++;
            $sqlString .= $sql;
        }

        return $this->dbo->queryForColumnList($sqlString);
    }
}