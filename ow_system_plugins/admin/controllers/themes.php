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
 * Themes manage admin controller class.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.admin.controllers
 * @since 1.0
 */
class ADMIN_CTRL_Themes extends ADMIN_CTRL_Abstract
{
    /**
     * @var BOL_ThemeService
     */
    private $themeService;

    /**
     * @var BASE_CMP_ContentMenu
     */
    private $menu;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->themeService = BOL_ThemeService::getInstance();
        $this->setDefaultAction('chooseTheme');
    }

    public function init()
    {
        $router = OW_Router::getInstance();

        $pageActions = array('choose_theme', 'add_theme');

        $menuItems = array();

        foreach ( $pageActions as $key => $item )
        {
            $menuItem = new BASE_MenuItem();
            $menuItem->setKey($item)->setLabel(OW::getLanguage()->text('admin', 'themes_menu_item_' . $item))->setOrder($key)->setUrl($router->urlFor(__CLASS__, $item));
            $menuItems[] = $menuItem;
        }

        $this->menu = new BASE_CMP_ContentMenu($menuItems);

        $this->addComponent('contentMenu', $this->menu);

        $this->setPageHeading(OW::getLanguage()->text('admin', 'themes_choose_page_title'));
    }

    public function chooseTheme()
    {
        $this->themeService->updateThemeList();
        $themes = $this->themeService->findAllThemes();
        $themesInfo = array();

        $activeTheme = OW::getThemeManager()->getSelectedTheme()->getDto()->getName();

        /* @var $theme BOL_Theme */
        foreach ( $themes as $theme )
        {
            $themesInfo[$theme->getName()] = (array) json_decode($theme->getDescription());
            $themesInfo[$theme->getName()]['key'] = $theme->getName();
            $themesInfo[$theme->getName()]['title'] = $theme->getTitle();
            $themesInfo[$theme->getName()]['iconUrl'] = $this->themeService->getStaticUrl($theme->getName()) . BOL_ThemeService::ICON_FILE;
            $themesInfo[$theme->getName()]['previewUrl'] = $this->themeService->getStaticUrl($theme->getName()) . BOL_ThemeService::PREVIEW_FILE;
            $themesInfo[$theme->getName()]['active'] = ( $theme->getName() === $activeTheme );
            $themesInfo[$theme->getName()]['changeUrl'] = OW::getRouter()->urlFor(__CLASS__, 'changeTheme', array('theme' => $theme->getName()));
        }

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('admin')->getStaticJsUrl() . 'theme_select.js');

        OW::getDocument()->addOnloadScript(
            "window.owThemes = new ThemesSelect(" . json_encode($themesInfo) . ");
        	$('.selected_theme_info input.theme_select_submit').click(function(){
    			window.location.href = '" . $themesInfo[$activeTheme]['changeUrl'] . "';
    		});
        	"
        );

        $this->assign('themeInfo', $themesInfo[$activeTheme]);
        $this->assign('themes', $themesInfo);
    }

    public function addTheme()
    {
        $this->checkXP();

        OW::getNavigation()->activateMenuItem(OW_Navigation::ADMIN_PLUGINS, 'admin', 'sidebar_menu_themes_add');
        $this->setPageHeading(OW::getLanguage()->text('admin', 'themes_add_theme_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_monitor');

        $language = OW::getLanguage();

        $form = new Form('theme-add');
        $form->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);
        $file = new FileField('file');
        $form->addElement($file);

        $submit = new Submit('submit');
        $submit->setValue($language->text('admin', 'plugins_manage_add_submit_label'));
        $form->addElement($submit);

        $this->addForm($form);

        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                $data = $form->getValues();

                if ( empty($_FILES['file']) || $_FILES['file']['error'] > 0 || !is_uploaded_file($_FILES['file']['tmp_name']) )
                {
                    OW::getFeedback()->error($language->text('admin', 'manage_plugins_add_empty_field_error_message'));
                    $this->redirect();
                }

                if ( $_FILES['file']['size'] > 50000000 )
                {
                    OW::getFeedback()->error($language->text('admin', 'manage_plugins_add_size_error_message'));
                    $this->redirect();
                }

                $tempFile = OW_DIR_PLUGINFILES . 'ow' . DS . uniqid('theme_add') . '.zip';
                $tempDir = OW_DIR_PLUGINFILES . 'ow' . DS . uniqid('theme_add') . DS;

                copy($_FILES['file']['tmp_name'], $tempFile);
                
                $zip = new ZipArchive();

                if ( $zip->open($tempFile) === true )
                {
                    $zip->extractTo($tempDir);
                    $zip->close();
                }
                else
                {
                    OW::getFeedback()->error(OW::getLanguage()->text('admin', 'manage_theme_add_extract_error'));
                    $this->redirectToAction();
                }
                
                unlink($tempFile);
                $this->redirect(OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor(__CLASS__, 'processAdd'), array('dir' => urlencode($tempDir))));
            }
        }
    }

    public function processAdd()
    {
        $this->checkXP();
        $language = OW::getLanguage();

        if ( empty($_GET['dir']) || !file_exists(urldecode($_GET['dir'])) )
        {
            OW::getFeedback()->error($language->text('admin', 'manage_plugins_add_ftp_move_error'));
            $this->redirectToAction('add');
        }

        $tempDir = urldecode($_GET['dir']);
        $handle = opendir($tempDir);

        if ( $handle !== false )
        {
            while ( ($item = readdir($handle)) !== false )
            {
                if ( $item === '.' || $item === '..' )
                {
                    continue;
                }

                $innerDir = $item;
            }

            closedir($handle);
        }

        if ( !empty($innerDir) && file_exists($tempDir . $innerDir . DS . 'theme.xml') )
        {
            $localDir = $tempDir . $innerDir . DS;
        }
        else
        {
            OW::getFeedback()->error(OW::getLanguage()->text('admin', 'theme_add_extract_error'));
            $this->redirectToAction('addTheme');
        }

        if( file_exists(OW_DIR_THEME . $innerDir) )
        {
            OW::getFeedback()->error(OW::getLanguage()->text('admin', 'theme_add_duplicated_dir_error', array('dir' => $innerDir)));
            $this->redirectToAction('addTheme');
        }
        
        $ftp = $this->getFtpConnection();
        $ftp->uploadDir($localDir, OW_DIR_THEME . $innerDir);
        UTIL_File::removeDir($tempDir);
        OW::getFeedback()->info($language->text('base', 'themes_item_add_success_message'));
        $this->redirectToAction('chooseTheme');
    }

    public function changeTheme( $params )
    {
        OW::getConfig()->saveConfig('base', 'selectedTheme', trim($params['theme']));
        OW::getFeedback()->info(OW::getLanguage()->text('admin', 'theme_change_success_message'));
        $this->redirect(OW::getRouter()->uriForRoute('admin_themes_choose'));
    }

    private function checkXP()
    {
        if ( defined('OW_PLUGIN_XP') )
        {
            throw new Redirect404Exception();
        }
    }

    /**
     * Returns ftp connection.
     * 
     * @return UTIL_Ftp 
     */
    private function getFtpConnection()
    {
        try
        {
            $ftp = BOL_PluginService::getInstance()->getFtpConnection();
        }
        catch ( LogicException $e )
        {
            OW::getFeedback()->error($e->getMessage());
            $this->redirect(OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('ADMIN_CTRL_Plugins', 'ftpAttrs'), array('back_uri' => urlencode(OW::getRequest()->getRequestUri()))));
        }

        return $ftp;
    }
}
