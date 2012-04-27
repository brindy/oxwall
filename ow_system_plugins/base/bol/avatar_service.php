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
 * Avatar service class
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_system_plugins.base.bol
 * @since 1.0
 */
final class BOL_AvatarService
{
    /**
     * @var BOL_AvatarDao
     */
    private $avatarDao;


    const AVATAR_PREFIX = 'avatar_';

    const AVATAR_BIG_PREFIX = 'avatar_big_';

    const AVATAR_ORIGINAL_PREFIX = 'avatar_original_';

    /**
     * @var BOL_AvatarService
     */
    private static $classInstance;

    /**
     * Class constructor
     */
    private function __construct()
    {
        $this->avatarDao = BOL_AvatarDao::getInstance();
    }

    /**
     * Singleton instance.
     *
     * @return BOL_AvatarService
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
     * Find avatar object by userId
     *
     * @param int $userId
     * @return BOL_Avatar
     */
    public function findByUserId( $userId )
    {
        return $this->avatarDao->findByUserId($userId);
    }

    /**
     * Updates avatar object
     *
     * @param BOL_Avatar $avatar
     * @return int
     */
    public function updateAvatar( BOL_Avatar $avatar )
    {
        $this->avatarDao->save($avatar);

        return $avatar->id;
    }

    /**
     * Removes avatar image file
     *
     * @param string $path
     */
    public function removeAvatarImage( $path )
    {
        $storage = OW::getStorage();

        if ( $storage->fileExists($path) )
        {
            $storage->removeFile($path);
        }
    }

    /**
     * Removes user avatar
     * 
     * @param int $userId
     * @return boolean
     */
    public function deleteUserAvatar( $userId )
    {
        if ( !$userId )
        {
            return false;
        }

        if ( !$this->userHasAvatar($userId) )
        {
            return true;
        }

        $avatar = $this->findByUserId($userId);

        if ( $avatar )
        {
            $this->avatarDao->deleteById($avatar->id);

            // avatar image
            $avatarPath = $this->getAvatarPath($userId, 1, $avatar->hash);
            $this->removeAvatarImage($avatarPath);

            // avatar big image
            $bigAvatarPath = $this->getAvatarPath($userId, 2, $avatar->hash);
            $this->removeAvatarImage($bigAvatarPath);

            // avatar original image
            $origAvatarPath = $this->getAvatarPath($userId, 3, $avatar->hash);
            $this->removeAvatarImage($origAvatarPath);

            return true;
        }
    }

    /**
     * Crops user avatar using coordinates
     *
     * @param int $userId
     * @param array $coords
     * @param int $viewSize
     * @param int $hash
     */
    public function cropAvatar( $userId, $coords, $viewSize, $hash )
    {
        if ( !empty($coords) )
        {
            $origAvatarPath = $this->getAvatarPath($userId, 3);

            // tmp file in pluginfiles
            $tmpPath = $this->getAvatarPluginFilesPath($userId, 3, $hash);

            $storage = OW::getStorage();
            $storage->copyFileToLocalFS($origAvatarPath, $tmpPath);

            $image = new UTIL_Image($tmpPath);

            $width = $image->getWidth();
            $height = $image->getHeight();

            $k = $width / $viewSize;

            $config = OW::getConfig();
            $avatarSize = (int) $config->getValue('base', 'avatar_size');
            $bigAvatarSize = (int) $config->getValue('base', 'avatar_big_size');

            $avatarPath = $this->getAvatarPath($userId, 1, $hash);
            $bigAvatarPath = trim($this->getAvatarPath($userId, 2, $hash));

            // pluginfiles tmp path
            $avatarPFPath = $this->getAvatarPluginFilesPath($userId, 1, $hash);
            $bigAvatarPFPath = trim($this->getAvatarPluginFilesPath($userId, 2, $hash));

            $image->cropImage($coords['x'] * $k, $coords['y'] * $k, $coords['w'] * $k, $coords['h'] * $k)
                ->resizeImage($bigAvatarSize, $bigAvatarSize, true)
                ->saveImage($bigAvatarPFPath)
                ->resizeImage($avatarSize, $avatarSize, true)
                ->saveImage($avatarPFPath);

            $storage->copyFile($bigAvatarPFPath, $bigAvatarPath);
            $storage->copyFile($avatarPFPath, $avatarPath);

            @unlink($tmpPath);
            @unlink($avatarPFPath);
            @unlink($bigAvatarPFPath);
        }
    }

