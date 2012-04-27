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
 * Photo base action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.controllers
 * @since 1.0
 */
class PHOTO_CTRL_Photo extends OW_ActionController
{
    /**
     * @var OW_PluginManager
     */
    private $plugin;
    /**
     * @var string
     */
    private $pluginJsUrl;
    /**
     * @var string
     */
    private $ajaxResponder;
    /**
     * @var PHOTO_BOL_PhotoService 
     */
    private $photoService;
    /**
     * @var PHOTO_BOL_PhotoAlbumService 
     */
    private $photoAlbumService;
    /**
     * @var BASE_CMP_ContentMenu
     */
    private $menu;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->plugin = OW::getPluginManager()->getPlugin('photo');
        $this->pluginJsUrl = $this->plugin->getStaticJsUrl();
        $this->ajaxResponder = OW::getRouter()->urlFor('PHOTO_CTRL_Photo', 'ajaxResponder');

        $this->photoService = PHOTO_BOL_PhotoService::getInstance();
        $this->photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();

        $this->menu = $this->getMenu();

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'photo', 'photo');
        }
    }

    /**
     * Returns menu component
     *
     * @return BASE_CMP_ContentMenu
     */
    private function getMenu()
    {        
        $validLists = array('featured', 'latest', 'toprated', 'tagged');
        $classes = array('ow_ic_push_pin', 'ow_ic_clock', 'ow_ic_star', 'ow_ic_tag');

        $checkPrivacy = PHOTO_BOL_PhotoService::getInstance()->countPhotos('featured');
        if ( !PHOTO_BOL_PhotoService::getInstance()->countPhotos('featured', $checkPrivacy) )
        {
            array_shift($validLists);
            array_shift($classes);
        }

        $language = OW::getLanguage();

        $menuItems = array();

        $order = 0;
        foreach ( $validLists as $type )
        {
            $item = new BASE_MenuItem();
            $item->setLabel($language->text('photo', 'menu_' . $type));
            $item->setUrl(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => $type)));
            $item->setKey($type);
            $item->setIconClass($classes[$order]);
            $item->setOrder($order);

            array_push($menuItems, $item);

            $order++;
        }

        $menu = new BASE_CMP_ContentMenu($menuItems);

        return $menu;
    }

    /**
     * View photo action
     *
     * @param array $params
     */
    public function view( array $params )
    {
        if ( !isset($params['id']) || !($photoId = (int) $params['id']) )
        {
            throw new Redirect404Exception();
        }

        $photo = $this->photoService->findPhotoById($photoId);

        if ( !$photo )
        {
            throw new Redirect404Exception();
        }

        $album = $this->photoAlbumService->findAlbumById($photo->albumId);
        $this->assign('album', $album);

        $language = OW::getLanguage();

        // is owner
        $contentOwner = $this->photoService->findPhotoOwner($photo->id);
        $userId = OW::getUser()->getId();
        $ownerMode = $contentOwner == $userId;
        $this->assign('ownerMode', $ownerMode);
       
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');
        $this->assign('moderatorMode', $modPermissions);

        if ( !$ownerMode && !$modPermissions && !OW::getUser()->isAuthorized('photo', 'view') )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }
        
        // permissions check
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'photo_view_album', 'ownerId' => $contentOwner, 'viewerId' => $userId);
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);
        }

        // add photo edit form
        if ( $ownerMode || $modPermissions )
        {
            $photoEditForm = new photoEditForm($photo->id);
            $this->addForm($photoEditForm);

            $photoEditForm->getElement('id')->setValue($photo->id);
            $photoEditForm->getElement('description')->setValue($photo->description);

            if ( OW::getRequest()->isPost() && $photoEditForm->isValid($_POST) )
            {
                $res = $photoEditForm->process();
                if ( $res['result'] )
                {
                    OW::getFeedback()->info($language->text('photo', 'photo_updated'));
                    $this->redirect();
                }
            }
        }

        $cmtParams = new BASE_CommentsParams('photo', 'photo_comments');
        $cmtParams->setEntityId($photo->id);
        $cmtParams->setOwnerId($contentOwner);
        $cmtParams->setDisplayType(BASE_CommentsParams::DISPLAY_TYPE_BOTTOM_FORM_WITH_FULL_LIST);

        $photoCmts = new BASE_CMP_Comments($cmtParams);
        $this->addComponent('comments', $photoCmts);

        $photoRates = new BASE_CMP_Rate('photo', 'photo_rates', $photo->id, $contentOwner);
        $this->addComponent('rate', $photoRates);

        $photoTags = new BASE_CMP_EntityTagCloud('photo');
        $photoTags->setEntityId($photo->id);
        $photoTags->setRouteName('view_tagged_photo_list');

        $this->addComponent('tags', $photoTags);
        $description = $photo->description;
        
        $photo->description = UTIL_HtmlTag::autoLink($photo->description);
        $this->assign('photo', $photo);
        $is_featured = PHOTO_BOL_PhotoFeaturedService::getInstance()->isFeatured($photo->id);
        $this->assign('featured', $is_featured);

        $this->assign('url', $this->photoService->getPhotoUrl($photo->id));
        $this->assign('ownerName', BOL_UserService::getInstance()->getUserName($album->userId));
        $this->assign('ownerDisplayName', BOL_UserService::getInstance()->getDisplayName($album->userId));

        $config = OW::getConfig();
        $this->assign('widthConfig', $config->getValue('photo', 'main_image_width'));
        $this->assign('heightConfig', $config->getValue('photo', 'main_image_height'));

        $nextPhoto = $this->photoService->getNextPhoto($photo->albumId, $photo->id);
        $this->assign('nextPhoto', $nextPhoto);

        $previousPhoto = $this->photoService->getPreviousPhoto($photo->albumId, $photo->id);
        $this->assign('previousPhoto', $previousPhoto);

        $photoCount = $this->photoAlbumService->countAlbumPhotos($photo->albumId);
        $this->assign('photoCount', $photoCount);

        $photoIndex = $this->photoService->getPhotoIndex($photo->albumId, $photo->id);
        $this->assign('photoIndex', $photoIndex);

        OW::getDocument()->addScript($this->pluginJsUrl . 'photo.js');

        $objParams = array(
            'ajaxResponder' => $this->ajaxResponder,
            'photoId' => $params['id'],
            'txtDelConfirm' => $language->text('photo', 'confirm_delete'),
            'txtMarkFeatured' => $language->text('photo', 'mark_featured'),
            'txtRemoveFromFeatured' => $language->text('photo', 'remove_from_featured'),
            'txtApprove' => $language->text('base', 'approve'),
            'txtDisapprove' => $language->text('base', 'disapprove')
        );

        $script =
            "$(document).ready(function(){
                var photo = new userPhoto( " . json_encode($objParams) . ");
            }); ";

        OW::getDocument()->addOnloadScript($script);

        OW::getDocument()->setHeading($album->name);
        OW::getDocument()->setHeadingIconClass('ow_ic_picture');

        $toolbar = array();

        $language->addKeyForJs('photo', 'tb_edit_photo');

        if ( (int) OW::getConfig()->getValue('photo', 'store_fullsize') && $photo->hasFullsize )
        {
            array_push($toolbar, array(
                'href' => $this->photoService->getPhotoFullsizeUrl($photo->id),
                'label' => $language->text('photo', 'view_fullsize')
            ));
        }

        array_push($toolbar, array(
            'href' => 'javascript://',
            'id' => 'btn-photo-flag',
            'label' => $language->text('base', 'flag')
        ));

        if ( $ownerMode || $modPermissions )
        {
            array_push($toolbar, array(
                'href' => 'javascript://',
                'id' => 'btn-photo-edit',
                'label' => $language->text('base', 'edit')
            ));

            array_push($toolbar, array(
                'href' => 'javascript://',
                'id' => 'photo-delete',
                'label' => $language->text('base', 'delete')
            ));
        }

        if ( $modPermissions )
        {
            if ( $is_featured )
            {
                array_push($toolbar, array(
                    'href' => 'javascript://',
                    'id' => 'photo-mark-featured',
                    'rel' => 'remove_from_featured',
                    'label' => $language->text('photo', 'remove_from_featured')
                ));
            }
            else
            {
                array_push($toolbar, array(
                    'href' => 'javascript://',
                    'id' => 'photo-mark-featured',
                    'rel' => 'mark_featured',
                    'label' => $language->text('photo', 'mark_featured')
                ));
            }

            /*
            if ( $photo->status == 'approved' )
            {
                array_push($toolbar, array(
                    'href' => 'javascript://',
                    'id' => 'photo-set-approval-status',
                    'rel' => 'disapprove',
                    'label' => $language->text('base', 'disapprove')
                ));
            }
            else
            {
                array_push($toolbar, array(
                    'href' => 'javascript://',
                    'id' => 'photo-set-approval-status',
                    'rel' => 'approve',
                    'label' => $language->text('base', 'approve')
                ));
            }
            */
        }

        $this->assign('toolbar', $toolbar);
        $description = strip_tags($description);
        $description = mb_strlen($description) ? $description : $photo->id;
        
        $js = UTIL_JsGenerator::newInstance()
                ->jQueryEvent('#btn-photo-flag', 'click', 'document.flag(e.data.entity, e.data.id, e.data.title, e.data.href, "photo+flags");', array('e'),
                    array('entity' => 'photo', 'id' => $photo->id, 'title' => $description, 'href' => OW::getRouter()->urlForRoute('view_photo', array('id' => $photo->id))
                ));

        OW::getDocument()->addOnloadScript($js, 1001);
        
        OW::getDocument()->setTitle($language->text('photo', 'meta_title_photo_view', array('title' => $description)));
        $tagsArr = BOL_TagService::getInstance()->findEntityTags($photo->id, 'photo');
    
        foreach ( $tagsArr as $t )
        {
            $labels[] = $t->label;
        }
        $tagStr = $tagsArr ? implode(', ', $labels) : '';
        OW::getDocument()->setDescription($language->text('photo', 'meta_description_photo_view', array('title' => $description, 'tags' => $tagStr)));
    }

    /**
     * Photo list action
     *
     * @param array $params
     */
    public function viewList( array $params )
    {
        $listType = isset($params['listType']) ? $params['listType'] : 'latest';

        $validLists = array('featured', 'latest', 'toprated', 'tagged');

        if ( !in_array($listType, $validLists) )
        {
            $this->redirect(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'latest')));
        }
       
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');
        
        if ( !$modPermissions && !OW::getUser()->isAuthorized('photo', 'view') )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }
        
        $el = $this->menu->getElement($listType);
        if ( $el )
        {
            $el->setActive(true);
        }

        $this->addComponent('photoMenu', $this->menu);
        $this->assign('listType', $listType);

        OW::getDocument()->setHeading(OW::getLanguage()->text('photo', 'page_title_browse_photos'));
        OW::getDocument()->setHeadingIconClass('ow_ic_picture');
        
        OW::getDocument()->setTitle(OW::getLanguage()->text('photo', 'meta_title_photo_'.$listType));
        OW::getDocument()->setDescription(OW::getLanguage()->text('photo', 'meta_description_photo_'.$listType));

        $js = UTIL_JsGenerator::newInstance()
                ->newVariable('addNewUrl', OW::getRouter()->urlFor('PHOTO_CTRL_Upload', 'index'))
                ->jQueryEvent('#btn-add-new-photo', 'click', 'document.location.href = addNewUrl');

        OW::getDocument()->addOnloadScript($js);
    }

    /**
     * Tagged photo list action
     *
     * @param array $params
     */
    public function viewTaggedList( array $params = null )
    {
        if ( isset($params['tag']) )
        {
            $tag = htmlspecialchars(urldecode($params['tag']));
        }
        
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');
        
        if ( !$modPermissions && !OW::getUser()->isAuthorized('photo', 'view') )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }

        $this->addComponent('photoMenu', $this->menu);

        $this->menu->getElement('tagged')->setActive(true);

        $this->setTemplate(OW::getPluginManager()->getPlugin('photo')->getCtrlViewDir() . 'photo_view_list-tagged.html');

        $listUrl = OW::getRouter()->urlForRoute('view_tagged_photo_list_st');

        OW::getDocument()->addScript($this->pluginJsUrl . 'photo_tag_search.js');

        $objParams = array(
            'listUrl' => $listUrl
        );

        $script =
            "$(document).ready(function(){
                var photoSearch = new photoTagSearch(" . json_encode($objParams) . ");
            }); ";

        OW::getDocument()->addOnloadScript($script);

        if ( isset($tag) )
        {
            $this->assign('tag', $tag);
            OW::getDocument()->setTitle(OW::getLanguage()->text('photo', 'meta_title_photo_tagged_as', array('tag' => $tag)));
            OW::getDocument()->setDescription(OW::getLanguage()->text('photo', 'meta_description_photo_tagged_as', array('tag' => $tag)));
        }
        else
        {
            $tags = new BASE_CMP_EntityTagCloud('photo');
            $tags->setRouteName('view_tagged_photo_list');
            $this->addComponent('tags', $tags);
            
            OW::getDocument()->setTitle(OW::getLanguage()->text('photo', 'meta_title_photo_tagged'));
            $tagsArr = BOL_TagService::getInstance()->findMostPopularTags('photo', 20);
            foreach ( $tagsArr as $t )
            {
                $labels[] = $t['label'];
            }
            $tagStr = $tagsArr ? implode(', ', $labels) : '';
            OW::getDocument()->setDescription(OW::getLanguage()->text('photo', 'meta_description_photo_tagged', array('topTags' => $tagStr)));
        }

        $this->assign('listType', 'tagged');

        OW::getDocument()->setHeading(OW::getLanguage()->text('photo', 'page_title_browse_photos'));
        OW::getDocument()->setHeadingIconClass('ow_ic_picture');

        $js = UTIL_JsGenerator::newInstance()
                ->newVariable('addNewUrl', OW::getRouter()->urlFor('PHOTO_CTRL_Upload', 'index'))
                ->jQueryEvent('#btn-add-new-photo', 'click', 'document.location.href = addNewUrl');

        OW::getDocument()->addOnloadScript($js);
    }

    /**
     * Controller action for user albums list
     *
     * @param unknown_type $params
     */
    public function userAlbums( array $params )
    {
        if ( !isset($params['user'])
            || !strlen($user = trim($params['user']))
            || !$userId = BOL_UserService::getInstance()->findByUsername($user)->id )
        {
            throw new Redirect404Exception();
            return;
        }

        $ownerMode = $userId == OW::getUser()->getId();
        
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');

        if ( !OW::getUser()->isAuthorized('photo', 'view') && !$modPermissions && !$ownerMode )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }

        // permissions check
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'photo_view_album', 'ownerId' => $userId, 'viewerId' => OW::getUser()->getId());
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);
        }
        
        $this->assign('user', $user);
        $userName = BOL_UserService::getInstance()->getUserName($userId);
        $this->assign('userName', $userName);
        $displayName = BOL_UserService::getInstance()->getDisplayName($userId);
        $this->assign('displayName', $displayName);

        $total = $this->photoAlbumService->countUserAlbums($userId);
        $page = isset($_GET['page']) && (int) $_GET['page'] ? (int) $_GET['page'] : 1;

        $config = OW::getConfig();
        $albumPerPage = $config->getValue('photo', 'photos_per_page');

        $albums = $this->photoAlbumService->findUserAlbumList($userId, $page, $albumPerPage);
        $this->assign('albums', $albums);
        $this->assign('total', $total);
        $this->assign('userId', $userId);

        // Paging
        $pages = (int) ceil($total / $albumPerPage);
        $paging = new BASE_CMP_Paging($page, $pages, $albumPerPage);
        $this->assign('paging', $paging->render());

        $this->assign('widthConfig', $config->getValue('photo', 'preview_image_width'));
        $this->assign('heightConfig', $config->getValue('photo', 'preview_image_height'));

        OW::getDocument()->setHeading(
            OW::getLanguage()->text('photo', 'page_title_user_albums', array('user' => $displayName))
        );

        OW::getDocument()->setHeadingIconClass('ow_ic_picture');
        OW::getDocument()->setTitle(OW::getLanguage()->text('photo', 'meta_title_photo_useralbums', array('displayName' => $displayName)));
        
        if ( $albums )
        {
            $albumTitles = array(); 
            $i = 0;
            foreach ( $albums as $album )
            {
                $albumTitles[] = $album['dto']->name;
                if ( $i == 10 )
                {
                    break;
                }
                $i++;
            }
            $albumTitles = implode(', ', $albumTitles);
            OW::getDocument()->setDescription(
                OW::getLanguage()->text('photo', 'meta_description_photo_useralbums', array('displayName' => $displayName, 'albums' => $albumTitles))
            );
        }
    }

    /**
     * Controller action for user album
     *
     * @param array $params
     */
    public function userAlbum( array $params )
    {
        if ( !isset($params['user']) || !strlen($user = trim($params['user'])) )
        {
            throw new Redirect404Exception();
        }

        if ( !isset($params['album']) || !($albumId = (int) $params['album']) )
        {
            throw new Redirect404Exception();
        }

        // is owner
        $userDto = BOL_UserService::getInstance()->findByUsername($user);
        
        if ( $userDto )
        {
            $ownerMode = $userDto->id == OW::getUser()->getId();
        }
        else 
        {
            $ownerMode = false;
        }
        
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');

        if ( !OW::getUser()->isAuthorized('photo', 'view') && !$modPermissions && !$ownerMode )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }
                
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

        $config = OW::getConfig();
        $photoPerPage = $config->getValue('photo', 'photos_per_page');

        $album = $this->photoAlbumService->findAlbumById($albumId);

        if ( !$album )
        {
            throw new Redirect404Exception();
            return;
        }

        $this->assign('album', $album);
        
        // permissions check
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'photo_view_album', 'ownerId' => $album->userId, 'viewerId' => OW::getUser()->getId());
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);
        }

        $this->assign('userName', BOL_UserService::getInstance()->getUserName($album->userId));
        $displayName = BOL_UserService::getInstance()->getDisplayName($album->userId);
        $this->assign('displayName', $displayName);

        $photos = $this->photoService->getAlbumPhotos($albumId, $page, $photoPerPage);
        $this->assign('photos', $photos);

        $total = $this->photoAlbumService->countAlbumPhotos($albumId);
        $this->assign('total', $total);

        $lastUpdated = $this->photoAlbumService->getAlbumUpdateTime($albumId);
        $this->assign('lastUpdate', $lastUpdated);

        $this->assign('widthConfig', $config->getValue('photo', 'preview_image_width'));
        $this->assign('heightConfig', $config->getValue('photo', 'preview_image_height'));

        // Paging
        $pages = (int) ceil($total / $photoPerPage);
        $paging = new BASE_CMP_Paging($page, $pages, $photoPerPage);
        $this->assign('paging', $paging->render());

        OW::getDocument()->setHeading(
            $album->name .
            ' <span class="ow_small">' .
            OW::getLanguage()->text('photo', 'photos_in_album', array('total' => $total)) .
            '</span>'
        );

        OW::getDocument()->setHeadingIconClass('ow_ic_picture');

        // check permissions
        $canEdit = OW::getUser()->isAuthorized('photo', 'upload', $album->userId);
        $canModerate = OW::getUser()->isAuthorized('photo');

        $authorized = $canEdit || $canModerate;
        $this->assign('authorized', $canEdit || $canModerate);

        $lang = OW::getLanguage();

        if ( $authorized )
        {
            $albumEditForm = new albumEditForm();
            $albumEditForm->getElement('albumName')->setValue($album->name);
            $albumEditForm->getElement('id')->setValue($album->id);

            $this->addForm($albumEditForm);

            OW::getDocument()->addScript($this->pluginJsUrl . 'album.js');

            if ( OW::getRequest()->isPost() && $albumEditForm->isValid($_POST) )
            {
                $res = $albumEditForm->process();
                if ( $res['result'] )
                {
                    OW::getFeedback()->info($lang->text('photo', 'photo_album_updated'));
                    $this->redirect();
                }
            }

            $lang->addKeyForJs('photo', 'confirm_delete_album');
            $lang->addKeyForJs('photo', 'edit_album');

            $objParams = array(
                'ajaxResponder' => $this->ajaxResponder,
                'albumId' => $albumId
            );

            $script =
                "$(document).ready(function(){
                    var album = new photoAlbum( " . json_encode($objParams) . ");
                }); ";

            OW::getDocument()->addOnloadScript($script);
        }
        
        OW::getDocument()->setTitle(
            $lang->text('photo', 'meta_title_photo_useralbum', array('displayName' => $displayName, 'albumName' => $album->name))
        );
        OW::getDocument()->setDescription(
            $lang->text('photo', 'meta_description_photo_useralbum', array('displayName' => $displayName, 'number' => $total))
        );
    }

    /**
     * Method acts as ajax responder. Calls methods using ajax
     * 
     * @return JSON encoded string
     *
     */
    public function ajaxResponder()
    {
        if ( isset($_POST['ajaxFunc']) && OW::getRequest()->isAjax() )
        {
            $callFunc = (string) $_POST['ajaxFunc'];

            $result = call_user_func(array($this, $callFunc), $_POST);
        }
        else
        {
            throw new Redirect404Exception();
            exit;
        }

        exit(json_encode($result));
    }

    /**
     * Set photo approval status (approved | blocked)
     *
     * @param array $params
     * @return array
     */
    public function ajaxSetApprovalStatus( array $params )
    {
        $photoId = $params['photoId'];
        $status = $params['status'];

        $isModerator = OW::getUser()->isAuthorized('photo');

        if ( !$isModerator )
        {
            throw new Redirect404Exception();
            return;
        }

        $setStatus = $this->photoService->updatePhotoStatus($photoId, $status);

        if ( $setStatus )
        {
            $return = array('result' => true, 'msg' => OW::getLanguage()->text('photo', 'status_changed'));
        }
        else
        {
            $return = array('result' => false, 'error' => OW::getLanguage()->text('photo', 'status_not_changed'));
        }

        return $return;
    }

    /**
     * Set photo's 'is featured' status 
     *
     * @param array $params
     * @return array
     */
    public function ajaxSetFeaturedStatus( array $params )
    {
        $photoId = $params['photoId'];
        $status = $params['status'];

        $isModerator = OW::getUser()->isAuthorized('photo');

        if ( !$isModerator )
        {
            throw new Redirect404Exception();
            return;
        }

        $setResult = $this->photoService->updatePhotoFeaturedStatus($photoId, $status);

        if ( $setResult )
        {
            $return = array('result' => true, 'msg' => OW::getLanguage()->text('photo', 'status_changed'));
        }
        else
        {
            $return = array('result' => false, 'error' => OW::getLanguage()->text('photo', 'status_not_changed'));
        }

        return $return;
    }

    /**
     * Deletes photo
     *
     * @param array $params
     * @return array
     */
    public function ajaxDeletePhoto( array $params )
    {
        $photoId = $params['photoId'];

        $photo = $this->photoService->findPhotoById($photoId);

        if ( $photo )
        {
            $ownerId = $this->photoService->findPhotoOwner($photoId);
            $isOwner = OW::getUser()->isAuthorized('photo', 'upload', $ownerId);
            $isModerator = OW::getUser()->isAuthorized('photo');

            if ( !$isOwner && !$isModerator )
            {
                throw new Redirect404Exception();
                return;
            }

            $album = $this->photoAlbumService->findAlbumById($photo->albumId);
            $delResult = $this->photoService->deletePhoto($photoId);

            if ( $delResult )
            {
                $photosInAlbum = (int) $this->photoAlbumService->countAlbumPhotos($photo->albumId);
                if ( $photosInAlbum == 0 )
                {
                    $url = OW_Router::getInstance()->urlForRoute(
                            'photo_user_albums',
                            array('user' => BOL_UserService::getInstance()->getUserName($album->userId))
                    );

                    $this->photoAlbumService->deleteAlbum($photo->albumId);
                }
                else
                {
                    $url = OW_Router::getInstance()->urlForRoute(
                            'photo_user_album',
                            array('user' => BOL_UserService::getInstance()->getUserName($album->userId), 'album' => $photo->albumId)
                    );
                }

                $return = array('result' => true, 'msg' => OW::getLanguage()->text('photo', 'photo_deleted'), 'url' => $url);
            }
            else
            {
                $return = array('result' => false, 'error' => OW::getLanguage()->text('photo', 'photo_not_deleted'));
            }
        }

        return $return;
    }

    /**
     * Deletes photo album
     *
     * @param array $params
     * @return array
     */
    public function ajaxDeletePhotoAlbum( array $params )
    {
        $albumId = $params['albumId'];
        $lang = OW::getLanguage();

        $album = $this->photoAlbumService->findAlbumById($albumId);

        if ( $album )
        {
            // check permissions
            $canEdit = OW::getUser()->isAuthorized('photo', 'upload', $album->userId);
            $canModerate = OW::getUser()->isAuthorized('photo');

            $authorized = $canEdit || $canModerate;

            if ( $authorized )
            {
                $delResult = $this->photoAlbumService->deleteAlbum($albumId);

                if ( $delResult )
                {
                    $url = OW_Router::getInstance()->urlForRoute(
                            'photo_user_albums',
                            array('user' => BOL_UserService::getInstance()->getUserName($album->userId))
                    );

                    return array('result' => true, 'msg' => $lang->text('photo', 'album_deleted'), 'url' => $url);
                }
            }
            else
            {
                $url = OW_Router::getInstance()->urlForRoute(
                        'photo_user_album',
                        array('user' => BOL_UserService::getInstance()->getUserName($album->userId), 'album' => $album->id)
                );

                return array('result' => false, 'error' => $lang->text('photo', 'album_delete_not_allowed'), 'url' => $url);
            }
        }
    }
}

