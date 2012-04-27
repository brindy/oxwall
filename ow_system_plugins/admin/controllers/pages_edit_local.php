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
 * @author Aybat Duyshokov <duyshokov@gmail.com>
 * @package ow_system_plugins.base.controller
 * @since 1.0
 */
class ADMIN_CTRL_PagesEditLocal extends ADMIN_CTRL_Abstract
{

    public function __construct()
    {
        parent::__construct();

        $this->setPageHeading(OW::getLanguage()->text('admin', 'pages_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_gear_wheel');
        OW::getDocument()->getMasterPage()->getMenu(OW_Navigation::ADMIN_PAGES)->getElement('sidebar_menu_item_pages_manage')->setActive(true);
    }

    public function index( $params )
    {
        $id = (int) $params['id'];

        $this->assign('id', $id);

        $menu = BOL_NavigationService::getInstance()->findMenuItemById($id);

        $navigationService = BOL_NavigationService::getInstance();

        $document = $navigationService->findDocumentByKey($menu->getDocumentKey());

        if( $document === null )
        {
            $document = new BOL_Document();
            $document->setKey($menu->getDocumentKey());
            $document->setIsStatic(true);
        }
        
        $service = BOL_NavigationService::getInstance();

        $form = new EditLocalPageForm('edit-form', $menu);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $data = $form->getValues();
//--
            $visibleFor = 0;
            $arr = !empty($data['visible-for']) ? $data['visible-for'] : array();
            foreach ( $arr as $val )
            {
                $visibleFor += $val;
            }

            $service->saveMenuItem(
                $menu->setVisibleFor($visibleFor)
            );

            $uri = str_replace(OW::getRouter()->getBaseUrl(), '', UTIL_String::removeFirstAndLastSlashes($data['url']));
            $document->setUri($uri);

            $navigationService->saveDocument($document);

            $languageService = BOL_LanguageService::getInstance();

            $plugin = OW::getPluginManager()->getPlugin('base');

//- name
            $langKey = $languageService->findKey(
                    $plugin->getKey(), $menu->getKey()
            );
            if ( !empty($langKey) )
            {
                $langValue = $languageService->findValue($languageService->getCurrent()->getId(), $langKey->getId());

                if ( $langValue === null )
                {
                    $langValue = new BOL_LanguageValue();
                    $langValue->setKeyId($langKey->getId());
                    $langValue->setLanguageId($languageService->getCurrent()->getId());
                }

                $languageService->saveValue(
                    $langValue->setValue($data['name'])
                );
            }
//- title

            $langKey = $languageService->findKey(
                    $plugin->getKey(), 'local_page_title_' . $menu->getKey()
            );


            if ( !empty($langKey) )
            {
                $langValue = $languageService->findValue(
                        $languageService->getCurrent()->getId(), $langKey->getId()
                );

                if ( $langValue === null )
                {
                    $langValue = new BOL_LanguageValue();
                    $langValue->setKeyId($langKey->getId());
                    $langValue->setLanguageId($languageService->getCurrent()->getId());
                }

                $languageService->saveValue(
                    $langValue->setValue($data['title'])
                );
            }
//- meta tags

            $langKey = $languageService->findKey(
                    $plugin->getKey(), 'local_page_meta_tags_' . $menu->getKey());
            if ( !empty($langKey) )
            {
                $langValue = $languageService->findValue($languageService->getCurrent()->getId(), $langKey->getId());

                if ( $langValue === null )
                {
                    $langValue = new BOL_LanguageValue();
                    $langValue->setKeyId($langKey->getId());
                    $langValue->setLanguageId($languageService->getCurrent()->getId());
                }

                $languageService->saveValue(
                    $langValue->setValue($data['meta-tags'])
                );
            }

//- content

            $langKey = $languageService->findKey(
                    $plugin->getKey(), 'local_page_content_' . $menu->getKey()
            );

            if ( !empty($langKey) )
            {
                $langValue = $languageService->findValue($languageService->getCurrent()->getId(), $langKey->getId());

                if ( $langValue === null )
                {
                    $langValue = new BOL_LanguageValue();
                    $langValue->setKeyId($langKey->getId());
                    $langValue->setLanguageId($languageService->getCurrent()->getId());
                }

                $languageService->saveValue(
                    $langValue->setValue($data['content'])
                );
            }

//~
            $languageService->generateCache($languageService->getCurrent()->getId());

            $adminPlugin = OW::getPluginManager()->getPlugin('admin');

            OW::getFeedback()->info(OW::getLanguage()->text($adminPlugin->getKey(), 'updated_msg'));

            $this->redirect();

//--    		
        }

        $this->addForm($form, $menu);
    }

