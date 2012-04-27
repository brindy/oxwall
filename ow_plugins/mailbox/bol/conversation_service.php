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
 * Conversation Service Class
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.bol
 * @since 1.0
 */
final class MAILBOX_BOL_ConversationService
{
    const MARK_TYPE_READ = 'read';
    const MARK_TYPE_UNREAD = 'unread';

    /**
     * @var MAILBOX_ConversationDao
     */
    private $conversationDao;
    /**
     * @var MAILBOX_BOL_LastMessageDao
     */
    private $lastMessageDao;
    /**
     * @var MAILBOX_BOL_MessageDao
     */
    private $messageDao;
    /**
     * @var MAILBOX_BOL_AttachmentDao
     */
    private $attachmentDao;
    /**
     * @var array
     */
    private static $allowedExtensions =
        array(
        'txt', 'doc', 'docx', 'sql', 'csv', 'xls', 'ppt',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'psd', 'ai', 'pdf',
        'avi', 'wmv', 'mp3', '3gp', 'flv', 'mkv', 'mpeg', 'mpg', 'swf',
        'zip', 'gz', '.tgz', 'gzip', '7z', 'bzip2', 'rar'
    );
    /**
     * Class instance
     *
     * @var MAILBOX_BOL_ConversationService
     */
    private static $classInstance;

