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
 * Confirm send gift component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.virtual_gifts.components
 * @since 1.0
 */
class VIRTUALGIFTS_CMP_SendGiftConfirm extends OW_Component
{
    public function __construct( $templateId, $senderId, $userIdList )
    {
        parent::__construct();
        
        $senderId = (int) $senderId;
        $templateId = (int) $templateId;
        
        if ( !$senderId || !$templateId )
        {
            return;
        }
        
        $form = new ConfirmGiftForm($templateId, $userIdList);
        $this->addForm($form);
        
        $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        $template = $giftService->findTemplateById($templateId);
        $this->assign('template', $template);
        $this->assign('imageUrl', $giftService->getGiftFileUrl($template->id, $template->uploadTimestamp, $template->extension));

        $lang = OW::getLanguage();

        if ( !OW::getUser()->isAuthorized('virtualgifts', 'send_gift') )
        {
            $this->assign('authMessage', $lang->text('base', 'authorization_failed_feedback'));
            return;
        }
        
        $this->assign('showPrice', OW::getPluginManager()->isPluginActive('usercredits'));
                
        $js = '$("#cancel_btn").click(function(){
            giftConfirmFloatBox.close();
        });';
        
        OW::getDocument()->addOnloadScript($js);
        
        $url = OW::getPluginManager()->getPlugin('virtualgifts')->getStaticCssUrl() . 'style.css';
        OW::getDocument()->addStyleSheet($url);
    }
    
    
    public static function process( $data )
    {
        $resp = array();
        $lang = OW::getLanguage();
        
        if ( !OW::getUser()->isAuthenticated() )
        {
            $resp['error'] = $lang->text('virtualgifts', 'not_authenticated');
            echo json_encode($resp);
            exit;
        }
        
        if ( empty($data['tplId']) )
        {
            $resp['error'] = 'Error';
            echo json_encode($resp);
            exit;
        }
        
        if ( empty($data['userIdList']) || !$idList = explode("|", $data['userIdList']) )
        {
            $resp['error'] = $lang->text('virtualgifts', 'no_users_selected');
            echo json_encode($resp);
            exit;
        }
        
        $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        
        $tpl = $giftService->findTemplateById($data['tplId']);
        if ( !$tpl )
        {
            $resp['error'] = 'Gift not found';
            echo json_encode($resp);
            exit;
        }
        
        $senderId = OW::getUser()->getId();
        $authorized = OW::getUser()->isAuthorized('virtualgifts', 'send_gift');
        
        if ( !$authorized )
        {
            $resp['error'] = $lang->text('virtualgifts', 'not_authorized');
            echo json_encode($resp);
            exit;
        }
                
        $count = 0;
        
        $eventParams = array('pluginKey' => 'virtualgifts', 'action' => 'template_' . $tpl->id, 'userId' => $senderId);
        foreach ( $idList as $userId )
        {
            $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams);
            if ( $credits === false )
            {
                $par = array('pluginKey' => 'virtualgifts', 'action' => 'send_virtual_gift', 'userId' => $senderId);
                $resp['error'] = OW::getEventManager()->call('usercredits.error_message', $par);
                break;
            }
            
            $gift = new VIRTUALGIFTS_BOL_UserGift();
            $gift->senderId = $senderId;
            $gift->recipientId = $userId;
            $gift->private = $data['isPrivate'] == 'on' ? 1 : 0;
            $gift->message = htmlspecialchars(trim($data['message']));
            $gift->templateId = $tpl->id;
            $gift->sendTimestamp = time();
                
            if ( $giftId = $giftService->sendUserGift($gift) )
            {                    
                if ( !$gift->private )
                {
                    // Newsfeed
                    $event = new OW_Event('feed.action', array(
                        'pluginKey' => 'virtualgifts',
                        'entityType' => 'user_gift',
                        'entityId' => $giftId,
                        'userId' => $userId
                    ));
                
                    OW::getEventManager()->trigger($event);
                }
                
                $event = new OW_Event('virtualgifts.send_gift', array(
                    'recipientId' => $userId,
                    'senderId' => $senderId,
                    'giftId' => $giftId
                ));
                OW::getEventManager()->trigger($event);
                
                $count++;
                
                if ( $credits === true )
                {
                    OW::getEventManager()->call('usercredits.track_action', $eventParams);
                }
            }
        }
        
        if ( $count )
        {
            $resp['message'] = $lang->text('virtualgifts', 'gift_sent_to', array('count' => $count));
        }
        
        echo json_encode($resp);
        exit;
    }
}

class ConfirmGiftForm extends Form
{
    public function __construct( $templateId, $users )
    {
        parent::__construct('confirm-gift-form');
        
        $lang = OW::getLanguage();

        $this->setAjax(true);
        $this->setAction(OW::getRouter()->urlFor('VIRTUALGIFTS_CTRL_Gifts', 'ajaxSendGifts'));
        
        $tplId = new HiddenField('tplId');
        $tplId->setRequired(true);
        $tplId->setValue($templateId);
        $this->addElement($tplId);
        
        $message = new Textarea('message');
        $this->addElement($message);
        
        $userIdList = new HiddenField('userIdList');
        $userIdList->setValue($users);
        $this->addElement($userIdList);
        
        $isPrivate = new CheckboxField('isPrivate');
        $isPrivate->setLabel($lang->text('virtualgifts', 'send_private'));
        $this->addElement($isPrivate);
        
        $submit = new Submit('send');
        $submit->setLabel($lang->text('virtualgifts', 'btn_send'));
        $this->addElement($submit);
        
        $js = 'owForms["'.$this->getName().'"].bind("success", function(data){
            if ( data.error != undefined ){
                OW.error(data.error);
            }
            if ( data.message != undefined ){
                OW.info(data.message);
            }
            giftConfirmFloatBox.close()
        });';
        
        OW::getDocument()->addOnloadScript($js);
    }
}