    public function setUserAvatar( $userId, $uploadedFileName )
    {
        $avatar = $this->findByUserId($userId);

        if ( !$avatar )
        {
            $avatar = new BOL_Avatar();
            $avatar->userId = $userId;
        }
        else
        {
            $oldHash = $avatar->hash;
        }
        $avatar->hash = time();

        // destination path
        $avatarPath = $this->getAvatarPath($userId, 1, $avatar->hash);
        $avatarBigPath = $this->getAvatarPath($userId, 2, $avatar->hash);
        $avatarOriginalPath = $this->getAvatarPath($userId, 3, $avatar->hash);

        // pluginfiles tmp path
        $avatarPFPath = $this->getAvatarPluginFilesPath($userId, 1, $avatar->hash);
        $avatarPFBigPath = $this->getAvatarPluginFilesPath($userId, 2, $avatar->hash);
        $avatarPFOriginalPath = $this->getAvatarPluginFilesPath($userId, 3, $avatar->hash);

        if ( !is_writable(dirname($avatarPFPath)) )
        {
            return false;
        }

        try
        {
            $image = new UTIL_Image($uploadedFileName);

            $config = OW::getConfig();

            $configAvatarSize = $config->getValue('base', 'avatar_size');
            $configBigAvatarSize = $config->getValue('base', 'avatar_big_size');

            $image->copyImage($avatarPFOriginalPath)
                ->resizeImage($configBigAvatarSize, $configBigAvatarSize, true)
                ->saveImage($avatarPFBigPath)
                ->resizeImage($configAvatarSize, $configAvatarSize, true)
                ->saveImage($avatarPFPath);

            $this->updateAvatar($avatar);

            // remove old images
            if ( isset($oldHash) )
            {
                $oldAvatarPath = $this->getAvatarPath($userId, 1, $oldHash);
                $oldAvatarBigPath = $this->getAvatarPath($userId, 2, $oldHash);
                $oldAvatarOriginalPath = $this->getAvatarPath($userId, 3, $oldHash);

                $this->removeAvatarImage($oldAvatarPath);
                $this->removeAvatarImage($oldAvatarBigPath);
                $this->removeAvatarImage($oldAvatarOriginalPath);
            }

            $storage = OW::getStorage();

            $storage->copyFile($avatarPFOriginalPath, $avatarOriginalPath);
            $storage->copyFile($avatarPFBigPath, $avatarBigPath);
            $storage->copyFile($avatarPFPath, $avatarPath);

            @unlink($avatarPFPath);
            @unlink($avatarPFBigPath);
            @unlink($avatarPFOriginalPath);

            return true;
        }
        catch ( Exception $e )
        {
            return false;
        }
    }

    /**
     * Give avatar original new name after hash is changed
     *
     * @param int $userId
     * @param int $oldHash
     * @param int $newHash
     */
    public function renameAvatarOriginal( $userId, $oldHash, $newHash )
    {
        $originalPath = $this->getAvatarPath($userId, 3, $oldHash);
        $newPath = $this->getAvatarPath($userId, 3, $newHash);

        OW::getStorage()->renameFile($originalPath, $newPath);
    }

    /**
     * Get url to access avatar image
     *
     * @param int $userId
     * @param boolean $original
     * @return string
     */
    public function getAvatarUrl( $userId, $size = 1, $hash = null )
    {
        $avatar = $this->avatarDao->findByUserId($userId);

        if ( $avatar )
        {
            $dir = OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'avatars' . DS;

            $hash = isset($hash) ? $hash : $avatar->getHash();

            switch ( $size )
            {
                case 1:
                    return OW::getStorage()->getFileUrl($dir . self::AVATAR_PREFIX . $userId . '_' . $hash . '.jpg');

                case 2:
                    return OW::getStorage()->getFileUrl($dir . self::AVATAR_BIG_PREFIX . $userId . '_' . $hash . '.jpg');

                case 3:
                    return OW::getStorage()->getFileUrl($dir . self::AVATAR_ORIGINAL_PREFIX . $userId . '_' . $hash . '.jpg');
            }
        }
        else
        {
            return null;
        }
    }

