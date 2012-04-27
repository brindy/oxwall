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
 * Questions controller
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_system_plugins.admin.controllers
 * @since 1.0
 */
class ADMIN_CTRL_Questions extends ADMIN_CTRL_Abstract
{
    const ADD_QUESTION_SESSION_VAR = "ADMIN_ADD_QUESTION";
    const EDIT_QUESTION_SESSION_VAR = "ADMIN_EDIT_QUESTION";
    const SESSION_VAR_ACCIUNT_TYPE = "BASE_QUESTION_ACCOUNT_TYPE";

    /**
     * @var BOL_QuestionService
     *
     */
    private $questionService;
    private $ajaxResponderUrl;
    private $columnCountValues = array();
    /**
     * @var BASE_CMP_ContentMenu
     */
    private $contentMenu;

    public function __construct()
    {
        parent::__construct();

        $this->questionService = BOL_QuestionService::getInstance();
        $this->ajaxResponderUrl = OW::getRouter()->urlFor("ADMIN_CTRL_Questions", "ajaxResponder");

        $this->qstColumnCountValues = array();
        for ( $i = 1; $i <= 5; $i++ )
        {
            $this->qstColumnCountValues[$i] = $i;
        }
        $language = OW::getLanguage();

        $this->setPageHeading($language->text('admin', 'heading_questions'));
        $this->setPageHeadingIconClass('ow_ic_files');

        OW::getNavigation()->activateMenuItem('admin_users', 'admin', 'sidebar_menu_item_questions');
    }

    public function index( $params = array() )
    {
        $this->addContentMenu();

        $accountType = null;
        if ( isset($_GET['accountType']) )
        {
            OW::getSession()->set(self::SESSION_VAR_ACCIUNT_TYPE, trim($_GET['accountType']));
        }

        if ( OW::getSession()->get(self::SESSION_VAR_ACCIUNT_TYPE) )
        {
            $accountType = OW::getSession()->get(self::SESSION_VAR_ACCIUNT_TYPE);
        }

        $serviceLang = BOL_LanguageService::getInstance();
        $language = OW::getLanguage();
        $currentLanguageId = OW::getLanguage()->getCurrentId();

        // get available account types from DB
        $accountTypes = $this->questionService->findAllAccountTypesWithQuestionsCount();

        /* @var $value BOL_QuestionAccount */
        foreach ( $accountTypes as $key => $value )
        {
            $accounts[$value['name']] = $language->text('base', 'questions_account_type_' . $value['name']);
        }

        $accountsKeys = array_keys($accounts);
        $accountType = (!isset($accountType) || !in_array($accountType, $accountsKeys) ) ? $accountsKeys[0] : $accountType;

        // -- Select account type form --
        $accountTypeSelectForm = new Form('qst_account_type_select_form');
        $accountTypeSelectForm->setMethod(Form::METHOD_GET);

        $qstAccountType = new Selectbox('accountType');
        $qstAccountType->addAttribute('id', 'qst_account_type_select');
        $qstAccountType->setLabel($language->text('admin', 'questions_account_type_label'));
        $qstAccountType->setOptions($accounts);
        $qstAccountType->setValue($accountType);
        $qstAccountType->setHasInvitation(false);

        $accountTypeSelectForm->addElement($qstAccountType);

        $this->addForm($accountTypeSelectForm);

        $script = '
                        $("#qst_account_type_select").change( function(){
                                $(this).parents("form:eq(0)").submit();
                        } );
                    ';

        OW::getDocument()->addOnloadScript($script);

        $this->assign('accountTypes', $accountTypes);
        $this->assign('editAccountTypeUrl', OW::getRouter()->urlFor('ADMIN_CTRL_Questions', 'editAccountType'));

        $addSectionForm = new Form('qst_add_section_form');

        $qstSectionName = new TextField('section_name');
        $qstSectionName->addAttribute('class', 'ow_text');
        $qstSectionName->addAttribute('style', 'width: auto;');
        $qstSectionName->setRequired();
        $qstSectionName->setLabel($language->text('admin', 'questions_new_section_label'));

        $addSectionForm->addElement($qstSectionName);

        if ( OW::getRequest()->isPost() && isset($_POST['section_name']) )
        {
            if ( $addSectionForm->isValid($_POST) )
            {
                $data = $addSectionForm->getValues();

                $questionSection = new BOL_QuestionSection();
                $questionSection->name = md5(uniqid());
                $questionSection->sortOrder = ($this->questionService->findLastSectionOrder()) + 1;

                $this->questionService->saveOrUpdateSection($questionSection);

                $serviceLang->addValue($currentLanguageId, 'base', 'questions_section_' . ( $questionSection->name ) . '_label', htmlspecialchars($data['section_name']));

                if ( OW::getDbo()->getAffectedRows() > 0 )
                {
                    OW::getFeedback()->info($language->text('admin', 'questions_section_was_added'));
                }

                $this->redirect(OW::getRequest()->getRequestUri());
            }
        }

        $this->addForm($addSectionForm);

        // -- Get all section, questions and question values --

        $questions = $this->questionService->findAllQuestionsWithSectionForAccountType($accountType);

        $section = null;
        $questionArray = array();
        $questionNameList = array();
        $sectionDeleteUrlList = array();

        foreach ( $questions as $sort => $question )
        {
            if ( $section !== $question['sectionName'] )
            {
                $section = $question['sectionName'];
                $sectionDeleteUrlList[$section] = OW::getRouter()->urlFor('ADMIN_CTRL_Questions', 'deleteSection', array("sectionName" => $section));
                $questionArray[$section] = array();
            }

            if ( isset($questions[$sort]['id']) )
            {
                $questionArray[$section][$sort] = $questions[$sort];
                $questionArray[$section][$sort]['questionEditUrl'] = OW::getRouter()->urlFor('ADMIN_CTRL_Questions', 'edit', array("questionId" => $questions[$sort]['id']));
                $questionArray[$section][$sort]['questionDeleteUrl'] = OW::getRouter()->urlFor('ADMIN_CTRL_Questions', 'deleteQuestion', array("questionId" => $questions[$sort]['id']));

                $questionNameList[] = $questions[$sort]['name'];
            }
        }

        $questionValues = $this->questionService->findQuestionsValuesByQuestionNameList($questionNameList);

        $this->assign('questionsBySections', $questionArray);
        $this->assign('questionValues', $questionValues);
        $this->assign('sectionDeleteUrlList', $sectionDeleteUrlList);

        $language->addKeyForJs('admin', 'questions_delete_question_confirmation');
        $language->addKeyForJs('admin', 'questions_delete_section_confirmation');

        $script = ' var indexQuest = new indexQuestions( ' . json_encode(array('questionAddUrl' => OW::getRouter()->urlFor("ADMIN_CTRL_Questions", "add"), 'ajaxResponderUrl' => $this->ajaxResponderUrl)) . ' )'; //' . json_encode( array( 'questionEditUrl' => $questionEditUrl ) ) . ' ); ';

        OW::getDocument()->addOnloadScript($script);

        $jsDir = OW::getPluginManager()->getPlugin("admin")->getStaticJsUrl();

        OW::getDocument()->addScript($jsDir . "questions.js");

        $baseJsDir = OW::getPluginManager()->getPlugin("base")->getStaticJsUrl();

        OW::getDocument()->addScript($baseJsDir . "jquery.dimensions.min.js");
        OW::getDocument()->addScript($baseJsDir . "jquery-ui-1.8.9.custom.min.js");
    }

