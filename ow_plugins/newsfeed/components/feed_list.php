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
 * Feed List component
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.components
 * @since 1.0
 */
class NEWSFEED_CMP_FeedList extends OW_Component
{
    private $feed = array();
    private $sharedData = array();

    public function __construct( $actionList, $data )
    {
        parent::__construct();

        $this->feed = $actionList;

        $userIds = array();
        foreach ( $this->feed as $action )
        {
            /* @var $action NEWSFEED_CLASS_Action */
            $userIds[$action->getUserId()] = $action->getUserId();
        }
        $userIds = array_values($userIds);

        $this->sharedData['feedAutoId'] = $data['feedAutoId'];

        $this->sharedData['feedType'] = $data['feedType'];
        $this->sharedData['feedId'] = $data['feedId'];
        $this->sharedData['configs'] = OW::getConfig()->getValues('newsfeed');

        $this->sharedData['usersInfo'] = array(
            'avatars' => BOL_AvatarService::getInstance()->getAvatarsUrlList($userIds),
            'urls' => BOL_UserService::getInstance()->getUserUrlsForList($userIds),
            'names' => BOL_UserService::getInstance()->getDisplayNamesForList($userIds),
            'roleLabels' => BOL_AvatarService::getInstance()->getDataForUserAvatars($userIds, false, false, false)
        );
    }

    public function tplRenderItem( $params = array() )
    {
        $action = $this->feed[$params['action']];

        $cycle = array(
            'lastItem' => $params['lastItem'],
            'lastSection' => $params['lastSection'],
        );

        $feedItem = new NEWSFEED_CMP_FeedItem($action, $this->sharedData);

        return $feedItem->renderMarkup($cycle);
    }

    public function render()
    {
        $out = array();
        foreach ( $this->feed as $action )
        {
            /* @var $action NEWSFEED_CLASS_Action */
            $updateTime = $action->getUpdateTime();
            $sectionKey = date('dmY', $updateTime);

            if ( empty($out[$sectionKey]) )
            {
                $out[$sectionKey] = array(
                    'date' => UTIL_DateTime::formatDate($updateTime, true),
                    'list' => array()
                );
            }

            $out[$sectionKey]['list'][] = $action->getId();
        }

        $this->assign('feed', $out);

	OW_ViewRenderer::getInstance()->registerFunction('newsfeed_item', array( $this, 'tplRenderItem' ) );
        $out = parent::render();
	OW_ViewRenderer::getInstance()->unregisterFunction('newsfeed_item');

	return $out;
    }
}