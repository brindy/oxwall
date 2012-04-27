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
 * Photo Service Class.  
 * 
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.bol
 * @since 1.0
 */
final class PHOTO_BOL_PhotoService
{
    /**
     * @var PHOTO_BOL_PhotoDao
     */
    private $photoDao;
    /**
     * @var PHOTO_BOL_PhotoFeaturedDao
     */
    private $photoFeaturedDao;
    /**
     * Class instance
     *
     * @var PHOTO_BOL_PhotoService
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    private function __construct()
    {
        $this->photoDao = PHOTO_BOL_PhotoDao::getInstance();
        $this->photoFeaturedDao = PHOTO_BOL_PhotoFeaturedDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return PHOTO_BOL_PhotoService
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * Adds photo
     *
     * @param PHOTO_BOL_Photo $photo
     * @return int
     */
    public function addPhoto( PHOTO_BOL_Photo $photo )
    {
        $this->photoDao->save($photo);

        return $photo->id;
    }

    /**
     * Updates photo
     *
     * @param PHOTO_BOL_Photo $photo
     * @return int
     */
    public function updatePhoto( PHOTO_BOL_Photo $photo )
    {
        $this->photoDao->save($photo);

        return $photo->id;
    }

    /**
     * Finds photo by id
     *
     * @param int $id
     * @return PHOTO_BOL_Photo
     */
    public function findPhotoById( $id )
    {
        return $this->photoDao->findById($id);
    }

    /**
     * Finds photo owner
     *
     * @param int $id
     * @return int
     */
    public function findPhotoOwner( $id )
    {
        return $this->photoDao->findOwner($id);
    }

    /**
     * Returns photo list 
     *
     * @param string $type
     * @param int $page
     * @param int $limit
     * @return array of PHOTO_BOL_Photo
     */
    public function findPhotoList( $type, $page, $limit, $checkPrivacy = true )
    {
        if ( $type == 'toprated' )
        {
            $first = ( $page - 1 ) * $limit;
            $topRatedList = BOL_RateService::getInstance()->findMostRatedEntityList('photo_rates', $first, $limit);

            if ( !$topRatedList )
            {
                return array();
            }
            $photoArr = $this->photoDao->findPhotoInfoListByIdList(array_keys($topRatedList));

            $photos = array();

            foreach ( $photoArr as $key => $photo )
            {
                $photos[$key] = $photo;
                $photos[$key]['score'] = $topRatedList[$photo['id']]['avgScore'];
            }

            usort($photos, array('PHOTO_BOL_PhotoService', 'sortArrayItemByDesc'));
        }
        else
        {
            $photos = $this->photoDao->getPhotoList($type, $page, $limit, $checkPrivacy);
        }

        if ( $photos )
        {
            foreach ( $photos as $key => $photo )
            {
                $photos[$key]['url'] = $this->getPhotoPreviewUrl($photo['id']);
            }
        }

        return $photos;
    }

    public static function sortArrayItemByDesc( $el1, $el2 )
    {
        if ( $el1['score'] === $el2['score'] )
        {
            return 0;
        }

        return $el1['score'] < $el2['score'] ? 1 : -1;
    }

    /**
     * Returns tagged photo list 
     *
     * @param string $type
     * @param int $page
     * @param int $limit
     * @return array of PHOTO_BOL_Photo
     */
    public function findTaggedPhotos( $tag, $page, $limit )
    {
        $first = ($page - 1 ) * $limit;

        $photoIdList = BOL_TagService::getInstance()->findEntityListByTag('photo', $tag, $first, $limit);

        if ( !$photoIdList )
        {
            return array();
        }
        
        $photos = $this->photoDao->findPhotoInfoListByIdList($photoIdList);

        if ( $photos )
        {
            foreach ( $photos as $key => $photo )
            {
                $photos[$key]['url'] = $this->getPhotoPreviewUrl($photo['id']);
            }
        }

        return $photos;
    }

    /**
     * Counts photos
     *
     * @param string $type
     * @return int
     */
    public function countPhotos( $type, $checkPrivacy = true )
    {
        if ( $type == 'toprated' )
        {
            return BOL_RateService::getInstance()->findMostRatedEntityCount('photo');
        }

        return $this->photoDao->countPhotos($type, $checkPrivacy);
    }

    public function countFullsizePhotos()
    {
        return (int) $this->photoDao->countFullsizePhotos();
    }

    /**
     * Counts all user uploaded photos
     *
     * @param int $userId
     */
    public function countUserPhotos( $userId )
    {
        return $this->photoDao->countUserPhotos($userId);
    }

    /**
     * Counts photos with tag
     *
     * @param string $tag
     * @return int
     */
    public function countTaggedPhotos( $tag )
    {
        return BOL_TagService::getInstance()->findEntityCountByTag('photo', $tag);
    }

    /**
     * Returns photo URL
     *
     * @param int $id
     * @return string
     */
    public function getPhotoUrl( $id, $preview = false )
    {
        return $this->photoDao->getPhotoUrl($id, $preview);
    }

    /**
     * Returns photo preview URL
     *
     * @param int $id
     * @return string
     */
    public function getPhotoPreviewUrl( $id )
    {
        return $this->photoDao->getPhotoUrl($id, true);
    }

    public function getPhotoFullsizeUrl( $id )
    {
        return $this->photoDao->getPhotoFullsizeUrl($id);
    }

