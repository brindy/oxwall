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
 * Virtual Gifts Service Class
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.virtual_gifts.bol
 * @since 1.0
 */
final class VIRTUALGIFTS_BOL_VirtualGiftsService
{
    /**
     * @var VIRTUALGIFTS_BOL_VirtualGiftsService
     */
    private static $classInstance;
    /**
     * @var VIRTUALGIFTS_BOL_CategoryDao
     */
    private $categoryDao;
    /**
     * @var VIRTUALGIFTS_BOL_TemplateDao
     */
    private $templateDao;
    /**
     * @var VIRTUALGIFTS_BOL_UserGiftDao
     */
    private $userGiftDao;

    const GIFT_PREFIX = 'gift_';
    
    private static $ext = array('jpg', 'jpeg', 'png', 'gif', 'bmp');
    
    /**
     * Class constructor
     */
    private function __construct()
    {
        $this->categoryDao = VIRTUALGIFTS_BOL_CategoryDao::getInstance();
        $this->templateDao = VIRTUALGIFTS_BOL_TemplateDao::getInstance();
        $this->userGiftDao = VIRTUALGIFTS_BOL_UserGiftDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return VIRTUALGIFTS_BOL_VirtualGiftsService
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
            self::$classInstance = new self();

        return self::$classInstance;
    }
    
    /**
     * Adds virtual gift template
     * 
     * @param VIRTUALGIFTS_BOL_Template $template
     * @param string $file
     * 
     * @return boolean
     */
    public function addTemplate( VIRTUALGIFTS_BOL_Template $template, $file )
    {
        $this->templateDao->save($template);
        $tplId = $template->id;
        
        $filePath = $this->getGiftFilePath($tplId, $template->uploadTimestamp, $template->extension);
        $pluginFilesPath = $this->getGiftPluginFilesPath($tplId, $template->uploadTimestamp, $template->extension);

        if ( move_uploaded_file($file, $pluginFilesPath) )
        {
            $storage = OW::getStorage();
            $storage->copyFile($pluginFilesPath, $filePath);
            @unlink($pluginFilesPath);

            return $tplId;
        }
        else
        {
            $this->templateDao->deleteById($tplId);
            
            return false;
        }
    }
    
    /**
     * Updates virtual gift template
     * 
     * @param VIRTUALGIFTS_BOL_Template $template
     */
    public function updateTemplate( VIRTUALGIFTS_BOL_Template $template )
    {
        $this->templateDao->save($template);
        
        return true;
    }
    