    public function add()
    {
        // add common content menu
        $this->addContentMenu();

        // get available account types from DB
        $accountTypes = $this->questionService->findAllAccountTypes();

        $serviceLang = BOL_LanguageService::getInstance();
        $language = OW::getLanguage();
        $currentLanguageId = OW::getLanguage()->getCurrentId();

        $accounts = array(BOL_QuestionService::ALL_ACCOUNT_TYPES => $language->text('base', 'questions_account_type_all'));

        /* @var $value BOL_QuestionAccount */
        foreach ( $accountTypes as $value )
        {
            $accounts[$value->name] = $language->text('base', 'questions_account_type_' . $value->name);
        }

        $sections = $this->questionService->findAllSections();

        // need to hide sections select box
        if ( empty($sections) )
        {
            $this->assign('no_sections', true);
        }

        $sectionsArray = array();

        /* @var $section BOL_QuestionSection */
        foreach ( $sections as $section )
        {
            $sectionsArray[$section->name] = $language->text('base', 'questions_section_' . $section->name . '_label');
        }

        $presentations2types = $this->questionService->getPresentations();
        $presentationList = array_keys($presentations2types);

        $presentations = array();

        foreach ( $presentationList as $item )
        {
            $presentations[$item] = $item; //TODO add langs with presentation labels
        }

        $presentation = $presentationList[0];

        if ( OW::getSession()->isKeySet(self::ADD_QUESTION_SESSION_VAR) )
        {
            $session = OW::getSession()->get(self::ADD_QUESTION_SESSION_VAR);

            if ( isset($presentations[$session['qst_answer_type']]) )
            {
                $presentation = $presentations[$session['qst_answer_type']];
            }
        }

        if ( isset($_POST['qst_answer_type']) && isset($presentations[$_POST['qst_answer_type']]) )
        {
            $presentation = $presentations[$_POST['qst_answer_type']];
        }

        $displayPossibleValues = in_array( $presentations2types[$presentation], array('select', 'multiselect') ) ? true : false;

        // form creating
        $addForm = new Form('qst_add_form');
        $addForm->setId('qst_add_form');

        $qstName = new TextField('qst_name');
        $qstName->setLabel($language->text('admin', 'questions_question_name_label'));
        //$qstName->addValidator(new StringValidator(0, 24000));
        $qstName->setRequired();

        $addForm->addElement($qstName);

        $qstName = new TextField('qst_description');
        $qstName->setLabel($language->text('admin', 'questions_question_description_label'));
        //$qstName->addValidator(new StringValidator(0, 24000));

        $addForm->addElement($qstName);

        if ( count($accountTypes) > 1 )
        {
            $qstAccountType = new Selectbox('qst_account_type');
            $qstAccountType->setLabel($language->text('admin', 'questions_for_account_type_label'));
            $qstAccountType->setRequired();
            $qstAccountType->setDescription($language->text('admin', 'questions_for_account_type_description'));
            $qstAccountType->setOptions($accounts);
            $qstAccountType->setValue(0);
            $qstAccountType->setHasInvitation(false);

            $addForm->addElement($qstAccountType);
        }

        if ( !empty($sectionsArray) )
        {
            $qstSection = new Selectbox('qst_section');
            $qstSection->setLabel($language->text('admin', 'questions_question_section_label'));
            $qstSection->setOptions($sectionsArray);
            $qstSection->setHasInvitation(false);

            $addForm->addElement($qstSection);
        }

        $qstAnswerType = new Selectbox('qst_answer_type');
        $qstAnswerType->setLabel($language->text('admin', 'questions_answer_type_label'));
        $qstAnswerType->addAttribute('class', $qstAnswerType->getName());
        $qstAnswerType->setOptions($presentations);
        $qstAnswerType->setRequired();
        $qstAnswerType->setHasInvitation(false);
        $qstAnswerType->setValue($presentation);

        $addForm->addElement($qstAnswerType);

        if ( $displayPossibleValues )
        {
            $qstPossibleValues = new Textarea('qst_possible_values');
            $qstPossibleValues->addAttribute('class', $qstPossibleValues->getName());
            $qstPossibleValues->setLabel($language->text('admin', 'questions_possible_values_label'));
            $qstPossibleValues->setDescription($language->text('admin', 'questions_possible_values_description'));

            $addForm->addElement($qstPossibleValues);
        }

        $presentationConfigList = BOL_QuestionService::getInstance()->getConfigList($presentation);

        foreach ( $presentationConfigList as $config )
        {
            $className = $config->presentationClass;

            /* @var $qstConfig OW_FormElement */
            $qstConfig = new $className($config->name);
            $qstConfig->setLabel($language->text('admin', 'questions_config_' . ($config->name) . '_label'));

            if ( !empty($config->description) )
            {
                $qstConfig->setDescription($config->description);
            }

            $addForm->addElement($qstConfig);
        }

        $qstColumnCount = new Selectbox('qst_column_count');
        $qstColumnCount->addAttribute('class', $qstColumnCount->getName());
        $qstColumnCount->setLabel($language->text('admin', 'questions_columns_count_label'));
        $qstColumnCount->setOptions($this->qstColumnCountValues);
        $qstColumnCount->setValue(1);

        $addForm->addElement($qstColumnCount);

        $qstRequired = new CheckboxField('qst_required');
        $qstRequired->setLabel($language->text('admin', 'questions_required_label'));
        $qstRequired->setDescription($language->text('admin', 'questions_required_description'));

        $addForm->addElement($qstRequired);

        $qstOnSignUp = new CheckboxField('qst_on_sign_up');
        $qstOnSignUp->setLabel($language->text('admin', 'questions_on_sing_up_label'));
        $qstOnSignUp->setDescription($language->text('admin', 'questions_on_sing_up_description'));

        $addForm->addElement($qstOnSignUp);

        $qstOnEdit = new CheckboxField('qst_on_edit');
        $qstOnEdit->setLabel($language->text('admin', 'questions_on_edit_label'));
        $qstOnEdit->setDescription($language->text('admin', 'questions_on_edit_description'));

        $addForm->addElement($qstOnEdit);

        $qstOnView = new CheckboxField('qst_on_view');
        $qstOnView->setLabel($language->text('admin', 'questions_on_view_label'));
        $qstOnView->setDescription($language->text('admin', 'questions_on_view_description'));

        $addForm->addElement($qstOnView);

        $qstOnSearch = new CheckboxField('qst_on_search');
        $qstOnSearch->setLabel($language->text('admin', 'questions_on_search_label'));
        $qstOnSearch->setDescription($language->text('admin', 'questions_on_search_description'));

        $addForm->addElement($qstOnSearch);

        $qstSubmit = new Submit('qst_submit');
        $qstSubmit->setValue($language->text('admin', 'save_btn_label'));
        $qstSubmit->addAttribute('class', 'ow_button ow_ic_save');
        $addForm->addElement($qstSubmit);

        $qstSubmitAdd = new Submit('qst_submit_and_add');
        $qstSubmitAdd->addAttribute('class', 'ow_button ow_ic_save');
        $qstSubmitAdd->setValue($language->text('admin', 'questions_save_and_new_label'));

        $addForm->addElement($qstSubmitAdd);

        if ( OW::getSession()->isKeySet(self::ADD_QUESTION_SESSION_VAR) )
        {
            $addForm->setValues(OW::getSession()->get(self::ADD_QUESTION_SESSION_VAR));
            OW::getSession()->delete(self::ADD_QUESTION_SESSION_VAR);
        }

        
        if ( OW_Request::getInstance()->isPost() )
        {
            if ( ( isset($_POST['qst_submit_and_add']) || isset($_POST['qst_submit']) ) && $addForm->isValid($_POST) )
            {
                OW::getSession()->delete(self::ADD_QUESTION_SESSION_VAR);

                $data = $addForm->getValues();

                if ( !isset($data['qst_section']) )
                {
                    $data['qst_section'] = null;
                }
                else
                {
                    $data['qst_section'] = htmlspecialchars(trim($data['qst_section']));
                }

                $presentations = BOL_QuestionService::getInstance()->getPresentations();

                // insert question
                $question = new BOL_Question();

                $question->name = md5(uniqid());

                $question->required = (int) $data['qst_required'];
                $question->onJoin = (int) $data['qst_on_sign_up'];
                $question->onEdit = (int) $data['qst_on_edit'];
                $question->onSearch = (int) $data['qst_on_search'];
                $question->onView = (int) $data['qst_on_view'];
                $question->presentation = htmlspecialchars($data['qst_answer_type']);
                $question->type = htmlspecialchars($presentations[trim($data['qst_answer_type'])]);

                if ( (int) $data['qst_column_count'] > 1 )
                {
                    $question->columnCount = (int) $data['qst_column_count'];
                }

                if ( isset($data['qst_account_type']) )
                {
                    $question->accountTypeName = htmlspecialchars(trim($data['qst_account_type']));

                    if ( $question->accountTypeName === BOL_QuestionService::ALL_ACCOUNT_TYPES )
                    {
                        $question->accountTypeName = null;
                    }
                }

                if ( $data['qst_section'] !== null )
                {
                    $section = $this->questionService->findSectionBySectionName(htmlspecialchars(trim($data['qst_section'])));
                    if ( isset($section) )
                    {
                        $question->sectionName = $section->name;
                    }
                    else
                    {
                        $question->sectionName = null;
                    }
                }

                $question->sortOrder = ( (int) BOL_QuestionService::getInstance()->findLastQuestionOrder($question->sectionName) ) + 1;

                // save question configs
                $configs = array();

                foreach ( $presentationConfigList as $config )
                {
                    if ( isset($data[$config->name]) )
                    {
                        $configs[$config->name] = $data[$config->name];
                    }
                }

                $question->custom = json_encode($configs);

                $this->questionService->saveOrUpdateQuestion($question);
                $this->questionService->setQuestionDescription($question->name, htmlspecialchars(trim($data['qst_description'])));
                $this->questionService->setQuestionLabel($question->name, htmlspecialchars(trim($data['qst_name'])));

                //add question values
                if ( isset($data['qst_possible_values']) && mb_strlen(trim($data['qst_possible_values'])) > 0 && in_array( $question->type,  array('select', 'multiselect') ) )
                {
                    $questionValues = preg_split('/\\n/', trim($data['qst_possible_values']));

                    if ( is_array($questionValues) && count($questionValues) > 0 )
                    {
                        $key = 0;
                        foreach ( $questionValues as $value )
                        {
                            if ( $key > 30 )
                            {
                                break;
                            }

                            $value = trim($value);
                            if ( isset($value) && mb_strlen($value) === 0 )
                            {
                                continue;
                            }

                            $valueId = pow(2, $key);

                            $questionValue = new BOL_QuestionValue();
                            $questionValue->questionName = $question->name;
                            $questionValue->sortOrder = $key;
                            $questionValue->value = $valueId;

                            $this->questionService->saveOrUpdateQuestionValue($questionValue);

                            $serviceLang->addValue($currentLanguageId, 'base', 'questions_question_' . ($question->name) . '_value_' . $valueId, $value);
                            $key++;
                        }
                    }
                }

                OW::getFeedback()->info($language->text('admin', 'questions_add_question_message'));

                if ( isset($_POST['qst_submit']) )
                {
                    $this->redirect(OW::getRouter()->urlFor('ADMIN_CTRL_Questions', 'index'));
                }

                $this->redirect(OW::getRequest()->getRequestUri());
            }

            $addForm->setValues($_POST);
            OW::getSession()->set(self::ADD_QUESTION_SESSION_VAR, $_POST);
            $this->redirect();
        }

//        $types = array();
//        foreach ( $this->questionService->getPresentations() as $presentation => $type )
//        {
//            if ( $type === 'select' )
//            {
//                $types[] = $presentation;
//            }
//        }

        $this->addForm($addForm);

        $fields = array();
        foreach ( $addForm->getElements() as $element )
        {
            if ( !($element instanceof HiddenField) )
            {
                $fields[$element->getName()] = $element->getName();
            }
        }

        $this->assign('formData', $fields);

//        $script = '
//                    var addQuest = new addQuestion(' . json_encode($types) . ');
//                    addQuest.displayPossibleValues();
//                    ';
//
//        OW::getDocument()->addOnloadScript($script);
//
//        $jsDir = OW::getPluginManager()->getPlugin("admin")->getStaticJsUrl();
//
//        OW::getDocument()->addScript($jsDir . "questions.js");
    }

