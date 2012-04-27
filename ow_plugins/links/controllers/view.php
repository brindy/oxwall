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
 * @package ow_plugins.links.controllers
 * @since 1.0
 */
class LINKS_CTRL_View extends OW_ActionController
{

    public function index( $params )
    {
        /**
         * @var $pl OW_ActionController
         */
        $plugin = OW::getPluginManager()->getPlugin('links');

        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, $plugin->getKey(), 'main_menu_item');

        if ( !OW::getUser()->isAuthorized('links', 'view') )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');

            return;
        }

        $id = $params['id'];

        $service = LinkService::getInstance();
        $userService = BOL_UserService::getInstance();

        $link = $service->findById($id);

        if ( $link === null )
        {
            throw new Redirect404Exception();
        }
        
        $link->setUrl(strip_tags($link->getUrl()));
        $link->setTitle(strip_tags($link->getTitle()));
        $link->setDescription(BASE_CMP_TextFormatter::fromBBtoHtml($link->getDescription()));
       

        if ( (OW::getUser()->isAuthenticated() && $link->getUserId() != OW::getUser()->getId() ) && !OW::getUser()->isAuthorized('links', 'view') )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');

            return;
        }

        /* Check privacy permissions */
        if ( $link->userId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('links') )
        {
            $eventParams = array(
                'action' => LinkService::PRIVACY_ACTION_VIEW_LINKS,
                'ownerId' => $link->userId,
                'viewerId' => OW::getUser()->getId()
            );

            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        /* */

        $this->setPageHeading(htmlspecialchars($link->getTitle()));
        $this->setPageHeadingIconClass('ow_ic_link');
    
        OW::getDocument()->setTitle(OW::getLanguage()->text('links', 'link_title', array('link_title' => htmlspecialchars($link->getTitle()), 'link_url' => $link->getUrl())));
        OW::getDocument()->setDescription(
            OW::getLanguage()->text('links', 'link_description', array('link_title' => htmlspecialchars($link->getTitle()),
                'link_description' => htmlspecialchars(strip_tags($link->getDescription()))
                )
            )
        );


        $this->assign('info', array('dto' => $link,
            'link' => mb_ereg_replace('http(s)?:\\/\\/', '', $link->getUrl())));

        $userId = OW::getUser()->getId();

        $user = (!empty($userId) && intval($userId) > 0 ) ? $userService->findUserById($userId) : null;

        $this->assign('user', $user);
        $this->assign('userInfo', array(
            'displayName' => $userService->getDisplayName($link->getUserId()),
            'userName' => $userService->getUserName($link->getUserId()),
        ));

        $this->assign('isModerator', OW::getUser()->isAuthorized('links'));

        $tb = array();
        if ( OW::getUser()->isAuthenticated() )
        {
            $tb[] = array(
                'href' => 'javascript://',
                'click' => "flag('link', {$link->getId()}, " . htmlspecialchars(json_encode($link->getTitle())) . ", location.href, 'links+flags')",
                'label' => OW::getLanguage()->text('base', 'flag')
            );

            if ( OW::getUser()->isAuthenticated() )
            {
                $isModerator = BOL_AuthorizationService::getInstance()->isModerator(OW::getUser()->getId());
                $isGroupAssignedToModerator = BOL_AuthorizationService::getInstance()->isActionAuthorizedForUser(OW::getUser()->getId(), 'links');

                $isOwner = OW::getUser()->getId() == $link->getUserId();
            }

            if ( OW::getUser()->isAuthenticated() && ( $isOwner || OW::getUser()->isAdmin() || ( $isModerator && $isGroupAssignedToModerator ) ) )
            {

                $tb[] = array(
                    'href' => OW::getRouter()->urlForRoute('link-save-edit', array('id' => $link->getId())),
                    'label' => OW::getLanguage()->text('links', 'toolbar_edit')
                );

                $tb[] = array(
                    'href' => OW::getRouter()->urlFor('LINKS_CTRL_Save', 'delete', array('id' => $link->getId())),
                    'click' => "return confirm('" . OW::getLanguage()->text('base', 'are_you_sure') . "')",
                    'label' => OW::getLanguage()->text('links', 'toolbar_delete')
                );
            }
        }

        $this->assign('tb', $tb);

        /* Check comments privacy permissions */
        $allow_comments = true;
        if ( $link->userId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('links') )
        {
            $eventParams = array(
                'action' => LinkService::PRIVACY_ACTION_COMMENT_LINKS,
                'ownerId' => $link->userId,
                'viewerId' => OW::getUser()->getId()
            );

            try
            {
                OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
            }
            catch ( RedirectException $ex )
            {
                $allow_comments = false;
            }
        }
        /* */

        $cmpParams = new BASE_CommentsParams('links', 'link');
        $cmpParams->setEntityId($link->getId())
            ->setOwnerId($link->getUserId())
            ->setDisplayType(1)
            ->setAddComment($allow_comments);

        $this->addComponent('comments', new BASE_CMP_Comments($cmpParams));

        $tags = BOL_TagService::getInstance()->findEntityTagsWithPopularity($link->getId(), 'link');

        $tags = $tags !== null ? $tags : array();

        $this->addComponent('tagCloud', new BASE_CMP_TagCloud($tags, OW::getRouter()->urlForRoute('links-by-tag')));
    }
}

?>