    /**
     * Returns default avatar URL
     *
     * @param int $size
     * @return string
     */
    public function getDefaultAvatarUrl( $size = 1 )
    {
        $custom = self::getCustomDefaultAvatarUrl($size);
        
        if ( $custom != null )
        {
            return $custom;
        }
        
        switch ( $size )
        {
            case 1:
                return OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'no-avatar.png';

            case 2:
                return OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'no-avatar-big.png';
        }
    }
    
    private function getCustomDefaultAvatarUrl( $size = 1 )
    {
        if ( !in_array($size, array(1,2)) )
        {
            return null;
        }
        
        $conf = json_decode(OW::getConfig()->getValue('base', 'default_avatar'), true);
        
        $url = OW::getPluginManager()->getPlugin('base')->getUserFilesUrl() . 'avatars/';
        
        return isset($conf[$size]) ? $url . $conf[$size] : null;
    }
    
    public function setCustomDefaultAvatar( $size, $file )
    {
        $conf = json_decode(OW::getConfig()->getValue('base', 'default_avatar'), true);
        
        $dir = OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'avatars' . DS;
        
        $ext = UTIL_File::getExtension($file['name']);
        $prefix = 'default_' . ($size == 1 ? self::AVATAR_PREFIX : self::AVATAR_BIG_PREFIX); 
        
        $fileName = $prefix . uniqid() . '.' . $ext;
        
        if ( move_uploaded_file($file['tmp_name'], $dir . $fileName) )
        {
            if ( isset($conf[$size]) )
            {
                @unlink($dir . $prefix . $conf[$size]);
            }
            
            $conf[$size] = $fileName;
            OW::getConfig()->saveConfig('base', 'default_avatar', json_encode($conf));

            return true;
        }
        
        return false;
    }
    
    public function deleteCustomDefaultAvatar ( $size )
    {
        $conf = json_decode(OW::getConfig()->getValue('base', 'default_avatar'), true);
        
        if ( !isset($conf[$size]) )
        {
            return false;
        }
        
        $dir = OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'avatars' . DS;
        
        @unlink($dir . $conf[$size]);
        
        unset($conf[$size]);
        OW::getConfig()->saveConfig('base', 'default_avatar', json_encode($conf));
        
        return true;
    }

    /**
     * Returns list of users' avatars
     *
     * @param array $userIds
     * @return array
     */
    public function getAvatarsUrlList( array $userIds )
    {
        if ( empty($userIds) )
        {
            return array();
        }

        $urlsList = array();

        if ( is_array($userIds) )
        {
            $avatars = array();

            $prefix = OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'avatars' . DS . self::AVATAR_PREFIX;
            $defAvatarUrl = $this->getDefaultAvatarUrl();

            $objList = $this->avatarDao->getAvatarsList($userIds);

            foreach ( $objList as $avatar )
            {
                $avatars[$avatar->userId] = $avatar;
            }

            foreach ( $userIds as $userId )
            {
                if ( array_key_exists($userId, $avatars) )
                {
                    $urlsList[$userId] = OW::getStorage()->getFileUrl($prefix . $userId . '_' . $avatars[$userId]->hash . '.jpg');
                }
                else
                {
                    $urlsList[$userId] = $defAvatarUrl;
                }
            }
        }

        return $urlsList;
    }

    /**
     * Get avatar path in filesystem
     *
     * @param int $userId
     * @param boolean $original
     * @param int $hash
     * @return string
     */
    public function getAvatarPath( $userId, $size = 1, $hash = null )
    {
        $avatar = $this->avatarDao->findByUserId($userId);

        $dir = $this->getAvatarsDir();

        if ( $avatar )
        {
            $hash = isset($hash) ? $hash : $avatar->getHash();
        }

        switch ( $size )
        {
            case 1:
                return $dir . self::AVATAR_PREFIX . $userId . '_' . $hash . '.jpg';

            case 2:
                return $dir . self::AVATAR_BIG_PREFIX . $userId . '_' . $hash . '.jpg';

            case 3:
                return $dir . self::AVATAR_ORIGINAL_PREFIX . $userId . '_' . $hash . '.jpg';
        }
    }