    public function edit( $params )
    {
        if ( !isset($params['questionId']) )
        {
            throw new Redirect404Exception();
        }

        $questionId = (int) @$params['questionId'];
        
        if ( empty($questionId) )
        {
            throw new Redirect404Exception();
        }

        $this->addContentMenu();

        $this->contentMenu->getElement('qst_index')->setActive(true);

        $editQuestion = $this->questionService->findQuestionById($questionId);

        if ( $editQuestion === null )
        {
            throw new Redirect404Exception();
        }

        $this->assign('question', $editQuestion);

        //$editQuestionToAccountType = $this->questionService->findAccountTypeByQuestionName( $editQuestion->name );
        // get available account types from DB
        $accountTypes = $this->questionService->findAllAccountTypes();

        $serviceLang = BOL_LanguageService::getInstance();
        $language = OW::getLanguage();
        $currentLanguageId = OW::getLanguage()->getCurrentId();

        $accounts = array(BOL_QuestionService::ALL_ACCOUNT_TYPES => $language->text('base', 'questions_account_type_all'));

        /* @var $value BOL_QuestionAccount */
        foreach ( $accountTypes as $value )
        {
            $accounts[$value->name] = $language->text('base', 'questions_account_type_' . $value->name);
        }

        $sections = $this->questionService->findAllSections();

        // need to hide sections select box
        if ( empty($section) )
        {
            $this->assign('no_sections', true);
        }

        $sectionsArray = array();

        /* @var $section BOL_QuestionSection */
        foreach ( $sections as $section )
        {
            $sectionsArray[$section->name] = $language->text('base', 'questions_section_' . $section->name . '_label');
        }

        $presentations = array();

        $presentationList = $this->questionService->getPresentations();

        foreach ( $presentationList as $presentationKey => $presentation )
        {
            if ( $presentationList[$editQuestion->presentation] == $presentation )
            {
                $presentations[$presentationKey] = $presentationKey; //TODO add langs with presentation labels
            }
        }

        $presentation = $editQuestion->presentation;

        if ( OW::getSession()->isKeySet(self::EDIT_QUESTION_SESSION_VAR) )
        {
            $session = OW::getSession()->get(self::EDIT_QUESTION_SESSION_VAR);

            if ( isset($session['qst_answer_type']) && isset($presentations[$session['qst_answer_type']]) )
            {
                $presentation = $presentations[$session['qst_answer_type']];
            }
        }

        if ( isset($_POST['qst_answer_type']) && isset($presentations[$_POST['qst_answer_type']]) )
        {
            $presentation = $presentations[$_POST['qst_answer_type']];
        }

        //$this->addForm(new LanguageValueEditForm( 'base', 'questions_question_' . ($editQuestion->id) . '_label', $this ) );
        //--  -------------------------------------
        //--  add question values form creating
        //--  -------------------------------------

        $questionValues = $this->questionService->findQuestionValues($editQuestion->name);

        $this->assign('questionValues', $questionValues);

        //add field values form
        $addQuestionValuesForm = new AddValuesForm($questionId);
        $addQuestionValuesForm->setAction($this->ajaxResponderUrl);
        $this->addForm($addQuestionValuesForm);

        //--  -------------------------------------
        //--  edit field form creating
        //--  -------------------------------------

        $editForm = new Form('qst_edit_form');
        $editForm->setId('qst_edit_form');

        if ( count($accountTypes) > 1 )
        {
            $qstAccountType = new Selectbox('qst_account_type');
            $qstAccountType->setLabel($language->text('admin', 'questions_for_account_type_label'));
            $qstAccountType->setDescription($language->text('admin', 'questions_for_account_type_description'));
            $qstAccountType->setOptions($accounts);
            $qstAccountType->setValue(BOL_QuestionService::ALL_ACCOUNT_TYPES);
            $qstAccountType->setHasInvitation(false);

            if ( $editQuestion->accountTypeName !== null )
            {
                $qstAccountType->setValue($editQuestion->accountTypeName);
            }

            if ( $editQuestion->base == 1 )
            {
                $qstAccountType->addAttribute('disabled', 'disabled');
            }

            $editForm->addElement($qstAccountType);
        }

        if ( !empty($sectionsArray) )
        {
            $qstSection = new Selectbox('qst_section');
            $qstSection->setLabel($language->text('admin', 'questions_question_section_label'));
            $qstSection->setOptions($sectionsArray);
            $qstSection->setValue($editQuestion->sectionName);
            $qstSection->setHasInvitation(false);

            $editForm->addElement($qstSection);
        }

        $qstAnswerType = new Selectbox('qst_answer_type');
        $qstAnswerType->setLabel($language->text('admin', 'questions_answer_type_label'));
        $qstAnswerType->addAttribute('class', $qstAnswerType->getName());
        $qstAnswerType->setOptions($presentations);
        $qstAnswerType->setValue($presentation);
        $qstAnswerType->setRequired();
        $qstAnswerType->setHasInvitation(false);

        if ( count($presentations) <= 1 )
        {
            $qstAnswerType->setRequired(false);
            $qstAnswerType->addAttribute('disabled','disabled');
        }

        if ( (int) $editQuestion->base === 1 )
        {
            $qstAnswerType->setRequired(false);
            $qstAnswerType->addAttribute('disabled', 'disabled');
        }

        $editForm->addElement($qstAnswerType);

        if ( in_array( $editQuestion->type,  array('select', 'multiselect') ) && $editQuestion->name !== 'joinStamp' )
        {
            $qstColumnCount = new Selectbox('qst_column_count');
            $qstColumnCount->addAttribute('class', $qstColumnCount->getName());
            $qstColumnCount->setLabel($language->text('admin', 'questions_columns_count_label'));
            $qstColumnCount->setRequired();
            $qstColumnCount->setOptions($this->qstColumnCountValues);
            $qstColumnCount->setValue($editQuestion->columnCount);

            $editForm->addElement($qstColumnCount);
        }

        $presentationConfigList = BOL_QuestionService::getInstance()->getConfigList($presentation);
        $presentationConfigValues = json_decode($editQuestion->custom, true);

        if ( $editQuestion->name !== 'joinStamp' )
        {
            foreach ( $presentationConfigList as $config )
            {
                $className = $config->presentationClass;

                /* @var $qstConfig OW_FormElement */
                $qstConfig = new $className($config->name);
                $qstConfig->setLabel($language->text('admin', 'questions_config_' . ( $config->name ) . '_label'));

                if ( !empty($config->description) )
                {
                    $qstConfig->setDescription($config->description);
                }

                if ( isset($presentationConfigValues[$config->name]) )
                {
                    $qstConfig->setValue($presentationConfigValues[$config->name]);
                }

                $editForm->addElement($qstConfig);
            }
        }

        $qstRequired = new CheckboxField('qst_required');
        $qstRequired->setLabel($language->text('admin', 'questions_required_label'));
        $qstRequired->setDescription($language->text('admin', 'questions_required_description'));
        $qstRequired->setValue((boolean) $editQuestion->required);

        if ( (int) $editQuestion->base === 1 )
        {
            $qstRequired->addAttribute('disabled', 'disabled');
        }

        $editForm->addElement($qstRequired);

        $qstOnSignUp = new CheckboxField('qst_on_sign_up');
        $qstOnSignUp->setLabel($language->text('admin', 'questions_on_sing_up_label'));
        $qstOnSignUp->setDescription($language->text('admin', 'questions_on_sing_up_description'));
        $qstOnSignUp->setValue((boolean) $editQuestion->onJoin);

        if ( (int) $editQuestion->base === 1 )
        {
            $qstOnSignUp->addAttribute('disabled', 'disabled');
        }

        $editForm->addElement($qstOnSignUp);

        $qstOnEdit = new CheckboxField('qst_on_edit');
        $qstOnEdit->setLabel($language->text('admin', 'questions_on_edit_label'));
        $qstOnEdit->setDescription($language->text('admin', 'questions_on_edit_description'));
        $qstOnEdit->setValue((boolean) $editQuestion->onEdit);

        $description = $language->text('admin', 'questions_on_edit_description');

        if ( $editQuestion->name === 'username' )
        {
            $qstOnEdit->setDescription($language->text('admin', 'questions_on_edit_description') . "<br/><br/>" . $language->text('admin', 'questions_edit_username_warning'));
        }
        else if ( (int) $editQuestion->base === 1 )
        {
            $qstOnEdit->addAttribute('disabled', 'disabled');
        }

        $editForm->addElement($qstOnEdit);

        $qstOnView = new CheckboxField('qst_on_view');
        $qstOnView->setLabel($language->text('admin', 'questions_on_view_label'));
        $qstOnView->setDescription($language->text('admin', 'questions_on_view_description'));
        $qstOnView->setValue((boolean) $editQuestion->onView);
        
        if ( (int) $editQuestion->base === 1 && $editQuestion->name !== 'joinStamp' )
        {
            $qstOnView->addAttribute('disabled', 'disabled');
        }

        $editForm->addElement($qstOnView);

        $qstOnSearch = new CheckboxField('qst_on_search');
        $qstOnSearch->setLabel($language->text('admin', 'questions_on_search_label'));
        $qstOnSearch->setDescription($language->text('admin', 'questions_on_search_description'));
        $qstOnSearch->setValue((boolean) $editQuestion->onSearch);

        if ( (int) $editQuestion->base === 1 )
        {
            $qstOnSearch->addAttribute('disabled', 'disabled');
        }

        $editForm->addElement($qstOnSearch);

        $qstSubmit = new Submit('qst_submit');
        $qstSubmit->addAttribute('class', 'ow_button ow_ic_save');
        $qstSubmit->setValue($language->text('admin', 'btn_label_edit'));

        $editForm->addElement($qstSubmit);

        if ( OW::getSession()->isKeySet(self::EDIT_QUESTION_SESSION_VAR) )
        {
            $editForm->setValues(OW::getSession()->get(self::EDIT_QUESTION_SESSION_VAR));
            OW::getSession()->delete(self::EDIT_QUESTION_SESSION_VAR);
        }

        $this->addForm($editForm);
        
        if ( OW_Request::getInstance()->isPost() )
        {
            if ( ( isset($_POST['qst_submit_and_add']) || isset($_POST['qst_submit']) ) && $editForm->isValid($_POST) )
            {
                OW::getSession()->delete(self::EDIT_QUESTION_SESSION_VAR);
                $updated = false;

                $data = $editForm->getValues();

                $elements = $editForm->getElements();

                foreach ( $elements as $element )
                {
                    if ( !$element->getAttribute('disabled') )
                    {
                        switch ( $element->getName() )
                        {
                            case 'qst_required':
                                $editQuestion->required = isset($_POST['qst_required']) ? 1 : 0;
                                break;

                            case 'qst_on_sign_up':
                                $editQuestion->onJoin = isset($_POST['qst_on_sign_up']) ? 1 : 0;
                                break;

                            case 'qst_on_edit':
                                $editQuestion->onEdit = isset($_POST['qst_on_edit']) ? 1 : 0;
                                break;

                            case 'qst_on_search':
                                $editQuestion->onSearch = isset($_POST['qst_on_search']) ? 1 : 0;
                                break;

                            case 'qst_on_view':
                                $editQuestion->onView = isset($_POST['qst_on_view']) ? 1 : 0;
                                break;

                            case 'qst_answer_type':
                                $editQuestion->presentation = htmlspecialchars($data['qst_answer_type']);
                                break;

                            case 'qst_column_count':
                                $editQuestion->columnCount = htmlspecialchars($data['qst_column_count']);
                                break;

                            case 'qst_section':
                                if ( !empty($data['qst_section']) )
                                {
                                    $section = $this->questionService->findSectionBySectionName(htmlspecialchars(trim($data['qst_section'])));

                                    $sectionName = null;
                                    if ( isset($section) )
                                    {
                                        $sectionName = $section->name;
                                    }

                                    if ( $editQuestion->sectionName !== $sectionName )
                                    {
                                        $editQuestion->sectionName = $sectionName;
                                        $editQuestion->sortOrder = ( (int) BOL_QuestionService::getInstance()->findLastQuestionOrder($editQuestion->sectionName) ) + 1;
                                    }
                                }
                                break;

                            case 'qst_account_type':
                                if ( $data['qst_account_type'] !== null )
                                {
                                    $editQuestion->accountTypeName = htmlspecialchars(trim($data['qst_account_type']));

                                    if ( $editQuestion->accountTypeName === BOL_QuestionService::ALL_ACCOUNT_TYPES )
                                    {
                                        $editQuestion->accountTypeName = null;
                                    }
                                }
                                break;
                        }
                    }
                }

                // save question configs
                $configs = array();

                foreach ( $presentationConfigList as $config )
                {
                    if ( isset($data[$config->name]) )
                    {
                        $configs[$config->name] = $data[$config->name];
                    }
                }

                $editQuestion->custom = json_encode($configs);

                $this->questionService->saveOrUpdateQuestion($editQuestion);

                if ( OW::getDbo()->getAffectedRows() > 0 )
                {
                    $updated = true;
                }

                //update question values sort
                if ( isset($_POST['question_values_order']) )
                {
                    $valuesOrder = json_decode($_POST['question_values_order'], true);
                    if ( isset($valuesOrder) && count($valuesOrder) > 0 && is_array($valuesOrder) )
                    {
                        foreach ( $questionValues as $questionValue )
                        {
                            if ( isset($valuesOrder[$questionValue->value]) )
                            {
                                $questionValue->sortOrder = (int) $valuesOrder[$questionValue->value];
                            }

                            $this->questionService->saveOrUpdateQuestionValue($questionValue);

                            if ( OW::getDbo()->getAffectedRows() > 0 )
                            {
                                $updated = true;
                            }
                        }
                    }
                }

                if ( $updated )
                {
                    OW::getFeedback()->info($language->text('admin', 'questions_update_question_message'));
                }
                else
                {
                    OW::getFeedback()->info($language->text('admin', 'questions_question_was_not_updated_message'));
                }

                //exit;
                $this->redirect(OW::getRouter()->urlFor('ADMIN_CTRL_Questions', 'index'));
            }

            $editForm->setValues($_POST);
            OW::getSession()->set(self::EDIT_QUESTION_SESSION_VAR, $_POST);
            //OW::getFeedback()->error($language->text('admin', 'questions_question_was_not_updated_message'));
            $this->redirect();
        }

        $types = array();
        foreach ( $this->questionService->getPresentations() as $presentation => $type )
        {
            if ( $type === 'select' )
            {
                $types[] = $presentation;
            }
        }

        $questionLabel = $this->questionService->getQuestionLang($editQuestion->name);
        $questionDescription = $this->questionService->getQuestionDescriptionLang($editQuestion->name);
        $noValue = $language->text('admin', 'questions_empty_lang_value');

        $questionLabel = mb_strlen(trim($questionLabel)) === 0 ? $noValue : $questionLabel;
        $questionDescription = mb_strlen(trim($questionDescription)) === 0 ? $noValue : $questionDescription;

        $this->assign('questionLabel', $questionLabel);
        $this->assign('questionDescription', $questionDescription);

        $language->addKeyForJs('admin', 'questions_empty_lang_value');
        $language->addKeyForJs('admin', 'questions_edit_question_name_title');
        $language->addKeyForJs('admin', 'questions_edit_question_description_title');
        $language->addKeyForJs('admin', 'questions_edit_question_value_title');
        $language->addKeyForJs('admin', 'questions_edit_delete_value_confirm_message');

        $fields = array();
        foreach ( $editForm->getElements() as $element )
        {
            if ( !($element instanceof HiddenField) )
            {
                $fields[$element->getName()] = $element->getName();
            }
        }

        $this->assign('formData', $fields);
        $script = '
                    window.editQuestion = new editQuestion(' . json_encode(array('types' => $types, 'ajaxResponderUrl' => $this->ajaxResponderUrl)) . ');
                    ';

        OW::getDocument()->addOnloadScript($script);

        $jsDir = OW::getPluginManager()->getPlugin("admin")->getStaticJsUrl();
        $baseJsDir = OW::getPluginManager()->getPlugin("base")->getStaticJsUrl();

        OW::getDocument()->addScript($jsDir . "questions.js");
        OW::getDocument()->addScript($baseJsDir . "jquery.dimensions.min.js");
        OW::getDocument()->addScript($baseJsDir . "jquery-ui-1.8.9.custom.min.js");
    }

