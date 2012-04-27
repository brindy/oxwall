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
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow_plugins.links.components
 * @since 1.0
 */
class LINKS_CMP_LinksWidget extends BASE_CLASS_Widget
{

    public function __construct( BASE_CLASS_WidgetParameter $params )
    {
        parent::__construct();

        $service = LinkService::getInstance();

        $count = $params->customParamList['count'];

        $list = $service->findList(0, $count);

        if ( empty($list) && !OW::getUser()->isAuthorized('links', 'add') && !$params->customizeMode )
        {
            $this->setVisible(false);
            return;
        }

        $links = array();

        $toolbars = array();

        $userService = BOL_UserService::getInstance();
        $authorIdList = array();

        foreach ( $list as $dto ) /* @var dto Link */
        {
            $dto->setUrl(strip_tags($dto->getUrl()));
            $dto->setTitle(strip_tags($dto->getTitle()));
            $dto->setDescription(strip_tags($dto->getDescription()));
            $links[] = array(
                'dto' => $dto,
            );

            $idList[] = $dto->id;

            $authorIdList[] = $dto->getUserId();
        }

        $commentInfo = array();

        $this->assign('avatars', null);
        if ( !empty($idList) )
        {
            $commentInfo = BOL_CommentService::getInstance()->findCommentCountForEntityList('link', $idList);

            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($authorIdList, true, false);
            $this->assign('avatars', $avatars);

            $urls = BOL_UserService::getInstance()->getUserUrlsForList($authorIdList);
        }

        $tbars = array();
        foreach ( $list as $dto )
        {
            $tbars[$dto->getId()] = array(
                array(
                    'class' => 'ow_icon_control ow_ic_user',
                    'href' => !empty($urls[$dto->getUserId()]) ? $urls[$dto->getUserId()] : '#',
                    'label' => !empty($avatars[$dto->getUserId()]['title']) ? $avatars[$dto->getUserId()]['title'] : ''
                ),
                array(
                    'class' => 'ow_remark',
                    'label' => UTIL_DateTime::formatDate($dto->getTimestamp())
                )
            );
        }

        $this->assign('tbars', $tbars);
        $this->assign('commentInfo', $commentInfo);

        $this->assign('list', $links);
    }

    public static function getSettingList()
    {

        $options = array();

        for ( $i = 3; $i <= 10; $i++ )
        {
            $options[$i] = $i;
        }

        $settingList['count'] = array(
            'presentation' => self::PRESENTATION_SELECT,
            'label' => OW::getLanguage()->text('links', 'cmp_widget_post_count'),
            'optionList' => $options,
            'value' => 3,
        );

        return $settingList;
    }

    public static function getStandardSettingValueList()
    {
        $service = LinkService::getInstance();

        $list = array(
            self::SETTING_TITLE => OW::getLanguage()->text('links', 'links'),
            self::SETTING_ICON => 'ow_ic_link',
            self::SETTING_SHOW_TITLE => true
        );

        $list[self::SETTING_TOOLBAR] = array();

        if ( $service->countAll() && OW::getUser()->isAuthorized('links', 'add') )
        {
            array_push($list[self::SETTING_TOOLBAR], array(
                'label' => OW::getLanguage()->text('links', 'add_new'),
                'href' => OW::getRouter()->urlForRoute('link-save-new')
            ));
        }

        if ( $service->countAll() && OW::getUser()->isAuthorized('links', 'view') )
        {
            array_push($list[self::SETTING_TOOLBAR], array(
                'label' => OW::getLanguage()->text('links', 'go_to_links'),
                'href' => Ow::getRouter()->urlForRoute('links')
            ));
        }

        return $list;
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }
}

