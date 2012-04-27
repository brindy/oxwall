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
 * User gifts widget component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.virtual_gifts.components
 * @since 1.0
 */
class VIRTUALGIFTS_CMP_UserGiftsWidget extends BASE_CLASS_Widget
{
    private $giftService;

    /**
     * Class constructor
     */
    public function __construct( BASE_CLASS_WidgetParameter $paramObj )
    {
        parent::__construct();

        $this->giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        
        $userId = (int) $paramObj->additionalParamList['entityId'];
        $count = isset($paramObj->customParamList['giftsCount']) ? (int) $paramObj->customParamList['giftsCount'] : 4;
        
        $gifts = $this->giftService->findUserReceivedGifts($userId, 1, $count, true);
        
        if ( !$gifts )
        {
            $this->setVisible(false);
            return;
        }
        
        $this->assign('gifts', $gifts);
        $username = BOL_UserService::getInstance()->getUserName($userId);
        
        $this->setSettingValue(
            self::SETTING_TOOLBAR,
            array(
                array(
                    'label' => OW::getLanguage()->text('base', 'view_all'),
                    'href' => OW::getRouter()->urlForRoute('virtual_gifts_user_list', array('userName' => $username))
                )
            )
        );
        
        $url = OW::getPluginManager()->getPlugin('virtualgifts')->getStaticCssUrl() . 'style.css';
        OW::getDocument()->addStyleSheet($url);
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }

    public static function getSettingList()
    {
        $lang = OW::getLanguage();

        $settingList = array();

        $settingList['giftsCount'] = array(
            'presentation' => self::PRESENTATION_NUMBER,
            'label' => $lang->text('virtualgifts', 'cmp_widget_gifts_count'),
            'value' => 3
        );

        return $settingList;
    }
    
    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_TITLE => OW::getLanguage()->text('virtualgifts', 'gifts'),
            self::SETTING_ICON => self::ICON_HEART,
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_WRAP_IN_BOX => true
        );
    }
}