/**
 * Photo edit form class
 */
class photoEditForm extends Form
{

    /**
     * Class constructor
     */
    public function __construct( $photoId )
    {
        parent::__construct('photoEditForm');

        $language = OW::getLanguage();
        $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);

        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($photo->albumId);
        $userId = OW::getUser()->getId();

        // photo id field
        $photoIdField = new HiddenField('id');
        $photoIdField->setRequired(true);
        $this->addElement($photoIdField);

        // photo album Field
        $albumField = new SuggestField('album');
        $responderUrl = OW::getRouter()->urlFor('PHOTO_CTRL_Upload', 'suggestAlbum', array('userId' => $userId));

        $this->addElement($albumField->setResponderUrl($responderUrl)
                ->setValue($album->name)
                ->setRequired(true)
                ->setLabel($language->text('photo', 'album')));

        // description Field
        $descField = new WysiwygTextarea('description', null, false);
        $descField->setId("photo-desc-area");
        $this->addElement($descField->setLabel($language->text('photo', 'description')));
        $tags = array();

        $entityTags = BOL_TagService::getInstance()->findEntityTags($photo->id, 'photo');

        if ( $entityTags )
        {
            $tags = array();
            foreach ( $entityTags as $entityTag )
            {
                $tags[] = $entityTag->label;
            }

            $tagsField = new TagsField('tags', $tags);
        }
        else
        {
            $tagsField = new TagsField('tags');
        }

