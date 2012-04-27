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
 * Mailbox responder class
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.classes
 * @since 1.0
 */
class MailboxResponder
{
    public $error;
    public $notice;

    /**
     * Class constructor
     */
    public function __construct()
    {
        return $this;
    }

    /**
     * Marks conversation as Read
     *
     * @param array $params
     * @return boolean
     */
    public function markConversationRead( $params )
    {
        $userId = OW::getUser()->getId();
        $language = OW::getLanguage();

        if ( !OW::getUser()->isAuthenticated() || $userId === null )
        {
            throw new Redirect404Exception(); // TODO: Redirect to login page
        }

        if ( empty($params['conversationId']) )
        {
            $this->error = 'Mark conversation as read fail! \nEmpty param conversationId!';
            return false;
        }

        $conversation = (int) $params['conversationId'];
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        try
        {
            $conversationService->markRead(array($conversation), $userId);
        }
        catch ( Exception $e )
        {
            $this->error = $language->text('mailbox', 'mark_read_fail_message');
            return false;
        }

        $this->notice = $language->text('mailbox', 'mark_conversation_read_message');

        return true;
    }

    /**
     * Marks conversation as UnRead
     *
     * @param array $params
     * @return boolean
     */
    public function markConversationUnRead( $params )
    {
        $userId = OW::getUser()->getId();
        $language = OW::getLanguage();

        if ( !OW::getUser()->isAuthenticated() || $userId === null )
        {
            throw new Redirect404Exception(); // TODO: Redirect to login page
        }

        if ( empty($params['conversationId']) )
        {
            $this->error = 'Mark conversation as unread fail! \nEmpty param conversationId!';
            return false;
        }

        $conversation = (int) $params['conversationId'];
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        try
        {
            $conversationService->markUnRead(array($conversation), $userId);
        }
        catch ( Exception $e )
        {
            $this->error = $language->text('mailbox', 'mark_unread_fail_message');
            return false;
        }

        $this->notice = $language->text('mailbox', 'mark_conversation_unread_message');

        return true;
    }

    public function deleteUploadFile( $params )
    {
        $language = OW::getLanguage();
        $hash = $params['hash'];

        $userId = OW::getUser()->getId();

        if ( empty($hash) )
        {
            $this->error = $language->text('mailbox', 'upload_file_not_found');
            return false;
        }

        try
        {
            MAILBOX_BOL_FileUploadService::getInstance()->deleteUploadFile($hash, $userId);
        }
        catch( Exception $ex )
        {
            $this->error = $language->text('mailbox', 'upload_file_delete_fail');
            return false;
        }

        $this->notice = $language->text('mailbox', 'upload_file_delete_success');
        return true;
    }
}