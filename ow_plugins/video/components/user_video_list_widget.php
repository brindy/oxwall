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
 * User video list widget
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.video.components
 * @since 1.0
 */
class VIDEO_CMP_UserVideoListWidget extends BASE_CLASS_Widget
{

    /**
     * @return Constructor.
     */
    public function __construct( BASE_CLASS_WidgetParameter $paramObj )
    {
        parent::__construct();

        $clipService = VIDEO_BOL_ClipService::getInstance();

        $count = isset($paramObj->customParamList['clipCount']) ? (int) $paramObj->customParamList['clipCount'] : 4;

        $userId = $paramObj->additionalParamList['entityId'];

        $showTitles = $paramObj->customParamList['showTitles'];
        $this->assign('showTitles', $showTitles);

        $clips = $clipService->findUserClipsList($userId, 1, $count);
        if ( $clips )
        {
            if ( !$showTitles )
            {
                $clips[0]['code'] = $this->prepareClipCode($clips[0]['code'], $clips[0]['provider']);
            }
            $this->assign('clips', $clips);
            $totalClips = $clipService->findUserClipsCount($userId);
            $this->assign('clipCount', $totalClips);
        }
        else
        {
            $this->assign('clips', null);
            $this->assign('clipCount', 0);
            $totalClips = 0;

            if ( !$paramObj->customizeMode )
            {
                $this->setVisible(false);
            }
        }
        
        // privacy check
        $viewerId = OW::getUser()->getId();
        $ownerMode = $userId == $viewerId;
        $modPermissions = OW::getUser()->isAuthorized('video');
        
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'video_view_video', 'ownerId' => $userId, 'viewerId' => $viewerId);
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            
            try {
                OW::getEventManager()->trigger($event);
            }
            catch ( RedirectException $e )
            {
                $this->setVisible(false);
            }
        }

        $userName = BOL_UserService::getInstance()->getUserName($userId);

        $this->assign('user', $userName);

        $lang = OW::getLanguage();

        $this->setSettingValue(self::SETTING_TOOLBAR, array(
            array('label' => $lang->text('video', 'total_video', array('total' => $totalClips))),
            array('label' => $lang->text('base', 'view_all'), 'href' => OW::getRouter()->urlForRoute('video_user_video_list', array('user' => $userName)))
        ));
    }

    public static function getSettingList()
    {
        $lang = OW::getLanguage();

        $settingList = array();

        $settingList['clipCount'] = array(
            'presentation' => self::PRESENTATION_NUMBER,
            'label' => $lang->text('video', 'cmp_widget_video_count'),
            'value' => 3
        );

        $settingList['showTitles'] = array(
            'presentation' => self::PRESENTATION_CHECKBOX,
            'label' => $lang->text('video', 'cmp_widget_user_video_show_titles'),
            'value' => true
        );

        return $settingList;
    }

    public static function validateSettingList( $settingList )
    {
        $validationMessage = OW::getLanguage()->text('video', 'cmp_widget_video_count_msg');

        if ( !preg_match('/^\d+$/', $settingList['clipCount']) )
        {
            throw new WidgetSettingValidateException($validationMessage, 'clipCount');
        }
        if ( $settingList['clipCount'] > 20 )
        {
            throw new WidgetSettingValidateException($validationMessage, 'clipCount');
        }
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_TITLE => OW::getLanguage()->text('video', 'video_list_widget'),
            self::SETTING_ICON => self::ICON_VIDEO,
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_WRAP_IN_BOX => true
        );
    }

    private function prepareClipCode( $code, $provider )
    {
        $clipService = VIDEO_BOL_ClipService::getInstance();

        $code = $clipService->validateClipCode($code, $provider);
        $code = $clipService->addCodeParam($code, 'wmode', 'transparent');

        return $code;
    }
}