    public function getAvatarPluginFilesPath( $userId, $size = 1, $hash = null )
    {
        $avatar = $this->avatarDao->findByUserId($userId);

        $dir = $this->getAvatarsPluginFilesDir();

        if ( $avatar )
        {
            $hash = isset($hash) ? $hash : $avatar->getHash();
        }

        switch ( $size )
        {
            case 1:
                return $dir . self::AVATAR_PREFIX . $userId . '_' . $hash . '.jpg';

            case 2:
                return $dir . self::AVATAR_BIG_PREFIX . $userId . '_' . $hash . '.jpg';

            case 3:
                return $dir . self::AVATAR_ORIGINAL_PREFIX . $userId . '_' . $hash . '.jpg';
        }
    }

    public function getAvatarsDir()
    {
        return OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'avatars' . DS;
    }

    public function getAvatarsPluginFilesDir()
    {
        return OW::getPluginManager()->getPlugin('base')->getPluginFilesDir() . 'avatars' . DS;
    }

    /**
     * Checks if user has avatar
     *
     * @param int $userId
     * @return boolean
     */
    public function userHasAvatar( $userId )
    {
        $avatar = $this->avatarDao->findByUserId($userId);

        return $avatar != null;
    }

    public function trackAvatarChangeActivity( $userId, $avatarId )
    {
        $data = array(
            'string' => OW::getLanguage()->text('base', 'avatar_activity_string',
                array(
                    'user' => BOL_UserService::getInstance()->getDisplayName($userId),
                    'userUrl' => BOL_UserService::getInstance()->getUserUrl($userId)
                )
            ),
            'content' => '<img src="' . $this->getAvatarUrl($userId) . '" />'
        );

        // Newsfeed
        $event = new OW_Event('feed.action', array(
            'pluginKey' => 'base_avatar',
            'entityType' => 'avatar-change',
            'entityId' => $avatarId,
            'userId' => $userId,
            'replace' => true
        ), array(
            'string' => OW::getLanguage()->text('base', 'avatar_feed_string'),
            'view' => array(
                'iconClass' => 'ow_ic_picture'
            )
        ));
        OW::getEventManager()->trigger($event);
    }
    
    public function getDataForUserAvatars( $userIdList, $src = true, $url = true, $dispName = true, $role = true )
    {
        if ( !count($userIdList) )
        {
            return null;
        }
        
        $data = array();
        
        if ( $src )
        {
            $srcArr = $this->getAvatarsUrlList($userIdList);
        }
        
        if ( $url )
        {
            $urlArr = BOL_UserService::getInstance()->getUserUrlsForList($userIdList);
        }
        
        if ( $dispName )
        {
            $dnArr = BOL_UserService::getInstance()->getDisplayNamesForList($userIdList);
        }
        
        if ( $role )
        {
            $roleArr = BOL_AuthorizationService::getInstance()->getRoleListOfUsers($userIdList);
        }

        foreach ( $userIdList as $userId )
        {
            if ( $src )
            {
                $data[$userId]['src'] = !empty($srcArr[$userId]) ? $srcArr[$userId] : '_AVATAR_SRC_';
            }
            if ( $url )
            {
                $data[$userId]['url'] = !empty($urlArr[$userId]) ? $urlArr[$userId] : '#_USER_URL_';
            }
            if ( $dispName )
            {
                $data[$userId]['title'] = !empty($dnArr[$userId]) ? $dnArr[$userId] : null;
            }
            if ( $role )
            {
                $data[$userId]['label'] = !empty($roleArr[$userId]) ? $roleArr[$userId]['label'] : null;
                $data[$userId]['labelColor'] = !empty($roleArr[$userId]) ? $roleArr[$userId]['custom'] : null;
            }
        }
        
        return $data;
    }
}