    /**
     * Get directory where 'photo' plugin images are uploaded
     *
     * @return string
     */
    public function getPhotoUploadDir()
    {
        return $this->photoDao->getPhotoUploadDir();
    }

    /**
     * Get path to photo in file system
     *
     * @param int $photoId
     * @param string $type
     * @return string
     */
    public function getPhotoPath( $photoId, $type = '' )
    {
        return $this->photoDao->getPhotoPath($photoId, $type);
    }

    public function getPhotoPluginFilesPath( $photoId, $type = '' )
    {
        return $this->photoDao->getPhotoPluginFilesPath($photoId, $type);
    }

    /**
     * Returns a list of thotos in album
     *
     * @param int $album
     * @param int $page
     * @param int $limit
     * @return string
     */
    public function getAlbumPhotos( $album, $page, $limit )
    {
        $photos = $this->photoDao->getAlbumPhotos($album, $page, $limit);

        $list = array();

        if ( $photos )
        {
            $commentService = BOL_CommentService::getInstance();

            foreach ( $photos as $key => $photo )
            {
                $list[$key]['dto'] = $photo;
                $list[$key]['comments_count'] = $commentService->findCommentCount('photo', $photo->id);
                $list[$key]['url'] = $this->getPhotoPreviewUrl($photo->id);
            }
        }

        return $list;
    }

    /**
     * Updates the 'status' field of the photo object 
     *
     * @param int $id
     * @param string $status
     * @return boolean
     */
    public function updatePhotoStatus( $id, $status )
    {
        $photo = $this->photoDao->findById($id);

        $newStatus = $status == 'approve' ? 'approved' : 'blocked';

        $photo->status = $newStatus;

        $this->photoDao->save($photo);

        return $photo->id ? true : false;
    }

    /**
     * Changes photo's 'featured' status
     *
     * @param int $id
     * @param string $status
     * @return boolean
     */
    public function updatePhotoFeaturedStatus( $id, $status )
    {
        $photo = $this->photoDao->findById($id);

        if ( $photo )
        {
            $photoFeaturedService = PHOTO_BOL_PhotoFeaturedService::getInstance();

            if ( $status == 'mark_featured' )
            {
                return $photoFeaturedService->markFeatured($id);
            }
            else
            {
                return $photoFeaturedService->markUnfeatured($id);
            }
        }

        return false;
    }

    /**
     * Returns album's next photo
     *
     * @param int $albumId
     * @param int $id
     * @return array
     */
    public function getNextPhoto( $albumId, $id )
    {
        $photo = $this->photoDao->getNextPhoto($albumId, $id);

        if ( $photo )
        {
            $router = OW_Router::getInstance();

            $next = array();

            $nextPhoto['dto'] = $photo;
            $nextPhoto['url'] = $this->getPhotoPreviewUrl($photo->id);

            $nextPhoto['href'] = $router->urlForRoute('view_photo', array('id' => $photo->id));

            return $nextPhoto;
        }

        return null;
    }

    /**
     * Returns album's previous photo
     *
     * @param int $albumId
     * @param int $id
     * @return array
     */
    public function getPreviousPhoto( $albumId, $id )
    {
        $photo = $this->photoDao->getPreviousPhoto($albumId, $id);

        if ( $photo )
        {
            $router = OW_Router::getInstance();

            $next = array();

            $nextPhoto['dto'] = $photo;
            $nextPhoto['url'] = $this->getPhotoPreviewUrl($photo->id);

            $nextPhoto['href'] = $router->urlForRoute('view_photo', array('id' => $photo->id));

            return $nextPhoto;
        }

        return null;
    }

    /**
     * Returns current photo index in album
     *
     * @param int $albumId
     * @param int $id
     * @return int
     */
    public function getPhotoIndex( $albumId, $id )
    {
        return $this->photoDao->getPhotoIndex($albumId, $id);
    }

    /**
     * Deletes photo
     *
     * @param int $id
     * @return int
     */
    public function deletePhoto( $id )
    {
        if ( !$id )
        {
            return false;
        }

        if ( $this->photoDao->deleteById($id) )
        {
            BOL_CommentService::getInstance()->deleteEntityComments('photo_comments', $id);
            BOL_RateService::getInstance()->deleteEntityRates($id, 'photo_rates');
            BOL_TagService::getInstance()->deleteEntityTags($id, 'photo');

            // remove files
            $this->photoDao->removePhotoFile($id, 'main');
            $this->photoDao->removePhotoFile($id, 'preview');
            $this->photoDao->removePhotoFile($id, 'original');

            $this->photoFeaturedDao->markUnfeatured($id);

            BOL_FlagService::getInstance()->deleteByTypeAndEntityId('photo', $id);
            
            OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array(
                'entityType' => 'photo_comments',
                'entityId' => $id
            )));

            return true;
        }

        return false;
    }
    
    public function deleteFullsizePhotos()
    {
        $this->photoDao->deleteFullsizePhotos();
    }
    
    public function setMaintenanceMode( $mode = true )
    {
        $config = OW::getConfig();
        
        if ( $mode )
        {
            $state = (int) $config->getValue('base', 'maintenance');
            $config->saveConfig('photo', 'maintenance_mode_state', $state);
            OW::getApplication()->setMaintenanceMode($mode);
        }
        else 
        {
            $state = (int) $config->getValue('photo', 'maintenance_mode_state');
            $config->saveConfig('base', 'maintenance', $state);
        }
    }
}