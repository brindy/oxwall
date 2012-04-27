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
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
final class BOL_UserService
{
    const CREATE_USER_INVALID_USERNAME = -1;
    const CREATE_USER_INVALID_EMAIL = -2;
    const CREATE_USER_INVALID_PASSWORD = -3;

    const CREATE_USER_DUPLICATE_USERNAME = -4;
    const CREATE_USER_DUPLICATE_EMAIL = -5;

    const PERMISSIONS_ANYONE_CAN_JOIN = 1;
    const PERMISSIONS_JOIN_BY_INVITATIONS = 2;
    const PERMISSIONS_MEMBERS_CAN_INVITE = 1;
    const PERMISSIONS_ADMIN_CAN_INVITE = 2;
    const PERMISSIONS_GUESTS_CAN_VIEW = 1;
    const PERMISSIONS_GUESTS_CANT_VIEW = 2;
    const PERMISSIONS_GUESTS_PASSWORD_VIEW = 3;

    const CONFIG_JOIN_DISPLAY_PHOTO_UPLOAD = 'display';
    const CONFIG_JOIN_DISPLAY_AND_SET_REQUIRED_PHOTO_UPLOAD = 'display_and_required';
    const CONFIG_JOIN_NOT_DISPLAY_PHOTO_UPLOAD = 'not_display';

    /**
     * @var BOL_UserDao
     */
    private $userDao;
    /**
     * @var BOL_LoginCookieDao
     */
    private $loginCookieDao;
    /**
     *
     * @var BOL_UserFeaturedDao
     */
    private $userFeaturedDao;
    /**
     * @var BOL_UserOnlineDao
     */
    private $userOnlineDao;
    /**
     * @var BOL_UserSuspendDao
     */
    private $userSuspendDao;
    /**
     * @var BOL_UserApproveDao
     */
    private $userApproveDao;
    /**
     * @var BOL_RestrictedUsernamesDao
     */
    private $restrictedUsernamesDao;
    /**
     * @var BOL_InviteCodeDao
     */
    private $inviteCodeDao;
    /**
     * @var BOL_UserApproveDao
     */
    private $approveDao;
    /**
     * @var BOL_UserResetPasswordDao
     */
    private $resetPasswordDao;
    /**
     * @var BOL_UserBlockDao
     */
    private $userBlockDao;
    /**
     * @var BOL_UserService
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return BOL_UserService
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
        $this->userDao = BOL_UserDao::getInstance();
        $this->loginCookieDao = BOL_LoginCookieDao::getInstance();
        $this->userFeaturedDao = BOL_UserFeaturedDao::getInstance();
        $this->userOnlineDao = BOL_UserOnlineDao::getInstance();
        $this->userSuspendDao = BOL_UserSuspendDao::getInstance();
        $this->userApproveDao = BOL_UserApproveDao::getInstance();
        $this->restrictedUsernamesDao = BOL_RestrictedUsernamesDao::getInstance();
        $this->inviteCodeDao = BOL_InviteCodeDao::getInstance();
        $this->approveDao = BOL_UserApproveDao::getInstance();
        $this->resetPasswordDao = BOL_UserResetPasswordDao::getInstance();
        $this->userBlockDao = BOL_UserBlockDao::getInstance();
    }

    /**
     * @param string $var
     * @param string $password
     * @return BOL_User
     */
    public function findUserForStandardAuth( $var )
    {
        return $this->userDao->findUserByUsernameOrEmail($var);
    }

    private $cachedUsers = array();


    /**
     * Finds user by id.
     *
     * @param integer $id
     * @return BOL_User
     */
    public function findUserById( $id )
    {
        $id = (int)$id;

        if( empty($this->cachedUsers[$id]) )
        {
            $this->cachedUsers[$id] = $this->userDao->findById($id);
        }

        return $this->cachedUsers[$id];
    }

    /**
     * Returns display name for provided user id.
     *
     * @param integer $userId
     * @return string
     */
    public function getDisplayName( $userId )
    {
        $questionName = OW::getConfig()->getValue('base', 'display_name_question');

        $questionValue = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array($questionName));

