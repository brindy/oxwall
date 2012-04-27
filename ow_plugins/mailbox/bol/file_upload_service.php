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
 * Upload File Service Class
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.bol
 * @since 1.0
 */
final class MAILBOX_BOL_FileUploadService
{
    /**
     * @var MAILBOX_BOL_FileUploadDao
     */
    private $uploadFileDao;

    /**
     * Class instance
     *
     * @var MAILBOX_BOL_FileUploadService
     */
    private static $classInstance;

    /**
     * Class constructor
     */
    private function __construct()
    {
        $this->uploadFileDao = MAILBOX_BOL_FileUploadDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return MAILBOX_BOL_FileUploadService
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
     *
     * @param int $userId
     * @param string $entityId
     * @return array<MAILBOX_BOL_FileUpload>
     */
    public function findUploadFileList( $entityId )
    {
        return $this->uploadFileDao->findUploadFileList( $entityId );
    }

    public function findExpireFileList( $expirationPeriod )
    {
        return $this->uploadFileDao->findExpireFileList( $expirationPeriod );
    }

    public function saveOrUpdate( MAILBOX_BOL_FileUpload $dto )
    {
        $this->uploadFileDao->save($dto);
    }

    public function addFile( MAILBOX_BOL_FileUpload $dto, $filePath )
    {
        $ext = UTIL_File::getExtension($dto->fileName);

        if( !$this->fileExtensionIsAllowed($ext) && !file_exists($filePath) )
        {
            return false;
        }

        $uploadPath = $this->getUploadFilePath($dto->hash, $ext);
        
        $dto->filePath = $uploadPath;
        $this->saveOrUpdate($dto);

        $attId = $dto->id;

        if ( move_uploaded_file($filePath, $uploadPath) )
        {
            @chmod($uploadPath, 0666);
            return true;
        }
        else
        {
            $this->uploadFileDao->deleteById($attId);
            return false;
        }
    }
    
    public function getUploadFilePath( $hash, $ext )
    {
        return MAILBOX_BOL_ConversationService::getInstance()->getAttachmentDir() . $this->getUploadFileName( $hash, $ext );
    }

    public function getUploadFileUrl( $hash, $ext )
    {
        return MAILBOX_BOL_ConversationService::getInstance()->getAttachmentUrl(). $this->getUploadFileName( $hash, $ext );
    }

    public function getUploadFileName( $hash, $ext )
    {
        return 'ajax_upload_' . $hash . (strlen($ext) ? '.' . $ext : '');
    }

    public function fileExtensionIsAllowed( $ext )
    {
        return MAILBOX_BOL_ConversationService::getInstance()->fileExtensionIsAllowed($ext);
    }

    public function deleteUploadFile( $hash, $userId )
    {
        $dto = $this->uploadFileDao->findUploadFile( $hash, $userId );

        if ( $dto === null )
        {
            return;
        }

        @unlink($dto->filePath);
        $this->uploadFileDao->deleteById($dto->id);
    }
}