    public function delete( $params )
    {
        $id = 0;

        if ( empty($params['id'])
            || ( $id = (int) $params['id'] ) <= 0
        )
        {
            exit();
        }

        $menu = BOL_NavigationService::getInstance()->findMenuItemById($id);

        $navigationService = BOL_NavigationService::getInstance();

        $document = $navigationService->findDocumentByKey($menu->getDocumentKey());

        $navigationService->deleteDocument($document);

        $languageService = BOL_LanguageService::getInstance();

        $navigationService->deleteMenuItem($menu);

        $langKey = $languageService->findKey($menu->getPrefix(), $menu->getKey());
        $languageService->deleteKey($langKey->getId());

        $langKey = $languageService->findKey('base', 'local_page_meta_tags_' . $document->getKey());
        if ( $langKey !== null )
        {
            $languageService->deleteKey($langKey->getId());
        }

        $langKey = $languageService->findKey('base', 'local_page_title_' . $document->getKey());
        if ( $langKey !== null )
        {
            $languageService->deleteKey($langKey->getId());
        }

        $langKey = $languageService->findKey('base', 'local_page_content_' . $document->getKey());
        if ( $langKey !== null )
        {
            $languageService->deleteKey($langKey->getId());
        }

        $this->redirect(OW::getRouter()->urlForRoute('admin_pages_main'));
    }
}

class EditLocalPageForm extends Form
{

    public function __construct( $name, BOL_MenuItem $menu )
    {
        parent::__construct($name);

        $navigationService = BOL_NavigationService::getInstance();

        $document = $navigationService->findDocumentByKey($menu->getDocumentKey());

        if( $document === null )
        {
            $document = new BOL_Document();
            $document->setKey($menu->getDocumentKey());
        }
        
        $language = OW_Language::getInstance();

        $plugin = OW::getPluginManager()->getPlugin('base');
        $adminPlugin = OW::getPluginManager()->getPlugin('admin');

        $nameTextField = new TextField('name');

        $this->addElement(
            $nameTextField->setValue($language->text($plugin->getKey(), $menu->getKey()))
                ->setLabel(OW::getLanguage()->text('admin', 'pages_edit_local_menu_name'))
                ->setRequired()
        );

        $titleTextField = new TextField('title');

        $this->addElement(
            $titleTextField->setValue($language->text($plugin->getKey(), 'local_page_title_' . $menu->getKey()))
                ->setLabel(OW::getLanguage()->text('admin', 'pages_edit_local_page_title'))
                ->setRequired(true)
        );

        $urlTextField = new TextField('url');

        $this->addElement(
            $urlTextField->setValue(OW::getRouter()->getBaseUrl() . $document->getUri())
                ->setLabel(OW::getLanguage()->text('admin', 'pages_edit_local_page_url'))
                ->setRequired(true)
        );

        $visibleForCheckboxGroup = new CheckboxGroup('visible-for');

        $visibleFor = $menu->getVisibleFor();

        $options = array(
            '1' => OW::getLanguage()->text('admin', 'pages_edit_visible_for_guests'),
            '2' => OW::getLanguage()->text('admin', 'pages_edit_visible_for_members')
        );

        $values = array();

        foreach ( $options as $value => $option )
        {
            if ( !($value & $visibleFor) )
                continue;

            $values[] = $value;
        }

        $this->addElement(
            $visibleForCheckboxGroup->setOptions($options)
                ->setValue($values)
                ->setLabel(OW::getLanguage()->text('admin', 'pages_edit_local_visible_for'))
        );

        $metaTagsTextarea = new Textarea('meta-tags');

        $this->addElement(
            $metaTagsTextarea->setLabel('Page meta tags')
                ->setValue($language->text($plugin->getKey(), 'local_page_meta_tags_' . $menu->getKey()))
                ->setDescription(OW::getLanguage()->text('admin', 'pages_page_field_meta_desc'))
                ->setId('meta-tags')
        );

        $contentTextArea = new Textarea('content');

        $contentTextArea->setDescription(
            OW::getLanguage()->text('admin', 'pages_page_field_content_desc', array(
                'src' => OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'question.png',
                'url' => '#'
                )
            )
        );

        $this->addElement(
            $contentTextArea->setLabel(OW::getLanguage()->text('admin', 'pages_edit_local_page_content'))
                ->setValue($language->text($plugin->getKey(), 'local_page_content_' . $menu->getKey()))
                ->setId('content')
        );


        $saveSubmit = new Submit('save');

        $this->addElement(
            $saveSubmit->setValue($language->text($adminPlugin->getKey(), 'save_btn_label'))
        );
    }
}