    /**
     * Class constructor
     */
    private function __construct()
    {
        $this->conversationDao = MAILBOX_BOL_ConversationDao::getInstance();
        $this->lastMessageDao = MAILBOX_BOL_LastMessageDao::getInstance();
        $this->messageDao = MAILBOX_BOL_MessageDao::getInstance();
        $this->attachmentDao = MAILBOX_BOL_AttachmentDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return MAILBOX_BOL_ConversationService
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * Returns inbox conversations list by $userId
     *
     * @param int $userId
     * @param int $first
     * @param int $count
     * @throws InvalidArgumentException
     * @return array
     */
    public function getInboxConversationList( $userId, $first, $count )
    {
        if ( empty($userId) || !isset($first) || !isset($count) )
        {
            throw new InvalidArgumentException("Not numeric params were provided! Numbers are expected!");
        }

        return $this->conversationDao->getInboxConversationList($userId, $first, $count);
    }

    /**
     * Returns sent conversations list by $userId
     *
     * @param int $userId
     * @param int $first
     * @param int $count
     * @throws InvalidArgumentException
     * @return array
     */
    public function getSentConversationList( $userId, $first, $count )
    {
        if ( empty($userId) || !isset($first) || !isset($count) )
        {
            throw new InvalidArgumentException("Not numeric params were provided! Numbers are expected!");
        }

        return $this->conversationDao->getSentConversationList($userId, $first, $count);
    }

    /**
     * Marks conversation as Read or Unread
     *
     * @param array $conversationsId
     * @param int $userId
     * @param string $markType = self::MARK_TYPE_READ
     * @throws InvalidArgumentException
     *
     * retunn int
     */
    public function markConversation( array $conversationsId, $userId, $markType = self::MARK_TYPE_READ )
    {
        if ( empty($userId) )
        {
            throw new InvalidArgumentException("Not numeric params were provided! Numbers are expected!");
        }

        if ( empty($conversationsId) || !is_array($conversationsId) )
        {
            throw new InvalidArgumentException("Wrong parameter conversationsId!");
        }

        $userId = (int) $userId;
        $conversations = $this->conversationDao->findByIdList($conversationsId);

        $count = 0;

        foreach ( $conversations as $key => $value )
        {
            $conversation = &$conversations[$key];

            $lastMessages = $this->lastMessageDao->findByConversationId($conversation->id);

            $readBy = MAILBOX_BOL_ConversationDao::READ_NONE;
            $isOpponentLastMessage = false;

            switch ( $userId )
            {
                case $conversation->initiatorId :

                    if ( $lastMessages->initiatorMessageId < $lastMessages->interlocutorMessageId )
                    {
                        $isOpponentLastMessage = true;
                    }

                    $readBy = MAILBOX_BOL_ConversationDao::READ_INITIATOR;

                    break;

                case $conversation->interlocutorId :

                    if ( $lastMessages->initiatorMessageId > $lastMessages->interlocutorMessageId )
                    {
                        $isOpponentLastMessage = true;
                    }

                    $readBy = MAILBOX_BOL_ConversationDao::READ_INTERLOCUTOR;

                    break;
            }

            if ( !$isOpponentLastMessage )
            {
                continue;
            }

            switch ( $markType )
            {
                case self::MARK_TYPE_READ :
                    $conversation->read = (int) $conversation->read | $readBy;
                    break;

                case self::MARK_TYPE_UNREAD :
                    $conversation->read = (int) $conversation->read & (~$readBy);
                    break;
            }

            $this->conversationDao->save($conversation);

            if ( $this->conversationDao->getAffectedRows() > 0 )
            {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Marks conversation as Read
     *
     * @param array $conversationsId
     * @param int $userId
     *
     * retunn int
     */
    public function markRead( array $conversationsId, $userId )
    {
        return $this->markConversation($conversationsId, $userId, self::MARK_TYPE_READ);
    }

    /**
     * Marks conversation as Unread
     *
     * @param array $conversationsId
     * @param int $userId
     *
     * retunn int
     */
    public function markUnread( array $conversationsId, $userId )
    {
        return $this->markConversation($conversationsId, $userId, self::MARK_TYPE_UNREAD);
    }

    /**
     * Deletes conversation
     *
     * @param array $conversationsId
     * @param int $userId
     * @throws InvalidArgumentException
     *
     * return int
     */
    public function deleteConversation( array $conversationsId, $userId )
    {
        if ( empty($userId) )
        {
            throw new InvalidArgumentException("Not numeric params were provided! Numbers are expected!");
        }

        if ( empty($conversationsId) || !is_array($conversationsId) )
        {
            throw new InvalidArgumentException("Wrong parameter conversationsId!");
        }

        $userId = (int) $userId;
        $conversations = $this->conversationDao->findByIdList($conversationsId);

        $count = 0;

        foreach ( $conversations as $key => $value )
        {
            $conversation = &$conversations[$key];

            $deletedBy = MAILBOX_BOL_ConversationDao::DELETED_NONE;

            switch ( $userId )
            {
                case $conversation->initiatorId :
                    $deletedBy = MAILBOX_BOL_ConversationDao::DELETED_INITIATOR;
                    break;

                case $conversation->interlocutorId :
                    $deletedBy = MAILBOX_BOL_ConversationDao::DELETED_INTERLOCUTOR;
                    break;
            }

            $conversation->deleted = (int) $conversation->deleted | $deletedBy;

            if ( $conversation->deleted == MAILBOX_BOL_ConversationDao::DELETED_ALL )
            {
                $this->conversationDao->deleteById($conversation->id);
                $this->deleteAttachmentsByConversationList( array($conversation->id) );
            }
            else
            {
                $this->conversationDao->save($conversation);
            }

            if ( $this->conversationDao->getAffectedRows() > 0 )
            {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Creates new conversation
     *
     * @param int $initiatorId
     * @param int $interlocutorId
     * @param string $subject
     * @param string $text
     * @throws InvalidArgumentException
     * 
     * return MAILBOX_BOL_Conversation
     */
    public function createConversation( $initiatorId, $interlocutorId, $subject, $text )
    {
        if ( empty($initiatorId) || empty($interlocutorId) )
        {
            throw new InvalidArgumentException("Not numeric params were provided! Numbers are expected!");
        }

        $initiatorId = (int) $initiatorId;
        $interlocutorId = (int) $interlocutorId;
        $subject = trim($subject);
        $text = trim($text);

        if ( empty($subject) || empty($text) )
        {
            throw new InvalidArgumentException("Empty string params were provided!");
        }

        // create conversation
        $conversation = new MAILBOX_BOL_Conversation();
        $conversation->initiatorId = $initiatorId;
        $conversation->interlocutorId = $interlocutorId;
        $conversation->subject = $subject;
        $conversation->createStamp = time();

        $this->conversationDao->save($conversation);

        $this->createMessage($conversation, $initiatorId, $text);
        
        return $conversation;
    }

    /**
     * Returns conversation's messages list
     *
     * @param int $conversationId
     * @throws InvalidArgumentException
     * @return MAILBOX_BOL_Conversation
     */
    public function getConversationMessagesList( $conversationId )
    {
        if ( empty($conversationId) )
        {
            throw new InvalidArgumentException("Not numeric params were provided! Numbers are expected!");
        }

        return $this->messageDao->findListByConversationId($conversationId);
    }

    /**
     * Returns conversation info
     *
     * @param int $conversationId
     * @throws InvalidArgumentException
     * @return MAILBOX_BOL_Conversation
     */
    public function getConversation( $conversationId )
    {
        if ( empty($conversationId) )
        {
            throw new InvalidArgumentException("Not numeric params were provided! Numbers are expected!");
        }

        return $this->conversationDao->findById($conversationId);
    }

    /**
     * Creates New Message
     *
     * @param MAILBOX_BOL_Conversation $conversation
     * @param int $senderId
     * @param string $text
     * @throws InvalidArgumentException
     */
    public function createMessage( MAILBOX_BOL_Conversation $conversation, $senderId, $text )
    {
        if ( empty($senderId) )
        {
            throw new InvalidArgumentException("Not numeric params were provided! Numbers are expected!");
        }

        if ( $conversation === null )
        {
            throw new InvalidArgumentException("Conversation doesn't exist!");
        }

        if ( empty($conversation->id) )
        {
            throw new InvalidArgumentException("Conversation with id = " . ($conversation->id) . " is not exist");
        }

        if ( !in_array($senderId, array($conversation->initiatorId, $conversation->interlocutorId)) )
        {
            throw new InvalidArgumentException("Wrong senderId!");
        }

        $senderId = (int) $senderId;
        $recipientId = ($senderId == $conversation->initiatorId) ? $conversation->interlocutorId : $conversation->initiatorId;

        $message = $this->addMessage($conversation, $senderId, $text);

        $event = new OW_Event('mailbox.send_message', array(
                'senderId' => $senderId,
                'recipientId' => $recipientId,
                'conversationId' => $conversation->id,
                'message' => $text
            ));
        OW::getEventManager()->trigger($event);

        return $message;
    }

    /**
     * Returns user's inbox conversations count
     *
     * @param int $userId
     * @return int
     */
    public function getInboxConversationCount( $userId )
    {
        return $this->conversationDao->getInboxConversationCount($userId);
    }

    /**
     * Returns user's new inbox conversations count
     *
     * @param int $userId
     * @return int
     */
    public function getNewInboxConversationCount( $userId )
    {
        return $this->conversationDao->getNewInboxConversationCount($userId);
    }

    /**
     * Returns user's sent conversations count
     *
     * @param int $userId
     * @return int
     */
    public function getSentConversationCount( $userId )
    {
        return $this->conversationDao->getSentConversationCount($userId);
    }

    /**
     * Returns user's new sent conversations count
     *
     * @param int $userId
     * @return int
     */
    public function getNewSentConversationCount( $userId )
    {
        return $this->conversationDao->getNewSentConversationCount($userId);
    }

    /**
     * Returns the latest conversation message's id for initiator and interlocutor
     *
     * @param int $conversationId
     * @return MAILBOX_BOL_LastMessage
     */
    public function getLastMessages( $conversationId )
    {
        return $this->lastMessageDao->findByConversationId($conversationId);
    }

    public function deleteConverstionByUserId( $userId )
    {
        $count = 1000;
        $first = 0;

        if ( !empty($userId) )
        {
            $conversationList = array();

            do
            {
                $conversationList = $this->conversationDao->getConversationListByUserId($userId, $first, $count);

                $conversationIdList = array();

                foreach ( $conversationList as $conversation )
                {
                    $conversationIdList[$conversation['id']] = $conversation['id'];
                }

                if ( !empty($conversationIdList) )
                {
                    $this->conversationDao->deleteByIdList($conversationIdList);
                    $this->deleteAttachmentsByConversationList( $conversationIdList );
                }

                $first += $count;
            }
            while ( !empty($conversationList) );
        }
    }

    public function deleteUserContent( OW_Event $event )
    {
        $params = $event->getParams();

        $userId = (int) $params['userId'];

        if ( $userId > 0 )
        {
            $this->deleteConverstionByUserId($userId);
        }
    }

    public function getConversationUrl( $conversationId, $redirectTo = null )
    {
        $params = array();
        $params['conversationId'] = $conversationId;

        if ( $redirectTo !== null )
        {
            $params['redirectTo'] = $redirectTo;
        }

        return OW::getRouter()->urlForRoute('mailbox_conversation', $params);
    }

    /**
     * @param int $initiatorId
     * @param int $interlocutorId
     * @throws InvalidArgumentException
     * @return array<MAILBOX_BOL_Conversation>
     */
    public function findConversationList( $initiatorId, $interlocutorId )
    {
        if ( empty($initiatorId) || !isset($interlocutorId) )
        {
            throw new InvalidArgumentException("Not numeric params were provided! Numbers are expected!");
        }

        return $this->conversationDao->findConversationList($initiatorId, $interlocutorId);
    }

    /**
     * @param MAILBOX_BOL_Conversation $conversationd
     */
    public function saveConversation( MAILBOX_BOL_Conversation $conversation )
    {
        $this->conversationDao->save($conversation);
    }

    /**
     * Add message to conversation
     *
     * @param MAILBOX_BOL_Conversation $conversation
     * @param int $senderId
     * @param string $text
     * @throws InvalidArgumentException
     */
    public function addMessage( MAILBOX_BOL_Conversation $conversation, $senderId, $text )
    {
        if ( empty($senderId) )
        {
            throw new InvalidArgumentException("Not numeric params were provided! Numbers are expected!");
        }

        if ( $conversation === null )
        {
            throw new InvalidArgumentException("Conversation doesn't exist!");
        }

        if ( empty($conversation->id) )
        {
            throw new InvalidArgumentException("Conversation with id = " . ($conversation->id) . " is not exist");
        }

        if ( !in_array($senderId, array($conversation->initiatorId, $conversation->interlocutorId)) )
        {
            throw new InvalidArgumentException("Wrong senderId!");
        }

        $senderId = (int) $senderId;
        $recipientId = ($senderId == $conversation->initiatorId) ? $conversation->interlocutorId : $conversation->initiatorId;

        $text = trim($text);

        if ( empty($text) )
        {
            throw new InvalidArgumentException("Empty string params were provided!");
        }

        // create message
        $message = new MAILBOX_BOL_Message();
        $message->conversationId = $conversation->id;
        $message->senderId = $senderId;
        $message->recipientId = $recipientId;
        $message->text = $text;
        $message->timeStamp = time();

        $this->messageDao->save($message);

        // insert record into LastMessage table
        $lastMessage = $this->lastMessageDao->findByConversationId($conversation->id);

        if ( $lastMessage === null )
        {
            $lastMessage = new MAILBOX_BOL_LastMessage();
            $lastMessage->conversationId = $conversation->id;
            $lastMessage->initiatorMessageId = $message->id;
        }
        else
        {
            switch ( $senderId )
            {
                case $conversation->initiatorId :

                    $unReadBy = MAILBOX_BOL_ConversationDao::READ_INTERLOCUTOR;
                    $readBy = MAILBOX_BOL_ConversationDao::READ_INITIATOR;
                    $unDeletedBy = MAILBOX_BOL_ConversationDao::DELETED_INTERLOCUTOR;
                    $lastMessage->initiatorMessageId = $message->id;

                    break;

                case $conversation->interlocutorId :

                    $unReadBy = MAILBOX_BOL_ConversationDao::READ_INITIATOR;
                    $readBy = MAILBOX_BOL_ConversationDao::READ_INTERLOCUTOR;
                    $unDeletedBy = MAILBOX_BOL_ConversationDao::DELETED_INITIATOR;
                    $lastMessage->interlocutorMessageId = $message->id;

                    break;
            }

            $conversation->deleted = (int) $conversation->deleted & (~$unDeletedBy);
            $conversation->read = ( (int) $conversation->read & (~$unReadBy) ) | $readBy;
            $this->conversationDao->save($conversation);
        }

        $this->lastMessageDao->save($lastMessage);

        return $message;
    }

    /**
     * Add Attachment files to message
     *
     * @param int $messageId
     * @param array $filesList
     */
    public function addMessageAttachments( $messageId, $fileList )
    {
        $language = OW::getLanguage();
        $filesCount = count($fileList['name']);

        $configs = OW::getConfig()->getValues('mailbox');

        if( empty($configs['enable_attachments']) )
        {
            return;
        }

        for ( $i = 0; $i < $filesCount; $i++ )
        {
            $message = null;

            if ( !strlen($fileList['tmp_name'][$i]) )
            {
                continue;
            }

            $uploadError = $fileList['error'][$i];

            switch( $uploadError )
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

                   // skip unsupported extensions
                    $ext = UTIL_File::getExtension($fileList['name'][$i]);

                    if ( !$this->fileExtensionIsAllowed($ext) )
                    {
                        $message = $language->text('mailbox', 'upload_file_extension_is_not_allowed');
                    }
                    else if ( $fileList['size'][$i] > (float)$configs['upload_max_file_size'] * 1024 * 1024 )
                    {
                        $message = $language->text('mailbox', 'upload_file_max_upload_filesize_error');
                    }

                    break;
            }

            if ( !empty( $message ) )
            {
                OW::getFeedback()->warning($language->text('mailbox', 'upload_file_name', array('file_name' => $fileList['name'][$i])) . " " . $message);
                continue;
            }

            $attachmentDto = new MAILBOX_BOL_Attachment();
            $attachmentDto->messageId = $messageId;
            $attachmentDto->fileName = htmlspecialchars($fileList['name'][$i]);
            $attachmentDto->fileSize = $fileList['size'][$i];
            $attachmentDto->hash = uniqid();

            $this->addAttachment($attachmentDto, $fileList['tmp_name'][$i]);
        }
    }

    /**
     * Add attachment
     *
     * @param MAILBOX_BOL_Attachment $attachmentDto
     * @param string $filePath
     * @param boolean
     */
    public function addAttachment( $attachmentDto, $filePath )
    {
        $this->attachmentDao->save($attachmentDto);

        $attId = $attachmentDto->id;
        $ext = UTIL_File::getExtension($attachmentDto->fileName);

        $attachmentPath = $this->getAttachmentFilePath($attId, $attachmentDto->hash, $ext);
        $pluginFilesPath = OW::getPluginManager()->getPlugin('mailbox')->getPluginFilesDir() . uniqid('attach');

        if ( copy($filePath, $pluginFilesPath) )
        {
            $storage = OW::getStorage();
            $storage->copyFile($pluginFilesPath, $attachmentPath);
            @unlink($pluginFilesPath);
            @unlink($filePath);

            return true;
        }
        else
        {
            $this->attachmentDao->deleteById($attId);
            return false;
        }
    }

    public function getAttachmentFilePath( $attId, $hash, $ext )
    {
        return $this->getAttachmentDir() . $this->getAttachmentFileName($attId, $hash, $ext);
    }

    public function getAttachmentDir()
    {
        return OW::getPluginManager()->getPlugin('mailbox')->getUserFilesDir() . 'attachments' . DS;
    }

    public function getAttachmentUrl()
    {
        return OW::getPluginManager()->getPlugin('mailbox')->getUserFilesUrl() . 'attachments/';
    }

    public function getAttachmentFileName( $attId, $hash, $ext )
    {
        return 'attachment_' . $attId . '_' . $hash . (strlen($ext) ? '.' . $ext : '');
    }

    public function fileExtensionIsAllowed( $ext )
    {
        if ( !strlen($ext) )
        {
            return false;
        }

        return in_array($ext, self::$allowedExtensions);
    }

    /**
     *
     * @param array $messageIdList
     * @return array<MAILBOX_BOL_Attachment>
     */
    public function findAttachmentsByMessageIdList( array $messageIdList )
    {
        return $this->attachmentDao->findAttachmentsByMessageIdList($messageIdList);
    }

    /**
    *
    * @param array $conversationIdList
    * @return array<MAILBOX_BOL_Attachment>
    */
    public function getAttachmentsCountByConversationList( array $conversationIdList )
    {
        return $this->attachmentDao->getAttachmentsCountByConversationList($conversationIdList);
    }

    /**
    *
    * @param array $conversationIdList
    * @return array<MAILBOX_BOL_Attachment>
    */
    public function deleteAttachmentsByConversationList( array $conversationIdList )
    {
        $attachmentList = $this->attachmentDao->findAttachmentstByConversationList( $conversationIdList );

        foreach( $attachmentList as $attachment )
        {
            $ext = UTIL_File::getExtension($attachment['fileName']);
            $path = $this->getAttachmentFilePath($attachment['id'],$attachment['hash'],$ext);
            
            if ( OW::getStorage()->removeFile($path) )
            {
                $this->attachmentDao->deleteById($attachment['id']);
            }
        }
    }

   /**
    * don't call this function
    * This is a temporary method used for mailbox plugin update.
    *
    * @param array $conversationIdList
    * @return array<MAILBOX_BOL_Attachment>
    */
    public function removeNl2br()
    {
        if ( !OW::getConfig()->configExists('mailbox', 'update_to_revision_3081') )
        {
            return;
        }

        if ( !OW::getConfig()->configExists('mailbox', 'last_updated_id') )
        {
            OW::getConfig()->addConfig('mailbox', 'last_updated_id', 0, '');
        }

        $lastId = OW::getConfig()->getValue('mailbox', 'last_updated_id');
        $messageList = $this->messageDao->findNotUpdatedMessages( $lastId, 2000 );

        if ( empty($messageList) )
        {
            OW::getConfig()->deleteConfig('mailbox', 'update_to_revision_3081');
            OW::getConfig()->deleteConfig('mailbox', 'last_updated_id');
            return;
        }

        $count = 0;

        foreach( $messageList as $message )
        {
            $message->text = nl2br($message->text);
            $this->messageDao->save($message);
            $count++;

            if( $count > 100 )
            {
                OW::getConfig()->saveConfig('mailbox', 'last_updated_id', $message->id);
            }
        }

        OW::getConfig()->saveConfig('mailbox', 'last_updated_id', $message->id);
    }
}