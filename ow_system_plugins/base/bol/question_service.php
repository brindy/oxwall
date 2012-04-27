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
 * Question Service
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
class BOL_QuestionService
{
    /* account types */
    const ALL_ACCOUNT_TYPES = 'all';

    /* langs */
    const QUESTION_LANG_PREFIX = 'base';

    const LANG_KEY_TYPE_QUESTION_LABEL = 'label';
    const LANG_KEY_TYPE_QUESTION_DESCRIPTION = 'description';
    const LANG_KEY_TYPE_QUESTION_SECTION = 'section';
    const LANG_KEY_TYPE_QUESTION_VALUE = 'value';
    const LANG_KEY_TYPE_ACCOUNT_TYPE = 'account_type';

    /* date field display formats */
    const DATE_FIELD_FORMAT_MONTH_DAY_YEAR = 'mdy';
    const DATE_FIELD_FORMAT_DAY_MONTH_YEAR = 'dmy';

    /* field store types */
    const QUESTION_VALUE_TYPE_TEXT = 'text';
    const QUESTION_VALUE_TYPE_SELECT = 'select';
    const QUESTION_VALUE_TYPE_MULTISELECT = 'multiselect';
    const QUESTION_VALUE_TYPE_DATETIME = 'datetime';
    const QUESTION_VALUE_TYPE_BOOLEAN = 'boolean';

    /* field presentation types */
    const QUESTION_PRESENTATION_TEXT = 'text';
    const QUESTION_PRESENTATION_TEXTAREA = 'textarea';
    const QUESTION_PRESENTATION_SELECT = 'select';
    const QUESTION_PRESENTATION_DATE = 'date';
    const QUESTION_PRESENTATION_BIRTHDATE = 'birthdate';
    const QUESTION_PRESENTATION_AGE = 'age';
    const QUESTION_PRESENTATION_LOCATION = 'location';
    const QUESTION_PRESENTATION_CHECKBOX = 'checkbox';
    const QUESTION_PRESENTATION_MULTICHECKBOX = 'multicheckbox';
    const QUESTION_PRESENTATION_RADIO = 'radio';
    const QUESTION_PRESENTATION_URL = 'url';
    const QUESTION_PRESENTATION_PASSWORD = 'password';

    /* field presentation configs */
    const QUESTION_CONFIG_DATE_RANGE = 'dateRange';

    /**
     * @var BOL_QuestionDao
     */
    private $questionDao;
    /**
     * @var BOL_QuestionValueDao
     */
    private $valueDao;
    /**
     * @var BOL_QuestionSectionDao
     */
    private $sectionDao;
    /**
     * @var BOL_QuestionDataDao
     */
    private $dataDao;
    /**
     * @var BOL_QuestionAccountTypeDao
     */
    private $accountDao;
    /**
     * @var BOL_UserService
     */
    private $userService;
    /**
     * @var BOL_QuestionConfigDao
     */
    private $questionConfigDao;
    /**
     * @var array
     */
    private $presentations;
    private $questionsBOL = array();
    private $questionsData = array();
    private $presentation2config = array();
    /**
     * Singleton instance.
     *
     * @var BOL_QuestionService
     */
    private static $classInstance;

    /**
     * Constructor.
     *
     */
    private function __construct()
    {
        $this->questionsBOL['base'] = array();
        $this->questionsBOL['notBase'] = array();

        $this->questionDao = BOL_QuestionDao::getInstance();
        $this->valueDao = BOL_QuestionValueDao::getInstance();
        $this->dataDao = BOL_QuestionDataDao::getInstance();
        $this->sectionDao = BOL_QuestionSectionDao::getInstance();
        $this->accountDao = BOL_QuestionAccountTypeDao::getInstance();
        $this->userService = BOL_UserService::getInstance();
        $this->questionConfigDao = BOL_QuestionConfigDao::getInstance();

        // all available presentations are hardcoded here
        $this->presentations = array(
            self::QUESTION_PRESENTATION_TEXT => self::QUESTION_VALUE_TYPE_TEXT,
            self::QUESTION_PRESENTATION_SELECT => self::QUESTION_VALUE_TYPE_SELECT,
            self::QUESTION_PRESENTATION_TEXTAREA => self::QUESTION_VALUE_TYPE_TEXT,
            self::QUESTION_PRESENTATION_CHECKBOX => self::QUESTION_VALUE_TYPE_BOOLEAN,
            self::QUESTION_PRESENTATION_RADIO => self::QUESTION_VALUE_TYPE_SELECT,
            self::QUESTION_PRESENTATION_MULTICHECKBOX => self::QUESTION_VALUE_TYPE_MULTISELECT,
            self::QUESTION_PRESENTATION_DATE => self::QUESTION_VALUE_TYPE_DATETIME,
            self::QUESTION_PRESENTATION_BIRTHDATE => self::QUESTION_VALUE_TYPE_DATETIME,
            self::QUESTION_PRESENTATION_AGE => self::QUESTION_VALUE_TYPE_DATETIME,
            self::QUESTION_PRESENTATION_URL => self::QUESTION_VALUE_TYPE_TEXT,
            self::QUESTION_PRESENTATION_PASSWORD => self::QUESTION_VALUE_TYPE_TEXT
        );
    }

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return BOL_QuestionService
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
            self::$classInstance = new self();

