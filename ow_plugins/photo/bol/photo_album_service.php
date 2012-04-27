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
 * Photo Album Service Class.  
 * 
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.bol
 * @since 1.0
 * 
 */
final class PHOTO_BOL_PhotoAlbumService
{
    /**
     * @var PHOTO_BOL_PhotoAlbumDao
     */
    private $photoAlbumDao;
    /**
     * @var PHOTO_BOL_PhotoDao
     */
    private $photoDao;
    /**
     * Class instance
     *
     * @var PHOTO_BOL_PhotoAlbumService
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    private function __construct()
    {
        $this->photoAlbumDao = PHOTO_BOL_PhotoAlbumDao::getInstance();
        $this->photoDao = PHOTO_BOL_PhotoDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return PHOTO_BOL_PhotoAlbumService
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
     * Finds album by id
     *
     * @param int $id
     * @return PHOTO_BOL_PhotoAlbum
     */
    public function findAlbumById( $id )
    {
        return $this->photoAlbumDao->findById($id);
    }
    
    public function countAlbums()
    {
        return $this->photoAlbumDao->countAll();
    }

    /**
     * Finds album by name
     *
     * @param string $name
     * @param int $userId
     * @return PHOTO_BOL_PhotoAlbum
     */
    public function findAlbumByName( $name, $userId )
    {
        return $this->photoAlbumDao->findAlbumByName($name, $userId);
    }

    /**
     * Counts user albums
     *
     * @param string $type
     * @return int
     */
    public function countUserAlbums( $userId )
    {
        return $this->photoAlbumDao->countAlbums($userId);
    }

    /**
     * Counts photos in the album
     *
     * @param int $albumId
     */
    public function countAlbumPhotos( $albumId )
    {
        return $this->photoDao->countAlbumPhotos($albumId);
    }

    /**
     * Returns user's photo albums list
     *
     * @param int $userId
     * @param int $page
     * @param int $limit
     * @return array of PHOTO_BOL_PhotoAlbum
     */
    public function findUserAlbumList( $userId, $page, $limit )
    {
        $albums = $this->photoAlbumDao->getUserAlbumList($userId, $page, $limit);

        $list = array();

        if ( $albums )
        {
            foreach ( $albums as $key => $album )
            {
                $list[$key]['dto'] = $album;
                $list[$key]['photo_count'] = $this->photoDao->countAlbumPhotos($album->id);
                $list[$key]['cover'] = $this->photoAlbumDao->getAlbumCover($album->id);
            }
        }

        return $list;
    }

    /**
     * Deletes user albums
     * 
     * 
     * @param int $userId
     * @return boolean
     */
    public function deleteUserAlbums( $userId )
    {
        if ( !$userId )
        {
            return false;
        }

        $count = $this->countUserAlbums($userId);

        if ( !$count )
        {
            return true;
        }

        $albums = $this->findUserAlbumList($userId, 1, $count);

        if ( $albums )
        {
            foreach ( $albums as $album )
            {
                $dto = $album['dto'];
                $this->deleteAlbum($dto->id);
            }
        }

        return true;
    }

    /**
     * Get a list of albums for suggest
     *
     * @param int $userId
     * @param string $query
     * @return array of PHOTO_Bol_PhotoAlbum
     */
    public function suggestUserAlbums( $userId, $query = '' )
    {
        return $this->photoAlbumDao->suggestUserAlbums($userId, $query);
    }

    /**
     * Get album update time - time when last photo was added
     *
     * @param int $albumId
     * @return int
     */
    public function getAlbumUpdateTime( $albumId )
    {
        $lastPhoto = $this->photoDao->getLastPhoto($albumId);

        return $lastPhoto ? $lastPhoto->addDatetime : null;
    }

    /**
     * Adds photo album
     *
     * @param PHOTO_BOL_PhotoAlbum $album
     * @return int
     */
    public function addAlbum( PHOTO_BOL_PhotoAlbum $album )
    {
        $this->photoAlbumDao->save($album);

        return $album->id;
    }

    /**
     * Updates photo album
     *
     * @param PHOTO_BOL_PhotoAlbum $album
     * @return int
     */
    public function updateAlbum( PHOTO_BOL_PhotoAlbum $album )
    {
        $this->photoAlbumDao->save($album);

        return $album->id;
    }

    /**
     * Deletes photo album
     * 
     * @param int $albumId
     * @return boolean
     */
    public function deleteAlbum( $albumId )
    {
        if ( !$albumId )
            return false;

        $album = $this->findAlbumById($albumId);

        if ( $album )
        {
            $photos = $this->photoDao->getAlbumAllPhotos($albumId);

            $photoService = PHOTO_BOL_PhotoService::getInstance();

            if ( $photos )
            {
                foreach ( $photos as $photo )
                {
                    $photoService->deletePhoto($photo->id);
                }
            }

            return $this->photoAlbumDao->deleteById($albumId) ? true : false;
        }
    }
    
    public function deleteAlbums( $limit )
    {
        $config = OW::getConfig();
        
        $albums = $this->photoAlbumDao->getAlbumsForDelete($limit);
        
        if ( $albums )
        {
            foreach ( $albums as $albumId )
            {
                $this->deleteAlbum($albumId);
            }
        }
    }
    
    public function updatePhotosPrivacy( $userId, $privacy )
    {
        $albumIdList = $this->photoAlbumDao->getUserAlbumIdList($userId);

        if ( !$albumIdList )
        {
            return;
        }
        
        $this->photoDao->updatePrivacyByAlbumIdList($albumIdList, $privacy);
        
        foreach ( $albumIdList as $albumId ) 
        {
            if ( !$photos = $this->photoDao->getAlbumAllPhotos($albumId) )
            {
                continue;
            }
            
            $idList = array();
            foreach ( $photos as $photo )
            {
                array_push($idList, $photo->id);
            }
            
            $event = new OW_Event(
                'base.update_entity_items_status', 
                array('entityType' => 'photo_comments', 'entityIds' => $idList, 'status' => $privacy == 'everybody')
            );
            OW::getEventManager()->trigger($event);
        }
    }
}