    public function editAccountType( $params )
    {
        $this->addContentMenu();

        $language = OW::getLanguage();

        // get available account types from DB
        $accountTypes = $this->questionService->findAllAccountTypesWithQuestionsCount();
        $deleteUrlList = array();

        /* @var $value BOL_QuestionAccount */
        foreach ( $accountTypes as $key => $value )
        {
            $accounts[$value['name']] = $language->text('base', 'questions_account_type_' . $value['name']);
            $deleteUrlList[$value['name']] = OW::getRouter()->urlFor('ADMIN_CTRL_Questions', 'deleteAccountType', array("accountType" => $value['name']));
        }

        $addNewAccountTypeForm = new AddNewAccountTypeForm();

        if ( OW::getRequest()->isPost() )
        {
            if ( $addNewAccountTypeForm->isValid($_POST) )
            {
                if ( $addNewAccountTypeForm->process() )
                {
                    $this->redirect();
                }
            }
        }

        $this->addForm($addNewAccountTypeForm);

        $this->assign('accountTypeCount', count($accountTypes));
        $this->assign('accountTypes', $accountTypes);
        $this->assign('deleteUrlList', $deleteUrlList);

        $language->addKeyForJs('admin', 'questions_delete_account_type_confirmation');

        $script = ' var accountType = new editAccountType( ' . json_encode(array('ajaxResponderUrl' => $this->ajaxResponderUrl)) . ' )'; //' . json_encode( array( 'questionEditUrl' => $questionEditUrl ) ) . ' ); ';

        OW::getDocument()->addOnloadScript($script);

        $jsDir = OW::getPluginManager()->getPlugin("admin")->getStaticJsUrl();

        OW::getDocument()->addScript($jsDir . "questions.js");

        $baseJsDir = OW::getPluginManager()->getPlugin("base")->getStaticJsUrl();

        OW::getDocument()->addScript($baseJsDir . "jquery.dimensions.min.js");
        OW::getDocument()->addScript($baseJsDir . "jquery-ui-1.8.9.custom.min.js");
    }

