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
 * Join user
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_system_plugins.base.controllers
 * @since 1.0
 */
class BASE_CTRL_Join extends OW_ActionController
{
    const JOIN_CONNECT_HOOK = 'join_connect_hook';
    private $responderUrl;

    public function __construct()
    {
        parent::__construct();

        $this->responderUrl = OW::getRouter()->urlFor("BASE_CTRL_Join", "ajaxResponder");

        $this->userService = BOL_UserService::getInstance();
    }

    public function index( $params )
    {
        $session = OW::getSession();

        if ( OW::getUser()->isAuthenticated() )
        {
            $this->redirect(OW_URL_HOME);
        }

        $joinData = $session->get(JoinForm::SESSION_JOIN_DATA);

        if ( !isset($joinData) || !is_array($joinData) )
        {
            $joinData = array();
        }

        $language = OW::getLanguage();
        $this->setPageHeading($language->text('base', 'join_index'));

        //TODO DELETE config who_can_join from join
        if ( OW::getConfig()->getValue('base', 'who_can_join') === (String) BOL_UserService::PERMISSIONS_JOIN_BY_INVITATIONS )
        {
            $code = null;
            if ( isset($_GET['code']) )
            {
                $code = $_GET['code'];
            }

            //close join form
            try
            {
                $event = new OW_Event(OW_EventManager::ON_JOIN_FORM_RENDER, array('code' => $code));
                OW::getEventManager()->trigger($event);
                $this->assign('notValidInviteCode', true);
                return;
            }
            catch ( JoinRenderException $ex )
            {
                //ignore;
            }
        }

        $joinForm = new JoinForm($this);

        $step = $joinForm->getStep();

        if ( OW::getRequest()->isPost() )
        {
            if ( $joinForm->isValid($_POST) )
            {
                $data = $joinForm->getValues();

                unset($data['repeatPassword']);

                if ( $joinForm->isLastStep )
                {
                    $joinData = array_merge($joinData, $data);

                    $session->set(JoinForm::SESSION_JOIN_DATA, $joinData);

                    foreach ( $joinForm->questions as $question )
                    {
                        switch ( $question['presentation'] )
                        {
                            case BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX:

                                if ( is_array($joinData[$question['name']]) )
                                {
                                    $joinData[$question['name']] = array_sum($joinData[$question['name']]);
                                }
                                else
                                {
                                    $joinData[$question['name']] = 0;
                                }

                                break;
                        }
                    }

                    $this->joinUser($joinData, $joinForm->accountType);

                    $this->redirect(OW::getRouter()->urlForRoute('base_default_index'));
                }
                else
                {
                    $joinData = array_merge($data, $joinData);

                    $step++;

                    $session->set(JoinForm::SESSION_JOIN_DATA, $joinData);
                    $session->set(JoinForm::SESSION_JOIN_STEP, $step);

                    $this->redirect();
                }
            }
        }

        $this->addForm($joinForm);

        $language->addKeyForJs('base', 'join_error_username_not_valid');
        $language->addKeyForJs('base', 'join_error_username_already_exist');
        $language->addKeyForJs('base', 'join_error_email_not_valid');
        $language->addKeyForJs('base', 'join_error_email_already_exist');
        $language->addKeyForJs('base', 'join_error_password_not_valid');
        $language->addKeyForJs('base', 'join_error_password_too_short');
        $language->addKeyForJs('base', 'join_error_password_too_long');

        //include js
        $onLoadJs = " window.join = new OW_BaseFieldValidators( " .
            json_encode(array(
                'formName' => $joinForm->getName(),
                'responderUrl' => $this->responderUrl,
                'passwordMaxLength' => UTIL_Validator::PASSWORD_MAX_LENGTH,
                'passwordMinLength' => UTIL_Validator::PASSWORD_MIN_LENGTH)) . ",
                                                        " . UTIL_Validator::EMAIL_PATTERN . ", " . UTIL_Validator::USER_NAME_PATTERN . " ); ";

        OW::getDocument()->addOnloadScript($onLoadJs);