        return self::$classInstance;
    }

    /**
     * Returns all available presentations
     *
     * @return array<string>
     */
    public function getPresentations()
    {
        return $this->presentations;
    }

    /**
     * Returns configs list
     *
     * @return array<string>
     */
    public function getConfigList( $presentation )
    {
        if ( !isset($this->presentation2config[$presentation]) )
        {
            $this->presentation2config[$presentation] = $this->questionConfigDao->getConfigListByPresentation($presentation);
        }

        return $this->presentation2config[$presentation];
    }

    public function getAllConfigs()
    {
        $result = $this->questionConfigDao->getAllConfigs();

        foreach ( $result as $item )
        {
            $this->presentation2config[$item->questionPresentation] = $item;
        }

        return $this->presentation2config[$presentation];
    }

    /**
     * Returns all available presentations
     *
     * @return array<string>
     */
    public function getPresentationClass( $presentation, $fieldName, $configs = null )
    {
        $class = null;

        switch ( $presentation )
        {
            case self::QUESTION_PRESENTATION_TEXT :
                $class = new TextField($fieldName);
                break;

            case self::QUESTION_PRESENTATION_SELECT :
                $class = new Selectbox($fieldName);
                break;

            case self::QUESTION_PRESENTATION_TEXTAREA :
                $class = new Textarea($fieldName);
                break;

            case self::QUESTION_PRESENTATION_CHECKBOX :
                $class = new CheckboxField($fieldName);
                break;

            case self::QUESTION_PRESENTATION_RADIO :
                $class = new RadioField($fieldName);
                break;

            case self::QUESTION_PRESENTATION_MULTICHECKBOX :
                $class = new CheckboxGroup($fieldName);
                break;

            case self::QUESTION_PRESENTATION_BIRTHDATE :
            case self::QUESTION_PRESENTATION_AGE :
            case self::QUESTION_PRESENTATION_DATE :
                $class = new DateField($fieldName);

                if ( !empty($configs) && mb_strlen( trim($configs) ) > 0 )
                {
                    $configsList = json_decode($configs, true);
                    foreach ( $configsList as $name => $value )
                    {
                        if ( $name = 'year_range' && isset($value['from']) && isset($value['to']) )
                        {
                            $class->setMinYear($value['from']);
                            $class->setMaxYear($value['to']);
                        }
                    }
                }

                $class->addValidator(new DateValidator($class->getMinYear(), $class->getMaxYear()));
                break;

            case self::QUESTION_PRESENTATION_URL :
                $class = new TextField($fieldName);
                $class->addValidator(new UrlValidator());
                break;

            case self::QUESTION_PRESENTATION_PASSWORD :
                $class = new PasswordField($fieldName);
                break;
        }        

        return $class;
    }

    /**
     * Returns all available presentations
     *
     * @return array<string>
     */
    public function getSearchPresentationClass( $presentation, $fieldName, $configs = array() )
    {
        $class = null;

        switch ( $presentation )
        {
            case self::QUESTION_PRESENTATION_TEXT :
            case self::QUESTION_PRESENTATION_TEXTAREA :
                $class = new TextField($fieldName);
                break;

            case self::QUESTION_PRESENTATION_CHECKBOX :
                $class = new CheckboxField($fieldName);
                break;

            case self::QUESTION_PRESENTATION_RADIO :
            case self::QUESTION_PRESENTATION_SELECT :
            case self::QUESTION_PRESENTATION_MULTICHECKBOX :
                $class = new CheckboxGroup($fieldName);
                break;

            case self::QUESTION_PRESENTATION_BIRTHDATE :
            case self::QUESTION_PRESENTATION_AGE :
                $class = new AgeRange($fieldName);
                break;

            case self::QUESTION_PRESENTATION_DATE :
                $class = new DateRange($fieldName);
                break;

            case self::QUESTION_PRESENTATION_URL :
                $class = new TextField($fieldName);
                $class->addValidator(new UrlValidator());
                break;
        }

        if ( !empty($configs) && mb_strlen( trim($configs) ) > 0 )
        {
            $configsList = json_decode($configs, true);
            foreach ( $configsList as $name => $value )
            {
                if ( $name = 'year_range' && isset($value['from']) && isset($value['to']) )
                {
                    $class->setMinYear($value['from']);
                    $class->setMaxYear($value['to']);
                }
            }
        }

        return $class;
    }

    public function findAllQuestions()
    {
        return $this->questionDao->findAll();
    }

    /**
     * Returns fields for provided account type.
     * Method is used in admin panel.
     *
     * @param string $accountType
     * @return array
     */
    public function findAllQuestionsWithSectionForAccountType( $accountType )
    {
        return $this->questionDao->findAllQuestionsWithSectionForAccountType($accountType);
    }

    /**
     *
     * @param string $accountType
     * @param boolean $baseOnly
     */
    public function findSignUpQuestionsForAccountType( $accountType, $baseOnly = false )
    {
        return $this->questionDao->findSignUpQuestionsForAccountType($accountType, $baseOnly);
    }

    public function findBaseSignUpQuestions()
    {
        return $this->questionDao->findBaseSignUpQuestions();
    }

    public function findEditQuestionsForAccountType( $accountType )
    {
        return $this->questionDao->findEditQuestionsForAccountType($accountType);
    }

    public function findViewQuestionsForAccountType( $accountType )
    {
        return $this->questionDao->findViewQuestionsForAccountType($accountType);
    }

    /**
     * Returns fields values for provided account type.
     * Method is used in frontend cmps and forms.
     *
     * @param array $questionsNameList
     */
    public function findQuestionsValuesByQuestionNameList( array $questionsNameList )
    {
        return $this->valueDao->findQuestionsValuesByQuestionNameList($questionsNameList);
    }

    /**
     * Returns field by name.
     *
     * @param string $name
     */
    public function findQuestionById( $id )
    {
        return $this->questionDao->findById($id);
    }

    /**
     * Returns field by name.
     *
     * @param string $name
     * @return BOL_Question
     */
    public function findQuestionByName( $questionName )
    {
        return $this->questionDao->findQuestionByName($questionName);
    }

    /**
     * Returns fields list.
     *
     * @param array $questionNameList
     * @return array <BOL_Question>
     */
    public function findQuestionByNameList( $questionNameList )
    {
        return $this->questionDao->findQuestionByNameList($questionNameList);
    }

    public function findQuestionListByPresentationList( $presentation )
    {
        $questions = $this->questionDao->findQuestionsByPresentationList($presentation);

        $result = array();

        foreach ( $questions as $question )
        {
            $result[$question->name] = $question;
        }

        return $result;
    }

    /**
     * Saves/updates <BOL_Question> objects.
     *
     * @param BOL_Question $field
     */
    public function saveOrUpdateQuestion( BOL_Question $question, $label = null, $description = null )
    {
        $this->questionDao->save($question);
    }

    public function setQuestionLabel( $questionName, $label )
    {
        if ( empty($questionName) )
        {
            throw new InvalidArgumentException('invalid questionName');
        }

        $serviceLang = BOL_LanguageService::getInstance();

        $currentLanguageId = OW::getLanguage()->getCurrentId();

        $nameKey = $serviceLang->findKey(self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_LABEL, $questionName));
        
        if ( $nameKey !== null )
        {
            $serviceLang->deleteKey($nameKey->id);
        }

        $serviceLang->addValue($currentLanguageId, self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_LABEL, $questionName), empty($label) ? ' ' : $label );
    }

    public function setQuestionDescription( $questionName, $description )
    {
        if ( empty($questionName) )
        {
            throw new InvalidArgumentException('invalid questionName');
        }

        $serviceLang = BOL_LanguageService::getInstance();

        $currentLanguageId = OW::getLanguage()->getCurrentId();

        $descriptionKey = $serviceLang->findKey(self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_DESCRIPTION, $questionName));

        if ( $descriptionKey !== null )
        {
            $serviceLang->deleteKey($descriptionKey->id);
        }

        $serviceLang->addValue($currentLanguageId, self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_DESCRIPTION, $questionName), empty($description) ? ' ' : $description);
    }

    /**
     * Saves/updates <BOL_QuestionValue> objects.
     *
     * @param BOL_QuestionValue $value
     */
    public function saveOrUpdateQuestionValue( BOL_QuestionValue $value )
    {
        $this->valueDao->save($value);
    }

    public function findQuestionValues( $questionName )
    {
        return $this->valueDao->findQuestionValues($questionName);
    }

    public function findQuestionValue( $questionId, $value )
    {
        return $this->valueDao->findQuestionValue($questionId, $value);
    }

    public function deleteQuestionValue( $questionName, $value )
    {
        if ( $questionName === null )
        {
            return;
        }

        $name = trim($questionName);
        $valueId = (int) $value;

        $isDelete = $this->valueDao->deleteQuestionValue($name, $valueId);

        if ( $isDelete )
        {
            $serviceLang = BOL_LanguageService::getInstance();
            $key = $serviceLang->findKey('base', 'questions_question_' . $name . '_value_' . $valueId);

            if ( $key !== null )
            {
                $serviceLang->deleteKey($key->id);
            }
        }

        return $isDelete;
    }

    public function saveOrUpdateAccountType( BOL_QuestionAccountType $value )
    {
        $this->accountDao->save($value);
    }

    public function deleteAccountType( $accountType )
    {
        if ( !isset($accountType) )
        {
            return false;
        }

        $accountTypeName = trim($accountType);
        $account = null;
        $repleaceToAccount = null;
        $prevKey = null;

        $accounts = $this->accountDao->findAll();

        if ( count($accounts) <= 1 )
        {
            return false;
        }

        foreach ( $accounts as $key => $value )
        {
            if ( $repleaceToAccount === null && $account !== null )
            {
                $repleaceToAccount = $accounts[$key];
            }

            if ( $accountTypeName === $value->name )
            {
                $account = $value;
                if ( $prevKey !== null )
                {
                    $repleaceToAccount = $accounts[$prevKey];
                }
            }

            $prevKey = $key;
        }

        if ( $account === null )
        {
            return false;
        }

        $questions = $this->questionDao->findQuestionsForAccountType($account->name);
        $questionIdList = array();

        foreach ( $questions as $key => $value )
        {
            $questionIdList[$value['name']] = $value['id'];
        }

        $this->deleteQuestion($questionIdList);

        $this->userService->replaceAccountTypeForUsers($account->name, $repleaceToAccount->name);

        $key = BOL_LanguageService::getInstance()->findKey(self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_ACCOUNT_TYPE, $account->name));

        if ( $key !== null )
        {
            BOL_LanguageService::getInstance()->deleteKey($key->id);
        }

        $this->accountDao->deleteById($account->id);

        return (boolean) OW::getDbo()->getAffectedRows();
    }

    public function deleteSection( $sectionName )
    {
        if ( $sectionName === null || mb_strlen($sectionName) === 0 )
        {
            return false;
        }

        $section = $this->sectionDao->findBySectionName($sectionName);

        if ( $section !== null )
        {
            $nextSection = $this->sectionDao->findPreviousSection($section->sortOrder);

            if ( $nextSection === null )
            {
                $nextSection = $this->sectionDao->findNextSection($section->sortOrder);
            }
        }
        else
        {
            return false;
        }

        $questions = $this->questionDao->findQuestionsBySectionNameList(array($sectionName));
        $nextSectionName = isset($nextSection) ? $nextSection->name : null;

        $lastOrder = $this->questionDao->findLastQuestionOrder($nextSectionName);

        if ( $lastOrder === null )
        {
            $lastOrder = 0;
        }

        foreach ( $questions as $key => $question )
        {
            $questions[$key]->sectionName = $nextSectionName;
            $questions[$key]->sortOrder = ++$lastOrder;
        }

        if ( count($questions) > 0 )
        {
            $this->questionDao->batchReplace($questions);
        }

        $key = BOL_LanguageService::getInstance()->findKey(self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_SECTION, $sectionName));
        if ( $key !== null )
        {
            BOL_LanguageService::getInstance()->deleteKey($key->id);
        }

        $this->sectionDao->deleteById($section->id);

        return true;
    }

    public function deleteQuestion( array $questionIdList )
    {
        if ( $questionIdList === null || count($questionIdList) == 0 )
        {
            return false;
        }

        $questionArray = $this->questionDao->findByIdList($questionIdList);

        $questionsNameList = array();
        $questionValueIdList = array();

        foreach ( $questionArray as $question )
        {
            if ( (int) $question->base === 1 || (int) $question->removable === 0 )
            {
                continue;
            }

            $questionsNameList[] = $question->name;

            $values = $this->valueDao->findQuestionValues($question->id);

            foreach ( $values as $value )
            {
                $questionValueIdList[] = $value->id;

                $key = BOL_LanguageService::getInstance()->findKey(self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_VALUE, $question->name, $value->id));

                if ( $key !== null )
                {
                    BOL_LanguageService::getInstance()->deleteKey($key->id);
                }
            }

            $key = BOL_LanguageService::getInstance()->findKey(self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_LABEL, $question->name));

            if ( $key !== null )
            {
                BOL_LanguageService::getInstance()->deleteKey($key->id);
            }

            $key = BOL_LanguageService::getInstance()->findKey(self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_DESCRIPTION, $question->name));

            if ( $key !== null )
            {
                BOL_LanguageService::getInstance()->deleteKey($key->id);
            }
        }

        $this->valueDao->deleteByIdList($questionValueIdList);

        $this->dataDao->deleteByQuestionNamesList($questionsNameList);

        $this->questionDao->deleteByIdList($questionIdList);

        return (boolean) OW::getDbo()->getAffectedRows();
    }

    public function saveOrUpdateSection( BOL_QuestionSection $value )
    {
        $this->sectionDao->save($value);
    }

    /**
     * Finds all account types.
     *
     * @return array<BOL_QuestionAccountType>
     */
    public function findAllAccountTypes()
    {
        return $this->accountDao->findAllAccountTypes();
    }

    public function findAllAccountTypesWithQuestionsCount()
    {
        return $this->accountDao->findAllAccountTypesWithQuestionsCount();
    }

    public function findLastAccountTypeOrder()
    {
        $value = $this->accountDao->findLastAccountTypeOrder();

        if ( $value === null )
        {
            $value = 0;
        }

        return $value;
    }

    /**
     * Finds all sections.
     *
     * @return array<BOL_QuestionSection>
     */
    public function findAllSections()
    {
        return $this->sectionDao->findAll();
    }

    public function findSortedSectionList()
    {
        return $this->sectionDao->findSortedSectionList();
    }

    /**
     * Finds all sections.
     *
     * @return array<BOL_QuestionSection>
     */
    public function findSectionBySectionName( $sectionName )
    {
        return $this->sectionDao->findBySectionName($sectionName);
    }

    public function findLastSectionOrder()
    {
        $value = $this->sectionDao->findLastSectionOrder();

        if ( $value === null )
        {
            $value = 0;
        }

        return $value;
    }

    public function findLastQuestionOrder( $sectionName = null )
    {
        $value = $this->questionDao->findLastQuestionOrder($sectionName);

        if ( $value === null )
        {
            $value = 0;
        }

        return $value;
    }

    /**
     * Save questions data.
     *
     * @param array $data
     * @param int $userId
     */
    public function saveQuestionsData( array $data, $userId )
    {
        if ( $data === null || !is_array($data) )
        {
            return false;
        }

        $user = null;
        if ( (int) $userId > 0 )
        {
            $user = $this->userService->findUserById($userId);

            if ( $user === null )
            {
                return false;
            }
        }
        else
        {
            return false;
        }
        
        $oldUserEmail = $user->email;

        $dataFields = array_keys($data);

        $questions = $this->questionDao->findQuestionsByQuestionNameList($dataFields);
        $questionsData = $this->dataDao->findByQuestionsNameList($dataFields, $userId);

        $questionsUserData = array();
        
        foreach ( $questionsData as $questionData )
        {
            $questionsUserData[$questionData->questionName] = $questionData;
        }

        $questionDataArray = array();

        foreach ( $questions as $key => $question )
        {
            if ( isset($data[$question->name]) )
            {
                switch ( $question->type )
                {
                    case self::QUESTION_VALUE_TYPE_TEXT:

                        $value = $question->presentation !== self::QUESTION_PRESENTATION_PASSWORD ? $this->questionTextFormatter(trim($data[$question->name])) : BOL_UserService::getInstance()->hashPassword($data[$question->name]);
                        
                        if ( (int) $question->base === 1 && in_array($question->name, $dataFields) )
                        {
                            $property = new ReflectionProperty('BOL_User', $question->name);
                            $property->setValue($user, $value);
                        }
                        else
                        {
                            if ( isset($questionsUserData[$question->name]) )
                            {
                                $questionData = $questionsUserData[$question->name];
                            }
                            else
                            {
                                $questionData = new BOL_QuestionData();
                                $questionData->userId = $userId;
                                $questionData->questionName = $question->name;
                            }

                            $questionData->textValue = $value;

                            if ( $question->presentation === self::QUESTION_PRESENTATION_URL && !empty($value) )
                            {
                                $questionData->textValue = $this->urlFilter($value);
                            }

                            $questionDataArray[] = $questionData;
                            //$this->dataDao->save($questionData);
                        }

                        break;

                    case self::QUESTION_VALUE_TYPE_DATETIME:

                        $date = UTIL_DateTime::parseDate($data[$question->name]);

                        if ( isset($date) )
                        {
                            if ( UTIL_Validator::isDateValid($date[UTIL_DateTime::PARSE_DATE_MONTH], $date[UTIL_DateTime::PARSE_DATE_DAY], $date[UTIL_DateTime::PARSE_DATE_YEAR]) )
                            {
                                $value = $date[UTIL_DateTime::PARSE_DATE_YEAR] . '-' . $date[UTIL_DateTime::PARSE_DATE_MONTH] . '-' . $date[UTIL_DateTime::PARSE_DATE_DAY];

                                if ( (int) $question->base === 1 && in_array($question->name, $dataFields) )
                                {
                                    $property = new ReflectionProperty('BOL_User', $question->name);
                                    $property->setValue($user, $value);
                                }
                                else
                                {
                                    if ( isset($questionsUserData[$question->name]) )
                                    {
                                        $questionData = $questionsUserData[$question->name];
                                    }
                                    else
                                    {
                                        $questionData = new BOL_QuestionData();
                                        $questionData->userId = $userId;
                                        $questionData->questionName = $question->name;
                                    }

                                    $questionData->dateValue = $value;

                                    $questionDataArray[] = $questionData;
                                }
                            }
                        }

                        break;
                        
                    case self::QUESTION_VALUE_TYPE_MULTISELECT:
                    case self::QUESTION_VALUE_TYPE_SELECT:

                        $value = (int) $data[$question->name];

                        if ( (int) $question->base === 1 && in_array($question->name, $dataFields) )
                        {
                            $property = new ReflectionProperty('BOL_User', $question->name);
                            $property->setValue($user, $value);
                        }
                        else
                        {
                            if ( isset($questionsUserData[$question->name]) )
                            {
                                $questionData = $questionsUserData[$question->name];
                            }
                            else
                            {
                                $questionData = new BOL_QuestionData();
                                $questionData->userId = $userId;
                                $questionData->questionName = $question->name;
                            }

                            $questionData->intValue = $value;

                            $questionDataArray[] = $questionData;
                            //$this->dataDao->save($questionData);
                        }

                        break;

                    case self::QUESTION_VALUE_TYPE_BOOLEAN:

                        $value = false;

                        $issetValues = array('1', 'true', 'on');

                        if ( in_array(mb_strtolower((string) $data[$question->name]), $issetValues) )
                        {
                            $value = true;
                        }

                        if ( (int) $question->base === 1 && in_array($question->name, $dataFields) )
                        {
                            $property = new ReflectionProperty('BOL_User', $question->name);
                            $property->setValue($user, $value);
                        }
                        else
                        {
                            if ( isset($questionsUserData[$question->name]) )
                            {
                                $questionData = $questionsUserData[$question->name];
                            }
                            else
                            {
                                $questionData = new BOL_QuestionData();
                                $questionData->userId = $userId;
                                $questionData->questionName = $question->name;
                            }

                            $questionData->intValue = $value;

                            $questionDataArray[] = $questionData;
                            //$this->dataDao->save($questionData);
                        }

                        break;
                }
            }
        }

        $sendVerifyMail = false;

        if ( $user->id !== null )
        {
            if ( strtolower($user->email) !== strtolower($oldUserEmail) )
            {
                $user->emailVerify = false;
                $sendVerifyMail = true;
            }
        }

        $this->userService->saveOrUpdate($user);

        if ( count($questionDataArray) > 0 )
        {
            $this->dataDao->batchReplace($questionDataArray);
        }

        if ( $sendVerifyMail && OW::getConfig()->getValue('base', 'confirm_email') )
        {
            BOL_EmailVerifyService::getInstance()->sendUserVerificationMail($user);
        }

        return true;
    }

    /**
     * Save questions data.
     *
     * @param array $data
     */
    public function questionTextFormatter( $value )
    {
        return strip_tags($value); //TODO: check question value
    }

    public function reOrderAccountType( array $accountTypeList )
    {
        if ( $accountTypeList === null || !is_array($accountTypeList) || count($accountTypeList) === 0 )
        {
            return false;
        }

        $accountTypeNameList = array_keys($accountTypeList);

        $accountTypes = $this->accountDao->findAccountTypeByNameList($accountTypeNameList);

        foreach ( $accountTypes as $key => $accountType )
        {
            if ( isset($accountTypeList[$accountType->name]) )
            {
                $accountTypes[$key]->sortOrder = $accountTypeList[$accountType->name];
            }
        }

        return (boolean) $this->accountDao->batchReplace($accountTypes);
    }

    public function reOrderQuestion( $sectionName, array $questionOrder )
    {
        if ( $questionOrder === null || !is_array($questionOrder) || count($questionOrder) === 0 )
        {
            return false;
        }


        $section = null;

        if ( $sectionName !== null )
        {
            $section = $this->sectionDao->findBySectionName($sectionName);

            if ( $section === null )
            {
                return false;
            }

            $section = $section->name;
        }

        $questionNameList = array_keys($questionOrder);

        $questions = $this->questionDao->findQuestionsByQuestionNameList($questionNameList);

        if ( count($questionOrder) === 0 )
        {
            return false;
        }

        foreach ( $questions as $key => $question )
        {
            if ( isset($questionOrder[$question->name]) )
            {
                $questions[$key]->sortOrder = $questionOrder[$question->name];
                $questions[$key]->sectionName = $section;
            }
        }

        $result = $this->questionDao->batchReplace($questions);
        return $result;
    }

    public function reOrderSection( array $sectionOrder )
    {
        if ( $sectionOrder === null || !is_array($sectionOrder) || count($sectionOrder) === 0 )
        {
            return false;
        }

        $sectionNameList = array_keys($sectionOrder);

        $sections = $this->sectionDao->findBySectionNameList($sectionNameList);

        foreach ( $sections as $key => $section )
        {
            if ( isset($sectionOrder[$section->name]) )
            {
                $sections[$key]->sortOrder = $sectionOrder[$section->name];
            }
        }

        return $this->sectionDao->batchReplace($sections);
    }

    /**
     * Save questions data.
     *
     * @param array $data
     * @param int $userId
     */
    public function getQuestionData( array $userIdList, array $fieldsList )
    {
        if ( $userIdList === null || !is_array($userIdList) || count($userIdList) === 0 )
        {
            return array();
        }

        if ( $fieldsList === null || !is_array($fieldsList) || count($fieldsList) === 0 )
        {
            return array();
        }

        $usersBol = BOL_UserService::getInstance()->findUserListByIdList($userIdList);

        if ( $usersBol === null || count($usersBol) === 0 )
        {
            return array();
        }

        $userData = array();

        // get not cached questions
        $notCachedQuestionsData = array();

        foreach ( $userIdList as $userId )
        {
            if ( array_key_exists($userId, $this->questionsData) )
            {
                foreach ( $fieldsList as $field )
                {
                    if ( array_key_exists($field, $this->questionsData[$userId]) )
                    {
                        $userData[$userId][$field] = $this->questionsData[$userId][$field];
                    }
                    else
                    {
                        if ( !array_key_exists($field, $notCachedQuestionsData) )
                        {
                            $notCachedQuestionsData[$field] = $field;
                        }
                    }
                }
            }
            else
            {
                $userData = array();
                $notCachedQuestionsData = $fieldsList;
                break;
            }
        }

        if ( count($notCachedQuestionsData) > 0 )
        {

            // -- get questions BOL --

            $notCachedQuestions = array();
            foreach ( $notCachedQuestionsData as $field )
            {
                if ( !array_key_exists($field, $this->questionsBOL['base']) && !array_key_exists($field, $this->questionsBOL['notBase']) )
                {
                    $notCachedQuestions[$field] = $field;
                }
            }

            if ( count($notCachedQuestions) > 0 )
            {
                $questionsBolArray['base'] = array();
                $questionsBolArray['notBase'] = array();

                $questions = $this->questionDao->findQuestionsByQuestionNameList($notCachedQuestions);

                foreach ( $questions as $question )
                {
                    if ( $question->base )
                    {
                        $questionsBolArray['base'][$question->name] = $question;
                    }
                    else
                    {
                        $questionsBolArray['notBase'][$question->name] = $question;
                    }
                }

                $this->questionsBOL['base'] = array_merge($questionsBolArray['base'], $this->questionsBOL['base']);
                $this->questionsBOL['notBase'] = array_merge($questionsBolArray['notBase'], $this->questionsBOL['notBase']);
            }

            $baseFields = array_keys($this->questionsBOL['base']);
            $notBaseFields = array_keys($this->questionsBOL['notBase']);

            if ( count($notBaseFields) === 0 && count($baseFields) === 0 )
            {
                return array();
            }

            // -------------------- --

            if ( count($notBaseFields) > 0 )
            {
                //get not base question values
                $questionsData = $this->dataDao->findByQuestionsNameListForUserList($notBaseFields, $userIdList);

                if ( count($questionsData) > 0 )
                {
                    foreach ( $userIdList as $userId )
                    {
                        foreach ( $notBaseFields as $field )
                        {
                            if ( isset($questionsData[$userId][$field]) )
                            {
                                $value = null;

                                switch ( $this->questionsBOL['notBase'][$field]->type )
                                {
                                    case self::QUESTION_VALUE_TYPE_BOOLEAN :
                                    case self::QUESTION_VALUE_TYPE_SELECT :
                                    case self::QUESTION_VALUE_TYPE_MULTISELECT :
                                        $value = $questionsData[$userId][$field]->intValue;
                                        break;

                                    case self::QUESTION_VALUE_TYPE_TEXT :
                                        $value = $questionsData[$userId][$field]->textValue;
                                        break;

                                    case self::QUESTION_VALUE_TYPE_DATETIME :
                                        $value = $questionsData[$userId][$field]->dateValue;
                                        break;
                                }

                                $userData[$userId][$field] = $value;
                            }
                        }
                    }
                }
            }

            if ( count($baseFields) > 0 )
            {
                //get base question values

                $usersBolArray = array();

                foreach ( $usersBol as $userBol )
                {
                    $usersBolArray[$userBol->id] = $userBol;
                }

                foreach ( $userIdList as $userId )
                {
                    foreach ( $baseFields as $field )
                    {
                        $userData[$userId][$field] = null;

                        if ( isset($usersBolArray[$userId]->$field) )
                        {
                            $userData[$userId][$field] = $usersBolArray[$userId]->$field;
                        }
                    }
                }
            }
        }

        //cached questions data
        if ( count($userData) > 0 )
        {
            foreach ( $userData as $userId => $fields )
            {
                if ( isset($this->questionsData[$userId]) )
                {
                    $this->questionsData[$userId] = array_merge($fields, $this->questionsData[$userId]);
                }
                else
                {
                    $this->questionsData[$userId] = $fields;
                }
            }
        }

        $result = array();

        foreach ( $usersBol as $user )
        {
            $result[$user->id] = isset($userData[$user->id]) ? $userData[$user->id] : array();
        }

        return $result;
    }

    private function urlFilter( $url )
    {
        $value = $url;

        if( !empty($value) )
        {
            $pattern = '/^http(s)?:\/\//';

            if( !preg_match($pattern, $url) )
            {
                $value = 'http://'.$url;
            }
        }

        return $value;
    }

    /**
     *
     * @param string $type
     *
     * $params['name'] - question name
     * $params['value'] - question value
     * @param array $params
     */
    public function getQuestionLangKeyName( $type, $name, $value = null )
    {
        $key = null;

        switch ( $type )
        {
            case self::LANG_KEY_TYPE_QUESTION_LABEL:
                $key = 'questions_question_' . $name . '_label';
                break;

            case self::LANG_KEY_TYPE_QUESTION_DESCRIPTION:
                $key = 'questions_question_' . $name . '_description';
                break;

            case self::LANG_KEY_TYPE_QUESTION_SECTION:
                $key = 'questions_section_' . $name . '_label';
                break;

            case self::LANG_KEY_TYPE_QUESTION_VALUE:
                $key = 'questions_question_' . $name . '_value_' . $value;
                break;

            case self::LANG_KEY_TYPE_ACCOUNT_TYPE:
                $key = 'questions_account_type_' . $name;
                break;

            default:
                $key = '';
                break;
        }

        return $key;
    }

    public function getQuestionDescriptionLang( $questionName )
    {
        $prefix = self::QUESTION_LANG_PREFIX;
        $key = $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_DESCRIPTION, $questionName);

        $text = null;
        try
        {   //TODO HARD CODE - set current language
            $text = BOL_LanguageService::getInstance()->getValue(BOL_LanguageService::getInstance()->getCurrent()->getId(), $prefix, $key);
        }
        catch ( Exception $e )
        {
            return $prefix . '+' . $key;
        }

        if ( $text === null )
        {
            return "";
        }

        return $text->getValue();
    }

    public function getQuestionLang( $questionName )
    {
        return OW::getLanguage()->text(self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_LABEL, $questionName));
    }

    public function getQuestionValueLang( $questionName, $value )
    {
        return OW::getLanguage()->text(self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_VALUE, $questionName, $value));
    }

    public function getSectionLang( $sectionName )
    {
        return OW::getLanguage()->text(self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_QUESTION_SECTION, $sectionName));
    }

    public function getAccountTypeLang( $accountType )
    {
        return OW::getLanguage()->text(self::QUESTION_LANG_PREFIX, $this->getQuestionLangKeyName(self::LANG_KEY_TYPE_ACCOUNT_TYPE, $accountType));
    }

    public function deleteQuestionDataByUserId( $userId )
    {
        $this->dataDao->deleteByUserId($userId);
    }

    public function findSearchQuestionsForAccountType( $accountType )
    {
        return $this->questionDao->findSearchQuestionsForAccountType($accountType);
    }
}
