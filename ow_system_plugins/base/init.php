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
$owBasePlugin = OW::getPluginManager()->getPlugin('base');
OW::getThemeManager()->addDecorator('form_base', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('main_menu', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('box_toolbar', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('avatar_item', $owBasePlugin->getKey());

$classesToAutoload = array(
    'BASE_Members' => $owBasePlugin->getCtrlDir() . 'user_list.php',
    'BASE_MenuItem' => $owBasePlugin->getCmpDir() . 'menu.php',
    'BASE_CommentsParams' => $owBasePlugin->getCmpDir() . 'comments.php',
    'BASE_EventCollector' => $owBasePlugin->getClassesDir() . 'deprecated_event_collector.php' //TODO Should be deleted in future
);

OW::getAutoloader()->addClassArray($classesToAutoload);

$router = OW::getRouter();

$router->addRoute(new OW_Route('static_sign_in', 'sign-in', 'BASE_CTRL_User', 'standardSignIn'));
$router->addRoute(new OW_Route('base_forgot_password', 'forgot-password', 'BASE_CTRL_User', 'forgotPassword'));
$router->addRoute(new OW_Route('base_sign_out', 'sign-out', 'BASE_CTRL_User', 'signOut'));
$router->addRoute(new OW_Route('ajax-form', 'ajax-form', 'BASE_CTRL_AjaxForm', 'index'));

$router->addRoute(new OW_Route('users', 'users', 'BASE_CTRL_UserList', 'index', array('list' => array(OW_Route::PARAM_OPTION_HIDDEN_VAR => 'latest'))));
$router->addRoute(new OW_Route('base_user_lists', 'users/:list', 'BASE_CTRL_UserList', 'index'));

$router->addRoute(new OW_Route('users-waiting-for-approval', 'users/waiting-for-approval', 'BASE_CTRL_UserList', 'forApproval'));

$router->addRoute(new OW_Route('users-search', 'users/search', 'BASE_CTRL_UserSearch', 'index'));
$router->addRoute(new OW_Route('users-search-result', 'users/search-result', 'BASE_CTRL_UserSearch', 'result'));

$router->addRoute(new OW_Route('base_join', 'join', 'BASE_CTRL_Join', 'index'));
$router->addRoute(new OW_Route('base_edit', 'profile/edit', 'BASE_CTRL_Edit', 'index'));

$router->addRoute(new OW_Route('base_email_verify', 'email-verify', 'BASE_CTRL_EmailVerify', 'index'));
$router->addRoute(new OW_Route('base_email_verify_code_form', 'email-verify-form', 'BASE_CTRL_EmailVerify', 'verifyForm'));
$router->addRoute(new OW_Route('base_email_verify_code_check', 'email-verify-check/:code', 'BASE_CTRL_EmailVerify', 'verify'));

$router->addRoute(new OW_Route('base_massmailing_unsubscribe', 'unsubscribe/:id/:code', 'BASE_CTRL_Unsubscribe', 'index'));

// Drag And Drop panels
$router->addRoute(new OW_Route('base_member_dashboard', 'dashboard', 'BASE_CTRL_ComponentPanel', 'dashboard'));
$router->addRoute(new OW_Route('base_member_dashboard_customize', 'dashboard/:mode', 'BASE_CTRL_ComponentPanel', 'dashboard'));

$router->addRoute(new OW_Route('base_member_profile', 'my-profile', 'BASE_CTRL_ComponentPanel', 'myProfile'));
$router->addRoute(new OW_Route('base_member_profile_customize', 'my-profile/:mode', 'BASE_CTRL_ComponentPanel', 'myProfile'));

$router->addRoute(new OW_Route('base_user_profile', 'user/:username', 'BASE_CTRL_ComponentPanel', 'profile'));
$router->addRoute(new OW_Route('base_page_404', '404', 'BASE_CTRL_BaseDocument', 'page404'));
$router->addRoute(new OW_Route('base_page_403', '403', 'BASE_CTRL_BaseDocument', 'page403'));
$router->addRoute(new OW_Route('base_page_splash_screen', 'splash-screen', 'BASE_CTRL_BaseDocument', 'splashScreen'));
$router->addRoute(new OW_Route('base_page_alert', 'alert-page', 'BASE_CTRL_BaseDocument', 'alertPage'));
$router->addRoute(new OW_Route('base_page_confirm', 'confirm-page', 'BASE_CTRL_BaseDocument', 'confirmPage'));
$router->addRoute(new OW_Route('base_page_install_completed', 'install/completed', 'BASE_CTRL_BaseDocument', 'installCompleted'));

$router->addRoute(new OW_Route('base_index_customize', 'index/:mode', 'BASE_CTRL_ComponentPanel', 'index'));
$router->addRoute(new OW_Route('base_index', 'index', 'BASE_CTRL_ComponentPanel', 'index'));
$router->addRoute(new OW_Route('base_avatar_crop', 'profile/avatar', 'BASE_CTRL_Avatar', 'crop'));

$router->addRoute(new OW_Route('base_delete_user', 'profile/delete', 'BASE_CTRL_DeleteUser', 'index'));
$router->addRoute(new OW_Route('base.reset_user_password', 'reset-password/:code', 'BASE_CTRL_User', 'resetPassword'));
$router->addRoute(new OW_Route('base.reset_user_password_request', 'reset-password-request', 'BASE_CTRL_User', 'resetPasswordRequest'));
$router->addRoute(new OW_Route('base.reset_user_password_expired_code', 'reset-password-code-expired', 'BASE_CTRL_User', 'resetPasswordCodeExpired'));

$router->addRoute(new OW_Route('base_billing_completed', 'order/:hash/completed', 'BASE_CTRL_Billing', 'completed'));
$router->addRoute(new OW_Route('base_billing_completed_st', 'order/completed', 'BASE_CTRL_Billing', 'completed'));
$router->addRoute(new OW_Route('base_billing_canceled', 'order/:hash/canceled', 'BASE_CTRL_Billing', 'canceled'));
$router->addRoute(new OW_Route('base_billing_canceled_st', 'order/canceled', 'BASE_CTRL_Billing', 'canceled'));
$router->addRoute(new OW_Route('base_billing_error', 'order/incomplete', 'BASE_CTRL_Billing', 'error'));

$router->addRoute(new OW_Route('base_preference_index', 'profile/preference', 'BASE_CTRL_Preference', 'index'));

$router->addRoute(new OW_Route('base_user_privacy_no_permission', 'profile/:username/no-permission', 'BASE_CTRL_ComponentPanel', 'privacyMyProfileNoPermission'));

$router->addRoute(new OW_Route('base-api-server', 'api-server', 'BASE_CTRL_ApiServer', 'request'));
$router->addRoute(new OW_Route('base.robots_txt', 'robots.txt', 'BASE_CTRL_Base', 'robotsTxt'));

OW::getThemeManager()->addDecorator('box_cap', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('box', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('ipc', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('mini_ipc', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('tooltip', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('paging', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('floatbox', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('button', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('user_list_item', $owBasePlugin->getKey());
OW::getThemeManager()->addDecorator('button_list_item', $owBasePlugin->getKey());

OW_ViewRenderer::getInstance()->registerFunction('display_rate', array('BASE_CTRL_Rate', 'displayRate'));

function base_add_global_langs( BASE_CLASS_EventCollector $event )
{
    $event->add(array('site_name' => OW::getConfig()->getValue('base', 'site_name')));
    $event->add(array('site_url' => OW_URL_HOME));
    $event->add(array('site_email' => OW::getConfig()->getValue('base', 'site_email')));
    $event->add(array('default_currency' => BOL_BillingService::getInstance()->getActiveCurrency()));
}
OW::getEventManager()->bind('base.add_global_lang_keys', 'base_add_global_langs');

if ( OW::getUser()->isAuthenticated() )
{
    $user = BOL_UserService::getInstance()->findUserById(OW::getUser()->getId());

    if ( OW::getUser()->isAuthenticated() && !BOL_UserService::getInstance()->isApproved() )
    {
        OW::getRequestHandler()->setCatchAllRequestsAttributes('base.wait_for_approval', array('controller' => 'BASE_CTRL_WaitForApproval', 'action' => 'index'));
        OW::getRequestHandler()->addCatchAllRequestsExclude('base.wait_for_approval', 'BASE_CTRL_User', 'signOut');
    }

    if ( $user !== null )
    {
        if ( BOL_UserService::getInstance()->isSuspended($user->getId()) && !OW::getUser()->isAdmin() )
        {
            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.suspended_user', array('controller' => 'BASE_CTRL_SuspendedUser', 'action' => 'index'));
            OW::getRequestHandler()->addCatchAllRequestsExclude('base.suspended_user', 'BASE_CTRL_User', 'signOut');
            OW::getRequestHandler()->addCatchAllRequestsExclude('base.suspended_user', 'BASE_CTRL_Avatar');
            OW::getRequestHandler()->addCatchAllRequestsExclude('base.suspended_user', 'BASE_CTRL_Edit');
        }

        if ( (int) $user->emailVerify === 0 && OW::getConfig()->getValue('base', 'confirm_email') )
        {
            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.email_verify', array(OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'BASE_CTRL_EmailVerify', OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'index'));

            OW::getRequestHandler()->addCatchAllRequestsExclude('base.email_verify', 'BASE_CTRL_User', 'signOut');
            OW::getRequestHandler()->addCatchAllRequestsExclude('base.email_verify', 'BASE_CTRL_EmailVerify');
        }
    }
    else
    {
        OW::getUser()->logout();
    }
}

// maybe need to devide entities
function base_delete_user_content( OW_Event $event )
{
    $params = $event->getParams();

    $userId = (int) $params['userId'];

    if ( $userId > 0 )
    {
        $moderatorId = BOL_AuthorizationService::getInstance()->getModeratorIdByUserId($userId);
        if ( $moderatorId !== null )
        {
            BOL_AuthorizationService::getInstance()->deleteModerator($moderatorId);
        }

        BOL_AuthorizationService::getInstance()->deleteUserRolesByUserId($userId);

        if ( isset($params['deleteContent']) && (bool) $params['deleteContent'] )
        {
            BOL_CommentService::getInstance()->deleteUserComments($userId);
            BOL_RateService::getInstance()->deleteUserRates($userId);
            BOL_VoteService::getInstance()->deleteUserVotes($userId);
        }
    }
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'base_delete_user_content');

function base_delete_widgets( OW_Event $event )
{
    $params = $event->getParams();

    $userId = (int) $params['userId'];
    BOL_ComponentEntityService::getInstance()->onEntityDelete(BOL_ComponentEntityService::PLACE_DASHBOARD, $userId);
    BOL_ComponentEntityService::getInstance()->onEntityDelete(BOL_ComponentEntityService::PLACE_PROFILE, $userId);
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'base_delete_widgets');

function base_delete_verify_email_code( OW_Event $event )
{
    $params = $event->getParams();

    $userId = (int) $params['userId'];
    BOL_EmailVerifyService::getInstance()->deleteByUserId($userId);
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'base_delete_verify_email_code');

function base_delete_remote_auth( OW_Event $event )
{
    $params = $event->getParams();

    $userId = (int) $params['userId'];
    BOL_RemoteAuthService::getInstance()->deleteByUserId($userId);
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'base_delete_remote_auth');

function base_delete_user_activity( OW_Event $event )
{
    if ( OW::getPluginManager()->isPluginActive('activity') )
    {
        $params = $event->getParams();

        $userId = (int) $params['userId'];

        ACTIVITY_BOL_Service::getInstance()->deleteAction($userId);
    }
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'base_delete_user_activity');

function base_join_user_activity( OW_Event $event )
{
    $params = $event->getParams();

    $userId = (int) $params['userId'];

    if ( $params['method'] === 'native' )
    {
        // add user activity
        if ( OW::getPluginManager()->isPluginActive('activity') )
        {
            $userUrl = BOL_UserService::getInstance()->getUserUrl($userId);
            $displayName = BOL_UserService::getInstance()->getDisplayName($userId);
            $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($userId);

            $avatar = '';
            if ( $avatarUrl )
            {
                $avatar = OW::getLanguage()->text('base', 'join_activity_user_avatar', array(
                    'avatarUrl' => BOL_AvatarService::getInstance()->getAvatarUrl($userId),
                    'userUrl' => $userUrl,
                    'user' => $displayName
                    )
                );
            }

            $data = array(
                'string' => OW::getLanguage()->text('base', 'join_activity_string', array(
                    'user' => $displayName,
                    'userUrl' => $userUrl
                    )
                ),
                'content' => $avatar
            );

            $action = new ACTIVITY_BOL_Action();

            $action->setUserId($userId)
                ->setTimestamp(time())
                ->setType('user-join')
                ->setEntityId($userId)
                ->setData($data);

            ACTIVITY_BOL_Service::getInstance()->addAction($action);
        }
    }
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_REGISTER, 'base_join_user_activity');

function base_mandatory_user_approve_on_join( OW_Event $event )
{
    $params = $event->getParams();

    if ( !OW::getConfig()->getValue('base', 'mandatory_user_approve') )
    {
        return;
    }

    BOL_UserService::getInstance()->disapprove((int) $params['userId']);
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_REGISTER, 'base_mandatory_user_approve_on_join');

function base_edit_user_activity( OW_Event $event )
{
    $params = $event->getParams();

    if ( $params['method'] != 'native' )
    {
        return;
    }

    $userId = (int) $params['userId'];

    if ( OW::getPluginManager()->isPluginActive('activity') )
    {
        $userUrl = BOL_UserService::getInstance()->getUserUrl($userId);
        $displayName = BOL_UserService::getInstance()->getDisplayName($userId);
        $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($userId);

        $avatar = '';
        if ( $avatarUrl )
        {
            $avatar = OW::getLanguage()->text('base', 'join_activity_user_avatar', array(
                'avatarUrl' => BOL_AvatarService::getInstance()->getAvatarUrl($userId),
                'userUrl' => $userUrl,
                'user' => $displayName
                )
            );
        }

        $action = ACTIVITY_BOL_Service::getInstance()->findLastOne('user-edit', $userId, $userId);

        if ( $action !== null )
        {
            $timeLimit = 60 * 30; // 30 minutes

            if ( time() - (int) $action->getTimestamp() >= $timeLimit )
            {
                $action = new ACTIVITY_BOL_Action();
            }
        }

        if ( $action === null )
        {
            $action = new ACTIVITY_BOL_Action();
        }

        $data = array(
            'string' => OW::getLanguage()->text('base', 'edit_activity_string', array(
                'user' => BOL_UserService::getInstance()->getDisplayName($userId),
                'userUrl' => BOL_UserService::getInstance()->getUserUrl($userId)
                )
            )
        );

        $action->setUserId($userId)
            ->setTimestamp(time())
            ->setType('user-edit')
            ->setEntityId($userId)
            ->setData($data);

        ACTIVITY_BOL_Service::getInstance()->addAction($action);
    }
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_EDIT, 'base_edit_user_activity');

function base_feed_after_user_edit( OW_Event $event )
{
    $params = $event->getParams();

    if ( $params['method'] != 'native' )
    {
        return;
    }

    $userId = (int) $params['userId'];

    $event = new OW_Event('feed.action', array(
            'pluginKey' => 'base',
            'entityType' => 'user_edit',
            'entityId' => $userId,
            'userId' => $userId,
            'replace' => true
            ), array(
            'string' => OW::getLanguage()->text('base', 'feed_user_edit_profile'),
            'view' => array(
                'iconClass' => 'ow_ic_user'
            )
        ));
    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_EDIT, 'base_feed_after_user_edit');

function base_feed_after_user_join( OW_Event $event )
{
    $params = $event->getParams();

    if ( $params['method'] != 'native' )
    {
        return;
    }

    $userId = (int) $params['userId'];

    $event = new OW_Event('feed.action', array(
            'pluginKey' => 'base',
            'entityType' => 'user_join',
            'entityId' => $userId,
            'userId' => $userId,
            'replace' => true
            ), array(
            'string' => OW::getLanguage()->text('base', 'feed_user_join'),
            'view' => array(
                'iconClass' => 'ow_ic_user'
            )
        ));
    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_REGISTER, 'base_feed_after_user_join');

function base_welcome_letter( OW_Event $event )
{
    $params = $event->getParams();

    $userId = (int) $params['userId'];

    if ( $userId === 0 )
    {
        return;
    }

    BOL_PreferenceService::getInstance()->savePreferenceValue('send_wellcome_letter', 1, $userId);
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_REGISTER, 'base_welcome_letter');

function base_elst_delete_user_action_tool( BASE_CLASS_EventCollector $event )
{
    if ( !OW::getUser()->isAuthorized('base') )
    {
        return;
    }

    $params = $event->getParams();

    if ( empty($params['userId']) )
    {
        return;
    }

    $userId = (int) $params['userId'];

    $linkId = 'ud' . rand(10, 1000000);
    $script = "$('#" . $linkId . "').click(function(){new OW_FloatBox({\$title:" . json_encode(OW::getLanguage()->text('base', 'delete_user_confirmation_label')) . ", \$contents: $('#base_user_delete_cmp'), width:'480px', height:'250px', icon_class:'ow_ic_add'});});";
    OW::getDocument()->addOnloadScript($script);

    $resultArray = array(
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LABEL => OW::getLanguage()->text('base', 'profile_toolbar_user_delete_label'),
        BASE_CMP_ProfileActionToolbar::DATA_KEY_CMP_CLASS => 'BASE_CMP_DeleteUser',
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_CLASS => 'ow_mild_red',
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_HREF => 'javascript://',
        BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_ID => $linkId
    );

    $event->add($resultArray);
}
OW::getEventManager()->bind(BASE_CMP_ProfileActionToolbar::EVENT_NAME, 'base_elst_delete_user_action_tool');

function base_elst_block_user_action_tool( BASE_CLASS_EventCollector $event )
{
    $params = $event->getParams();

    if (!OW::getUser()->isAuthenticated())
    {
        return;
    }
    
    if ( empty($params['userId']) )
    {
        return;
    }

    if ( $params['userId'] == OW::getUser()->getId() )
    {
        return;
    }

    $authorizationService = BOL_AuthorizationService::getInstance();
    if ( $authorizationService->isModerator($params['userId']) || $authorizationService->isSuperModerator($params['userId']) )
    {
        return;
    }

    $userId = (int) $params['userId'];
    
    if ( !BOL_UserService::getInstance()->isBlocked($userId, OW::getUser()->getId()) )
    {

        $linkId = 'userblock' . rand(10, 1000000);
        $script = "$('#" . $linkId . "').click(function(){new OW_FloatBox({\$title:" . json_encode(OW::getLanguage()->text('base', 'block_user_confirmation_label')) . ", \$contents: $('#base_user_block_cmp'), width:'480px', height:'165px', icon_class:'ow_ic_add'});});";
        OW::getDocument()->addOnloadScript($script);

        $resultArray = array(
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LABEL => OW::getLanguage()->text('base', 'user_block_btn_lbl'),
            BASE_CMP_ProfileActionToolbar::DATA_KEY_CMP_CLASS => 'BASE_CMP_BlockUser',
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_CLASS => 'ow_mild_red',
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_HREF => 'javascript://',
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_ID => $linkId
        );

        $event->add($resultArray);
    }
    else
    {
        $linkId = 'userunblock' . rand(10, 1000000);

        $resultArray = array(
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LABEL => OW::getLanguage()->text('base', 'user_unblock_btn_lbl'),
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_CLASS => 'ow_mild_red',
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_HREF => OW::getRouter()->urlFor('BASE_CTRL_User', 'unblock', array('id' => $userId)).'?backUrl='.OW::getRouter()->getBaseUrl() . OW::getRequest()->getRequestUri(),
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_ID => $linkId 
        );

        $event->add($resultArray);
    }
}
OW::getEventManager()->bind(BASE_CMP_ProfileActionToolbar::EVENT_NAME, 'base_elst_block_user_action_tool');

function base_invite_members_process_join_form( OW_Event $event )
{
    $params = $event->getParams();

    if ( $params['code'] !== null )
    {
        $info = BOL_UserService::getInstance()->findInvitationInfo($params['code']);

        if ( $info !== null )
        {
            throw new JoinRenderException();
        }
    }
}
OW::getEventManager()->bind(OW_EventManager::ON_JOIN_FORM_RENDER, 'base_invite_members_process_join_form');

function base_delete_disaproved_on_unregister( OW_Event $event )
{
    $params = $event->getParams();

    $userId = (int) $params['userId'];
    $userService = BOL_UserService::getInstance();

    if ( !$userService->isApproved($userId) )
    {
        return;
    }

    $userService->deleteDisaproveByUserId($userId);
}
OW::getThemeManager()->addDecorator('ic', $owBasePlugin->getKey());

function base_dashboard_menu_item( BASE_CLASS_EventCollector $event )
{
    $router = OW_Router::getInstance();
    $language = OW::getLanguage();

    $menuItems = array();

    $menuItem = new BASE_MenuItem();

    $menuItem->setKey('widget_panel');
    $menuItem->setLabel($language->text('base', 'widgets_panel_dashboard_label'));
    $menuItem->setIconClass('ow_ic_house');
    $menuItem->setUrl($router->urlForRoute('base_member_dashboard'));
    $menuItem->setOrder(1);

    $event->add($menuItem);


    $menuItem = new BASE_MenuItem();

    $menuItem->setKey('profile_edit');
    $menuItem->setLabel($language->text('base', 'edit_index'));
    $menuItem->setIconClass('ow_ic_user');
    $menuItem->setUrl($router->urlForRoute('base_edit'));
    $menuItem->setOrder(2);

    $event->add($menuItem);

    $menuItem = new BASE_MenuItem();

    $menuItem->setKey('preference');
    $menuItem->setLabel($language->text('base', 'preference_index'));
    $menuItem->setIconClass('ow_ic_gear_wheel');
    $menuItem->setUrl($router->urlForRoute('base_preference_index'));
    $menuItem->setOrder(4);

    $event->add($menuItem);
}
OW::getEventManager()->bind('base.dashboard_menu_items', 'base_dashboard_menu_item');

function base_add_members_only_exception( BASE_CLASS_EventCollector $event )
{
    $event->add(array('controller' => 'BASE_CTRL_Join', 'action' => 'index'));
    $event->add(array('controller' => 'BASE_CTRL_Captcha', 'action' => 'index'));
    $event->add(array('controller' => 'BASE_CTRL_Captcha', 'action' => 'ajaxResponder'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'forgotPassword'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'resetPasswordRequest'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'resetPassword'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'ajaxSignIn'));
    $event->add(array('controller' => 'BASE_CTRL_ApiServer', 'action' => 'request'));
    $event->add(array('controller' => 'BASE_CTRL_Unsubscribe', 'action' => 'index'));
}
OW::getEventManager()->bind('base.members_only_exceptions', 'base_add_members_only_exception');

function base_add_password_protected_exceptions( BASE_CLASS_EventCollector $event )
{
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'standardSignIn'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'ajaxSignIn'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'forgotPassword'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'resetPasswordRequest'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'resetPassword'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'resetPasswordCodeExpired'));
    $event->add(array('controller' => 'BASE_CTRL_EmailVerify', 'action' => 'verify'));
    $event->add(array('controller' => 'BASE_CTRL_ApiServer', 'action' => 'request'));
    $event->add(array('controller' => 'BASE_CTRL_Unsubscribe', 'action' => 'index'));
}
OW::getEventManager()->bind('base.password_protected_exceptions', 'base_add_password_protected_exceptions');

function base_add_maintenance_mode_exceptions( BASE_CLASS_EventCollector $event )
{
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'standardSignIn'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'forgotPassword'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'resetPassword'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'resetPasswordCodeExpired'));
    $event->add(array('controller' => 'BASE_CTRL_User', 'action' => 'resetPasswordRequest'));
    $event->add(array('controller' => 'BASE_CTRL_ApiServer', 'action' => 'request'));
}
OW::getEventManager()->bind('base.maintenance_mode_exceptions', 'base_add_maintenance_mode_exceptions');

/* -------- */

function base_on_notify_actions( BASE_CLASS_EventCollector $e )
{
    $e->add(array(
        'section' => 'base',
        'sectionLabel' => OW::getLanguage()->text('base', 'notification_section_label'),
        'action' => 'base_add_user_comment',
        'description' => OW::getLanguage()->text('base', 'email_notifications_setting_user_comment'),
        'sectionIcon' => 'ow_ic_file',
        'selected' => true
    ));
}
OW::getEventManager()->bind('base.notify_actions', 'base_on_notify_actions');

function base_on_add_comment( OW_Event $event )
{
    $params = $event->getParams();

    if ( empty($params['entityType']) || $params['entityType'] !== 'base_profile_wall' )
    {
        return;
    }

    $entityId = $params['entityId'];
    $userId = $params['userId'];
    $commentId = $params['commentId'];

    $userService = BOL_UserService::getInstance();

    $user = $userService->findUserById($entityId);

    if ( $user->getId() == $userId )
    {
        return;
    }

    $comment = BOL_CommentService::getInstance()->findComment($commentId);
    $url = OW::getRouter()->urlForRoute('base_user_profile', array('username' => BOL_UserService::getInstance()->getUserName($entityId)));

    $event = new OW_Event('base.notify', array(
            'plugin' => 'base',
            'action' => 'base_add_user_comment',
            'userId' => $user->getId(),
            'string' => OW::getLanguage()->text('base', 'profile_comment_notification', array(
                'userName' => $userService->getDisplayName($userId),
                'userUrl' => $userService->getUserUrl($userId),
                'profileUrl' => $userService->getUserUrl($user->getId())
            )),
            'content' => $comment->getMessage(),
            'url' => $userService->getUserUrl($user->getId())
        ));

    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind('base_add_comment', 'base_on_add_comment');

OW::getRegistry()->setArray('users_page_data', array());


if ( defined('OW_ADS_XP_TOP') )
{

    function base_add_page_banner( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();

        if ( $params['key'] == 'base.content_top' )
        {
            $event->add(OW_ADS_XP_TOP);
        }
        elseif ( $params['key'] == 'base.content_bottom' )
        {
            $event->add(OW_ADS_XP_BOT);
        }
    }
    OW::getEventManager()->bind('base.add_page_content', 'base_add_page_banner');
}

function base_on_avatar_toolbar_collect( BASE_CLASS_EventCollector $e )
{
    $e->add(array(
        'title' => OW::getLanguage()->text('base', 'console_item_label_dashboard'),
        'iconClass' => 'ow_ic_house',
        'url' => OW::getRouter()->urlForRoute('base_member_dashboard'),
        'order' => 1
    ));

    $e->add(array(
        'title' => OW::getLanguage()->text('base', 'console_item_label_profile'),
        'iconClass' => 'ow_ic_user',
        'url' => OW::getRouter()->urlForRoute('base_member_profile'),
        'order' => 3
    ));
}
OW::getEventManager()->bind('base.on_avatar_toolbar_collect', 'base_on_avatar_toolbar_collect');

//Ajax Floatbox responder
function base_floatbox_rsp_declaration( $e )
{
    $scriptGen = UTIL_JsGenerator::newInstance()->setVariable(array('OW', 'ajaxFloatboxRsp'), OW::getRouter()->urlFor('BASE_CTRL_AjaxFloatbox', 'index'));
    OW::getDocument()->addScriptDeclaration($scriptGen->generateJs());

    OW::getLanguage()->addKeyForJs('base', 'ajax_floatbox_users_title');
}
OW::getEventManager()->bind(OW_EventManager::ON_FINALIZE, 'base_floatbox_rsp_declaration');

function base_ads_enabled( BASE_EventCollector $event )
{
    $event->add('base');
}
OW::getEventManager()->bind('ads.enabled_plugins', 'base_ads_enabled');

// delete plugin comments
function base_delete_plugin_comments( OW_Event $event )
{
    $params = $event->getParams();

    if ( !empty($params['pluginKey']) )
    {
        BOL_CommentService::getInstance()->deletePluginComments($params['pluginKey']);
    }
}
OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_PLUGIN_UNINSTALL, 'base_delete_plugin_comments');

// add dashboard item to member console
function base_add_console_dashboard_item( BASE_CLASS_EventCollector $e )
{
    $e->add(
        array(
            BASE_CMP_Console::DATA_KEY_URL => OW::getRouter()->urlForRoute('base_member_dashboard'),
            BASE_CMP_Console::DATA_KEY_ICON_CLASS => 'ow_ic_house',
            BASE_CMP_Console::DATA_KEY_TITLE => OW::getLanguage()->text('base', 'console_item_label_dashboard')
        )
    );
}
$a = new BASE_CMP_AddNewContent();

OW::getEventManager()->bind(BASE_CMP_Console::EVENT_NAME, 'base_add_console_dashboard_item');

function base_add_auth_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(
        array(
            'base' => array(
                'label' => $language->text('base', 'auth_group_label'),
                'actions' => array(
                    'add_comment' => $language->text('base', 'add_comment'),
                    'delete_comment_by_content_owner' => $language->text('base', 'delete_comment_by_content_owner'),
                    'search_users' => $language->text('base', 'search_users'),
                    'view_profile' => $language->text('base', 'auth_view_profile')
                )
            )
        )
    );
}
OW::getEventManager()->bind('admin.add_auth_labels', 'base_add_auth_labels');

function base_preference_add_form_element( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $params = $event->getParams();
    $values = $params['values'];

    $fromElementList = array();

    $fromElement = new CheckboxField('mass_mailing_subscribe');
    $fromElement->setLabel($language->text('base', 'preference_mass_mailing_subscribe_label'));
    $fromElement->setDescription($language->text('base', 'preference_mass_mailing_subscribe_description'));

    if ( isset($values['mass_mailing_subscribe']) )
    {
        $fromElement->setValue($values['mass_mailing_subscribe']);
    }

    $fromElementList[] = $fromElement;

    $event->add($fromElementList);
}
OW::getEventManager()->bind(BOL_PreferenceService::PREFERENCE_ADD_FORM_ELEMENT_EVENT, 'base_preference_add_form_element');

function base_add_preference_section_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $sectionLabels = array(
        'general' => array(
            'label' => $language->text('base', 'preference_section_general'),
            'iconClass' => 'ow_ic_script'
        )
    );

    $event->add($sectionLabels);
}
OW::getEventManager()->bind(BOL_PreferenceService::PREFERENCE_SECTION_LABEL_EVENT, 'base_add_preference_section_labels');

function base_privacy_add_action( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $action = array(
        'key' => 'base_view_profile',
        'pluginKey' => 'base',
        'label' => $language->text('base', 'privacy_action_view_profile'),
        'description' => '',
        'defaultValue' => 'everybody'
    );

    $event->add($action);

    $action = array(
        'key' => 'base_view_my_presence_on_site',
        'pluginKey' => 'base',
        'label' => $language->text('base', 'privacy_action_view_my_presence_on_site'),
        'description' => '',
        'defaultValue' => 'everybody'
    );

    $event->add($action);
}
OW::getEventManager()->bind('plugin.privacy.get_action_list', 'base_privacy_add_action');

function base_remove_user_preference( OW_Event $event )
{
    $params = $event->getParams();

    $userId = (int) $params['userId'];
    BOL_PreferenceService::getInstance()->deletePreferenceDataByUserId($userId);
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'base_remove_user_preference');

function base_update_entity_items_status( OW_Event $event )
{
    $params = $event->getParams();

    if ( empty($params['entityType']) || empty($params['entityIds']) || !isset($params['status']) || !is_array($params['entityIds']) )
    {
        return;
    }

    $status = empty($params['status']) ? 0 : 1;

    foreach ( $params['entityIds'] as $entityId )
    {
        BOL_CommentService::getInstance()->setEntityStatus($params['entityType'], $entityId, $status);
        BOL_TagService::getInstance()->updateEntityItemStatus($params['entityType'], $entityId, $status);
        BOL_RateService::getInstance()->updateEntityStatus($params['entityType'], $entityId, $status);
        BOL_VoteService::getInstance()->updateEntityItemStatus($params['entityType'], $entityId, $status);
    }
}
OW::getEventManager()->bind('base.update_entity_items_status', 'base_update_entity_items_status');

function base_feed_collect_configurable_activity( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(array(
        'label' => $language->text('admin', 'feed_content_registration'),
        'activity' => 'create:user_join'
    ));

    $event->add(array(
        'label' => $language->text('admin', 'feed_content_edit'),
        'activity' => 'create:user_edit'
    ));

    $event->add(array(
        'label' => $language->text('admin', 'feed_content_user_comment'),
        'activity' => 'create:user-comment'
    ));
}
OW::getEventManager()->bind('feed.collect_configurable_activity', 'base_feed_collect_configurable_activity');

// attachments events
function base_delete_attachment_image( OW_Event $event )
{
    $params = $event->getParams();
    if ( !empty($params['url']) && strstr($params['url'], OW::getStorage()->getFileUrl(OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments')) )
    {
        BOL_AttachmentService::getInstance()->deleteImage($params['url']);
    }
}
OW::getEventManager()->bind('base.attachment_delete_image', 'base_delete_attachment_image');

function base_save_attachment_image( OW_Event $event )
{
    $params = $event->getParams();
    if ( !empty($params['genId']) )
    {
        return BOL_AttachmentService::getInstance()->saveTempImage($params['genId']);
    }

    return null;
}
OW::getEventManager()->bind('base.attachment_save_image', 'base_save_attachment_image');

function base_plugins_uninstall( OW_Event $e )
{
    $params = $e->getParams();
    $pluginKey = $params['pluginKey'];

    BOL_BillingService::getInstance()->deleteGatewayProductsByPluginKey($pluginKey);
}
OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_PLUGIN_UNINSTALL, 'base_plugins_uninstall');
