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
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_system_plugins.base.components
 * @since 1.0
 */
class BASE_CMP_UserViewWidget extends BASE_CLASS_Widget
{
    const USER_VIEW_PRESENTATION_TABS = 'tabs';

    const USER_VIEW_PRESENTATION_TABLE = 'table';

    /**
     * @return Constructor.
     */
    public function __construct( BASE_CLASS_WidgetParameter $params )
    {
        parent::__construct();

        $userId = $params->additionalParamList['entityId'];

        $viewerId = OW::getUser()->getId();

        $ownerMode = false;
        if ( $userId == $viewerId )
        {
            $ownerMode = true;
        }

        $questionService = BOL_QuestionService::getInstance();

        $user = BOL_UserService::getInstance()->findUserById($userId);

        $accountType = $user->accountType;

        $language = OW::getLanguage();

        $questions = $questionService->findViewQuestionsForAccountType($accountType);

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

        $questionData = $questionService->getQuestionData(array($userId), $questionNameList);

        $questionValues = $questionService->findQuestionsValuesByQuestionNameList($questionNameList);

        //add form fields
        foreach ( $questionArray as $sectionKey => $section )
        {
            foreach ( $section as $questionKey => $question )
            {
                if ( !empty($questionData[$userId][$question['name']]) )
                {
                    switch ( $question['presentation'] )
                    {
                        case BOL_QuestionService::QUESTION_PRESENTATION_CHECKBOX:

                            if ( (int) $questionData[$userId][$question['name']] === 1 )
                            {
                                $questionData[$userId][$question['name']] = $language->text('base', 'questions_checkbox_value_true');
                            }
                            else
                            {
                                unset($questionArray[$sectionKey][$questionKey]);
                            }

                            break;

                        case BOL_QuestionService::QUESTION_PRESENTATION_DATE:

                            $format = OW::getConfig()->getValue('base', 'date_field_format');

                            $value = 0;

                            switch ( $question['type'] )
                            {
                                case BOL_QuestionService::QUESTION_VALUE_TYPE_DATETIME:

                                    $date = UTIL_DateTime::parseDate($questionData[$userId][$question['name']], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);

                                    if ( isset($date) )
                                    {
                                        $format = OW::getConfig()->getValue('base', 'date_field_format');
                                        $value = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
                                    }

                                    break;

                                case BOL_QuestionService::QUESTION_VALUE_TYPE_SELECT:

                                    $value = (int)$questionData[$userId][$question['name']];

                                    break;
                            }

                            if ( $format === 'dmy' )
                            {
                                $questionData[$userId][$question['name']] = date("d/m/Y",$value) ;
                            }
                            else
                            {
                                $questionData[$userId][$question['name']] = date("m/d/Y", $value);
                            }

                            break;

                        case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE:

                            $date = UTIL_DateTime::parseDate($questionData[$userId][$question['name']], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
                            $questionData[$userId][$question['name']] = UTIL_DateTime::formatBirthdate($date['year'], $date['month'], $date['day']);

                            break;

                        case BOL_QuestionService::QUESTION_PRESENTATION_AGE:

                            $date = UTIL_DateTime::parseDate($questionData[$userId][$question['name']], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
                            $questionData[$userId][$question['name']] = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']) . " " . $language->text('base', 'questions_age_year_old');

                            break;

                        case BOL_QuestionService::QUESTION_PRESENTATION_SELECT:
                        case BOL_QuestionService::QUESTION_PRESENTATION_RADIO:
                        case BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX:

                            $value = "";
                            $multicheckboxValue = (int) $questionData[$userId][$question['name']];

                            $questionValues = BOL_QuestionService::getInstance()->findQuestionValues($question['name']);

                            foreach( $questionValues as $val )
                            {

                                /* @var $val BOL_QuestionValue */

                                if ( ( (int) $val->value ) & $multicheckboxValue )
                                {
                                    if ( strlen($value) > 0 )
                                    {
                                        $value .= ', ';
                                    }

                                    $value .= $language->text('base', 'questions_question_' . $question['name'] . '_value_' . ($val->value));
                                }
                            }

                            if ( strlen($value) > 0 )
                            {
                                $questionData[$userId][$question['name']] = $value;
                            }
                            else
                            {
                                unset($questionArray[$sectionKey][$questionKey]);
                            }

                            break;

                        case BOL_QuestionService::QUESTION_PRESENTATION_URL:
                        case BOL_QuestionService::QUESTION_PRESENTATION_TEXT:
                        case BOL_QuestionService::QUESTION_PRESENTATION_TEXTAREA:

                            $value = trim($questionData[$userId][$question['name']]);

                            if ( strlen($value) > 0 )
                            {
                                $questionData[$userId][$question['name']] = UTIL_HtmlTag::autoLink(nl2br($value));
                            }
                            else
                            {
                                unset($questionArray[$sectionKey]);
                            }

                            break;

                        default:
                            unset($questionArray[$sectionKey][$questionKey]);
                    }
                }
                else
                {
                    unset($questionArray[$sectionKey][$questionKey]);
                }
            }

            if ( count($questionArray[$sectionKey]) === 0 )
            {
                unset($questionArray[$sectionKey]);
            }
        }

        $sections = array_keys($questionArray);

        $template = OW::getPluginManager()->getPlugin('base')->getViewDir() . 'components' . DS . 'user_view_widget_table.html';

        $userViewPresntation = OW::getConfig()->getValue('base', 'user_view_presentation');

        if ( $userViewPresntation === self::USER_VIEW_PRESENTATION_TABS )
        {
            $template = OW::getPluginManager()->getPlugin('base')->getViewDir() . 'components' . DS . 'user_view_widget_tabs.html';

            OW::getDocument()->addOnloadScript(" view = new UserViewWidget(); ");

            $jsDir = OW::getPluginManager()->getPlugin("base")->getStaticJsUrl();
            OW::getDocument()->addScript($jsDir . "user_view_widget.js");

            $this->addMenu($sections);
        }

        $this->setTemplate($template);

        $accountTypes = $questionService->findAllAccountTypes();

        if ( !isset($sections[0]) )
        {
            $sections[0] = 0;
        }

        if ( count($accountTypes) > 1 )
        {
            if ( !isset($questionArray[$sections[0]]) )
            {
                $questionArray[$sections[0]] = array();
            }

            array_unshift($questionArray[$sections[0]], array('name' => 'accountType', 'presentation' => 'select'));
            $questionData[$userId]['accountType'] = $questionService->getAccountTypeLang($accountType);
        }

        if ( !isset($questionData[$userId]) )
        {
            $questionData[$userId] = array();
        }

        $this->assign('firstSection', $sections[0]);
        $this->assign('questionArray', $questionArray);
        $this->assign('questionData', $questionData[$userId]);
        $this->assign('ownerMode', $ownerMode);
        $this->assign('profileEditUrl', OW::getRouter()->urlForRoute('base_edit'));
    }

    public static function getStandardSettingValueList()
    {
        $language = OW::getLanguage();
        return array(
            self::SETTING_SHOW_TITLE => false,
            self::SETTING_WRAP_IN_BOX => false,
            self::SETTING_TITLE => $language->text('base', 'view_index'),
            self::SETTING_FREEZE => true
        );
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }

    public function addMenu( $sections )
    {
        $menuItems = array();

        foreach ( $sections as $key => $section )
        {
            $item = new BASE_MenuItem();

            $item->setLabel(BOL_QuestionService::getInstance()->getSectionLang($section))
                ->setKey($section)
                ->setUrl('javascript://')
                ->setPrefix('menu')
                ->setOrder($key);

            if ( $key == 0 )
            {
                $item->setActive(true);
            }

            $menuItems[] = $item;
            $script = '$(\'li.menu_' . $section . '\').click(function(){view.showSection(\'' . $section . '\');});';
            OW::getDocument()->addOnloadScript($script);
        }

        return $this->addComponent('menu', new BASE_CMP_ContentMenu($menuItems));
    }
}