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
 * @package ow.ow_system_plugins.base.controllers
 * @since 1.0
 */
class BASE_CTRL_User extends OW_ActionController
{
    /**
     * @var BOL_UserService
     */
    private $userService;

    public function __construct()
    {
        parent::__construct();
        $this->userService = BOL_UserService::getInstance();
    }

    public function forgotPassword()
    {
        if ( OW::getUser()->isAuthenticated() )
        {
            $this->redirect(OW::getRouter()->urlForRoute('base_member_dashboard'));
        }

        $this->setPageHeading(OW::getLanguage()->text('base', 'forgot_password_heading'));

        $language = OW::getLanguage();

        $form = new Form('forgot-password');

        $email = new TextField('email');
        $email->setRequired(true);
        $email->addValidator(new EmailValidator());
        $email->setHasInvitation(true);
        $email->setInvitation($language->text('base', 'forgot_password_email_invitation_message'));
        $form->addElement($email);

        $this->addForm($form);

        $submit = new Submit('submit');
        $submit->setValue($language->text('base', 'forgot_password_submit_label'));
        $form->addElement($submit);

        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                $data = $form->getValues();
                $email = trim($data['email']);
                $user = $this->userService->findByEmail($email);

                if ( $user === null )
                {
                    OW::getFeedback()->error($language->text('base', 'forgot_password_no_user_error_message'));
                    $this->redirect();
                }

                if ( $this->userService->findResetPasswordByUserId($user->getId()) !== null )
                {
                    OW::getFeedback()->error($language->text('base', 'forgot_password_request_exists_error_message'));
                    $this->redirect();
                }

                $resetPassword = $this->userService->getNewResetPassword($user->getId());

                $vars = array('code' => $resetPassword->getCode(), 'username' => $user->getUsername(), 'requestUrl' => OW::getRouter()->urlForRoute('base.reset_user_password_request'),
                    'resetUrl' => OW::getRouter()->urlForRoute('base.reset_user_password', array('code' => $resetPassword->getCode())));

                $mail = OW::getMailer()->createMail();
                $mail->addRecipientEmail($email);
                $mail->setSubject($language->text('base', 'reset_password_mail_template_subject'));
                $mail->setTextContent($language->text('base', 'reset_password_mail_template_content_txt', $vars));
                $mail->setHtmlContent($language->text('base', 'reset_password_mail_template_content_html', $vars));
                OW::getMailer()->send($mail);

                OW::getFeedback()->info($language->text('base', 'forgot_password_success_message'));
                $this->redirect();
            }
            else
            {
                OW::getFeedback()->error($language->text('base', 'forgot_password_general_error_message'));
                $this->redirect();
            }
        }
    }

    public function resetPasswordRequest()
    {
        if ( OW::getUser()->isAuthenticated() )
        {
            $this->redirect(OW::getRouter()->urlForRoute('base_member_dashboard'));
        }

        $form = new Form('reset-password-request');
        $code = new TextField('code');
        $code->setLabel(OW::getLanguage()->text('base', 'reset_password_request_code_field_label'));
        $code->setRequired();
        $form->addElement($code);
        $submit = new Submit('submit');
        $submit->setValue(OW::getLanguage()->text('base', 'reset_password_request_submit_label'));
        $form->addElement($submit);

        $this->addForm($form);

        $this->setPageHeading(OW::getLanguage()->text('base', 'reset_password_request_heading'));

        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                $data = $form->getValues();

                $resetPassword = $this->userService->findResetPasswordByCode($data['code']);

                if ( $resetPassword === null )
                {
                    OW::getFeedback()->error(OW::getLanguage()->text('base', 'reset_password_request_invalid_code_error_message'));
                    $this->redirect();
                }

                $this->redirect(OW::getRouter()->urlForRoute('base.reset_user_password', array('code' => $resetPassword->getCode())));
            }
            else
            {
                OW::getFeedback()->error(OW::getLanguage()->text('base', 'reset_password_request_invalid_code_error_message'));
                $this->redirect();
            }
        }
    }

    public function resetPassword( $params )
    {
        if ( OW::getUser()->isAuthenticated() )
        {
            $this->redirect(OW::getRouter()->urlForRoute('base_member_dashboard'));
        }

        $this->setPageHeading(OW::getLanguage()->text('base', 'reset_password_heading'));

        if ( empty($params['code']) )
        {
            throw new Redirect404Exception();
        }

        $resetCode = $this->userService->findResetPasswordByCode($params['code']);

        if ( $resetCode == null )
        {
            throw new RedirectException(OW::getRouter()->urlForRoute('base.reset_user_password_expired_code'));
        }

        $user = $this->userService->findUserById($resetCode->getUserId());

        if ( $user === null )
        {
            throw new Redirect404Exception();
        }

        $form = new Form('reset-password');
        $pass = new PasswordField('password');
        $pass->setRequired();
        $pass->setLabel(OW::getLanguage()->text('base', 'reset_password_field_label'));
        $form->addElement($pass);
        $repeatPass = new PasswordField('repeatPassword');
        $repeatPass->setRequired();
        $repeatPass->setLabel(OW::getLanguage()->text('base', 'reset_password_repeat_field_label'));
        $form->addElement($repeatPass);
        $submit = new Submit('submit');
        $submit->setValue(OW::getLanguage()->text('base', 'reset_password_submit_label'));
        $form->addElement($submit);
        $this->addForm($form);

        $this->assign('formText', OW::getLanguage()->text('base', 'reset_password_form_text', array('username' => $user->getUsername())));


        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                $data = $form->getValues();

                if ( trim($data['password']) !== trim($data['repeatPassword']) )
                {
                    OW::getFeedback()->error(OW::getLanguage()->text('base', 'reset_password_not_equal_error_message'));
                    $this->redirect();
                }

                if ( strlen(trim($data['password'])) > UTIL_Validator::PASSWORD_MAX_LENGTH || strlen(trim($data['password'])) < UTIL_Validator::PASSWORD_MIN_LENGTH )
                {
                    OW::getFeedback()->error(OW::getLanguage()->text('base', 'reset_password_length_error_message', array('min' => UTIL_Validator::PASSWORD_MIN_LENGTH, 'max' => UTIL_Validator::PASSWORD_MAX_LENGTH)));
                    $this->redirect();
                }

                $user->setPassword(BOL_UserService::getInstance()->hashPassword($data['password']));
                BOL_UserService::getInstance()->saveOrUpdate($user);
                $this->userService->deleteResetCode($resetCode->getId());
                OW::getFeedback()->info(OW::getLanguage()->text('base', 'reset_password_success_message'));
                $this->redirect(OW::getRouter()->urlForRoute('static_sign_in'));
            }
            else
            {
                OW::getFeedback()->error();
                $this->redirect();
            }
        }
    }

    public function resetPasswordCodeExpired()
    {
        $this->setPageHeading(OW::getLanguage()->text('base', 'reset_password_code_expired_cap_label'));
        $this->setPageHeadingIconClass('ow_ic_info');
        $this->assign('text', OW::getLanguage()->text('base', 'reset_password_code_expired_text', array('url' => OW::getRouter()->urlForRoute('base_forgot_password'))));
    }

    public function standardSignIn()
    {
        if ( OW::getRequest()->isAjax() )
        {
            exit(json_encode(array()));
        }

        if ( OW::getUser()->isAuthenticated() )
        {
            throw new RedirectException(OW::getRouter()->urlForRoute('base_member_dashboard'));
        }

        $this->assign('joinUrl', OW::getRouter()->urlForRoute('base_join'));

        OW::getDocument()->getMasterPage()->setTemplate(OW::getThemeManager()->getMasterPageTemplate(OW_MasterPage::TEMPLATE_BLANK));

        $form = $this->getSignInForm();
        $this->addForm($form);
        $form->setName('std-sign-in');

        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                try
                {
                    $result = $this->processSignIn();
                }
                catch ( LogicException $e )
                {
                    
                }

                $message = '';

                foreach ( $result->getMessages() as $value )
                {
                    $message .= $value;
                }

                if ( $result->isValid() )
                {
                    OW::getFeedback()->info($message);
                    $redirectUrl = isset($_GET['back-uri']) ? OW_URL_HOME . urldecode($_GET['back-uri']) : OW::getRequest()->buildUrlQueryString(null, array());
                    $this->redirect($redirectUrl);
                }
                else
                {
                    OW::getFeedback()->error($message);
                    $this->redirect();
                }

                OW::getFeedback()->error("_INVALID_POST_");
                $this->redirect();
            }
        }

        $this->setDocumentKey('base.sign_in');
    }

    public function ajaxSignIn()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        if ( OW::getRequest()->isPost() )
        {
            try
            {
                $result = $this->processSignIn();
            }
            catch ( LogicException $e )
            {
                
            }

            $message = '';

            foreach ( $result->getMessages() as $value )
            {
                $message .= $value;
            }

            if ( $result->isValid() )
            {
                exit(json_encode(array('result' => true, 'message' => $message)));
            }
            else
            {
                exit(json_encode(array('result' => false, 'message' => $message)));
            }

            exit(json_encode(array()));
        }

        exit(json_encode(array()));
    }

    public function signOut()
    {
        OW::getUser()->logout();

        if ( isset($_COOKIE['ow_login']) )
        {
            setcookie('ow_login', '', time() - 3600, '/');
        }

        $this->redirect(OW_URL_HOME);
    }

    public static function getSignInForm()
    {
        $form = new Form('sign-in');

        $form->setAjaxResetOnSuccess(false);

        $username = new TextField('identity');
        $username->setRequired(true);
        $username->setHasInvitation(true);
        $username->setInvitation(OW::getLanguage()->text('base', 'component_sign_in_login_invitation'));
        $form->addElement($username);

        $password = new PasswordField('password');
        $password->setHasInvitation(true);
        $password->setInvitation('password');
        $password->setRequired(true);

        $form->addElement($password);

        $remeberMe = new CheckboxField('remember');
        $remeberMe->setLabel(OW::getLanguage()->text('base', 'sign_in_remember_me_label'));
        $form->addElement($remeberMe);

        $submit = new Submit('submit');
        $submit->setValue(OW::getLanguage()->text('base', 'sign_in_submit_label'));
        $form->addElement($submit);

        return $form;
    }

    /**
     * @return OW_AuthResult
     */
    private function processSignIn()
    {
        $form = $this->getSignInForm();

        if ( $form->isValid($_POST) )
        {
            $data = $form->getValues();

            $adapter = new BASE_CLASS_StandardAuth($data['identity'], $data['password']);

            $result = OW::getUser()->authenticate($adapter);

            if ( $result->isValid() )
            {
                if ( isset($data['remember']) )
                {
                    $loginCookie = $this->userService->saveLoginCookie(OW::getUser()->getId());

                    setcookie('ow_login', $loginCookie->getCookie(), (time() + 86400 * 7), '/', null, null, true);
                }
            }

            return $result;
        }
        else
        {
            throw new LogicException();
        }
    }

    public function controlFeatured( $params )
    {
        $service = BOL_UserService::getInstance();

        if ( (!OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('base') ) || ($userId = intval($params['id'])) <= 0 )
        {
            exit;
        }

        switch ( $params['command'] )
        {
            case 'mark':

                $service->markAsFeatured($userId);
                OW::getFeedback()->info(OW::getLanguage()->text('base', 'user_feedback_marked_as_featured'));

                break;

            case 'unmark':

                $service->cancelFeatured($userId);
                OW::getFeedback()->info(OW::getLanguage()->text('base', 'user_feedback_unmarked_as_featured'));

                break;
        }

        $this->redirect($_GET['backUrl']);
    }

    public function updateActivity( $params )
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            exit;
        }

        BOL_UserService::getInstance()->updateActivityStamp($params['user']);
        exit;
    }

    public function deleteUser( $params )
    {
        $userId = (int) $params['user-id'];

        $user = BOL_UserService::getInstance()->findUserById($userId);

        if ( $user === null || !OW::getUser()->isAuthorized('base') )
        {
            throw new Redirect404Exception();
        }

        if ( BOL_AuthorizationService::getInstance()->isActionAuthorizedForUser($userId, BOL_AuthorizationService::ADMIN_GROUP_NAME) )
        {
            OW::getFeedback()->error(OW::getLanguage()->text('base', 'cannot_delete_admin_user_msg'));
            $this->redirect($_SERVER['HTTP_REFERER']);
        }

        $event = new OW_Event(OW_EventManager::ON_USER_UNREGISTER, array('userId' => $userId, 'deleteContent' => true));
        OW::getEventManager()->trigger($event);

        BOL_UserService::getInstance()->deleteUser($userId);

        $this->redirect(OW::getRouter()->urlFor(__CLASS__, 'userDeleted'));
    }

    public function userDeleted()
    {//TODO do smth
        OW::getDocument()->getMasterPage()->setTemplate(OW::getThemeManager()->getMasterPageTemplate(OW_MasterPage::TEMPLATE_BLANK));
    }

    public function approve( $params )
    {
        if ( !OW::getUser()->isAuthorized('base') )
        {
            throw new Redirect404Exception();
        }

        $userId = $params['userId'];

        $userService = BOL_UserService::getInstance();
        $userService->approve($userId);

        $user = $userService->findUserById($userId);
        $language = OW::getLanguage();


        $mail = OW::getMailer()->createMail();
        $vars = array('user_name' => $userService->getDisplayName($userId));
        $mail->addRecipientEmail($user->getEmail());
        $mail->setSubject($language->text('base', 'user_approved_mail_subject', $vars));
        $mail->setTextContent($language->text('base', 'user_approved_mail_txt', $vars));
        $mail->setHtmlContent($language->text('base', 'user_approved_mail_html', $vars));
        OW::getMailer()->send($mail);

        OW::getFeedback()->info(OW::getLanguage()->text('base', 'user_approved'));
        $this->redirect($_SERVER['HTTP_REFERER']);
    }

    public function updateUserRoles()
    {
        $user = BOL_UserService::getInstance()->findUserById((int) $_POST['userId']);

        if ( $user === null )
        {
            exit(json_encode(array('result' => 'error', 'mesaage' => 'Empty user')));
        }

        $roles = array();
        foreach ( $_POST['roles'] as $roleId => $onoff )
        {
            if ( !empty($onoff) )
            {
                $roles[] = $roleId;
            }
        }

        $aService = BOL_AuthorizationService::getInstance();
        $aService->deleteUserRolesByUserId($user->getId());

        foreach ( $roles as $roleId )
        {
            $aService->saveUserRole($user->getId(), $roleId);
        }

        exit(json_encode(array(
                'result' => 'success',
                'message' => OW::getLanguage()->text('base', 'authorization_feedback_roles_updated')
            )));
    }
    
    public function block( $params )
    {
        if ( empty($params['id']) )
        {
            exit;
        }
        if (!OW::getUser()->isAuthenticated())
        {
            throw new AuthenticateException();
        }
        $userId = (int) $params['id'];

        $userService = BOL_UserService::getInstance();
        $userService->block($userId);
        
        OW::getFeedback()->info(OW::getLanguage()->text('base', 'user_feedback_profile_blocked'));

        $this->redirect($_GET['backUrl']);
    }

    public function unblock( $params )
    {              
        if ( empty($params['id']) )
        {
            exit;
        }
        if (!OW::getUser()->isAuthenticated())
        {
            throw new AuthenticateException();
        }      
        $id = (int) $params['id'];

        $userService = BOL_UserService::getInstance();
        $userService->unblock($id);

        OW::getFeedback()->info(OW::getLanguage()->text('base', 'user_feedback_profile_unblocked'));
        
        $this->redirect($_GET['backUrl']);
    }
}

