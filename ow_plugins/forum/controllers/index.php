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
 * Forum base action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.controllers
 * @since 1.0
 */
class FORUM_CTRL_Index extends OW_ActionController
{

    /**
     * Controller's default action
     */
    public function index()
    {
        $isModerator = OW::getUser()->isAuthorized('forum');
        $viewPermissions = OW::getUser()->isAuthorized('forum', 'view');

        if ( !$viewPermissions && !$isModerator )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }

        $forumService = FORUM_BOL_ForumService::getInstance();

        $this->assign('customizeUrl', OW::getRouter()->urlForRoute('customize-default'));
        $this->assign('isModerator', $isModerator);

        $userId = OW::getUser()->getId();
        $sectionGroupList = $forumService->getSectionGroupList($userId);
                
        $authors = array();
        foreach ( $sectionGroupList as $section )
        {
            foreach ( $section['groups'] as $group )
            {
                if ( !$group['lastReply'] )
                {
                    continue;
                }
                $id = $group['lastReply']['userId'];

                if ( !in_array($id, $authors) )
                {
                    array_push($authors, $id);
                }
            }
        }
        
        $this->assign('sectionGroupList', $sectionGroupList);
        
        $userNames = BOL_UserService::getInstance()->getUserNamesForList($authors);
        $this->assign('userNames', $userNames);
        
        $displayNames = BOL_UserService::getInstance()->getDisplayNamesForList($authors);
        $this->assign('displayNames', $displayNames);

        $plugin = OW::getPluginManager()->getPlugin('forum');

        $template = $plugin->getCtrlViewDir() . 'index.html';

        $this->setTemplate($template);
        
        $this->addComponent('search', new FORUM_CMP_ForumSearch(array('scope' => 'all_forum')));

        OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'forum'));
        OW::getDocument()->setHeadingIconClass('ow_ic_forum');
    }
}
