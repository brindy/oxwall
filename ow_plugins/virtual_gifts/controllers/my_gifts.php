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
 * My Gifts action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.virtual_gifts.controllers
 * @since 1.0
 */
class VIRTUALGIFTS_CTRL_MyGifts extends OW_ActionController
{
    /**
     * Default action
     */
	public function index()
	{
        $language = OW::getLanguage();
        $giftsService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        
        $page = isset($_GET['page']) && (int)$_GET['page'] ? (int)$_GET['page'] : 1;
        $perPage = $giftsService->getGiftsPerPageConfig();
        $gifts = $giftsService->findUserReceivedGifts(OW::getUser()->getId(), $page, $perPage, false);

        $toolbars = array();
        
        if ( $gifts )
        {
            $users = array();
            
            foreach ( $gifts as $gift )
            {
                if ( !in_array($gift['dto']->senderId, $users) )
                {
                    array_push($users, $gift['dto']->senderId);
                }
            }
                                    
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($users);
            $this->assign('avatars', $avatars);
            
            foreach ( $gifts as $gift )
            {
                $giftId = $gift['dto']->id;
                $senderId = $gift['dto']->senderId;
                $toolbars[$giftId][] = array(
                    'class' => 'ow_icon_control ow_ic_user',
                    'href' => isset($avatars[$senderId]['url']) ? $avatars[$senderId]['url'] : null,
                    'label' => isset($avatars[$senderId]['title']) ? $avatars[$senderId]['title'] : null,
                );
                if ( $gift['dto']->private )
                {
                    $toolbars[$giftId][] = array(
                        'title' => $language->text('virtualgifts', 'private_gift_note'),
                        'label' => $language->text('virtualgifts', 'private_gift'),
                    );
                }
                $toolbars[$giftId][] = array(
                    'label' => UTIL_DateTime::formatSimpleDate($gift['dto']->sendTimestamp)
                );
            }
        }
        
        $this->assign('gifts', $gifts);
        $this->assign('toolbars', $toolbars);
        
        $total = $giftsService->countUserReceivedGifts(OW::getUser()->getId(), false);
        $pages = (int) ceil($total / $perPage);
        
        $paging = new BASE_CMP_Paging($page, $pages, 10);
        $this->assign('paging', $paging->render());
            
        $this->setPageHeading(OW::getLanguage()->text('virtualgifts', 'my_gifts'));
        $this->setPageHeadingIconClass('ow_ic_heart');
        
        OW::getDocument()->setTitle($language->text('virtualgifts', 'meta_title_my_gifts'));
        
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'base', 'dashboard');
        
        $url = OW::getPluginManager()->getPlugin('virtualgifts')->getStaticCssUrl() . 'style.css';
        OW::getDocument()->addStyleSheet($url);
	}
}