        $displayName = ( isset($questionValue[$userId]) ? ( isset($questionValue[$userId][$questionName]) ? $questionValue[$userId][$questionName] : '' ) : 'Deleted User' );

        return strip_tags($displayName);
    }

    /**
     * Returns display names for provided list of user ids.
     *
     * @param array $userIdList
     * @return array
     */
    public function getDisplayNamesForList( array $userIdList )
    {
        $userIdList = array_unique($userIdList);

        $questionName = OW::getConfig()->getValue('base', 'display_name_question');

        $questionValues = BOL_QuestionService::getInstance()->getQuestionData($userIdList, array($questionName));

        $resultArray = array();

        foreach ( $userIdList as $value )
        {
            $resultArray[$value] = 'Deleted user';

            if ( isset($questionValues[$value]) )
            {
                $resultArray[$value] = isset($questionValues[$value][$questionName]) ? htmlspecialchars($questionValues[$value][$questionName]) : '';
            }
        }

        return $resultArray;
    }

    public function getUserName( $userId )
    {
        $user = $this->findUserById($userId);

        return ( $user === null ? null : $user->getUsername() );
    }

    public function getUserNamesForList( array $userIdList )
    {
        $userIdList = array_unique($userIdList);

        $userList = $this->userDao->findByIdList($userIdList);

        $resultArray = array();

        /* @var $user BOL_User */
        foreach ( $userList as $user )
        {
            $resultArray[$user->getId()] = $user->getUsername();
        }

        $returnArray = array();

        foreach ( $userIdList as $id )
        {
            $returnArray[$id] = isset($resultArray[$id]) ? $resultArray[$id] : null; //todo check and replace with lang value
        }

        return $returnArray;
    }

    public function getUserUrl( $id )
    {
        $user = $this->findUserById($id);

        return $this->getUserUrlForUsername(($user === null ? 'deleted-user' : $user->getUsername()));
    }

    public function getUserUrlForUsername( $username )
    {
        return OW::getRouter()->urlForRoute('base_user_profile', array('username' => $username));
    }

    public function getUserUrlsForList( array $userIdList )
    {
        $userIdList = array_unique($userIdList);

        $userList = $this->userDao->findByIdList($userIdList);

        $resultArray = array();

        /* @var $user BOL_User */
        foreach ( $userList as $user )
        {
            $resultArray[$user->getId()] = $this->getUserUrlForUsername($user->getUsername());
        }

        $returnArray = array();

        foreach ( $userIdList as $id )
        {
            $returnArray[$id] = isset($resultArray[$id]) ? $resultArray[$id] : $this->getUserUrlForUsername('deleted-user');
        }

        return $returnArray;
    }

    public function getUserUrlsListForUsernames( array $usernamesList )
    {
        $usernamesList = array_unique($usernamesList);

        $returnArray = array();

        foreach ( $usernamesList as $key => $value )
        {
            $returnArray[$key] = $this->getUserUrlForUsername($value);
        }

        return $returnArray;
    }

    public function findByUsername( $username )
    {
        return $this->userDao->findByUsername($username);
    }

    public function findRestrictedUsername( $username )
    {
        return $this->restrictedUsernamesDao->findRestrictedUsername($username);
    }

    /**
     *
     * @param string $email
     * @return BOL_User
     */
    public function findByEmail( $email )
    {
        return $this->userDao->findByUseEmail($email);
    }

    /**
     * Creates and saves login cookie.
     *
     * @param integer $userId
     * @return BOL_LoginCookie
     */
    public function saveLoginCookie( $userId )
    {
        $loginCookie = $this->loginCookieDao->findByUserId($userId);

        if ( $loginCookie === null )
        {
            $loginCookie = new BOL_LoginCookie();
        }

        $loginCookie->setUserId($userId);
        $loginCookie->setCookie(hash_hmac('md5', time(), $userId));

        $this->loginCookieDao->save($loginCookie);

        return $loginCookie;
    }

    public function findUserIdByCookie( $cookie )
    {
        $obj = $this->loginCookieDao->findByCookie($cookie);

        return ( $obj === null ? null : $obj->getUserId() );
    }

    /**
     *
     * @param integer $userId
     * @return BOL_LoginCookie
     */
    public function findLoginCookieByUserId( $userId )
    {
        return $this->loginCookieDao->findByUserId($userId);
    }

    public function findList( $first, $count, $isAdmin=false )
    {
        return $this->userDao->findList($first, $count, $isAdmin);
    }

    public function findRecentlyActiveList( $first, $count, $isAdmin = false )
    {
        return $this->userDao->findRecentlyActiveList($first, $count, $isAdmin);
    }

    public function findOnlineList( $first, $count )
    {
        $onlineList = $this->userDao->findOnlineList($first, $count);
        $list = array();
        foreach ( $onlineList as $id => $user )
        {
            // Check privacy permissions
            $eventParams = array(
                'action' => 'base_view_my_presence_on_site',
                'ownerId' => $user->getId(),
                'viewerId' => OW::getUser()->getId()
            );
            $show = true;
            try
            {
                OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
            }
            catch ( RedirectException $e )
            {
                $show = false;
                continue;
            }

            if ($show)
            {
                $list[] = $user;
            }
        }
        return $list;
    }

    public function findOnlineUserById( $userId )
    {
        // Check privacy permissions
        $eventParams = array(
            'action' => 'base_view_my_presence_on_site',
            'ownerId' => $userId,
            'viewerId' => OW::getUser()->getId()
        );
        try
        {
            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        catch ( RedirectException $e )
        {
            return null;
        }
        return $this->userOnlineDao->findByUserId($userId);
    }

    public function countOnline()
    {
        return $this->userDao->countOnline();
    }

    public function count( $isAdmin=false )
    {
        return $this->userDao->count($isAdmin);
    }

    public function findSuspendedList( $first, $count )
    {
        return $this->userDao->findSuspendedList($first, $count);
    }

    public function countSuspended()
    {
        return $this->userDao->countSuspended();
    }

    public function findUnverifiedList( $first, $count )
    {
        return $this->userDao->findUnverifiedList($first, $count);
    }

    public function countUnverified()
    {
        return $this->userDao->countUnverified();
    }

    public function findUnapprovedList( $first, $count )
    {
        return $this->userDao->findUnapprovedList($first, $count);
    }

    public function countUnapproved()
    {
        return $this->userDao->countUnapproved();
    }

    public function saveOrUpdate( BOL_User $user )
    {
        $this->userDao->save($user);

        if( !empty($this->cachedUsers[$user->getId()]) )
        {
            unset($this->cachedUsers[$user->getId()]);
        }
    }

    public function isExistUserName( $value )
    {
        if ( $value === null )
        {
            return false;
        }

        $user = $this->findByUsername(trim($value));

        if ( isset($user) )
        {
            return true;
        }

        return false;
    }

    public function isRestrictedUsername( $value )
    {
        if ( $value === null )
        {
            return false;
        }

        $user = $this->findRestrictedUsername(trim($value));

        if ( isset($user) )
        {
            return true;
        }

        return false;
    }

    public function isExistEmail( $value )
    {
        if ( $value === null )
        {
            return false;
        }

        $email = $this->findByEmail(trim($value));

        if ( isset($email) )
        {
            return true;
        }

        return false;
    }

    public function isValidPassword( $userId, $value )
    {
        $user = $this->findUserById($userId);

        if ( $value === null || $user === null )
        {
            return false;
        }

        $password = $this->hashPassword($value);

        if ( $user->password === $password )
        {
            return true;
        }

        return false;
    }

    public function markAsFeatured( $userId )
    {
        $dto = new BOL_UserFeatured();
        $dto->setUserId($userId);

        return $this->userFeaturedDao->save($dto);
    }

    public function cancelFeatured( $userId )
    {
        $this->userFeaturedDao->deleteByUserId($userId);
    }

    public function isUserFeatured( $id )
    {
        $dto = $this->userFeaturedDao->findByUserId($id);

        return!empty($dto);
    }

    public function isBlocked( $id, $byUserId = null )
    {
        if ($byUserId === null)
        {
            $byUserId = OW::getUser()->getId();
        }
        $dto = $this->userBlockDao->findBlockedUser( $byUserId, $id);

        return !empty($dto);
    }

    public function findFeaturedList( $first, $count )
    {
        return $this->userDao->findFeaturedList($first, $count);
    }

    public function countFeatured()
    {
        return $this->userDao->countFeatured();
    }

    public function onLogin( $userId )
    {
        $this->updateActivityStamp($userId);
    }

    public function onLogout( $userId )
    {
        if ( (int) $userId < 1 )
        {
            return;
        }

        $user = $this->userDao->findById($userId);
        $userOnline = $this->userOnlineDao->findByUserId($userId);

        if ( $user === null || $userOnline === null )
        {
            return;
        }

        $user->setActivityStamp($userOnline->getActivityStamp());

        $this->userDao->save($user);

        $this->userOnlineDao->deleteById($userOnline->getId());
    }

    public function updateActivityStamp( $userId )
    {
        if ( !$userId )
        {
            return;
        }

        $user = $this->userDao->findById((int)$userId);

        if ( $user === null )
        {
            return;
        }

        $activityStamp = time();
        $userOnline = $this->userOnlineDao->findByUserId($userId);

        if ( $userOnline === null )
        {
            $userOnline = new BOL_UserOnline();
            $userOnline->setUserId($userId);
        }

        $userOnline->setActivityStamp($activityStamp);
        $this->userOnlineDao->save($userOnline);

        /* @var $user BOL_User */
        $user->setActivityStamp($activityStamp);
        $this->userDao->save($user);
    }

    public function findUserListByIdList( array $idList )
    {
        $idList = array_unique($idList);

        return $this->userDao->findByIdList($idList);
    }

    public function findOnlineStatusForUserList( $idList )
    {
        $onlineUsers = $this->userOnlineDao->findOnlineUserIdListFromIdList($idList);

        $resultArray = array();

        foreach ( $idList as $userId )
        {
            $resultArray[$userId] = in_array($userId, $onlineUsers) ? true : false;
        }

        return $resultArray;
    }

    public function deleteUser( $userId, $deleteContent = false )
    {
        $event = new OW_Event(OW_EventManager::ON_USER_UNREGISTER, array('userId' => $userId, 'deleteContent' => $deleteContent));
        OW::getEventManager()->trigger($event);

        BOL_QuestionService::getInstance()->deleteQuestionDataByUserId((int) $userId);
        BOL_AvatarService::getInstance()->deleteUserAvatar($userId);
        $this->userDao->deleteById($userId);

        return true;
    }

    public function addRestrictedUsername( $username )
    {
        $this->restrictedUsernamesDao->addRestrictedUsername($username);
    }

    public function getRestrictedUsername( $username )
    {
        return $this->restrictedUsernamesDao->getRestrictedUsername($username);
    }

    public function getRestrictedUsernameList()
    {
        return $this->restrictedUsernamesDao->getRestrictedUsernameList();
    }

    public function replaceAccountTypeForUsers( $oldType, $newType )
    {
        $this->userDao->replaceAccountTypeForUsers($oldType, $newType);
    }

    public function findMassMailingUsers( $start, $count, $ignoreUnsubscribe = false, $roles = array() )
    {
        return $this->userDao->findMassMailingUsers($start, $count, $ignoreUnsubscribe, $roles);
    }

    public function findMassMailingUserCount( $ignoreUnsubscribe = false, $roles = array() )
    {
        return $this->userDao->findMassMailingUserCount($ignoreUnsubscribe, $roles);
    }

    public function updateEmail( $userId, $email )
    {
        if ( UTIL_Validator::isEmailValid($email) )
        {
            $this->userDao->updateEmail((int) $userId, $email);
        }
        else
        {
            throw new InvalidArgumentException('Invalid email!');
        }
    }

    public function updatePassword( $userId, $password )
    {
        if ( !empty($password) )
        {
            $this->userDao->updatePassword((int) $userId, $this->hashPassword($password));
        }
        else
        {
            throw new InvalidArgumentException('Invalid password!');
        }
    }

    public function suspend( $userId )
    {
        if ( $this->isSuspended($userId) )
        {
            return;
        }

        $dto = new BOL_UserSuspend();

        $dto->setUserId($userId)
            ->setTimestamp(time());

        $this->userSuspendDao->save($dto);

        $event = new OW_Event(OW_EventManager::ON_USER_SUSPEND, array('userId' => $userId));
        OW::getEventManager()->trigger($event);
    }

    public function unsuspend( $userId )
    {
        if ( !$this->isSuspended($userId) )
        {
            return;
        }

        $dto = $this->userSuspendDao->findByUserId($userId);

        $this->userSuspendDao->delete($dto);
    }

     public function block( $userId )
    {
        if ( $this->isBlocked($userId) )
        {
            return;
        }

        $dto = new BOL_UserBlock();

        $dto->setUserId(OW::getUser()->getId());
        $dto->setBlockedUserId($userId);

        $this->userBlockDao->save($dto);

        $event = new OW_Event(OW_EventManager::ON_USER_BLOCK, array('userId' => OW::getUser()->getId(), 'blockedUserId'=>$userId));
        OW::getEventManager()->trigger($event);
    }

    public function unblock( $userId )
    {
        if ( !$this->isBlocked($userId) )
        {
            return;
        }

        $dto = $this->userBlockDao->findBlockedUser(OW::getUser()->getId(), $userId);

        $this->userBlockDao->delete($dto);

        $event = new OW_Event(OW_EventManager::ON_USER_UNBLOCK, array('userId' => OW::getUser()->getId(), 'blockedUserId'=>$userId));
        OW::getEventManager()->trigger($event);
    }

    public function isSuspended( $userId )
    {
        return $this->userSuspendDao->findByUserId($userId) !== null;
    }

    public function hashPassword( $password )
    {
        return hash('sha256', OW_PASSWORD_SALT . $password);
    }

    public function findListByRoleId( $roleId, $first, $count )
    {
        return $this->userDao->findListByRoleId($roleId, $first, $count);
    }

    public function countByRoleId( $roleId )
    {
        return $this->userDao->countByRoleId($roleId);
    }

    public function deleteExpiredOnlineUsers()
    {
        $timestamp = time() - 30 * 60;

        $this->userOnlineDao->deleteExpired($timestamp);
    }

    public function findListByEmailList( $emailList )
    {
        return $this->userDao->findListByEmailList($emailList);
    }

    public function createUser( $username, $password, $email, $accountType = null, $emailVerify = false )
    {
        if ( !UTIL_Validator::isEmailValid($email) )
        {
            throw new InvalidArgumentException('Invalid email!', self::CREATE_USER_INVALID_EMAIL);
        }

        if ( !UTIL_Validator::isUserNameValid($username) )
        {
            throw new InvalidArgumentException('Invalid username!', self::CREATE_USER_INVALID_USERNAME);
        }

        if ( !isset($password) || strlen($password) === 0 )
        {
            throw new InvalidArgumentException('Invalid password!', self::CREATE_USER_INVALID_PASSWORD);
        }

        if ( $this->isExistUserName($username) )
        {
            throw new LogicException('Duplicate username!', self::CREATE_USER_DUPLICATE_USERNAME);
        }

        if ( $this->isExistEmail($email) )
        {
            throw new LogicException('Duplicate email!', self::CREATE_USER_DUPLICATE_EMAIL);
        }

        $userAccountType = $accountType;

        if ( $userAccountType === null )
        {
            $accountTypes = BOL_QuestionService::getInstance()->findAllAccountTypes();
            $userAccountType = $accountTypes[0]->name;
        }

        $user = new BOL_User();

        $user->username = trim($username);
        $user->password = BOL_UserService::getInstance()->hashPassword($password);
        $user->email = trim($email);
        $user->joinStamp = time();
        $user->activityStamp = time();
        $user->accountType = $userAccountType;

        if ( $emailVerify === true )
        {
            $user->emailVerify = true;
        }

        $this->saveOrUpdate($user);

        BOL_AuthorizationService::getInstance()->assignDefaultRoleToUser($user->id);

        return $user;
    }

    /**
     *
     * @param string $code
     * @return BOL_InviteCode
     */
    public function findInvitationInfo( $code )
    {
        return $this->inviteCodeDao->findByCode($code);
    }

    public function sendAdminInvitation( $email )
    {
        $inviteCodeDto = new BOL_InviteCode();
        $inviteCodeDto->setCode(UTIL_String::generatePassword(20));
        $inviteCodeDto->setUserId(0);
        $inviteCodeDto->setExpiration_stamp(time() + 3600 * 24 * 30);
        $this->inviteCodeDao->save($inviteCodeDto);

        $inviteUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('base_join'), array('code' => $inviteCodeDto->getCode()));

        $mail = OW::getMailer()->createMail();
        $mail->setSender(OW::getConfig()->getValue('base', 'site_email'));
        $mail->addRecipientEmail($email);
        $mail->setSubject(OW::getLanguage()->text('base', 'mail_template_admin_invite_user_subject'));
        $mail->setHtmlContent(OW::getLanguage()->text('base', 'mail_template_admin_invite_user_content_html', array('url' => $inviteUrl)));
        $mail->setTextContent(OW::getLanguage()->text('base', 'mail_template_admin_invite_user_content_text', array('url' => $inviteUrl)));

        OW::getMailer()->addToQueue($mail);
    }

    public function saveUserInvitation( $userId, $code )
    {
        $dto = new BOL_InviteCode();
        $dto->setCode($code);
        $dto->setUserId($userId);
        $dto->setExpiration_stamp(time() + 3600 * 24 * 30);
        $this->inviteCodeDao->save($dto);
    }

    /**
     *
     * @param int $userId
     */
    public function disapprove( $userId )
    {
        if ( empty($userId) )
        {
            throw new InvalidArgumentException('invalid $userId param');
        }

        $dto = $this->approveDao->findByUserId($userId);
        if ( !empty($dto) )
        {
            return;
        }

        $dto = new BOL_UserDisapprove();
        $dto->setUserId($userId);

        $this->approveDao->save($dto);
    }

    /**
     *
     * @param int $userId
     */
    public function approve( $userId )
    {
        if ( empty($userId) )
        {
            throw new InvalidArgumentException('invalid $userId param');
        }

        $dto = $this->approveDao->findByUserId($userId);

        if ( empty($dto) )
        {
            throw new Exception('User already approved');
        }

        $this->approveDao->delete($dto);
    }

    public function isApproved( $userId=null )
    {
        if ( $userId == null )
        {
            $userId = OW::getUser()->getId();
        }

        return null === $this->approveDao->findByUserId($userId);
    }

    public function findDisapprovedList( $first, $count )
    {
        return $this->userDao->findDisapprovedList($first, $count);
    }

    public function countDisapproved()
    {
        return $this->userDao->countDisapproved();
    }

    public function deleteDisaproveByUserId( $userId )
    {
        $this->approve($userId);
    }

    public function findSupsendStatusForUserList( $idList )
    {
        $onlineUsers = $this->userSuspendDao->findSupsendStatusForUserList($idList);

        $resultArray = array();

        foreach ( $idList as $userId )
        {
            $resultArray[$userId] = in_array($userId, $onlineUsers) ? true : false;
        }

        return $resultArray;
    }

    /**
     *
     * @param int $userId
     * return array<BOL_User>
     *
     */
    public function findUserListByQuestionValues( $questionValues, $first, $count, $isAdmin = false )
    {
        return $this->userDao->findUserListByQuestionValues($questionValues, $first, $count, $isAdmin);
    }

    public function fcountUsersByQuestionValues( $questionValues, $isAdmin = false )
    {
        return $this->userDao->fcountUsersByQuestionValues($questionValues, $isAdmin);
    }

    public function findUnverifiedStatusForUserList( $idList )
    {
        $unverifiedUsers = $this->userDao->findUnverifyStatusForUserList($idList);

        $resultArray = array();

        foreach ( $idList as $userId )
        {
            $resultArray[$userId] = in_array($userId, $unverifiedUsers) ? true : false;
        }

        return $resultArray;
    }

    public function findUserIdListByQuestionValues( $questionValues, $first, $count, $isAdmin = false )
    {
        $first = (int) $first;
        $count = (int) $count;
        return $this->userDao->findUserIdListByQuestionValues($questionValues, $first, $count, $isAdmin = false);
    }

    public function findSearchResultList( $listId, $first, $count )
    {
        return $this->userDao->findSearchResultList($listId, $first, $count);
    }

    public function findUnapprovedStatusForUserList( $idList )
    {
        $unapprovedUsers = $this->userApproveDao->findUnapproveStatusForUserList($idList);
        $resultArray = array();

        foreach ( $idList as $userId )
        {
            $resultArray[$userId] = in_array($userId, $unapprovedUsers) ? true : false;
        }

        return $resultArray;
    }

    /**
     * @deprecated
     */
    public function findListByBirthdayPeriod( $start, $end, $first, $count )
    {
        return array();
    }

    /**
     * @deprecated
     */
    public function countByBirthdayPeriod( $start, $end )
    {
        return 0;
    }

    /**
     * @deprecated
     */
    public function findListByBirthdayPeriodAndUserIdList( $start, $end, $first, $count, $idList )
    {
        return array();
    }

    /**
     * @deprecated
     */
    public function countByBirthdayPeriodAndUserIdList( $start, $end, $idList )
    {
        return 0;
    }

    /**
     * @param integer $userId
     * @return BOL_UserResetPassword
     */
    public function findResetPasswordByUserId( $userId )
    {
        return $this->resetPasswordDao->findByUserId($userId);
    }

    /**
     * @param integer $userId
     * @return BOL_UserResetPassword
     */
    public function getNewResetPassword( $userId )
    {
        $resetPassword = new BOL_UserResetPassword();
        $resetPassword->setUserId($userId);
        $resetPassword->setExpirationTimeStamp(( time() + 10 * 60));
        $resetPassword->setCode(md5(UTIL_String::generatePassword(8, 5)));

        $this->resetPasswordDao->save($resetPassword);

        return $resetPassword;
    }

    /**
     * @param string $code
     * @return BOL_UserResetPassword
     */
    public function findResetPasswordByCode( $code )
    {
        return $this->resetPasswordDao->findByCode($code);
    }

    public function deleteExpiredResetPasswordCodes()
    {
        $this->resetPasswordDao->deleteExpiredEntities();
    }

    public function deleteResetCode( $resetCodeId )
    {
        $this->resetPasswordDao->deleteById($resetCodeId);
    }

    public function sendWellcomeLetter( BOL_User $user )
    {
        if ( $user === null )
        {
            return;
        }

        if( OW::getConfig()->getValue('base', 'confirm_email') && $user->emailVerify != true )
        {
            return;
        }

        $vars = array(
            'username' => BOL_UserService::getInstance()->getDisplayName($user->id),
        );

        $language = OW::getLanguage();

        $subject = !($language->text('base', 'welcome_letter_subject', $vars)) ? 'base+welcome_letter_subject' : $language->text('base', 'welcome_letter_subject', $vars);
        $template_html = !($language->text('base', 'welcome_letter_template_html', $vars)) ? 'base+welcome_letter_template_html' : $language->text('base', 'welcome_letter_template_html', $vars);
        $template_text = !($language->text('base', 'welcome_letter_template_text', $vars)) ? 'base+welcome_letter_template_text' : $language->text('base', 'welcome_letter_template_text', $vars);

        $mail = OW::getMailer()->createMail();
        $mail->addRecipientEmail($user->email);
        $mail->setSubject($subject);
        $mail->setHtmlContent($template_html);
        $mail->setTextContent($template_text);

        OW::getMailer()->send($mail);

        BOL_PreferenceService::getInstance()->savePreferenceValue('send_wellcome_letter', 0, $user->id);
    }

    public function cronSendWellcomeLetter()
    {
        $preferenceValues = array( 'send_wellcome_letter' => 1 );

        $userIdList = $this->userDao->findUserIdListByPreferenceValues($preferenceValues);

        if ( empty($userIdList) )
        {
            return;
        }

        $users = $this->findUserListByIdList($userIdList);

        foreach($users as $user)
        {
            $this->sendWellcomeLetter($user);
        }
    }
}