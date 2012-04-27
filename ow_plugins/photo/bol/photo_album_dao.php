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
 * Data Access Object for `photo_album` table.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.bol
 * @since 1.0
 */
class PHOTO_BOL_PhotoAlbumDao extends OW_BaseDao
{

    /**
     * Constructor.
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }
    /**
     * Singleton instance.
     *
     * @var PHOTO_BOL_PhotoAlbumDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return PHOTO_BOL_PhotoAlbumDao
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
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'PHOTO_BOL_PhotoAlbum';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'photo_album';
    }

    /**
     * Count albums added by a user
     *
     * @param int $userId
     * @return int
     */
    public function countAlbums( $userId )
    {
        if ( !$userId )
            return false;

        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);

        return $this->countByExample($example);
    }

    /**
     * Get the list of user albums
     *
     * @param int $userId
     * @param int $page
     * @param int $limit
     * @return array of PHOTO_BOL_PhotoAlbum
     */
    public function getUserAlbumList( $userId, $page, $limit )
    {
        $first = ( $page - 1 ) * $limit;

        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        $example->setLimitClause($first, $limit);

        return $this->findListByExample($example);
    }
    
    public function getUserAlbumIdList( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);

        return $this->findIdListByExample($example);
    }

    /**
     * Finds Photo album by album name
     *
     * @param string $name
     * @param int $userId
     * @return PHOTO_BOL_PhotoAlbum
     */
    public function findAlbumByName( $name, $userId )
    {
        $name = trim($name);

        $userId = (int) $userId;

        $example = new OW_Example();
        $example->andFieldEqual('name', $name);
        $example->andFieldEqual('userId', $userId);
        $example->setLimitClause(0, 1);

        return $this->findObjectByExample($example);
    }

    /**
     * Get album cover - album first image URL
     *
     * @param int $albumId
     * @return string
     */
    public function getAlbumCover( $albumId )
    {
        $photoDao = PHOTO_BOL_PhotoDao::getInstance();

        $photo = $photoDao->getFirstPhoto($albumId);

        return $photo ? $photoDao->getPhotoUrl($photo->id, true) : null;
    }

    /**
     * Get user albums for suggest field
     *
     * @param int $userId
     * @param string $query
     * @return array of PHOTO_Bol_PhotoAlbum
     */
    public function suggestUserAlbums( $userId, $query )
    {
        if ( !$userId )
            return false;

        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        $example->setOrder('`name` ASC');

        if ( strlen($query) )
            $example->andFieldLike('name', $query . '%');

        $example->setLimitClause(0, 10);

        return $this->findListByExample($example);
    }
    
    /**
     * Get albums to be deleted
     *
     * @param int $limit
     * @return array
     */
    public function getAlbumsForDelete( $limit )
    {
        $example = new OW_Example();
        $example->setOrder('createDatetime ASC');
        $example->setLimitClause(0, (int)$limit);
        
        return $this->findIdListByExample($example);
    }
}