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
 * @package ow_system_plugins.base.controllers
 * @since 1.0
 */
class BASE_CTRL_BaseDocument extends OW_ActionController
{

    public function index()
    {
        //TODO implement
    }

    public function alertPage()
    {
        OW::getDocument()->getMasterPage()->setTemplate(OW::getThemeManager()->getMasterPageTemplate(OW_MasterPage::TEMPLATE_BLANK));
        $this->assign('text', OW::getSession()->get('baseAlertPageMessage'));
        OW::getSession()->delete('baseMessagePageMessage');
    }

    public function confirmPage()
    {
        if ( empty($_GET['back_uri']) )
        {
            throw new Redirect404Exception();
        }

        OW::getDocument()->getMasterPage()->setTemplate(OW::getThemeManager()->getMasterPageTemplate(OW_MasterPage::TEMPLATE_BLANK));
        $this->assign('text', OW::getSession()->get('baseConfirmPageMessage'));
        OW::getSession()->delete('baseConfirmPageMessage');
        $this->assign('okBackUrl', OW::getRequest()->buildUrlQueryString(OW_URL_HOME . urldecode($_GET['back_uri']), array('confirm-result' => 1)));
        $this->assign('clBackUrl', OW::getRequest()->buildUrlQueryString(OW_URL_HOME . urldecode($_GET['back_uri']), array('confirm-result' => 0)));
    }

    public function page404()
    {
        OW::getResponse()->setHeader('HTTP/1.0', '404 Not Found');
        OW::getResponse()->setHeader('Status', '404 Not Found');
        $this->setPageHeading(OW::getLanguage()->text('base', 'base_document_404_heading'));
        $this->setPageTitle(OW::getLanguage()->text('base', 'base_document_404_title'));
        $this->setDocumentKey('base.page404');
        if ( OW::getSession()->isKeySet('base404RedirectMessage') )
        {
            $this->assign('message', OW::getSession()->get('base404RedirectMessage'));
        }
    }

    public function page403()
    {
        OW::getResponse()->setHeader('HTTP/1.0', '403 Forbidden');
        OW::getResponse()->setHeader('Status', '403 Forbidden');
        $this->setPageHeading(OW::getLanguage()->text('base', 'base_document_403_heading'));
        $this->setPageTitle(OW::getLanguage()->text('base', 'base_document_403_title'));
        $this->setDocumentKey('base.page403');
        if ( OW::getSession()->isKeySet('base403RedirectMessage') )
        {
            $this->assign('message', OW::getSession()->get('base403RedirectMessage'));
        }
    }

