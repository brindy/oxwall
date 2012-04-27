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
 * Mailbox controller
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.controllers
 * @since 1.0
 */
class MAILBOX_CTRL_Mailbox extends OW_ActionController
{
    const REDIRECT_TO_INBOX = 'inbox';

    const REDIRECT_TO_SENT = 'sent';

    const MAILBOX_RECORDS_TO_PAGE = 10;


    /**
     * @var string
     */
    public $recordsToPage;

    /**
     * @var string
     */
    public $responderUrl;

    /**
     * @var string
     */
    public $jsDirUrl;

    /**
     * @var OW_Plugin
     */
    public $plugin;

    /**
     * @see OW_ActionController::init()
     *
     */
    public function init()
    {
        parent::init();

        $language = OW::getLanguage();

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('mailbox', 'inbox_label'));
        $item->setIconClass('ow_ic_down_arrow');
        $item->setUrl(OW::getRouter()->urlForRoute("mailbox_default"));
        $item->setKey('inbox');
        $item->setOrder(1);

        $menuItems[] = $item;

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('mailbox', 'sent_label'));
        $item->setIconClass('ow_ic_up_arrow');
        $item->setUrl(OW::getRouter()->urlForRoute("mailbox_sent"));
        $item->setKey('sent');
        $item->setOrder(2);

        $menuItems[] = $item;

        $menu = new BASE_CMP_ContentMenu($menuItems);
        $this->addComponent("mailbox_menu", $menu);

        $this->setPageHeading($language->text('mailbox', 'mailbox'));
        $this->setPageHeadingIconClass('ow_ic_mail');

        $this->recordsToPage = (int) OW::getConfig()->getValue('mailbox', 'results_per_page');

        if ( $this->recordsToPage === 0 )
        {
            $this->recordsToPage = self::MAILBOX_RECORDS_TO_PAGE;
        }
    }

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->plugin = OW::getPluginManager()->getPlugin("mailbox");
        $this->jsDirUrl = $this->plugin->getStaticJsUrl();
        $this->responderUrl = OW::getRouter()->urlFor("MAILBOX_CTRL_Mailbox", "responder");
    }

    /**
     * Displays mailbox inbox page
     */
    public function inbox()
    {
        $language = OW::getLanguage();
        OW::getDocument()->setTitle($language->text('mailbox', 'inbox_meta_tilte'));

        $userId = OW::getUser()->getId();

        if ( !OW::getUser()->isAuthenticated() || $userId === null )
        {
            throw new AuthenticateException();
        }

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        if ( OW::getRequest()->isPost() )
        {
            if ( !empty($_POST['conversation']) && is_array($_POST['conversation']) )
            {
                $conversationList = $_POST['conversation'];

                if ( isset($_POST['mark_as_read']) )
                {
                    $count = $conversationService->markRead($conversationList, $userId);
                    OW::getFeedback()->info($language->text('mailbox', 'mark_read_message', array('count' => $count)));
                }
                else if ( isset($_POST['mark_as_unread']) )
                {
                    $count = $conversationService->markUnread($conversationList, $userId);

                    OW::getFeedback()->info($language->text('mailbox', 'mark_unread_message', array('count' => $count)));
                }
                else if ( isset($_POST['delete']) )
                {
                    $count = $conversationService->deleteConversation($conversationList, $userId);

                    OW::getFeedback()->info($language->text('mailbox', 'delete_message', array('count' => $count)));
                }
            }

            $this->redirect(OW::getRequest()->getRequestUri());
        }

        //paging
        $recordsToPage = $this->recordsToPage;

        $record = array();
        $recordsCount = (int) $conversationService->getInboxConversationCount($userId);
        $pageCount = (int) ceil($recordsCount / $recordsToPage);
        $page = 1;

        if ( $recordsCount > $recordsToPage )
        {
            if ( isset($_GET['page']) )
            {

                if ( (int) $_GET['page'] < 1 )
                {
                    $this->redirect(OW::getRouter()->urlForRoute('mailbox_inbox') . '?page=' . 1);
                }

                $page = (int) $_GET['page'];
            }
        }

        if ( empty($pageCount) || $pageCount < 1 )
        {
            $pageCount = 1;
        }

        if ( $page > $pageCount )
        {
            $this->redirect(OW::getRouter()->urlForRoute('mailbox_inbox') . '?page=' . $pageCount);
        }

        $paging = new BASE_CMP_Paging($page, $pageCount, $recordsToPage);
        $this->assign('paging', $paging->render());

        $startRecord = (int) (($page - 1) * $recordsToPage);

        $record['new'] = (int) $conversationService->getNewInboxConversationCount($userId);
        $record['total'] = $recordsCount;
        $record['start'] = $startRecord + 1;
        $record['end'] = ( (int) $page * $recordsToPage <= $recordsCount ) ? (int) $page * $recordsToPage : $recordsCount;

        $this->assign('record', $record);

        //--

        $conversations = $conversationService->getInboxConversationList($userId, $startRecord, $recordsToPage);
        $conversationList = array();

        $opponentsId = array();
        $conversationsId = array();

        foreach ( $conversations as $value )
        {
            $conversation = array();
            $conversation['conversationId'] = $value['conversationId'];
            $conversation['userId'] = $userId;
            $conversation['read'] = false;

            $conversation['url'] = $conversationService->getConversationUrl($conversation['conversationId']);

            $conversation['deleteUrl'] = OW::getRouter()->urlFor('MAILBOX_CTRL_Mailbox', 'deleteInbox', array("conversationId" => $conversation['conversationId'], "page" => $page));

            switch ( $userId )
            {
                case $value['initiatorId'] :

                    $conversation['opponentId'] = $value['interlocutorId'];
                    $conversation['isOpponentLastMessage'] = false;

                    if ( $value['initiatorMessageId'] < $value['interlocutorMessageId'] )
                    {
                        $conversation['isOpponentLastMessage'] = true;
                    }

                    if ( (int) $value['read'] & MAILBOX_BOL_ConversationDao::READ_INITIATOR )
                    {
                        $conversation['read'] = true;
                    }

                    break;

                case $value['interlocutorId'] :

                    $conversation['opponentId'] = $value['initiatorId'];
                    $conversation['isOpponentLastMessage'] = false;

                    if ( $value['initiatorMessageId'] > $value['interlocutorMessageId'] )
                    {
                        $conversation['isOpponentLastMessage'] = true;
                    }

                    if ( (int) $value['read'] & MAILBOX_BOL_ConversationDao::READ_INTERLOCUTOR )
                    {
                        $conversation['read'] = true;
                    }

                    break;
            }

            $conversation['timeStamp'] = UTIL_DateTime::formatDate((int) $value['timeStamp']);
            $conversation['subject'] = $value['subject'];
            $conversation['text'] = '<span class="error">' . OW::getLanguage()->text('mailbox', 'read_permission_denied') . '</span>';

            if ( OW::getUser()->isAuthorized('mailbox', 'read_message') )
            {
                $text = mb_strlen($value['text']) > 100 ? mb_substr($value['text'], 0, 100) . '...' : $value['text']; //TODO:
                $conversation['text'] = UTIL_HtmlTag::autoLink($text);
            }

            $conversationList[] = $conversation;
            $opponentsId[] = $conversation['opponentId'];
            $conversationsId[] = $conversation['conversationId'];
        }

        $opponentsId = array_unique($opponentsId);
        $conversationsId = array_unique($conversationsId);

        $opponentsAvatar = BOL_AvatarService::getInstance()->getDataForUserAvatars($opponentsId);
        $opponentsUrl = BOL_UserService::getInstance()->getUserUrlsForList($opponentsId);
        $opponentsDisplayNames = BOL_UserService::getInstance()->getDisplayNamesForList($opponentsId);

        $attachmentsCount = $conversationService->getAttachmentsCountByConversationList($conversationsId);

        $this->assign('attachments', $attachmentsCount);
        $this->assign('opponentsAvatar', $opponentsAvatar);
        $this->assign('opponentsUrl', $opponentsUrl);
        $this->assign('opponentsDisplayNames', $opponentsDisplayNames);
        $this->assign('conversationList', $conversationList);

        $deleteConfirmMessage = OW::getLanguage()->text('mailbox', 'delete_confirm_message');

        //include js
        $onLoadJs = " $( document ).ready( function(){
						var inbox = new mailboxConversationList( " . json_encode(array('responderUrl' => $this->responderUrl, 'deleteConfirmMessage' => $deleteConfirmMessage)) . " );
						inbox.bindFunction();
					} ); ";

        OW::getDocument()->addOnloadScript($onLoadJs);
        OW::getDocument()->addScript($this->jsDirUrl . "mailbox.js");
    }

    /**
     * Displays mailbox sent page
     */
    public function sent()
    {
        $language = OW::getLanguage();
        OW::getDocument()->setTitle($language->text('mailbox', 'sent_meta_tilte'));

        $userId = OW::getUser()->getId();

        if ( !OW::getUser()->isAuthenticated() || $userId === null )
        {
            throw new AuthenticateException(); // TODO: Redirect to login page
        }

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        if ( OW::getRequest()->isPost() && !empty($_POST['conversation']) && is_array($_POST['conversation']) )
        {
            $conversationList = $_POST['conversation'];

            if ( isset($_POST['mark_as_read']) )
            {
                $count = $conversationService->markRead($conversationList, $userId);

                OW::getFeedback()->info($language->text('mailbox', 'mark_read_message', array('count' => $count)));
            }
            else if ( isset($_POST['mark_as_unread']) )
            {
                $count = $conversationService->markUnread($conversationList, $userId);

                OW::getFeedback()->info($language->text('mailbox', 'mark_unread_message', array('count' => $count)));
            }
            else if ( isset($_POST['delete']) )
            {
                $count = $conversationService->deleteConversation($conversationList, $userId);

                OW::getFeedback()->info($language->text('mailbox', 'delete_message', array('count' => $count)));
            }

            $this->redirect();
        }

        //paging
        $recordsToPage = $this->recordsToPage;

        $record = array();
        $recordsCount = (int) $conversationService->getSentConversationCount($userId);
        $pageCount = (int) ceil($recordsCount / $recordsToPage);
        $page = 1;

        if ( $recordsCount > $recordsToPage )
        {
            if ( isset($_GET['page']) )
            {

                if ( (int) $_GET['page'] < 1 )
                {
                    $this->redirect(OW::getRouter()->urlForRoute('mailbox_sent') . '?page=' . 1);
                }

                $page = (int) $_GET['page'];
            }
        }

        if ( empty($pageCount) || $pageCount < 1 )
        {
            $pageCount = 1;
        }

        if ( $page > $pageCount )
        {
            $this->redirect(OW::getRouter()->urlForRoute('mailbox_sent') . '?page=' . $pageCount);
        }

        $paging = new BASE_CMP_Paging($page, $pageCount, $recordsToPage);
        $this->assign('paging', $paging->render());

        $startRecord = (int) (($page - 1) * $recordsToPage);

        $record['new'] = (int) $conversationService->getNewSentConversationCount($userId);
        $record['total'] = $recordsCount;
        $record['start'] = $startRecord + 1;
        $record['end'] = ( (int) $page * $recordsToPage <= $recordsCount ) ? (int) $page * $recordsToPage : $recordsCount;

        $this->assign('record', $record);

        //--

        $conversations = $conversationService->getSentConversationList($userId, $startRecord, $recordsToPage);
        $conversationList = array();

        $opponentsId = array();
        $conversationsId = array();

        foreach ( $conversations as $value )
        {
            $conversation = array();
            $conversation['conversationId'] = $value['conversationId'];
            $conversation['userId'] = $userId;
            $conversation['read'] = false;

            $conversation['url'] = $conversationService->getConversationUrl($conversation['conversationId'], MAILBOX_CTRL_Mailbox::REDIRECT_TO_SENT);

            $conversation['deleteUrl'] = OW::getRouter()->urlFor('MAILBOX_CTRL_Mailbox', 'deleteSent', array("conversationId" => $conversation['conversationId'], "page" => $page));

            switch ( $userId )
            {
                case $value['initiatorId'] :

                    $conversation['opponentId'] = $value['interlocutorId'];
                    $conversation['isOpponentLastMessage'] = false;

                    if ( $value['initiatorMessageId'] < $value['interlocutorMessageId'] )
                    {
                        $conversation['isOpponentLastMessage'] = true;
                    }

                    if ( (int) $value['read'] & MAILBOX_BOL_ConversationDao::READ_INITIATOR )
                    {
                        $conversation['read'] = true;
                    }

                    break;

                case $value['interlocutorId'] :

                    $conversation['opponentId'] = $value['initiatorId'];
                    $conversation['isOpponentLastMessage'] = false;

                    if ( $value['initiatorMessageId'] > $value['interlocutorMessageId'] )
                    {
                        $conversation['isOpponentLastMessage'] = true;
                    }

                    if ( (int) $value['read'] & MAILBOX_BOL_ConversationDao::READ_INTERLOCUTOR )
                    {
                        $conversation['read'] = true;
                    }

                    break;
            }

            $conversation['timeStamp'] = UTIL_DateTime::formatDate((int) $value['timeStamp']);
            $conversation['subject'] = $value['subject'];
            $text = mb_strlen($value['text']) > 100 ? mb_substr($value['text'], 0, 100) . '...' : $value['text']; //TODO:
            $conversation['text'] = UTIL_HtmlTag::autoLink($text);
            $conversationList[] = $conversation;

            $opponentsId[] = $conversation['opponentId'];
            $conversationsId[] = $conversation['conversationId'];
        }

        $opponentsId = array_unique($opponentsId);

        $opponentsAvatar = BOL_AvatarService::getInstance()->getDataForUserAvatars($opponentsId);
        $opponentsUrl = BOL_UserService::getInstance()->getUserUrlsForList($opponentsId);
        $opponentsDisplayNames = BOL_UserService::getInstance()->getDisplayNamesForList($opponentsId);

        $attachmentsCount = $conversationService->getAttachmentsCountByConversationList($conversationsId);

        $this->assign('attachments', $attachmentsCount);
        $this->assign('opponentsAvatar', $opponentsAvatar);
        $this->assign('opponentsUrl', $opponentsUrl);
        $this->assign('opponentsDisplayNames', $opponentsDisplayNames);
        $this->assign('conversationList', $conversationList);

        $deleteConfirmMessage = OW::getLanguage()->text('mailbox', 'delete_confirm_message');

        //include js
        $onLoadJs = " $( document ).ready( function(){
						var sent = new mailboxConversationList( " . json_encode(array('responderUrl' => $this->responderUrl, 'deleteConfirmMessage' => $deleteConfirmMessage)) . " );
						sent.bindFunction();
					} ); ";

        OW::getDocument()->addOnloadScript($onLoadJs);
        OW::getDocument()->addScript($this->jsDirUrl . "mailbox.js");
    }

    /**
     * Displays mailbox conversation page
     */
    public function conversation( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !OW::getUser()->isAuthenticated() || $userId === null )
        {
            throw new AuthenticateException(); // TODO: Redirect to login page
        }

        $messages = array();
        $conversation = null;
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();
        $userService = BOL_UserService::getInstance();

        $showMessageForm = false;

        if ( isset($params['conversationId']) )
        {
            $conversationId = (int) $params['conversationId'];

            if ( !empty($conversationId) )
            {
                $conversation = $conversationService->getConversation($conversationId);
            }
        }

        if ( $conversation === null )
        {
            throw new Redirect404Exception();
        }

        $lastMessages = $conversationService->getLastMessages($conversationId);

        if ( $lastMessages === null )
        {
            throw new Redirect404Exception();
        }

        $language = OW::getLanguage();
        OW::getDocument()->setTitle($language->text('mailbox', 'conversation_meta_tilte', array('conversation_title' => $conversation->subject)));

        $conversationService->markRead(array($conversation->id), $userId);
        $addMessageForm = new AddMessageForm();
        $this->addForm($addMessageForm);

        if ( OW::getRequest()->isPost() )
        {
            $showMessageForm = true;

            switch ( $userId )
            {
                case $conversation->initiatorId :
                    $blockedByUserId = $conversation->interlocutorId;
                    break;
                case $conversation->interlocutorId :
                    $blockedByUserId = $conversation->initiatorId;
                    break;
            }

            if ( BOL_UserService::getInstance()->isBlocked($userId, $blockedByUserId) )
            {
                OW::getFeedback()->error(OW::getLanguage()->text('base', 'user_block_message'));
            }
            else if ( $addMessageForm->isValid($_POST) )
            {
                $res = $addMessageForm->process($conversation, $userId);
                if ( !$res['result'] && !empty($res['error']) )
                {
                    OW::getFeedback()->warning($res['error']);
                }

                $this->redirect();
            }
            else
            {
                OW::getFeedback()->error(OW::getLanguage()->text('base', 'form_validate_common_error_message'));
            }
        }

        $conversationArray = array();
        $conversationArray["conversationId"] = $conversation->id;
        $conversationArray["subject"] = $conversation->subject;

        if ( !empty($_GET['redirectTo']) && $_GET['redirectTo'] === MAILBOX_CTRL_Mailbox::REDIRECT_TO_SENT )
        {
            $conversationArray["deleteUrl"] = OW::getRouter()->urlFor('MAILBOX_CTRL_Mailbox', 'deleteSent', array("conversationId" => $conversation->id, "page" => 1));
        }
        else
        {
            $conversationArray["deleteUrl"] = OW::getRouter()->urlFor('MAILBOX_CTRL_Mailbox', 'deleteInbox', array("conversationId" => $conversation->id, "page" => 1));
        }

        $conversationArray['isOpponentLastMessage'] = false;

        switch ( $userId )
        {
            case $conversation->initiatorId :

                $conversationArray['opponentId'] = $conversation->interlocutorId;
                $conversationArray['userId'] = $conversation->initiatorId;

                if ( $lastMessages->initiatorMessageId < $lastMessages->interlocutorMessageId )
                {
                    $conversationArray['isOpponentLastMessage'] = true;
                }

                if ( (int) $conversation->read & MAILBOX_BOL_ConversationDao::READ_INITIATOR )
                {
                    $conversationArray['read'] = true;
                }

                break;

            case $conversation->interlocutorId :

                $conversationArray['opponentId'] = $conversation->initiatorId;
                $conversationArray['userId'] = $conversation->interlocutorId;

                if ( $lastMessages->initiatorMessageId > $lastMessages->interlocutorMessageId )
                {
                    $conversationArray['isOpponentLastMessage'] = true;
                }

                if ( (int) $conversation->read & MAILBOX_BOL_ConversationDao::READ_INTERLOCUTOR )
                {
                    $conversationArray['read'] = true;
                }

                break;

            default :
                throw new Redirect403Exception();
        }

        $avatarList = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($conversationArray['userId'], $conversationArray['opponentId']));

        $displayNames = $userService->getDisplayNamesForList(array($userId, $conversationArray['opponentId']));

        $conversationArray['opponentDisplayName'] = $displayNames[$conversationArray['opponentId']];
        $conversationArray['userDisplayName'] = $displayNames[$conversationArray['userId']];

        $userNames = $userService->getDisplayNamesForList(array($conversationArray['userId'], $conversationArray['opponentId']));

        $conversationArray['opponentName'] = $userNames[$conversationArray['opponentId']];
        $conversationArray['userName'] = $userNames[$conversationArray['userId']];

        $conversationArray['opponentUrl'] = $userService->getUserUrl($conversationArray['opponentId'], $conversationArray['opponentName']);
        $conversationArray['userUrl'] = $userService->getUserUrl($conversationArray['userId'], $conversationArray['userName']);

        /* $userAvatars = BOL_AvatarService::getInstance()->getAvatarsUrlList(array($conversationArray['userId'], $conversationArray['opponentId']));
          $conversationArray['opponentAvatar'] = $userAvatars[$conversationArray['opponentId']];
          $conversationArray['userAvatar'] = $userAvatars[$conversationArray['userId']]; */

        $messages = $conversationService->getConversationMessagesList($conversationId);

        $messageList = array();
        $messageIdList = array();

        foreach ( $messages as $value )
        {
            $messageIdList[] = $value->id;

            $message = array();

            $message['id'] = $value->id;
            $message['senderId'] = $value->senderId;
            $message['recipientId'] = $value->recipientId;

            $message['senderDisplayName'] = $value->senderId == $userId ? $conversationArray['userDisplayName'] : $conversationArray['opponentDisplayName'];
            $message['senderName'] = $value->senderId == $userId ? $conversationArray['userName'] : $conversationArray['opponentName'];
            $message['timeStamp'] = UTIL_DateTime::formatDate((int) $value->timeStamp);

            $message['toolbar'] = array();

            $message['isReadableMessage'] = false;
            $message['content'] = '<span class="error">' . OW::getLanguage()->text('mailbox', 'read_permission_denied') . '</span>';

            if ( OW::getUser()->isAuthorized('mailbox', 'read_message') || $value->senderId == $userId )
            {
                $message['isReadableMessage'] = true;
                $message['content'] = UTIL_HtmlTag::autoLink($value->text); //TODO: Insert text formatter
            }

            $message['senderUrl'] = $userService->getUserUrl($value->senderId, $message['senderName']);

            $messageList[] = $message;
        }

        $attachments = $conversationService->findAttachmentsByMessageIdList($messageIdList);

        $attachmentList = array();
        foreach ( $attachments as $attachment )
        {
            $ext = UTIL_File::getExtension($attachment->fileName);
            $attachmentPath = $conversationService->getAttachmentFilePath($attachment->id, $attachment->hash, $ext);

            $list = array();
            $list['id'] = $attachment->id;
            $list['messageId'] = $attachment->messageId;
            $list['downloadUrl'] = OW::getStorage()->getFileUrl($attachmentPath);
            $list['fileName'] = $attachment->fileName;
            $list['fileSize'] = $attachment->fileSize;

            $attachmentList[$attachment->messageId][$attachment->id] = $list;
        }

        $this->assign('attachmentList', $attachmentList);
        $this->assign('messageList', $messageList);
        $this->assign('conversation', $conversationArray);
        $this->assign('writeMessage', OW::getUser()->isAuthorized('mailbox', 'send_message'));
        $this->assign('avatars', $avatarList);

        $configs = OW::getConfig()->getValues('mailbox');
        $this->assign('enableAttachments', !empty($configs['enable_attachments']));

        //include js
        $deleteConfirmMessage = OW::getLanguage()->text('mailbox', 'delete_confirm_message');

        $onLoadJs = " $(document).ready(function(){
						var conversation = new mailboxConversation( " . json_encode(array('responderUrl' => $this->responderUrl, 'deleteConfirmMessage' => $deleteConfirmMessage)) . " );
					}); ";

        OW::getDocument()->addOnloadScript($onLoadJs);
        OW::getDocument()->addScript($this->jsDirUrl . "mailbox.js");
    }

    /**
     * Action for mailbox ajax responder
     */
    public function responder()
    {
        if ( empty($_POST["function_"]) || !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        $function = (string) $_POST["function_"];

        $responder = new MailboxResponder();
        $result = call_user_func(array($responder, $function), $_POST);

        echo json_encode(array('result' => $result, 'error' => $responder->error, 'notice' => $responder->notice));
        exit();
    }

    public function createConversationResponder()
    {
        $userId = OW::getUser()->getId();

        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new Redirect404Exception();
        }

        if ( !isset($_POST['userId']) )
        {
            throw new Redirect404Exception();
        }

        new MAILBOX_CMP_CreateConversation($_POST);
        exit();
    }

    public function fileUpload( $params )
    {
        if ( OW::getRequest()->isAjax() )
        {
            exit();
        }

        $configs = OW::getConfig()->getValues('mailbox');

        $entityId = $params['entityId'];
        $formElementId = $params['formElementId'];

        $language = OW::getLanguage();
        $fileService = MAILBOX_BOL_FileUploadService::getInstance();

        $file = array();

        $message = $language->text('mailbox', 'upload_file_fail');
        $error = true;

        switch ( true )
        {
            case empty($configs['enable_attachments']):

                $message = $language->text('mailbox', 'file_attachment_disabled');

                break;

            case!empty($formElementId) && !empty($_FILES['attachmet']) && !empty($entityId) && OW::getUser()->isAuthorized('mailbox', 'send_message') :

                $list = $fileService->findUploadFileList($entityId);

                if ( count($list) < 5 )
                {
                    $fileDto = new MAILBOX_BOL_FileUpload();

                    $fileDto->fileName = $_FILES['attachmet']['name'];
                    $fileDto->entityId = $entityId;
                    $fileDto->fileSize = $_FILES['attachmet']['size'];
                    $fileDto->timestamp = time();
                    $fileDto->userId = OW::getUser()->getId();
                    $fileDto->hash = uniqid();

                    $uploadError = $_FILES['attachmet']['error'];

                    switch ( $uploadError )
                    {
                        case UPLOAD_ERR_INI_SIZE:
                            $message = $language->text('mailbox', 'upload_file_max_upload_filesize_error');
                            break;

                        case UPLOAD_ERR_PARTIAL:
                            $message = $language->text('mailbox', 'upload_file_file_partially_uploaded_error');
                            break;

                        case UPLOAD_ERR_NO_FILE:
                            $message = $language->text('mailbox', 'upload_file_no_file_error');
                            break;

                        case UPLOAD_ERR_NO_TMP_DIR:
                            $message = $language->text('mailbox', 'upload_file_no_tmp_dir_error');
                            break;

                        case UPLOAD_ERR_CANT_WRITE:
                            $message = $language->text('mailbox', 'upload_file_cant_write_file_error');
                            break;

                        case UPLOAD_ERR_EXTENSION:
                            $message = $language->text('mailbox', 'upload_file_invalid_extention_error');
                            break;

                        case UPLOAD_ERR_OK:

                            $ext = UTIL_File::getExtension($fileDto->fileName);

                            if ( !$fileService->fileExtensionIsAllowed($ext) )
                            {
                                $message = $language->text('mailbox', 'upload_file_extension_is_not_allowed');
                            }
                            else if ( $fileDto->fileSize > (float) $configs['upload_max_file_size'] * 1024 * 1024 )
                            {
                                $message = $language->text('mailbox', 'upload_file_max_upload_filesize_error');
                            }
                            else if ( $fileService->addFile($fileDto, $_FILES['attachmet']['tmp_name']) )
                            {
                                $error = false;
                                $message = '';

                                $file = array(
                                    'error' => false,
                                    'filename' => $fileDto->fileName,
                                    'filesize' => round($fileDto->fileSize / 1024, 2) . ' Kb',
                                    'hash' => $fileDto->hash,
                                    'url' => $fileService->getUploadFileUrl($fileDto->hash, UTIL_File::getExtension($fileDto->fileName))
                                );
                            }

                            break;
                    }
                }
                else
                {
                    $message = $language->text('mailbox', 'upload_file_count_files_error');
                }

                break;
        }

        $file['input_id'] = $formElementId;
        $file['error'] = $error;
        $file['message'] = $message;

        exit("<script>
                    parent.window.OW.trigger('mailbox.attach_file_complete', [" . json_encode($file) . "]);
              </script>");
    }

    /**
     * Deletes inbox conversation
     *
     * @param int $conversationId
     * @param int $page
     */
    public function deleteInbox( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !OW::getUser()->isAuthenticated() || $userId === null )
        {
            throw new AuthenticateException(); // TODO: Redirect to login page
        }

        $conversationId = (int) $params['conversationId'];
        $page = (int) $params['page'] > 0 ? (int) $params['page'] : 1;

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        if ( !empty($conversationId) )
        {
            $conversationService->deleteConversation(array($conversationId), $userId);

            $language = OW::getLanguage();
            OW::getFeedback()->info($language->text('mailbox', 'delete_conversation_message'));

            $this->redirect((OW::getRouter()->urlForRoute("mailbox_default")) . "?page=" . $page);
        }
    }

    /**
     * Deletes sent conversation
     *
     * @param int $conversationId
     * @param int $page
     */
    public function deleteSent( $params )
    {
        $userId = OW::getUser()->getId();

        if ( !OW::getUser()->isAuthenticated() || $userId === null )
        {
            throw new AuthenticateException(); // TODO: Redirect to login page
        }

        $conversationId = (int) $params['conversationId'];
        $page = (int) $params['page'] > 0 ? (int) $params['page'] : 1;

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        if ( !empty($conversationId) )
        {
            $conversationService->deleteConversation(array($conversationId), $userId);

            $language = OW::getLanguage();
            OW::getFeedback()->info($language->text('mailbox', 'delete_conversation_message'));

            $this->redirect((OW::getRouter()->urlForRoute("mailbox_sent")) . "?page=" . $page);
        }
    }
}