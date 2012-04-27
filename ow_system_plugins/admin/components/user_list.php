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
 * User list component class. 
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_system_plugins.admin.components
 * @since 1.0
 */
class ADMIN_CMP_UserList extends OW_Component
{

    /**
     * Constructor.
     * 
     * @param string $type
     * @param array $extra
     */
    public function __construct( $type, $extra = null )
    {
        parent::__construct();

        $language = OW::getLanguage();
        $userService = BOL_UserService::getInstance();

        // handle form
        if ( OW::getRequest()->isPost() && !empty($_POST['users']) && !empty($_POST['command']) )
        {
            $users = $_POST['users'];

            switch ( $_POST['command'] )
            {
                case ( 'suspend' ):

                    foreach ( $users as $id )
                    {
                        $userService->suspend($id);
                    }

                    OW::getFeedback()->info($language->text('admin', 'user_feedback_profiles_suspended'));

                    break;

                case ( 'unsuspend' ):

                    foreach ( $users as $id )
                    {
                        $userService->unsuspend($id);
                    }

                    OW::getFeedback()->info($language->text('admin', 'user_feedback_profiles_unsuspended'));

                    break;

                case ( 'delete' ):

                    $deleted = 0;

                    foreach ( $users as $id )
                    {
                        if ( OW::getUser()->getId() == $id )
                        {
                            continue;
                        }

                        if ( $userService->deleteUser($id, true) )
                        {
                            $deleted++;
                        }
                    }

                    OW::getFeedback()->info($language->text('admin', 'user_delete_msg', array('count' => $deleted)));

                    break;
                    
                case ( 'email_verify' ):

                    $userDtos = $userService->findUserListByIdList($users);
                    
                    foreach ( $userDtos as $dto )
                    {
                        /* @var $dto BOL_User */
                        $dto->emailVerify = 1;
                        $userService->saveOrUpdate($dto);
                    }

                    OW::getFeedback()->info($language->text('admin', 'user_feedback_email_verified'));

                    break;
                    
                case ( 'email_unverify' ):

                    $userDtos = $userService->findUserListByIdList($users);
                    
                    foreach ( $userDtos as $dto )
                    {
                        /* @var $dto BOL_User */
                        $dto->emailVerify = 0;
                        $userService->saveOrUpdate($dto);
                    }

                    OW::getFeedback()->info($language->text('admin', 'user_feedback_email_unverified'));

                    break;
            }

            $this->reloadParentPage();
        }

        $onPage = 20;

        $page = isset($_GET['page']) && (int) $_GET['page'] ? (int) $_GET['page'] : 1;
        $first = ( $page - 1 ) * $onPage;

        switch ( $type )
        {
            case 'recent':
                $userList = $userService->findRecentlyActiveList($first, $onPage, false);
                $userCount = $userService->count(false);
                break;

            case 'suspended':
                $userList = $userService->findSuspendedList($first, $onPage);
                $userCount = $userService->countSuspended();
                break;

            case 'unverified':
                $userList = $userService->findUnverifiedList($first, $onPage);
                $userCount = $userService->countUnverified();
                break;

            case 'unapproved':
                $userList = $userService->findUnapprovedList($first, $onPage);
                $userCount = $userService->countUnapproved();
                break;

            case 'search':
                if ( isset($extra['question']) )
                {
                    $search = htmlspecialchars(urldecode($extra['value']));
                    $this->assign('search', $search);

                    $userList = $userService->findUserListByQuestionValues(array($extra['question'] => $search), $first, $onPage, true);
                    $userCount = $userService->fcountUsersByQuestionValues(array($extra['question'] => $search), true);
                }
                break;

            case 'role':
                $roleId = $extra['roleId'];
                $userList = $userService->findListByRoleId($roleId, $first, $onPage);
                $userCount = $userService->countByRoleId($roleId);
                break;
        }

        if ( $userList )
        {
            $this->assign('users', $userList);
            $this->assign('total', $userCount);

            // Paging
            $pages = (int) ceil($userCount / $onPage);
            $paging = new BASE_CMP_Paging($page, $pages, $onPage);

            $this->addComponent('paging', $paging);

            $userIdList = array();

            foreach ( $userList as $user )
            {
                if ( !in_array($user->id, $userIdList) )
                {
                    array_push($userIdList, $user->id);
                }
            }

            $userNameList = $userService->getUserNamesForList($userIdList);
            $this->assign('userNameList', $userNameList);

            $displayNameList = $userService->getDisplayNamesForList($userIdList);
            $this->assign('displayNames', $displayNameList);

            $avatarUrlList = BOL_AvatarService::getInstance()->getAvatarsUrlList($userIdList);
            $this->assign('avatarUrlList', $avatarUrlList);

            $questionList = BOL_QuestionService::getInstance()->getQuestionData($userIdList, array('sex', 'birthdate', 'email'));
            $this->assign('questionList', $questionList);

            $sexList = array();
            
            foreach( $userIdList as $id )
            {
                if( empty($questionList[$id]['sex']) )
                {
                    
                    continue;
                }

                $sex = $questionList[$id]['sex'];

                if ( !empty($sex) )
                {
                    $sexValue = '';

                    for( $i = 0 ; $i < 31; $i++ )
                    {
                        $val = pow( 2, $i );
                        if ( (int)$sex & $val  )
                        {
                            $sexValue .= BOL_QuestionService::getInstance()->getQuestionValueLang('sex', $val) . ', ';
                        }
                    }

                    if( !empty($sexValue) )
                    {
                        $sexValue = substr($sexValue, 0, -2);
                    }
                }

                $sexList[$id] = $sexValue;
            }
            
            $this->assign('sexList', $sexList);

            $userSuspendedList = $userService->findSupsendStatusForUserList($userIdList);
            $this->assign('suspendedList', $userSuspendedList);

            $userUnverfiedList = $userService->findUnverifiedStatusForUserList($userIdList);
            $this->assign('unverifiedList', $userUnverfiedList);

            $userUnapprovedList = $userService->findUnapprovedStatusForUserList($userIdList);
            $this->assign('unapprovedList', $userUnapprovedList);

            $onlineStatus = $userService->findOnlineStatusForUserList($userIdList);
            $this->assign('onlineStatus', $onlineStatus);
        }
        else
        {
            $this->assign('users', null);
        }

        $language->addKeyForJs('admin', 'confirm_suspend_users');
        $language->addKeyForJs('base', 'delete_user_confirmation_label');

        $this->assign('adminId', OW::getUser()->getId());
    }

    private function reloadParentPage()
    {
        $router = OW::getRouter();

        OW::getApplication()->redirect($router->getBaseUrl() . $router->getUri());
    }
}