    public function maintenance()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->getMasterPage()->setTemplate(OW::getThemeManager()->getMasterPageTemplate('blank'));
            if ( isset($_COOKIE['isAdmin']) )
            {
                $this->assign('disableMessage', OW::getLanguage()->text('base', 'maintenance_disable_message', array('url' => OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('static_sign_in'), array('back-uri' => urlencode('admin/pages/maintenance'))))));
            }
        }
        else
        {
            exit('{}');
        }
    }

    public function splashScreen()
    {
        if ( isset($_GET['agree']) )
        {
            setcookie('splashScreen', 1, time() + 3600 * 24 * 30, '/');
            $this->redirect(OW_URL_HOME . $_GET['back_uri']);
        }

        OW::getDocument()->getMasterPage()->setTemplate(OW::getThemeManager()->getMasterPageTemplate('blank'));
        $this->assign('submit_url', OW::getRequest()->buildUrlQueryString(null, array('agree' => 1)));

        $leaveUrl = OW::getConfig()->getValue('base', 'splash_leave_url');

        if ( !empty($leaveUrl) )
        {
            $this->assign('leaveUrl', $leaveUrl);
        }
    }

    public function passwordProtection()
    {
        $form = new Form('password_protection');
        $form->setAjax(true);
        $form->setAction(OW::getRouter()->urlFor('BASE_CTRL_BaseDocument', 'passwordProtection'));
        $form->setAjaxDataType(Form::AJAX_DATA_TYPE_SCRIPT);

        $password = new PasswordField('password');
        $form->addElement($password);

        $submit = new Submit('submit');
        $submit->setValue(OW::getLanguage()->text('base', 'password_protection_submit_label'));
        $form->addElement($submit);
        $this->addForm($form);

        if ( OW::getRequest()->isAjax() && $form->isValid($_POST) )
        {
            $data = $form->getValues();
            $password = OW::getConfig()->getValue('base', 'guests_can_view_password');

            if ( !empty($data['password']) && trim($data['password']) === $password )
            {
                setcookie('base_password_protection', UTIL_String::generatePassword(), (time() + 86400 * 30), '/');
                echo "OW.info('" . OW::getLanguage()->text('base', 'password_protection_success_message') . "');window.location.reload();";
            }
            else
            {
                echo "OW.error('" . OW::getLanguage()->text('base', 'password_protection_error_message') . "');";
            }
            exit;
        }

        OW::getDocument()->getMasterPage()->setTemplate(OW::getThemeManager()->getMasterPageTemplate(OW_MasterPage::TEMPLATE_BLANK));
    }

    public function installCompleted()
    {
        if ( !empty($_GET['redirect']) )
        {
            BOL_LanguageService::getInstance(false)->generateCacheForAllActiveLanguages();
            BOL_ThemeService::getInstance()->processAllThemes();

            $this->redirect(OW::getRequest()->buildUrlQueryString(null, array('redirect' => null)));
        }

        $masterPageFileDir = OW::getThemeManager()->getMasterPageTemplate('blank');
        OW::getDocument()->getMasterPage()->setTemplate($masterPageFileDir);
    }

//    public function tos()
//    {
//        $language = OW::getLanguage();
//        $this->setPageHeading($language->text('base', 'terms_of_use_page_heading'));
//        $this->setPageTitle($language->text('base', 'terms_of_use_page_heading'));
//        $this->assign('content', $language->text('base', 'terms_of_use_page_content'));
//
//
//        $document = BOL_DocumentDao::getInstance()->findStaticDocument('terms-of-use');
//
//        if ( $document !== null )
//        {
//            $languageService = BOL_LanguageService::getInstance(false);
//            $languageId = $languageService->getCurrent()->getId();
//            $prefix = $languageService->findPrefix('base');
//
//            $key = $languageService->findKey('base', 'terms_of_use_page_heading');
//
//            if( $key === null )
//            {
//                $key = new BOL_LanguageKey();
//                $key->setKey('terms_of_use_page_heading');
//                $key->setPrefixId($prefix->getId());
//                $languageService->saveKey($key);
//            }
//
//            $value = $languageService->findValue($languageId, $key->getId());
//            $value->setValue($language->text('base', "local_page_title_{$document->getKey()}"));
//
//            $key = $languageService->findKey('base', 'terms_of_use_page_content');
//
//            if( $key === null )
//            {
//                $key = new BOL_LanguageKey();
//                $key->setKey('terms_of_use_page_content');
//                $key->setPrefixId($prefix->getId());
//                $languageService->saveKey($key);
//            }
//
//            $value = $languageService->findValue($languageId, $key->getId());
//            $value->setValue($language->text('base', "local_page_content_{$document->getKey()}"));
//
//            $key = $languageService->findKey('base', 'terms_of_use_page_meta');
//
//            if( $key === null )
//            {
//                $key = new BOL_LanguageKey();
//                $key->setKey('terms_of_use_page_meta');
//                $key->setPrefixId($prefix->getId());
//                $languageService->saveKey($key);
//            }
//
//            $value = $languageService->findValue($languageId, $key->getId());
//            $value->setValue($language->text('base', "local_page_meta_tags_{$document->getKey()}"));
//
//            $menuItem = BOL_NavigationService::getInstance()->findMenuItemByDocumentKey($document->getKey());
//
//        }
//    }
}