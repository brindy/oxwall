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
 * Edit user details
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_system_plugins.base.controllers
 * @since 1.0
 */
class BASE_CTRL_Edit extends OW_ActionController
{
    const EDIT_SYNCHRONIZE_HOOK = 'edit_synchronize_hook';

    private $questionService;    

    public function __construct()
    {
        parent::__construct();

        $this->questionService = BOL_QuestionService::getInstance();
        $this->userService = BOL_UserService::getInstance();
    }

    public function index( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !OW::getUser()->isAuthenticated() || $userId === null )
        {
            throw new AuthenticateException();
        }

        $changePassword = new BASE_CMP_ChangePassword();
        $this->addComponent("changePassword", $changePassword);

        $contentMenu = new BASE_CMP_DashboardContentMenu();
        $contentMenu->getElement('profile_edit')->setActive(true);

        $this->addComponent('contentMenu', $contentMenu);

        $user = BOL_UserService::getInstance()->findUserById($userId);
        $accountType = $user->accountType;

        $language = OW::getLanguage();

        $this->setPageHeading($language->text('base', 'edit_index'));
        $this->setPageHeadingIconClass('ow_ic_user');
        // -- Edit form --

        $editForm = new EditQuestionForm('editForm');
        $editForm->setId('editForm');

        $editSubmit = new Submit('editSubmit');
        $editSubmit->addAttribute('class', 'ow_button ow_ic_save');

        $editSubmit->setValue($language->text('base', 'edit_button'));

        $editForm->addElement($editSubmit);

        $questions = $this->questionService->findEditQuestionsForAccountType($accountType);

        $section = null;
        $questionArray = array();
        $questionNameList = array();

        foreach ( $questions as $sort => $question )
        {
            if ( $section !== $question['sectionName'] )
            {
                $section = $question['sectionName'];
            }

            $questionArray[$section][$sort] = $questions[$sort];
            $questionNameList[] = $questions[$sort]['name'];
        }

        $this->assign('questionArray', $questionArray);

        $questionData = $this->questionService->getQuestionData(array($userId), $questionNameList);

        $questionValues = $this->questionService->findQuestionsValuesByQuestionNameList($questionNameList);

        $editForm->addQuestions($questions, $questionValues, $questionData[$userId]);

        if ( OW::getRequest()->isPost() )
        {
            if ( $editForm->isValid($_POST) )
            {
                $data = $editForm->getValues();

                foreach ( $questionArray as $section )
                {
                    foreach ( $section as $key => $question )
                    {
                        switch ( $question['presentation'] )
                        {
                            case 'multicheckbox':

                                if ( is_array($data[$question['name']]) )
                                {
                                    $data[$question['name']] = array_sum($data[$question['name']]);
                                }
                                else
                                {
                                    $data[$question['name']] = 0;
                                }

                                break;
                        }
                    }
                }

                // save user data
                if ( !empty($user->id) )
                {
                    if ( $this->questionService->saveQuestionsData($data, $user->id) )
                    {
                        $event = new OW_Event(OW_EventManager::ON_USER_EDIT, array('userId' => $user->id, 'method' => 'native'));
                        OW::getEventManager()->trigger($event);

                        OW::getFeedback()->info($language->text('base', 'edit_successfull_edit'));
                        $this->redirect();
                    }
                    else
                    {
                        OW::getFeedback()->info($language->text('base', 'edit_edit_error'));
                    }
                }
                else
                {
                    OW::getFeedback()->info($language->text('base', 'edit_edit_error'));
                }
            }
        }

        $this->addForm($editForm);

        $this->assign('unregisterProfileUrl', OW::getRouter()->urlForRoute('base_delete_user'));

        $language->addKeyForJs('base', 'join_error_username_not_valid');
        $language->addKeyForJs('base', 'join_error_username_already_exist');
        $language->addKeyForJs('base', 'join_error_email_not_valid');
        $language->addKeyForJs('base', 'join_error_email_already_exist');
        $language->addKeyForJs('base', 'join_error_password_not_valid');
        $language->addKeyForJs('base', 'join_error_password_too_short');
        $language->addKeyForJs('base', 'join_error_password_too_long');

