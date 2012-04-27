<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
final class BOL_AttachmentService
{
    /**
     * Singleton instance.
     *
     * @var BOL_AttachmentService
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return BOL_AttachmentService
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
     * Constructor.
     */
    private function __construct()
    {
        
    }

    public function deleteExpiredTempImages()
    {
        $handle = opendir($this->getAttachmentsTempDir());

        if ( $handle )
        {
            while ( ($item = readdir($handle)) !== false )
            {
                if ( $item === '.' || $item === '..' )
                {
                    continue;
                }

                if ( ( time() - filemtime($this->getAttachmentsTempDir() . $item) ) > 3600 )
                {
                    @unlink($this->getAttachmentsTempDir() . $item);
                }
            }

            closedir($handle);
        }
    }

    public function deleteImage( $url )
    {
        if ( OW::getStorage()->fileExists($this->getAttachmentsDir() . basename($url)) )
        {
            OW::getStorage()->removeFile($this->getAttachmentsDir() . basename($url));
        }
    }

    public function saveTempImage( $uid )
    {
        $fileName = $this->findTempImageNameByUid($uid);
        if ( $fileName )
        {
            $filePath = $this->getAttachmentsTempDir() . $fileName;
            OW::getStorage()->copyFile($filePath, $this->getAttachmentsDir() . $fileName);
            @unlink($filePath);
        }

        return OW::getStorage()->getFileUrl($this->getAttachmentsDir() . $fileName);
    }

    public function findTempImageNameByUid( $uid )
    {
        $handle = opendir($this->getAttachmentsTempDir());

        while ( ($item = readdir($handle)) !== false )
        {
            if ( $item === '.' || $item === '..' )
            {
                continue;
            }

            $tempArr = explode('.', $item);

            if ( $tempArr[0] == $uid )
            {
                closedir($handle);
                return $item;
            }
        }

        closedir($handle);
        return null;
    }

    public function getAttachmentsTempDir()
    {
        return OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments' . DS . 'temp' . DS;
    }

    public function getAttachmentsTempDirUrl()
    {
        return OW::getPluginManager()->getPlugin('base')->getUserFilesUrl() . 'attachments/temp/';
    }

    public function getAttachmentsDir()
    {
        return OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments' . DS;
    }
//    public function getAttachmentsDirUrl()
//    {
//        return OW::getPluginManager()->getPlugin('base')->getUserFilesUrl() . 'attachments/';
//    }
}