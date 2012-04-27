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
 * Video list component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.video.components
 * @since 1.0
 */
class VIDEO_CMP_VideoList extends OW_Component
{
    /**
     * @var VIDEO_BOL_ClipService 
     */
    private $clipService;

    /**
     * Class constructor
     *
     * @param string $listType
     * @param int $count
     * @param string $tag
     * @param int $userId
     */
    public function __construct( array $params )
    {
        parent::__construct();

        $listType = isset($params['type']) ? $params['type'] : '';
        $count = isset($params['count']) ? $params['count'] : 5;
        $tag = isset($params['tag']) ? $params['tag'] : '';
        $userId = isset($params['userId']) ? $params['userId'] : null;

        $this->clipService = VIDEO_BOL_ClipService::getInstance();

        $page = isset($_GET['page']) && (int) $_GET['page'] ? (int) $_GET['page'] : 1;

        $clipsPerPage = $this->clipService->getClipPerPageConfig();

        if ( $userId )
        {
            $clips = $this->clipService->findUserClipsList($userId, $page, $clipsPerPage);
            $records = $this->clipService->findUserClipsCount($userId);
        }
        else if ( strlen($tag) )
        {
            $clips = $this->clipService->findTaggedClipsList($tag, $page, $clipsPerPage);
            $records = $this->clipService->findTaggedClipsCount($tag);
        }
        else
        {
            $clips = $this->clipService->findClipsList($listType, $page, $clipsPerPage);
            $records = $this->clipService->findClipsCount($listType);
        }

        $this->assign('listType', $listType);

        if ( $clips )
        {
            $this->assign('no_content', null);

            $this->assign('clips', $clips);

            $userIds = array();
            foreach ( $clips as $clip )
            {
                if ( !in_array($clip['userId'], $userIds) )
                    array_push($userIds, $clip['userId']);
            }

            $names = BOL_UserService::getInstance()->getDisplayNamesForList($userIds);
            $this->assign('displayNames', $names);
            $usernames = BOL_UserService::getInstance()->getUserNamesForList($userIds);
            $this->assign('usernames', $usernames);

            // Paging
            $pages = (int) ceil($records / $clipsPerPage);
            $paging = new BASE_CMP_Paging($page, $pages, 10);
            $this->assign('paging', $paging->render());

            $this->assign('count', $count);
        }
        else
        {
            $this->assign('no_content', OW::getLanguage()->text('video', 'no_video_found'));
        }
    }
}