        $jsDir = OW::getPluginManager()->getPlugin("base")->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir . "base_field_validators.js");


        $joinConnectHook = OW::getRegistry()->getArray(self::JOIN_CONNECT_HOOK);

        if ( !empty($joinConnectHook) )
        {
            $content = array();

            foreach ( $joinConnectHook as $function )
            {
                $result = call_user_func($function);

                if ( trim($result) )
                {
                    $content[] = $result;
                }
            }

            $this->assign('joinConnectHook', $content);
        }

        $this->setDocumentKey('base.user_join');
    }

    public function ajaxResponder()
    {
        if ( empty($_POST["command"]) || !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        $command = (string) $_POST["command"];

        switch ( $command )
        {
            case 'isExistUserName':

                $result = false;

                $username = $_POST["value"];

                $result = $this->userService->isExistUserName($username);

                echo json_encode(array('result' => !$result));

                break;

            case 'isExistEmail':

                $result = false;

                $email = $_POST["value"];

                $result = $this->userService->isExistEmail($email);

                echo json_encode(array('result' => !$result));

                break;

            default:
        }
        exit;
    }

    private function joinUser( $joinData, $accountType )
    {
        // create new user
        $user = $this->userService->createUser($joinData['username'], $joinData['password'], $joinData['email'], $accountType);

        $password = $joinData['password'];

        unset($joinData['username']);
        unset($joinData['password']);
        unset($joinData['email']);
        unset($joinData['accountType']);

        // save user data
        if ( !empty($user->id) )
        {
            if ( BOL_QuestionService::getInstance()->saveQuestionsData($joinData, $user->id) )
            {
                OW::getSession()->delete(JoinForm::SESSION_JOIN_DATA);
                OW::getSession()->delete(JoinForm::SESSION_JOIN_STEP);

                // authenticate user
                OW::getUser()->login($user->id);

                // create Avatar
                $this->createAvatar($user->id);

                $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array('userId' => $user->id, 'method' => 'native', 'params' => $_GET));
                OW::getEventManager()->trigger($event);

                OW::getFeedback()->info(OW::getLanguage()->text('base', 'join_successful_join'));

                if ( OW::getConfig()->getValue('base', 'confirm_email') )
                {
                    BOL_EmailVerifyService::getInstance()->sendUserVerificationMail($user);
                }
            }
            else
            {
                OW::getFeedback()->info($language->text('base', 'join_join_error'));
            }
        }
        else
        {
            OW::getFeedback()->info($language->text('base', 'join_join_error'));
        }
    }

    private function createAvatar( $userId )
    {
        $avatarService = BOL_AvatarService::getInstance();

        if ( !empty($_FILES['userPhoto']['tmp_name']) && strlen($_FILES['userPhoto']['tmp_name']) )
        {
            if ( !UTIL_File::validateImage($_FILES['userPhoto']['name']) )
            {
                return false;
            }

            return $avatarService->setUserAvatar($userId, $_FILES['userPhoto']['tmp_name']);
        }
        else
        {
            return false;
        }
    }
}

class JoinForm extends BASE_CLASS_UserQuestionForm
{
    const SESSION_JOIN_DATA = 'joinData';

    const SESSION_JOIN_STEP = 'joinStep';

    public $stepCount = 1;
    public $isLastStep = false;
    public $diaplayAccountType = false;
    public $questions = array();
    public $accountType = null;

    public function __construct( $controller )
    {
        parent::__construct('joinForm');

        $this->setId('joinForm');

        $stepCount = 1;
        $joinSubmitLabel = "";

        // get available account types from DB
        $accounts = $this->getAccountTypes();

        $joinData = OW::getSession()->get(self::SESSION_JOIN_DATA);

        if ( !isset($joinData) || !is_array($joinData) )
        {
            $joinData = array();
        }

        $accountsKeys = array_keys($accounts);
        $this->accountType = $accountsKeys[0];

        if ( isset($joinData['accountType']) )
        {
            $this->accountType = trim($joinData['accountType']);
        }

        $step = $this->getStep();

        if ( count($accounts) > 1 )
        {
            $this->stepCount = 2;
            switch ( $step )
            {
                case 1:
                    $this->diaplayAccountType = true;
                    $joinSubmitLabel = OW::getLanguage()->text('base', 'join_submit_button_continue');

                    break;

                case 2:
                    $this->isLastStep = true;
                    $joinSubmitLabel = OW::getLanguage()->text('base', 'join_submit_button_join');
                    break;
            }
        }
        else
        {
            $this->isLastStep = true;
            $joinSubmitLabel = OW::getLanguage()->text('base', 'join_submit_button_join');
        }

        $joinSubmit = new Submit('joinSubmit');
        $joinSubmit->addAttribute('class', 'ow_button ow_ic_save');
        $joinSubmit->setValue($joinSubmitLabel);
        $this->addElement($joinSubmit);

        if ( $this->diaplayAccountType )
        {
            $joinAccountType = new Selectbox('accountType');
            $joinAccountType->setLabel(OW::getLanguage()->text('base', 'questions_question_account_type_label'));
            $joinAccountType->setRequired();
            $joinAccountType->setOptions($accounts);
            $joinAccountType->setValue($this->accountType);
            $joinAccountType->setHasInvitation(false);

            $this->addElement($joinAccountType);
        }

        $this->getQuestions();

        $section = null;
        $questionListBySection = array();
        $questionNameList = array();
        $questionList = array();

        foreach ( $this->questions as $sort => $question )
        {
            if ( (string) $question['base'] === '0' && $step === 2 || $step === 1 )
            {
                if ( $section !== $question['sectionName'] )
                {
                    $section = $question['sectionName'];
                }

                $questionListBySection[$section][$sort] = $this->questions[$sort];
                $questionNameList[] = $this->questions[$sort]['name'];
                $questionList[] = $this->questions[$sort];
            }
        }

        $questionValues = BOL_QuestionService::getInstance()->findQuestionsValuesByQuestionNameList($questionNameList);

        $this->addQuestions($questionList, $questionValues, $joinData);

        if ( $this->isLastStep )
        {
            $this->addLastStepQuestions($controller);
        }

        $controller->assign('step', $step);
        $controller->assign('questionArray', $questionListBySection);
        $controller->assign('diaplayAccountType', $this->diaplayAccountType);
        $controller->assign('isLastStep', $this->isLastStep);
    }

