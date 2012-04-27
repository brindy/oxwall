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
 * Gifts action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.virtual_gifts.controllers
 * @since 1.0
 */
class VIRTUALGIFTS_CTRL_Gifts extends OW_ActionController
{    
    /**
     * Default action
     */
	public function view( array $params )
	{
        $language = OW::getLanguage();
        $giftsService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        
        if ( empty($params['giftId']) || !$giftId = (int)$params['giftId'] )
        {
            throw new Redirect404Exception();
        }
        
        $gift = $giftsService->findUserGiftById($giftId);
        
        if ( !$gift )
        {
            throw new Redirect404Exception();
        }
        
        $recipientId = $gift['dto']->recipientId;
        $viewerId = OW::getUser()->getId();
        $senderId = $gift['dto']->senderId;
            
        if ( $gift['dto']->private && !in_array($viewerId, array($recipientId, $senderId)) )
        {
            $this->assign('noPermission', $language->text('virtualgifts', 'private_gift'));
        }
        
        $senderName = BOL_UserService::getInstance()->getDisplayName($senderId);
        $this->assign('senderName', $senderName);
        $this->assign('senderUrl', BOL_UserService::getInstance()->getUserUrl($senderId));
        
        $recipientName = BOL_UserService::getInstance()->getDisplayName($recipientId);

        $isOwner = $recipientId == $viewerId;
        $this->assign('isOwner', $isOwner);
        
        $isModerator = OW::getUser()->isAuthorized('virtualgifts');
        $this->assign('canDelete', $isOwner || $isModerator);
        
        $toolbar = null;
	    if ( $gift['dto']->private )
        {
            $toolbar[] = array(
                'title' => $language->text('virtualgifts', 'private_gift_note', array('username' => $isOwner ? $senderName : $recipientName)),
                'label' => $language->text('virtualgifts', 'private_gift')
            );
        }

        $js = '';
        
        if ( $isOwner || $isModerator )
        {
            $toolbar[] = array(
                'label' => $language->text('base', 'delete'),
                'href' => 'javascript://',
                'id' => 'delete_gift_btn'
            );
            
            $js = 
            '$("#delete_gift_btn").click(function(){
                if ( confirm('.json_encode($language->text('base', 'are_you_sure')).') )
                {
                    $.ajax({
                        type: "POST",
                        url: ' . json_encode(OW::getRouter()->urlFor('VIRTUALGIFTS_CTRL_Gifts', 'ajaxDeleteGift')) . ',
                        data: "giftId=' . $giftId . '",
                        dataType: "json",
                        success : function(data){
                            if ( data.error != undefined )
                            {
                                OW.error(data.error);
                            }
                            else if ( data.message != undefined )
                            {
                                OW.info(data.message);
                                document.location.href = data.url;
                            }
                        }
                    });
                }
            });';
        }
        
        $isAuthorized = OW::getUser()->isAuthorized('virtualgifts', 'send_gift');
        
        if ( $isOwner && $isAuthorized )
        {
            $toolbar[] = array(
                'href' => 'javascript://',
                'label' => $language->text('virtualgifts', 'send_return_gift'),
                'id' => 'send_return_gift_btn'
            );
            
            $js .= 
            '$("#send_return_gift_btn").click(function(){
                sendGiftFloatBox = OW.ajaxFloatBox("VIRTUALGIFTS_CMP_SendGift", {recipientId: ' . $senderId . ' } , {width:580, iconClass: "ow_ic_heart", title: "' . OW::getLanguage()->text('virtualgifts', 'send_gift_to', array('user' => $senderName)) . '"});
            });';
        }

        $this->assign('gift', $gift);
        $this->assign('toolbar', $toolbar);
        
        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($senderId));
        
        $this->assign('senderAvatar', $avatars[$senderId]);
        
        $friends = OW::getEventManager()->call('plugin.friends.get_friend_list', array('userId' => $viewerId));
        $this->assign('sendToFriends', !empty($friends));
        
        if ( !empty($friends) && $isAuthorized )
        {
            $params = array($friends);

            $template = $giftsService->findTemplateById($gift['dto']->templateId);
            if ( $template->price != 0 )
            {
                $balance = OW::getEventManager()->call('usercredits.get_balance', array('userId' => $viewerId));
                $checkBalance = 'var checkBalance = true; var balance = ' . (int) $balance . '; var price = ' . $template->price . ';';
            }
            else 
            {
                $balance = 0;
                $checkBalance = 'var checkBalance = false;';
            }
            $js .= '
                $("#send_gift_btn").click(function(){
                    userSelectFloatBox = OW.ajaxFloatBox("BASE_CMP_AvatarUserListSelect", ' . json_encode($params) . ', {width:600, height:400, iconClass: "ow_ic_heart", title: "' . OW::getLanguage()->text('virtualgifts', 'send_gift') . '"});
                });
                
                OW.bind("base.avatar_user_list_select",
                    function(list){
                        '.$checkBalance.'
                        if ( checkBalance && balance < price * list.length ) { 
                            alert("'.$language->text('virtualgifts', 'send_multiple_gifts_error', array('price' => $template->price, 'balance' => (int)$balance)).'");
                        }
                        else
                        {
                            userSelectFloatBox.close();
                            giftConfirmFloatBox = OW.ajaxFloatBox("VIRTUALGIFTS_CMP_SendGiftConfirm", {templateId: ' . $gift['dto']->templateId  . ', senderId: ' . $senderId . ', userIdList: list.join("|") } , {width:450, height:300, iconClass: "ow_ic_info", title: "' . OW::getLanguage()->text('virtualgifts', 'send_gift') . '"});
                        }
                    }
                );';
        }
        
        if ( mb_strlen($js) )
        {
            OW::getDocument()->addOnloadScript($js);
        }
        
        $this->setPageHeading(OW::getLanguage()->text('virtualgifts', 'user_gifts', array('user' => $recipientName)));
        $this->setPageHeadingIconClass('ow_ic_heart');
        
        if ( $gift['dto']->message == '' )
        {
            OW::getDocument()->setTitle(OW::getLanguage()->text('virtualgifts', 'meta_title_view_gift', array('recipient' => $recipientName)));
            OW::getDocument()->setDescription(OW::getLanguage()->text('virtualgifts', 'meta_description_view_gift', array('sender' => $senderName, 'recipient' => $recipientName)));
        }
        else 
        {
            OW::getDocument()->setTitle(
                OW::getLanguage()->text('virtualgifts', 'meta_title_view_gift_msg', array('recipient' => $recipientName, 'message' => $gift['dto']->message))
            );
            OW::getDocument()->setDescription(
                OW::getLanguage()->text('virtualgifts', 'meta_description_view_gift_msg', array('sender' => $senderName, 'recipient' => $recipientName, 'message' => $gift['dto']->message))
            );
        }
        
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'base', 'dashboard');
        
        $url = OW::getPluginManager()->getPlugin('virtualgifts')->getStaticCssUrl() . 'style.css';
        OW::getDocument()->addStyleSheet($url);
	}
	
	public function userGifts( array $params )
	{
	    if ( empty($params['userName']) || !$user = BOL_UserService::getInstance()->findByUsername($params['userName']) )
	    {
            throw new Redirect404Exception();
	    }
	    
	    $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
	    
	    $perPage = $giftService->getGiftsPerPageConfig();
	    $page = !empty($_GET['page']) ? (int) $_GET['page'] : 1;
	    $gifts = $giftService->findUserReceivedGifts($user->id, $page, $perPage, true);
	    
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
                                
                $toolbars[$giftId][] = array(
                    'label' => UTIL_DateTime::formatSimpleDate($gift['dto']->sendTimestamp)
                );
            }
        }
        
        $this->assign('gifts', $gifts);
        $this->assign('toolbars', $toolbars);	    
	    
	    $total = $giftService->countUserReceivedGifts($user->id, true);
	    
        // Paging
        $pages = (int) ceil($total / $perPage);
        $paging = new BASE_CMP_Paging($page, $pages, 10);
        $this->assign('paging', $paging->render());
	    
	    $displayName = BOL_UserService::getInstance()->getDisplayName($user->id);
	    $this->setPageHeading(OW::getLanguage()->text('virtualgifts', 'user_gifts', array('user' => $displayName)));
        $this->setPageHeadingIconClass('ow_ic_heart');
        
        OW::getDocument()->setTitle(OW::getLanguage()->text('virtualgifts', 'meta_title_user_gifts', array('recipient' => $displayName)));
        OW::getDocument()->setDescription(OW::getLanguage()->text('virtualgifts', 'meta_description_user_gifts', array('recipient' => $displayName)));
        
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'base', 'dashboard');
        
        $url = OW::getPluginManager()->getPlugin('virtualgifts')->getStaticCssUrl() . 'style.css';
        OW::getDocument()->addStyleSheet($url);
	}
	
	public function ajaxDeleteGift( )
	{
        $giftId = (int) $_POST['giftId'];
        $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        
        $gift = $giftService->findUserGiftById($giftId);
        $isModerator = OW::getUser()->isAuthorized('virtualgifts');
        
        if ( $gift && ( $gift['dto']->recipientId == OW::getUser()->getId() || $isModerator ) )
        {
            $giftService->deleteUserGift($giftId);
            
            $resp['message'] = OW::getLanguage()->text('virtualgifts', 'gift_deleted');
            if ( $gift['dto']->recipientId == OW::getUser()->getId() )
            {
                $resp['url'] = OW::getRouter()->urlForRoute('virtual_gifts_private_list');
            }
            else 
            {
                $userName = BOL_UserService::getInstance()->getUserName($gift['dto']->recipientId);
                $resp['url'] = OW::getRouter()->urlForRoute('virtual_gifts_user_list', array('userName' => $userName));
            }
        }
        else 
        {
            $resp['error'] = 'Not authorized';
        }
        
        echo json_encode($resp);
        exit;
	}
	
	public function ajaxSendGifts( )
	{
        VIRTUALGIFTS_CMP_SendGiftConfirm::process($_POST);
	}
	
    public function ajaxSendGift( )
    {
        VIRTUALGIFTS_CMP_SendGift::process($_POST);
    }
}