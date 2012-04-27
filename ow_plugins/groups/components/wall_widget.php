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
 * Group Wall Widget
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.groups.components
 * @since 1.0
 */
class GROUPS_CMP_WallWidget extends BASE_CLASS_Widget
{

    /**
     * @return Constructor.
     */
    public function __construct( BASE_CLASS_WidgetParameter $paramObj )
    {
        parent::__construct();

        $params = $paramObj->customParamList;

        $commentParams = new BASE_CommentsParams('groups', GROUPS_BOL_Service::ENTITY_TYPE_WAL);

        $groupId = (int) $paramObj->additionalParamList['entityId'];
        $commentParams->setEntityId($groupId);

        if ( isset($params['comments_count']) )
        {
            $commentParams->setCommentCountOnPage($params['comments_count']);
        }

        if ( isset($params['display_mode']) )
        {
            $commentParams->setDisplayType($params['display_mode']);
        }

        $isMember = GROUPS_BOL_Service::getInstance()->findUser($groupId, OW::getUser()->getId()) !== null;
        $commentParams->setAddComment($isMember);

        $this->addComponent('comments', new BASE_CMP_Comments($commentParams));
    }

    public static function getSettingList()
    {
        $settingList = array();
        $settingList['comments_count'] = array(
            'presentation' => self::PRESENTATION_SELECT,
            'label' => OW::getLanguage()->text('base', 'cmp_widget_wall_comments_count'),
            'optionList' => array('3' => 3, '5' => 5, '10' => 10, '20' => 20, '50' => 50, '100' => 100),
            'value' => 10
        );

        $settingList['display_mode'] = array(
            'presentation' => self::PRESENTATION_SELECT,
            'label' => OW::getLanguage()->text('base', 'cmp_widget_wall_comments_mode'),
            'optionList' => array(
                '1' => OW::getLanguage()->text('base', 'cmp_widget_wall_comments_mode_option_1'),
                '2' => OW::getLanguage()->text('base', 'cmp_widget_wall_comments_mode_option_2')
            ),
            'value' => 2
        );

        return $settingList;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_SHOW_TITLE => false,
            self::SETTING_ICON => self::ICON_COMMENT,
            self::SETTING_TITLE => OW::getLanguage()->text('groups', 'wall_widget_label'),
            self::SETTING_WRAP_IN_BOX => false
        );
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }
}