    public function getStep()
    {
        $session = OW::getSession();

        $step = $session->get(self::SESSION_JOIN_STEP);

        if ( isset($step) )
        {
            $step = (int) $step;

            if ( $step === 0 )
            {
                $step = 1;
                $session->set(self::SESSION_JOIN_STEP, $step);
            }
        }
        else
        {
            $step = 1;
            $session->set(self::SESSION_JOIN_STEP, $step);
        }

        return $step;
    }

    public function getQuestions()
    {
        $this->questions = array();

        if ( $this->isLastStep )
        {
            $this->questions = BOL_QuestionService::getInstance()->findSignUpQuestionsForAccountType($this->accountType);
        }
        else
        {
            $this->questions = BOL_QuestionService::getInstance()->findBaseSignUpQuestions();
        }
    }

    private function addLastStepQuestions( $controller )
    {
        $displayPhoto = false;

        $displayPhotoUpload = OW::getConfig()->getValue('base', 'join_display_photo_upload');

        $photoValidator = new photoValidator(false);

        switch ( $displayPhotoUpload )
        {
            case BOL_UserService::CONFIG_JOIN_DISPLAY_AND_SET_REQUIRED_PHOTO_UPLOAD :
                $photoValidator = new photoValidator(true);

            case BOL_UserService::CONFIG_JOIN_DISPLAY_PHOTO_UPLOAD :
                $userPhoto = new FileField('userPhoto');
                $userPhoto->setLabel(OW::getLanguage()->text('base', 'questions_question_user_photo_label'));
                $userPhoto->addValidator($photoValidator);
                $this->addElement($userPhoto);

                $displayPhoto = true;
        }

        $displayTermsOfUse = false;

        if ( OW::getConfig()->getValue('base', 'join_display_terms_of_use') )
        {
            $termOfUse = new CheckboxField('termOfUse');
            $termOfUse->setLabel(OW::getLanguage()->text('base', 'questions_question_user_terms_of_use_label'));
            $termOfUse->setRequired();

            $this->addElement($termOfUse);

            $displayTermsOfUse = true;
        }

        $this->setEnctype('multipart/form-data');

        $captchaField = new CaptchaField('captchaField');

        $this->addElement($captchaField);

        $controller->assign('display_photo', $displayPhoto);
        $controller->assign('display_terms_of_use', $displayTermsOfUse);

        if ( OW::getRequest()->isPost() )
        {
            $captchaField->setValue(null);

            if ( isset($userPhoto) && isset($_FILES[$userPhoto->getName()]['name']) )
            {
                $_POST[$userPhoto->getName()] = $_FILES[$userPhoto->getName()]['name'];
            }
        }
    }

    protected function addFieldValidator( $formField, $question )
    {
        if ( (string) $question['base'] === '1' )
        {
            if ( $question['name'] === 'email' )
            {
                $formField->addValidator(new joinEmailValidator());
            }

            if ( $question['name'] === 'username' )
            {
                $formField->addValidator(new UserNameValidator());
            }

            if ( $question['name'] === 'password' )
            {
                $passwordRepeat = BOL_QuestionService::getInstance()->getPresentationClass($question['presentation'], 'repeatPassword');
                $passwordRepeat->setLabel(OW::getLanguage()->text('base', 'questions_question_repeat_password_label'));
                $passwordRepeat->setRequired((string) $question['required'] === '1');
                $this->addElement($passwordRepeat);

                $formField->addValidator(new PasswordValidator());
            }
        }
    }
}

class UserNameValidator extends OW_Validator
{

    /**
     * Constructor.
     *
     * @param array $params
     */
    public function __construct()
    {

    }

