<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Photo list widget
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.components
 * @since 1.0
 */
class PHOTO_CMP_PhotoListWidget extends BASE_CLASS_Widget
{

    /**
     * @return Constructor.
     */
    public function __construct( BASE_CLASS_WidgetParameter $paramObj )
    {
        parent::__construct();

        $photoService = PHOTO_BOL_PhotoService::getInstance();
        $photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();

        $num = isset($paramObj->customParamList['photoCount']) ? $paramObj->customParamList['photoCount'] : 8;

        $latest = $photoService->findPhotoList('latest', 1, $num);
        $this->assign('latest', $latest);

        $featured = $photoService->findPhotoList('featured', 1, $num);
        $this->assign('featured', $featured);

        $toprated = $photoService->findPhotoList('toprated', 1, $num);
        $this->assign('toprated', $toprated);

        $lang = OW::getLanguage();

        $menuItems['latest'] = array(
            'label' => $lang->text('photo', 'menu_latest'),
            'id' => 'photo-widget-menu-latest',
            'contId' => 'photo-widget-latest',
            'active' => true
        );
        
        if ( $featured )
        {
            $menuItems['featured'] = array(
                'label' => $lang->text('photo', 'menu_featured'),
                'id' => 'photo-widget-menu-featured',
                'contId' => 'photo-widget-featured',
            );
        }
        
        $menuItems['toprated'] = array(
            'label' => $lang->text('photo', 'menu_toprated'),
            'id' => 'photo-widget-menu-top-rated',
            'contId' => 'photo-widget-top-rated',
        );
        
        if ( !$paramObj->customizeMode )
        {
            $this->addComponent('menu', new BASE_CMP_WidgetMenu($menuItems));
        }
        
        if ( !$latest && !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $this->setVisible(false);

            return;
        }
        
        $this->assign('items', $menuItems);

        $toolbars = self::getToolbar();
        $this->assign('toolbars', $toolbars);

        if ( $latest )
        {
            $this->setSettingValue(self::SETTING_TOOLBAR, $toolbars['latest']);
        }
    }

    private static function getToolbar()
    {
        $lang = OW::getLanguage();

        $items = array('latest', 'featured', 'toprated');

        $auth = OW::getUser()->isAuthorized('photo', 'upload');

        foreach ( $items as $tbItem )
        {
            if ( $auth )
            {
                $toolbars[$tbItem][] = array(
                    'href' => OW::getRouter()->urlFor('PHOTO_CTRL_Upload'),
                    'label' => $lang->text('photo', 'add_new')
                );
            }
            $toolbars[$tbItem][] = array(
                'href' => OW::getRouter()->urlForRoute('view_photo_list', array('listType' => $tbItem)),
                'label' => $lang->text('base', 'view_all')
            );
        }

        return $toolbars;
    }

    public static function getSettingList()
    {
        $settingList = array();

        $settingList['photoCount'] = array(
            'presentation' => self::PRESENTATION_NUMBER,
            'label' => OW::getLanguage()->text('photo', 'cmp_widget_photo_count'),
            'value' => 8
        );

        return $settingList;
    }

    public static function validateSettingList( $settingList )
    {
        $validationMessage = OW::getLanguage()->text('photo', 'cmp_widget_photo_count_msg');

        if ( !preg_match('/^\d+$/', $settingList['photoCount']) )
        {
            throw new WidgetSettingValidateException($validationMessage, 'photoCount');
        }
        if ( $settingList['photoCount'] > 100 )
        {
            throw new WidgetSettingValidateException($validationMessage, 'photoCount');
        }
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_TITLE => OW::getLanguage()->text('photo', 'photo_list_widget'),
            self::SETTING_ICON => self::ICON_PICTURE,
            self::SETTING_SHOW_TITLE => true
        );
    }
}