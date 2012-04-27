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
 * New massege form class
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.classes
 * @since 1.0
 */
class AddMessageForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        $language = OW::getLanguage();

        parent::__construct('mailbox-add-message-form');
        $this->setId('mailbox-add-message-form');

        $this->setEnctype('multipart/form-data');
        
        $textarea = new WysiwygTextarea('message', array( BOL_TextFormatService::WS_BTN_IMAGE, BOL_TextFormatService::WS_BTN_VIDEO ), false);
        $textarea->addValidator(new StringValidator(0, 24000));
        $textarea->setHasInvitation(true);
        $textarea->setInvitation($language->text('mailbox', 'write_here'));
        $textarea->setRequired(true);
        $this->addElement($textarea);

        OW::getDocument()->addOnloadScript("$('#{$textarea->getId()}').focus(function(){this.htmlarea();this.htmlareaFocus();});");
        
        $configs = OW::getConfig()->getValues('mailbox');

        if( !empty($configs['enable_attachments']) )
        {
            $multiUpload = new MultiFileField('attachments', 5);
            $this->addElement($multiUpload);
        }

        $submit = new Submit("add");
        $submit->setValue($language->text('mailbox', 'add_button'));
        
        $this->addElement($submit);
    }

    /**
     * Adds new message to the conversation
     *
     * @param MAILBOX_BOL_Conversation $conversation
     * @param int $userId
     * @return boolean
     */
    public function process( MAILBOX_BOL_Conversation $conversation, $userId )
    {
        if ( !isset($conversation) || empty($userId) )
        {
            return false;
        }

        $isAuthorized = OW::getUser()->isAuthorized('mailbox', 'send_message');
        if ( !$isAuthorized )
        {
            return array('result' => false, 'error'=> OW::getLanguage()->text('mailbox', 'write_permission_denied'));
        }
        
        // credits check
        $eventParams = array('pluginKey' => 'mailbox', 'action' => 'send_message');
        $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams);
        
        if ( $credits === false )
        {
            $error = OW::getEventManager()->call('usercredits.error_message', $eventParams);
            return array('result'=> false, 'error' => $error);
        }
        
        $values = $this->getValues();
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();
        $message = $conversationService->createMessage($conversation, $userId, $values['message']);

        $language = OW::getLanguage();
        OW::getFeedback()->info($language->text('mailbox', 'add_message'));

        if ( isset($_FILES['attachments']) && count($_FILES['attachments']) )
        {
            $conversationService->addMessageAttachments($message->id, $_FILES['attachments']);
        }
        
        // credits track
        if ( $credits === true )
        {
            OW::getEventManager()->call('usercredits.track_action', $eventParams);
        }
        
        return array('result' => true);
    }
}