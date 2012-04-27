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
 * Forum Post Attachment Service Class
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.bol
 * @since 1.0
 */
final class FORUM_BOL_PostAttachmentService
{
    /**
     * @var FORUM_BOL_PostAttachmentService
     */
    private static $classInstance;
    /**
     * @var FORUM_BOL_PostAttachmentDao
     */
    private $attachmentDao;
    /**
     * @var array
     */
    private static $allowedExtensions =
        array(
        'txt', 'doc', 'docx', 'sql', 'csv', 'xls', 'ppt', 'pdf',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'psd', 'ai',
        'avi', 'wmv', 'mp3', '3gp', 'flv', 'mkv', 'mpeg', 'mpg', 'swf',
        'zip', 'gz', '.tgz', 'gzip', '7z', 'bzip2', 'rar'
    );

    /**
     * Class constructor
     */
    private function __construct()
    {
        $this->attachmentDao = FORUM_BOL_PostAttachmentDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return FORUM_BOL_PostAttachmentService
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
            self::$classInstance = new self();

        return self::$classInstance;
    }

    public function findPostAttachmentById( $id )
    {
        return $this->attachmentDao->findById($id);
    }

    public function findAllAttachments( )
    {
        return $this->attachmentDao->findAll();
    }
    
    /**
     * Returns attachments list
     * 
     * @param array $postIds
     * @return array
     */
    public function findAttachmentsByPostIdList( $postIds )
    {
        if ( !count($postIds) )
        {
            return array();
        }

        $attmList = $this->attachmentDao->findAttachmentsByPostIdList($postIds);

        $list = array();

        if ( $attmList )
        {
            foreach ( $attmList as $attm )
            {
                $attm['fileSize'] = round($attm['fileSize'] / 1024, 2);
                $attm['downloadUrl'] = $this->getAttachmentFileUrl($attm['id'], $attm['hash'], UTIL_File::getExtension($attm['fileName']));
                $list[$attm['postId']][] = $attm;
            }
        }

        return $list;
    }

    public function getAttachmentsCountByTopicIdList( $topicIds )
    {
        if ( !count($topicIds) )
        {
            return array();
        }

        $list = $this->attachmentDao->getAttachmentsCountByTopicIdList($topicIds);

        $countArray = array();

        foreach ( $list as $count )
        {
            $countArray[$count['topicId']] = $count['attachments'];
        }

        return $countArray;
    }

    public function addAttachment( FORUM_BOL_PostAttachment $attachment, $file )
    {
        $this->attachmentDao->save($attachment);

        $attId = $attachment->id;
        $ext = UTIL_File::getExtension($attachment->fileName);

        $filePath = $this->getAttachmentFilePath($attId, $attachment->hash, $ext);
        $pluginFilesPath = $this->getAttachmentPluginFilesPath($attId, $attachment->hash, $ext);

        if ( move_uploaded_file($file, $pluginFilesPath) )
        {
            $storage = OW::getStorage();
            $storage->copyFile($pluginFilesPath, $filePath);
            @unlink($pluginFilesPath);

            return true;
        }
        else
        {
            $this->attachmentDao->deleteById($attId);
            return false;
        }
    }

    public function deleteAttachment( $attId )
    {
        $attachment = $this->findPostAttachmentById($attId);

        if ( !$attachment )
        {
            return true;
        }

        $path = $this->getAttachmentFilePath($attId, $attachment->hash, UTIL_File::getExtension($attachment->fileName));

        $storage = OW::getStorage();

        if ( $storage->fileExists($path) )
        {
            $storage->removeFile($path);
        }

        $this->attachmentDao->deleteById($attId);

        return true;
    }

    public function deletePostAttachments( $postId )
    {
        if ( !$postId )
        {
            return false;
        }

        $attachments = $this->attachmentDao->findAttachmentsByPostId($postId);

        foreach ( $attachments as $file )
        {
            $this->deleteAttachment($file['id']);
        }

        return false;
    }

    public function getAttachmentFileName( $attId, $hash, $ext )
    {
        return 'attachment_' . $attId . '_' . $hash . (strlen($ext) ? '.' . $ext : '');
    }

    public function getAttachmentFilePath( $attId, $hash, $ext )
    {
        $userfilesDir = OW::getPluginManager()->getPlugin('forum')->getUserFilesDir();

        return $userfilesDir . $this->getAttachmentFileName($attId, $hash, $ext);
    }

    public function getAttachmentFileUrl( $attId, $hash, $ext )
    {
        $userfilesDir = OW::getPluginManager()->getPlugin('forum')->getUserFilesDir();
        $storage = OW::getStorage();

        return $storage->getFileUrl($userfilesDir . $this->getAttachmentFileName($attId, $hash, $ext));
    }

    public function getAttachmentPluginFilesPath( $attId, $hash, $ext )
    {
        $dir = OW::getPluginManager()->getPlugin('forum')->getPluginFilesDir();

        return $dir . $this->getAttachmentFileName($attId, $hash, $ext);
    }

    public function fileExtensionIsAllowed( $ext )
    {
        if ( !strlen($ext) )
        {
            return false;
        }

        return in_array($ext, self::$allowedExtensions);
    }
    
    public function countAttachments( )
    {
        return $this->attachmentDao->countAll();
    }
}