    /**
     * @see Validator::isValid()
     *
     * @param mixed $value
     */
    public function isValid( $value )
    {
        $language = OW::getLanguage();
        if ( !UTIL_Validator::isUserNameValid($value) )
        {
            $this->setErrorMessage($language->text('base', 'join_error_username_not_valid'));
            return false;
        }
        else if ( BOL_UserService::getInstance()->isExistUserName($value) )
        {
            $this->setErrorMessage($language->text('base', 'join_error_username_already_exist'));
            return false;
        }
        else if ( BOL_UserService::getInstance()->isRestrictedUsername($value) )
        {
            $this->setErrorMessage($language->text('base', 'join_error_username_restricted'));
            return false;
        }

        return true;
    }

    /**
     * @see Validator::getJsValidator()
     *
     * @return string
     */
    public function getJsValidator()
    {
        return "{
                validate : function( value )
                {
                    // window.join.validateUsername(false);
                    if( window.join.errors['username']['error'] !== undefined )
                    {
                        throw window.join.errors['username']['error'];
                    }
                },
                getErrorMessage : function(){
                    if( window.join.errors['username']['error'] !== undefined ){ return window.join.errors['username']['error']; }
                    else{ return " . json_encode($this->getError()) . " }
                }
        }";
    }
}

class joinEmailValidator extends OW_Validator
{

    /**
     * Constructor.
     *
     * @param array $params
     */
    public function __construct()
    {

    }

    /**
     * @see Validator::isValid()
     *
     * @param mixed $value
     */
    public function isValid( $value )
    {
        $language = OW::getLanguage();
        if ( !UTIL_Validator::isEmailValid($value) )
        {
            $this->setErrorMessage($language->text('base', 'join_error_email_not_valid'));
            return false;
        }
        else if ( BOL_UserService::getInstance()->isExistEmail($value) )
        {
            $this->setErrorMessage($language->text('base', 'join_error_email_already_exist'));
            return false;
        }

        return true;
    }

    /**
     * @see Validator::getJsValidator()
     *
     * @return string
     */
    public function getJsValidator()
    {
        return "{
        	validate : function( value )
                { 
                    // window.join.validateEmail(false);
                    if( window.join.errors['email']['error'] !== undefined )
                    {
                        throw window.join.errors['email']['error'];
                    }
                },
        	getErrorMessage : function(){
                    if( window.join.errors['email']['error'] !== undefined ){ return window.join.errors['email']['error']; }
                    else{ return " . json_encode($this->getError()) . " }
                 }
        }";
    }
}

class PasswordValidator extends BASE_CLASS_PasswordValidator
{

    /**
     * Constructor.
     *
     * @param array $params
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see Validator::getJsValidator()
     *
     * @return string
     */
    public function getJsValidator()
    {
        return "{
                validate : function( value )
                {
                    if( !window.join.validatePassword() )
                    {
                        throw window.join.errors['password']['error'];
                    }
                },
                getErrorMessage : function()
                {
                       if( window.join.errors['password']['error'] !== undefined ){ return window.join.errors['password']['error'] }
                       else{ return " . json_encode($this->getError()) . " }
                }
        }";
    }
}

class photoValidator extends OW_Validator
{
    protected $setRequired = false;

    /**
     * Constructor.
     *
     * @param array $params
     */
    public function __construct( $setRequired = false )
    {
        $this->setRequired = $setRequired;

        $language = OW::getLanguage();
        $this->setErrorMessage($language->text('base', 'not_valid_image'));
    }

    /**
     * @see Validator::isValid()
     *
     * @param mixed $value
     */
    public function isValid( $value )
    {
        $language = OW::getLanguage();

        if ( !isset($_FILES['userPhoto']['name']) || strlen($_FILES['userPhoto']['name']) == 0 )
        {
            if ( !$this->setRequired )
            {
                return true;
            }
        }

        if ( !UTIL_File::validateImage($_FILES['userPhoto']['name']) )
        {
            return false;
        }

        if ( !is_writable(BOL_AvatarService::getInstance()->getAvatarsDir()) )
        {
            $this->setErrorMessage($language->text('base', 'not_writable_avatar_dir'));
            return false;
        }

        return true;
    }

    /**
     * @see Validator::getJsValidator()
     *
     * @return string
     */
    public function getJsValidator()
    {
        $condition = '';

        if ( $this->setRequired )
        {
            $condition = "if( !value || $.trim(value).length == 0 ){ throw " . json_encode($this->getError()) . "; }";
        }

        return "{
                validate : function( value ){ " . $condition . " },
                getErrorMessage : function(){ return " . json_encode($this->getError()) . " }
        }";
    }
}

class JoinRenderException extends Exception
{
    
}