        //include js
        $onLoadJs = " window.edit = new OW_BaseFieldValidators( " .
            json_encode(array(
                'formName' => $editForm->getName(),
                'responderUrl' => OW::getRouter()->urlFor("BASE_CTRL_Edit", "ajaxResponder"))) . ",
                                                        " . UTIL_Validator::EMAIL_PATTERN . ", " . UTIL_Validator::USER_NAME_PATTERN . " ); ";

        $this->assign('isAdmin', OW::getUser()->isAdmin());

        OW::getDocument()->addOnloadScript($onLoadJs);

        $jsDir = OW::getPluginManager()->getPlugin("base")->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir . "base_field_validators.js");

        $editSynchronizeHook = OW::getRegistry()->getArray(self::EDIT_SYNCHRONIZE_HOOK);

        if ( !empty($editSynchronizeHook) )
        {
            $content = array();

            foreach ( $editSynchronizeHook as $function )
            {
                $result = call_user_func($function);

                if ( trim($result) )
                {
                    $content[] = $result;
                }
            }

            $content = array_filter($content, 'trim');

            if ( !empty($content) )
            {
                $this->assign('editSynchronizeHook', $content);
            }
        }
    }

    public function ajaxResponder()
    {
        if ( empty($_POST["command"]) || !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        $userId = OW::getUser()->getId();

        if ( !OW::getUser()->isAuthenticated() || $userId === null )
        {
            throw new AuthenticateException(); // TODO: Redirect to login page
        }

        $command = (string) $_POST["command"];

        switch ( $command )
        {
            case 'isExistEmail':

                $result = false;

                $email = $_POST["value"];

                $result = $this->userService->isExistEmail($email);

                if ( $result )
                {
                    $user = $this->userService->findUserById($userId);

                    if ( isset($user) && $user->email === $email )
                    {
                        $result = false;
                    }
                }

                echo json_encode(array('result' => !$result));

                break;

            case 'validatePassword':

                $result = false;

                $password = $_POST["value"];

                $result = $this->userService->isValidPassword(OW::getUser()->getId(), $password);

                echo json_encode(array('result' => $result));

                break;

            case 'isExistUserName':
                $username = $_POST["value"];

                $validator = new editUserNameValidator();
                $result = $validator->isValid($username);

                echo json_encode(array('result' => $result));

                break;

            default:
        }
        exit();
    }
}

class editUserNameValidator extends OW_Validator
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

        if ( BOL_UserService::getInstance()->isExistUserName($value) )
        {
            $userId = OW::getUser()->getId();

            $user = BOL_UserService::getInstance()->findUserById($userId);

            if ( $value !== $user->username )
            {
                $this->setErrorMessage($language->text('base', 'join_error_username_already_exist'));
                return false;
            }
        }

        if ( BOL_UserService::getInstance()->isRestrictedUsername($value) )
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
                    // window.edit.validateUsername(false);
                    if( window.edit.errors['username']['error'] !== undefined )
                    {
                        throw window.edit.errors['username']['error'];
                    }
                },
                getErrorMessage : function(){
                    if( window.edit.errors['username']['error'] !== undefined ){ return window.edit.errors['username']['error']; }
                    else{ return " . json_encode($this->getError()) . " }
                }
        }";
    }
}

class editEmailValidator extends OW_Validator
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
        if ( BOL_UserService::getInstance()->isExistEmail($value) )
        {
            $userId = OW::getUser()->getId();

            $user = BOL_UserService::getInstance()->findUserById($userId);

            if ( $value !== $user->email )
            {
                $this->setErrorMessage($language->text('base', 'join_error_email_already_exist'));
                return false;
            }
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
                    // window.edit.validateEmail(false);
                    if( window.edit.errors['email']['error'] !== undefined )
                    {
                        throw window.edit.errors['email']['error'];
                    }
                },
        	getErrorMessage : function(){
                    if( window.edit.errors['email']['error'] !== undefined ){ return window.edit.errors['email']['error']; }
                    else{ return " . json_encode($this->getError()) . " }
                 }
        }";
    }
}

class EditQuestionForm extends BASE_CLASS_UserQuestionForm
{

    /**
     * Set field validator
     *
     * @param FormElement $formField
     * @param array $question
     */
    protected function addFieldValidator( $formField, $question )
    {
        if ( (string) $question['base'] === '1' )
        {
            if ( $question['name'] === 'email' )
            {
                $formField->addValidator(new editEmailValidator());
            }
            else if ( $question['name'] === 'username' )
            {
                $formField->addValidator(new editUserNameValidator());
            }
        }

        return $formField;
    }

    /**
     * Set field value
     *
     * @param FormElement $formField
     * @param string $presentation
     * @param string $value
     */
    protected function setFieldValue( $formField, $presentation, $value )
    {
        switch ( $presentation )
        {
            case BOL_QuestionService::QUESTION_PRESENTATION_CHECKBOX:

                $formField->setValue(true);

                break;

            case BOL_QuestionService::QUESTION_PRESENTATION_AGE:
            case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE:
            case BOL_QuestionService::QUESTION_PRESENTATION_DATE:

                $date = UTIL_DateTime::parseDate($value, UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);

                if ( isset($date) )
                {
                    $formField->setValue($date['year'] . '/' . $date['month'] . '/' . $date['day']);
                }

                break;

            case BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX:

                $data = array();
                $multicheckboxValue = (int) $value;

                for ( $i = 0; $i < 31; $i++ )
                {
                    $val = (int) pow(2, $i);

                    if ( $val & $multicheckboxValue )
                    {
                        $data[] = $val;
                    }
                }

                $formField->setValue($data);

                break;

            default :
                $formField->setValue($value);
        }
    }
}

