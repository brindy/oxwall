<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2009, Skalfa LLC
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
 *  - Neither the name of the Skalfa LLC nor the names of its contributors may be used to endorse or promote products
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
 * Temporary Photo Service Class.  
 * 
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.bol
 * @since 1.0
 * 
 */
final class PHOTO_BOL_PhotoTemporaryService
{
    /**
     * @var PHOTO_BOL_PhotoTemporaryDao
     */
    private $photoTemporaryDao;
    /**
     * Class instance
     *
     * @var PHOTO_BOL_PhotoTemporaryService
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    private function __construct()
    {
        $this->photoTemporaryDao = PHOTO_BOL_PhotoTemporaryDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return PHOTO_BOL_PhotoTemporaryService
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    public function addTemporaryPhoto( $source, $userId, $order )
    {
        if ( !is_uploaded_file($source) || !$userId )
        {
            return false;
        }
        
        $tmpPhoto = new PHOTO_BOL_PhotoTemporary();
        $tmpPhoto->userId = $userId;
        $tmpPhoto->addDatetime = time();
        $tmpPhoto->hasFullsize = 0;
        $tmpPhoto->order = $order;
        $this->photoTemporaryDao->save($tmpPhoto);
        
        $preview = $this->photoTemporaryDao->getTemporaryPhotoPath($tmpPhoto->id, 1);
        $main = $this->photoTemporaryDao->getTemporaryPhotoPath($tmpPhoto->id, 2);
        $original = $this->photoTemporaryDao->getTemporaryPhotoPath($tmpPhoto->id, 3);
        
        $config = OW::getConfig();
        $width = $config->getValue('photo', 'main_image_width');
        $previewWidth = $config->getValue('photo', 'preview_image_width');
        $previewHeight = $config->getValue('photo', 'preview_image_height');
        
        try {
            $image = new UTIL_Image($source);
            
            $mainPhoto = $image
                ->resizeImage($width, null)
                ->saveImage($main);           

            if ( (bool) $config->getValue('photo', 'store_fullsize') && $mainPhoto->imageResized() )
            {
                $originalImage = new UTIL_Image($source);
                $res = (int) $config->getValue('photo', 'fullsize_resolution');
                $res = $res ? $res : 1024;
                $originalImage
                    ->resizeImage($res, $res)
                    ->saveImage($original);
                
                $tmpPhoto->hasFullsize = 1;
                $this->photoTemporaryDao->save($tmpPhoto);
            }
            
            $mainPhoto
                ->resizeImage($previewWidth, $previewHeight, true)
                ->saveImage($preview);
        }
        catch ( WideImage_Exception $e )
        {
            $this->photoTemporaryDao->deleteById($tmpPhoto->id);
            return false;
        }
        
        return true;
    }
    
    public function moveTemporaryPhoto( $tmpId, $albumId, $desc, $tag )
    {
        $photoService = PHOTO_BOL_PhotoService::getInstance();
        
        $tmp = $this->photoTemporaryDao->findById($tmpId);
        
        if ( !$tmp )
        {
            return false;
        }

        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId);
        $privacy = OW::getEventManager()->call(
            'plugin.privacy.get_privacy', 
            array('ownerId' => $album->userId, 'action' => 'photo_view_album')
        );
        
        $photo = new PHOTO_BOL_Photo();
        $photo->description = htmlspecialchars($desc);
        $photo->albumId = $albumId;
        $photo->addDatetime = time();
        $photo->status = 'approved';
        $photo->hasFullsize = $tmp->hasFullsize;
        $photo->privacy = mb_strlen($privacy) ? $privacy : 'everybody';
        
        if ( $photoService->addPhoto($photo) )
        {
            $storage = OW::getStorage();
            
            $main = $photoService->getPhotoPath($photo->id);
            $mainTmp = $this->photoTemporaryDao->getTemporaryPhotoPath($tmp->id, 2);
            $storage->copyFile($mainTmp, $main);
            @unlink($mainTmp);

            $preview = $photoService->getPhotoPath($photo->id, 'preview');
            $previewTmp = $this->photoTemporaryDao->getTemporaryPhotoPath($tmp->id, 1);
            $storage->copyFile($previewTmp, $preview);
            @unlink($previewTmp);
            
            if ( $photo->hasFullsize )
            {
                $original = $photoService->getPhotoPath($photo->id, 'original');
                $originalTmp = $this->photoTemporaryDao->getTemporaryPhotoPath($tmp->id, 3);
                $storage->copyFile($originalTmp, $original);
                @unlink($originalTmp);
            }
            
            $this->photoTemporaryDao->deleteById($tmp->id);
            
            if ( mb_strlen($tag) )
            {
                BOL_TagService::getInstance()->updateEntityTags($photo->id, 'photo', TagsField::getTags($tag));
            }
            
            return $photo;
        }
        
        return false;
    }
    
    public function findUserTemporaryPhotos( $userId, $orderBy = 'timestamp' )
    {
        $list = $this->photoTemporaryDao->findByUserId($userId, $orderBy);
        
        $result = array();
        if ( $list )
        {
            foreach ( $list as $photo )
            {
                $result[$photo->id]['dto'] = $photo;
                $result[$photo->id]['imageSrc'] = $this->photoTemporaryDao->getTemporaryPhotoUrl($photo->id, 1);
            }
        }
        
        return $result;
    }
    
    public function deleteUserTemporaryPhotos( $userId )
    {
        $list = $this->photoTemporaryDao->findByUserId($userId);
        
        if ( !$list )
        {
            return true;
        }
        
        foreach ( $list as $photo )
        {
            $preview = $this->photoTemporaryDao->getTemporaryPhotoPath($photo->id, 1);
            @unlink($preview);
            $main = $this->photoTemporaryDao->getTemporaryPhotoPath($photo->id, 2);
            @unlink($main);
            if ( $photo->hasFullsize )
            {
                $original = $this->photoTemporaryDao->getTemporaryPhotoPath($photo->id, 3);
                @unlink($original);
            }
            $this->photoTemporaryDao->delete($photo);
        }
        
        return true;
    }
    
    public function deleteTemporaryPhoto( $photoId )
    {
        $photo = $this->photoTemporaryDao->findById($photoId);
        if ( !$photo )
        {
            return false;
        }
        
        $preview = $this->photoTemporaryDao->getTemporaryPhotoPath($photoId, 1);
        @unlink($preview);
        $main = $this->photoTemporaryDao->getTemporaryPhotoPath($photoId, 2);
        @unlink($main);
        
        if ( $photo->hasFullsize )
        {
            $original = $this->photoTemporaryDao->getTemporaryPhotoPath($photoId, 3);
            @unlink($original);
        }
        
        $this->photoTemporaryDao->delete($photo);
        
        return true;
    }
}