    public function settings()
    {
        $this->addContentMenu();

        $form = new Form('questionSettings');

        $value = OW::getConfig()->getValue('base', 'user_view_presentation');

        $language = OW::getLanguage();

        $userViewPresentationnew = new CheckboxField("user_view_presentation");
        $userViewPresentationnew->setLabel($language->text('base', 'questions_config_user_view_presentation_label'));
        $userViewPresentationnew->setDescription($language->text('base', 'questions_config_user_view_presentation_description'));
        $userViewPresentationnew->setValue($value == 'tabs' ? true : false);

        $form->addElement($userViewPresentationnew);

        $submit = new Submit('save');
        $submit->setValue($language->text('admin', 'save_btn_label'));

        $form->addElement($submit);

        $this->addForm($form);

        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                if ( isset($_POST['user_view_presentation']) )
                {
                    OW::getConfig()->saveConfig('base', 'user_view_presentation', 'tabs');
                }
                else
                {
                    OW::getConfig()->saveConfig('base', 'user_view_presentation', 'table');
                }

                OW::getFeedback()->info($language->text('admin', 'question_settings_updated'));
            }

            $this->redirect();
        }
    }

    private function addContentMenu()
    {
        $language = OW::getLanguage();

        $router = OW_Router::getInstance();

        $menuItems = array();

        $menuItem = new BASE_MenuItem();
        $menuItem->setKey('qst_index')->setLabel($language->text('base', 'questions_menu_index'))->setUrl($router->urlForRoute('questions_index'))->setOrder('1');
        $menuItem->setIconClass('ow_ic_files');

        $menuItems[] = $menuItem;

        $menuItem = new BASE_MenuItem();
        $menuItem->setKey('qst_add')->setLabel($language->text('base', 'questions_menu_add'))->setUrl($router->urlForRoute('questions_add'))->setOrder('2');
        $menuItem->setIconClass('ow_ic_add');

        $menuItems[] = $menuItem;

        $menuItem = new BASE_MenuItem();
        $menuItem->setKey('qst_editAccountType')->setLabel($language->text('base', 'questions_menu_editAccountType'))->setUrl($router->urlForRoute('questions_edit_account_type'))->setOrder('3');
        $menuItem->setIconClass('ow_ic_edit');

        $menuItems[] = $menuItem;

        $menuItem = new BASE_MenuItem();
        $menuItem->setKey('qst_settings')->setLabel($language->text('base', 'questions_menu_settings'))->setUrl($router->urlForRoute('questions_settings'))->setOrder('4');
        $menuItem->setIconClass('ow_ic_gear_wheel');

        $menuItems[] = $menuItem;

        $this->contentMenu = new BASE_CMP_ContentMenu($menuItems);

        $this->addComponent('contentMenu', $this->contentMenu);
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
            case 'DeleteQuestionValue':

                $result = false;

                $questionName = htmlspecialchars($_POST["questionName"]);

                if ( $questionName === null )
                {
                    echo json_encode(array('result' => $result));
                    return;
                }

                $questionName = trim($questionName);
                $value = (int) $_POST["value"];

                if ( $this->questionService->deleteQuestionValue($questionName, $value) )
                {
                    $result = true;
                }

                echo json_encode(array('result' => $result));

                break;

            case 'submit_add_values_form':

                $questionId = (int) $_POST["questionId"];
                $addQuestionValuesForm = new AddValuesForm($questionId);
                $addQuestionValuesForm->isValid($_POST);
                $addQuestionValuesForm->process();

                break;

            case 'sortAccountType':

                $sortAccountType = json_decode($_POST['accountTypeList'], true);

                $result = false;

                if ( isset($sortAccountType) && is_array($sortAccountType) && count($sortAccountType) > 0 )
                {
                    $result = $this->questionService->reOrderAccountType($sortAccountType);
                }

                echo json_encode(array('result' => $result));

                break;

            case 'sortQuestions':

                $sectionName = htmlspecialchars($_POST['sectionName']);
                $sectionQuestionOrder = json_decode($_POST['questionOrder'], true);

                $check = true;

                if ( !isset($sectionName) )
                {
                    $check = false;
                }

                if ( !isset($sectionQuestionOrder) || !is_array($sectionQuestionOrder) || !count($sectionQuestionOrder) > 0 )
                {
                    $check = false;
                }

                if ( $sectionName === 'no_section' )
                {
                    $sectionName = null;
                }

                $result = false;
                if ( $check )
                {
                    $result = $this->questionService->reOrderQuestion($sectionName, $sectionQuestionOrder);
                }

                echo json_encode(array('result' => $result));

                break;

            case 'sortSection':

                $sectionOrder = json_decode($_POST['sectionOrder'], true);

                if ( !isset($sectionOrder) || !is_array($sectionOrder) || !count($sectionOrder) > 0 )
                {
                    return false;
                }

                $result = $this->questionService->reOrderSection($sectionOrder);

                echo json_encode(array('result' => $result));

                break;

            default:
        }
        exit;
    }

    public function deleteQuestion( $params )
    {
        $question = $this->questionService->findQuestionById($params['questionId']);

        if ( $question->base == 1 || !$question->removable )
        {
            throw new Redirect404Exception();
        }

        if ( !empty($params['questionId']) && (int) $params['questionId'] > 0 )
        {
            if ( $this->questionService->deleteQuestion(array((int) $params['questionId'])) )
            {
                OW::getFeedback()->info(OW::getLanguage()->text('admin', 'questions_question_was_deleted'));
            }
        }
        else
        {
            throw new Redirect404Exception();
        }

        $this->redirect(OW::getRouter()->urlFor('ADMIN_CTRL_Questions', 'index'));
    }

    public function deleteSection( $params )
    {
        if ( !empty($params['sectionName']) && mb_strlen($params['sectionName']) > 0 )
        {
            if ( $this->questionService->deleteSection(htmlspecialchars($params['sectionName'])) )
            {
                OW::getFeedback()->info(OW::getLanguage()->text('admin', 'questions_section_was_deleted'));
            }
        }
        else
        {
            throw new Redirect404Exception();
        }

        $this->redirect(OW::getRouter()->urlFor('ADMIN_CTRL_Questions', 'index'));
    }

    public function deleteAccountType( $params )
    {
        if ( !empty($params['accountType']) && mb_strlen($params['accountType']) > 0 )
        {
            if ( $this->questionService->deleteAccountType(htmlspecialchars($params['accountType'])) )
            {
                OW::getFeedback()->info(OW::getLanguage()->text('admin', 'questions_account_type_was_deleted'));
            }
        }
        else
        {
            throw new Redirect404Exception();
        }

        $this->redirect(OW::getRouter()->urlFor('ADMIN_CTRL_Questions', 'editAccountType'));
    }
}