        $this->addElement($tagsField->setLabel($language->text('photo', 'tags')));

        $submit = new Submit('edit');
        $submit->setValue($language->text('photo', 'btn_edit'));
        $this->addElement($submit);
    }

    /**
     * Updates photo
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();

        $photoService = PHOTO_BOL_PhotoService::getInstance();
        $albumService = PHOTO_BOL_PhotoAlbumService::getInstance();

        $language = OW::getLanguage();

        if ( isset($values['id']) && ($photoId = (int) $values['id']) )
        {
            $photo = $photoService->findPhotoById($photoId);

            if ( $photo )
            {
                if ( strlen($albumName = htmlspecialchars(trim($values['album']))) )
                {
                    $userId = $photoService->findPhotoOwner($photoId);

                    if ( !$userId )
                    {
                        return array('result' => false, 'id' => $photoId);
                    }

                    $album = $albumService->findAlbumByName($albumName, $userId);

                    if ( !$album )
                    {
                        $album = new PHOTO_BOL_PhotoAlbum();
                        $album->name = $albumName;
                        $album->userId = $userId;
                        $album->createDatetime = time();

                        $albumService->addAlbum($album);
                    }
                    $albumId = $album->id;
                }

                $photo->albumId = $albumId;
                $photo->description = $values['description'];

                BOL_TagService::getInstance()->updateEntityTags(
                    $photo->id,
                    'photo',
                    TagsField::getTags($values['tags'])
                );

                if ( $photoService->updatePhoto($photo) )
                {
                    return array('result' => true, 'id' => $photo->id);
                }
            }
        }
        else
        {
            return array('result' => false, 'id' => $photo->id);
        }

        return false;
    }
}

/**
 * Album edit form class
 */
class albumEditForm extends Form
{

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct('albumEditForm');

        $language = OW::getLanguage();

        // album id field
        $albumIdField = new HiddenField('id');
        $albumIdField->setRequired(true);
        $this->addElement($albumIdField);

        // album name Field
        $albumNameField = new TextField('albumName');

        $this->addElement($albumNameField->setLabel($language->text('photo', 'album')));

        $submit = new Submit('save');
        $submit->setValue($language->text('photo', 'btn_edit'));
        $this->addElement($submit);
    }

    /**
     * Updates photo album
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();

        $albumService = PHOTO_BOL_PhotoAlbumService::getInstance();

        $language = OW::getLanguage();

        if ( isset($values['id']) && ($albumId = (int) $values['id']) )
        {
            $album = $albumService->findAlbumById($albumId);

            if ( $album )
            {
                if ( strlen($albumName = htmlspecialchars(trim($values['albumName']))) )
                {
                    $album->name = $albumName;

                    if ( $albumService->updateAlbum($album) )
                    {
                        return array('result' => true, 'id' => $album->id);
                    }
                }
            }
        }
        else
        {
            return array('result' => false, 'id' => $album->id);
        }

        return false;
    }
}