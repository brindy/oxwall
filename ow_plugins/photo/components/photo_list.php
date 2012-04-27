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
 * Photo list component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.components
 * @since 1.0
 */
class PHOTO_CMP_PhotoList extends OW_Component
{
    /**
     * @var PHOTO_BOL_PhotoService 
     */
    private $photoService;

    /**
     * Class constructor
     *
     * @param string $listType
     * @param int $count
     * @param string $tag
     */
    public function __construct( array $params )
    {
        parent::__construct();

        $listType = $params['type'];
        $count = isset($params['count']) ? $params['count'] : 5;

        $this->photoService = PHOTO_BOL_PhotoService::getInstance();

        $page = isset($_GET['page']) && (int) $_GET['page'] ? (int) $_GET['page'] : 1;

        $config = OW::getConfig();

        $photosPerPage = $config->getValue('photo', 'photos_per_page');

        if ( isset($params['tag']) && strlen($tag = $params['tag']) )
        {
            $photos = $this->photoService->findTaggedPhotos($tag, $page, $photosPerPage);
            $records = $this->photoService->countTaggedPhotos($tag);
        }
        else
        {
            $checkPrivacy = $listType == 'latest' && !OW::getUser()->isAuthorized('photo');
            $photos = $this->photoService->findPhotoList($listType, $page, $photosPerPage, $checkPrivacy);
            $records = $this->photoService->countPhotos($listType, $checkPrivacy);
        }

        if ( $photos )
        {
            $userIds = array();
            foreach ( $photos as $photo )
            {
                if ( !in_array($photo['userId'], $userIds) )
                    array_push($userIds, $photo['userId']);
            }

            $names = BOL_UserService::getInstance()->getDisplayNamesForList($userIds);
            $this->assign('names', $names);
            $usernames = BOL_UserService::getInstance()->getUserNamesForList($userIds);
            $this->assign('usernames', $usernames);

            // Paging
            $pages = (int) ceil($records / $photosPerPage);
            $paging = new BASE_CMP_Paging($page, $pages, 10);
            $this->addComponent('paging', $paging);

            $this->assign('photos', $photos);
            $this->assign('no_content', false);
        }
        else
        {
            $this->assign('no_content', true);
        }

        $this->assign('listType', $listType);

        $this->assign('widthConfig', $config->getValue('photo', 'preview_image_width'));
        $this->assign('heightConfig', $config->getValue('photo', 'preview_image_height'));

        $this->assign('count', $count);
    }
}