class AddValuesForm extends Form
{
    private $questionId;

    /**
     * Class constructor
     *
     */
    public function __construct( $questionId )
    {
        $this->questionId = (int) $questionId;

        $language = OW::getLanguage();

        parent::__construct('add_qst_values_form');
        $this->setId('add_qst_values_form');
        $this->setAjax();

        $qstCommand = new HiddenField('command');
        $qstCommand->setValue('submit_add_values_form');
        $this->addElement($qstCommand);

        $qstQuestionId = new HiddenField('questionId');
        $qstQuestionId->setValue((int) $questionId);
        $this->addElement($qstQuestionId);

        $qstValues = new Textarea('qst_add_values');
        $qstValues->addAttribute('class', $qstValues->getName());
        $qstValues->setLabel($language->text('admin', 'questions_add_values_label'));
        $qstValues->setDescription($language->text('admin', 'questions_add_values_description'));
        $qstValues->setRequired();
        $this->addElement($qstValues);

        $qstValuesSubmit = new Submit('add_qst_submit');
        $qstValuesSubmit->addAttribute('class', 'ow_button ow_ic_save');
        $qstValuesSubmit->setValue($language->text('admin', 'questions_add_values_submit_button'));
        $this->addElement($qstValuesSubmit);

        if ( !OW::getRequest()->isAjax() )
        {
            $js = " owForms['add_qst_values_form'].bind( 'success',
            function( json )
            {
                $('#add_qst_values_form input[name=questionId]').val('" . $questionId . "');
                $('#add_qst_values_form input[name=command]').val('submit_add_values_form');
            	if( json.result == true )
            	{
            	    window.addValueBox.close();
                    
            	    OW.info(json.notice);
                    window.editQuestion.displayAddValues( json.values );
                }
                else
                {
                    OW.error(json.error);
                }

           } ); ";

            OW::getDocument()->addOnloadScript($js);
        }
    }

    public function process()
    {
        $addValuesCount = 0;
        $data = $this->getValues();
        $questionServise = BOL_QuestionService::getInstance();

        $question = $questionServise->findQuestionById($this->questionId);
        $language = OW::getLanguage();

        if ( $question === null )
        {
            echo json_encode(array('result' => false, 'error' => $language->text('admin', 'questions_question_is_not_exist')));
            return;
        }

        $addValues = array();

        //add question values
        if ( isset($data['qst_add_values']) && mb_strlen(trim($data['qst_add_values'])) > 0 && in_array( $question->type,  array('select', 'multiselect') ) )
        {
            $valuesArray = preg_split('/\\n/', htmlspecialchars(trim($data['qst_add_values'])));

            $questionValues = array();
            $index = 0;

            foreach ( $valuesArray as $value )
            {
                $trimVal = trim($value);

                if ( isset($trimVal) && mb_strlen($trimVal) > 0 )
                {
                    $questionValues[$index] = $trimVal;
                    $index++;
                }
            }

            if ( !empty($questionValues) )
            {
                $existsQuestionValues = array();
                $lastQuestionOrder = 0;

                foreach ( $questionServise->findQuestionValues($question->name) as $key => $value )
                {
                    if ( $lastQuestionOrder < $value->sortOrder )
                    {
                        $lastQuestionOrder = $value->sortOrder;
                    }

                    $existsQuestionValues[$value->value] = $value->sortOrder;
                }

                for ( $key = 0; $key < 31; $key++ )
                {
                    if ( !isset($questionValues[$addValuesCount]) )
                    {
                        break;
                    }

                    $valueId = pow(2, $key);

                    $questionValues[$addValuesCount] = trim($questionValues[$addValuesCount]);

                    if ( isset($existsQuestionValues[$valueId]) )
                    {
                        continue;
                    }

                    $questionValue = new BOL_QuestionValue();
                    $questionValue->questionName = $question->name;
                    $questionValue->sortOrder = ++$lastQuestionOrder;
                    $questionValue->value = $valueId;

                    $questionServise->saveOrUpdateQuestionValue($questionValue);

                    $serviceLang = BOL_LanguageService::getInstance();
                    $currentLanguageId = OW::getLanguage()->getCurrentId();
                    $serviceLang->addValue($currentLanguageId, 'base', 'questions_question_' . ($question->name) . '_value_' . $valueId, $questionValues[$addValuesCount]);

                    $addValues[$valueId] = $questionValues[$addValuesCount];
                    $addValuesCount++;
                }
            }
        }

        echo json_encode(array('result' => true, 'notice' => $language->text('admin', 'questions_add_question_values_message', array('count' => $addValuesCount)), 'values' => $addValues));
    }
}