    /**
     * Updates gift template image
     *  
     * @param VIRTUALGIFTS_BOL_Template $template
     * @param array $file
     * 
     * @return boolean
     */
    public function updateTemplateImage( VIRTUALGIFTS_BOL_Template $template, $file )
    {
        $time = time();
        $ext = UTIL_File::getExtension($file['name']);
        
        $pluginFilesPath = $this->getGiftPluginFilesPath($template->id, $time, $ext);
        
        if ( move_uploaded_file($file['tmp_name'], $pluginFilesPath) )
        {
            $filePath = $this->getGiftFilePath($template->id, $time, $ext);
            
            $storage = OW::getStorage();
            $storage->copyFile($pluginFilesPath, $filePath);
            @unlink($pluginFilesPath);
            
            // remove old image
            $filePath = $this->getGiftFilePath($template->id, $template->uploadTimestamp, $template->extension);
            $storage->removeFile($filePath);
            
            // update template dto
            $template->extension = $ext;
            $template->uploadTimestamp = $time;
            $this->templateDao->save($template);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Finds template by Id
     *
     * @param int $tplId
     */
    public function findTemplateById( $tplId )
    {
        return $this->templateDao->findById($tplId);
    }
    
    /**
     * Returns gift path
     * 
     * @param int $tplId
     * @param int $hash
     * @param string $ext
     */
    public function getGiftFilePath( $tplId, $hash, $ext )
    {
        $userfilesDir = OW::getPluginManager()->getPlugin('virtualgifts')->getUserFilesDir();

        return $userfilesDir . $this->getGiftFileName($tplId, $hash, $ext);
    }

    /**
     * Returns gift url
     * 
     * @param int $tplId
     * @param int $hash
     * @param string $ext
     */
    public function getGiftFileUrl( $tplId, $hash, $ext )
    {
    	$userfilesDir = $this->getGiftUploadDir();
        $storage = OW::getStorage();

        return $storage->getFileUrl($userfilesDir . $this->getGiftFileName($tplId, $hash, $ext));
    }
    
    public function getGiftPluginFilesPath( $tplId, $hash, $ext )
    {
        $dir = OW::getPluginManager()->getPlugin('virtualgifts')->getPluginFilesDir();

        return $dir . $this->getGiftFileName($tplId, $hash, $ext);
    }
    
    public function getGiftUploadDir()
    {
        return OW::getPluginManager()->getPlugin('virtualgifts')->getUserFilesDir();
    }
    
    /**
     * Returns gift file name
     * 
     * @param int $tplId
     * @param int $hash
     * @param string $ext
     */
    public function getGiftFileName( $tplId, $hash, $ext )
    {
        return self::GIFT_PREFIX . $tplId . '_' . $hash . '.' . $ext;
    }
    
    /**
     * Checks if file extension is allowed
     * 
     * @param string $ext
     */
    public function extIsAllowed( $ext )
    {
        if ( !mb_strlen($ext) )
        {
            return false;
        }

        return in_array($ext, self::$ext);
    }
    
    /**
     * Deletes template
     * 
     * @param int $templateId
     */
    public function deleteTemplate( $templateId )
    {
        if ( !$templateId )
        {
            return false;
        }
        $template = $this->templateDao->findById($templateId);
        if ( !$template )
        {
            return false;
        }
        
        $userGifts = $this->userGiftDao->findListByTemplateId($templateId);
        
        if ( $userGifts )
        {
            foreach ( $userGifts as $gift )
            {
                $this->deleteUserGift($gift->id);
            }
        }
        
        $storage = OW::getStorage();
        $imagePath = $this->getGiftFilePath($templateId, $template->uploadTimestamp, $template->extension);
        if ( $storage->fileExists($imagePath) )
        {
            $storage->removeFile($imagePath);
        }
        
        $this->templateDao->deleteById($templateId);
        
        return true;
    }
    
    public function deleteTemplates( )
    {
        $templates = $this->templateDao->findAll();
        
        foreach ( $templates as $tpl )
        {
            $this->deleteTemplate($tpl->id);
        }
    }
    
    /**
     * Returns template list
     * 
     * @param array $idList
     */
    public function getTemplateList( array $idList = null )
    {    
        if ( count($idList) )
        {
            $tpls = $this->templateDao->findByIdList($idList);
        }
        else 
        {
            $tpls = $this->templateDao->findAll();
        }
        
        $list = array();
        foreach ( $tpls as $tpl )
        {
        	$list[$tpl->id] = array(
        	   'dto' => $tpl,
        	   'id' => $tpl->id,
        	   'categoryId' => $tpl->categoryId, 
        	   'price' => floatval($tpl->price), 
        	   'imageUrl' => $this->getGiftFileUrl($tpl->id, $tpl->uploadTimestamp, $tpl->extension)
        	);
        }
        
        return $list;
    }
    
    public function getUncategorizedTemplateList()
    {
        $idList = $this->templateDao->findUncategorized();
        
        if ( !$idList )
        {
            return false;
        }
        
        return $this->getTemplateList($idList);
    }
    
    public function findAllTemplates()
    {
        return $this->templateDao->findAll();
    }
    
    /**
     * Returns templates by categories
     */
    public function getTemplateListByCategories ( )
    {
        $categories = $this->categoryDao->getCategories();
        $lang = OW::getLanguage();
        
        $list = array();
        foreach ( $categories as $cat )
        {
            $tpls = $this->findTemplatesByCategory($cat->id);
            
            if ( !$tpls )
            {
                continue;
            }
            
            $tplList = array();
            foreach ( $tpls as $tpl )
            {
                $tplList[$tpl->id] = array(
                   'id' => $tpl->id,
                   'categoryId' => $tpl->categoryId, 
                   'price' => floatval($tpl->price), 
                   'imageUrl' => $this->getGiftFileUrl($tpl->id, $tpl->uploadTimestamp, $tpl->extension)
                );
            }
            
            $list[$cat->id] = array(
                'title' => $lang->text('virtualgifts', 'category_'.$cat->id),
                'tpls' => $tplList
            );
        }
        
        return $list;
    }
    
    public function findTemplatesByCategory ( $catId )
    {
        return $this->templateDao->findByCategoryId($catId);
    }
    
    /**
     * Adds category
     * 
     * @param string $title
     * @return boolean
     */
    public function addCategory( $title )
    {
        $title = trim($title);
        
        if ( !mb_strlen($title) )
        {
            return false;
        }
        
        $category = new VIRTUALGIFTS_BOL_Category();
        $category->order = $this->getCategoryNextOrder();
        
        $this->categoryDao->save($category);
        
        if ( $category->id )
        {
            $langService = BOL_LanguageService::getInstance();
            $currentLang = $langService->getCurrent();
            $key = $langService->findKey('virtualgifts', 'category_' . $category->id);
            if ( $key && $langService->findValue($currentLang->getId(), $key->getId()) )
            {
                return true;
            }
            $langService->addValue($currentLang->getId(), 'virtualgifts', 'category_' . $category->id, $title);
            $langService->generateCache($currentLang->getId());
            return $category->id;
        }
        
        return false;
    }
    
    /**
     * Deletes category by Id
     * 
     * @param int $categoryId
     * @return boolean
     */
    public function deleteCategory( $categoryId )
    {
        $this->categoryDao->deleteById($categoryId);
        
        $key = BOL_LanguageService::getInstance()->findKey('virtualgifts', 'category_' . $categoryId);
        
        if ( $key )
        {
            BOL_LanguageService::getInstance()->deleteKey($key->id, true);
        }
        
        return true;
    }
    
    /**
     * Returns categories
     */
    public function getCategories()
    {
        $categories = $this->categoryDao->getCategories();
        $lang = OW::getLanguage();
        
        $result = array();
        foreach ( $categories as $category )
        {
        	$result[$category->id] = $lang->text('virtualgifts', 'category_'.$category->id);
        }
        
        return $result;
    }
    
    /**
     * Returns category by Id
     * 
     * @param int $categoryId
     * @return VIRTUALGIFTS_BOL_Category
     */
    public function findCategoryById( $categoryId )
    {
        return $this->categoryDao->findById($categoryId);
    }
    
    /**
     * Updates category
     * 
     * @param VIRTUALGIFTS_BOL_Category $category
     */
    public function updateCategory( VIRTUALGIFTS_BOL_Category $category )
    {
        $this->categoryDao->save($category);
    }
    
    
    /**
     * Checks if categories added
     * 
     * @return boolean
     */
    public function categoriesSetup()
    {
    	return (bool) $this->categoryDao->countAll();
    }
    
    /**
     * Returns the order of a new category
     * 
     * @return int
     */
    public function getCategoryNextOrder()
    {
        return 1 + $this->categoryDao->getMaxOrder();
    }
    
    /**
     * Sends user gift
     * 
     * @param VIRTUALGIFTS_BOL_UserGift $gift
     * @return int
     */
    public function sendUserGift( VIRTUALGIFTS_BOL_UserGift $gift )
    {
        $this->userGiftDao->save($gift);
        
        return $gift->id;
    }
    
    /**
     * Finds gifts received by a user
     * 
     * @param int $userId
     * @param int $page
     * @param int $limit
     * @param boolean $publicOnly
     */
    public function findUserReceivedGifts( $userId, $page, $limit, $publicOnly = true )
    {
        $gifts = $this->userGiftDao->findReceivedGifts($userId, $page, $limit, $publicOnly);
        
        if ( !$gifts )
        {
            return array();
        }
        
        $tplIds = array();
        $senderIds = array();
        foreach ( $gifts as $giftDto )
        {
            if ( !in_array($giftDto->templateId, $tplIds) )
            {
                array_push($tplIds, $giftDto->templateId);
            }
            
            if ( !in_array($giftDto->senderId, $senderIds) )
            {
                array_push($senderIds, $giftDto->senderId);
            }
        }
        
        $tpls = $this->getTemplateList($tplIds);
        $senderList = BOL_UserService::getInstance()->getDisplayNamesForList($senderIds);
        $senderUrlList = BOL_UserService::getInstance()->getUserUrlsForList($senderIds);
        
        $list = array();
        foreach ( $gifts as $giftDto )
        {
            $tpl = $tpls[$giftDto->templateId];
            $list[] = array(
                'dto' => $giftDto, 
                'imageUrl' => $tpl['imageUrl'],
                'sender' => $senderList[$giftDto->senderId],
                'senderUrl' => $senderUrlList[$giftDto->senderId]
            );
        }
        
        return $list;
    }
    
    /**
     * Counts gifts received by a user
     * 
     * @param int $userId
     * @param boolean $publicOnly
     * @return int
     */
    public function countUserReceivedGifts( $userId, $publicOnly = true )
    {
        if ( !$userId )
        {
            return false;
        }
        
        return $this->userGiftDao->countReceivedGifts($userId, $publicOnly);
    }
    
    /**
     * Finds user gift
     * 
     * @param int $giftId
     */
    public function findUserGiftById ( $giftId )
    {
        if ( !$giftId )
        {
            return false;
        }
        
        $gift = $this->userGiftDao->findById($giftId);
        
        if ( $gift )
        {
            $tpl = $this->findTemplateById($gift->templateId);

            if ( !$tpl )
            {
                return null;
            }
            
            return array(
                'dto' => $gift,
                'imageUrl' => $this->getGiftFileUrl($tpl->id, $tpl->uploadTimestamp, $tpl->extension)
            );
        }
        
        return null;
    }
    
    public function deleteUserGift ( $giftId )
    {
        if ( !$giftId )
        {
            return false;
        }
        
        $this->userGiftDao->deleteById($giftId);
        
        OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array(
            'entityType' => 'user_gift',
            'entityId' => $giftId
        )));
        
        return true;
    }

    public function getGiftsPerPageConfig()
    {
        return 10; // TODO: add config
    }
    
    public function setMaintenanceMode( $mode = true )
    {
        $config = OW::getConfig();
        
        if ( $mode )
        {
            $state = (int) $config->getValue('base', 'maintenance');
            $config->saveConfig('virtualgifts', 'maintenance_mode_state', $state);
            OW::getApplication()->setMaintenanceMode($mode);
        }
        else 
        {
            $state = (int) $config->getValue('virtualgifts', 'maintenance_mode_state');
            $config->saveConfig('base', 'maintenance', $state);
        }
    }
}