class AddNewAccountTypeForm extends Form
{
    /**
     * Class constructor
     *
     */
    private $questionId;

    public function __construct()
    {
        $language = OW::getLanguage();

        parent::__construct('add_account_type_form');
        $this->setId('add_account_type_form');

        $account_type = new TextField('account_type');
        $account_type->setRequired();
        $this->addElement($account_type);

        $account_type_submit = new Submit('account_type_submit');
        $account_type_submit->addAttribute('class', 'ow_button');
        $account_type_submit->setValue($language->text('admin', 'questions_add_new_account_type'));
        $this->addElement($account_type_submit);
    }

    public function process()
    {
        $data = $this->getValues();

        $language = OW::getLanguage();

        if ( isset($data['account_type']) && mb_strlen(trim($data['account_type'])) > 0 )
        {
            $value = htmlspecialchars(trim($data['account_type']));

            $name = md5(uniqid());

            $account_type = new BOL_QuestionAccountType();

            $account_type->name = $name;
            $account_type->sortOrder = (BOL_QuestionService::getInstance()->findLastAccountTypeOrder()) + 1;

            BOL_QuestionService::getInstance()->saveOrUpdateAccountType($account_type);

            $serviceLang = BOL_LanguageService::getInstance();
            $currentLanguageId = OW::getLanguage()->getCurrentId();
            $serviceLang->addValue($currentLanguageId, 'base', 'questions_account_type_' . ($name), $value);

            OW::getFeedback()->info($language->text('admin', 'questions_account_type_was_added'));

            return true;
        }

        OW::getFeedback()->error($language->text('admin', 'questions_account_type_added_error'));
        return false;
    }
}