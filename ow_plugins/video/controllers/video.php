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
 * Video action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_plugins.video.controllers
 * @since 1.0
 */
class VIDEO_CTRL_Video extends OW_ActionController
{
    /**
     * @var OW_Plugin
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
     * @var VIDEO_BOL_ClipService
     */
    private $clipService;
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

        $this->plugin = OW::getPluginManager()->getPlugin('video');
        $this->pluginJsUrl = $this->plugin->getStaticJsUrl();
        $this->ajaxResponder = OW::getRouter()->urlFor('VIDEO_CTRL_Video', 'ajaxResponder');

        $this->clipService = VIDEO_BOL_ClipService::getInstance();

        $this->menu = $this->getMenu();

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'video', 'video');
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

        if ( !VIDEO_BOL_ClipService::getInstance()->findClipsCount('featured') )
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
            $item->setLabel($language->text('video', 'menu_' . $type));
            $item->setUrl(OW::getRouter()->urlForRoute('view_list', array('listType' => $type)));
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
     * Video view action
     *
     * @param array $params
     */
    public function view( array $params )
    {
        if ( !isset($params['id']) || !($id = (int) $params['id']) )
        {
            throw new Redirect404Exception();
            return;
        }

        $clip = $this->clipService->findClipById($id);

        if ( !$clip )
        {
            throw new Redirect404Exception();
        }

        $contentOwner = (int) $this->clipService->findClipOwner($id);

        $language = OW_Language::getInstance();

        $description = $clip->description;
        $clip->description = UTIL_HtmlTag::autoLink($clip->description);
        $this->assign('clip', $clip);
        $is_featured = VIDEO_BOL_ClipFeaturedService::getInstance()->isFeatured($clip->id);
        $this->assign('featured', $is_featured);

        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('video');
        $this->assign('moderatorMode', $modPermissions);

        $userId = OW::getUser()->getId();
        $ownerMode = $contentOwner == $userId;
        $this->assign('ownerMode', $ownerMode);

        if ( !$ownerMode && !OW::getUser()->isAuthorized('video', 'view') && !$modPermissions )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }

        $this->assign('auth_msg', null);
        
        // permissions check
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'video_view_video', 'ownerId' => $contentOwner, 'viewerId' => $userId);
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);
        }

        $cmtParams = new BASE_CommentsParams('video', 'video_comments');
        $cmtParams->setEntityId($id);
        $cmtParams->setOwnerId($contentOwner);
        $cmtParams->setDisplayType(BASE_CommentsParams::DISPLAY_TYPE_BOTTOM_FORM_WITH_FULL_LIST);

        $videoCmts = new BASE_CMP_Comments($cmtParams);
        $this->addComponent('comments', $videoCmts);

        $videoRates = new BASE_CMP_Rate('video', 'video_rates', $id, $contentOwner);
        $this->addComponent('rate', $videoRates);

        $videoTags = new BASE_CMP_EntityTagCloud('video');
        $videoTags->setEntityId($id);
        $videoTags->setRouteName('view_tagged_list');
        $this->addComponent('tags', $videoTags);

        $username = BOL_UserService::getInstance()->getUserName($clip->userId);
        $this->assign('username', $username);

        $displayName = BOL_UserService::getInstance()->getDisplayName($clip->userId);
        $this->assign('displayName', $displayName);

        OW::getDocument()->addScript($this->pluginJsUrl . 'video.js');

        $objParams = array(
            'ajaxResponder' => $this->ajaxResponder,
            'clipId' => $id,
            'txtDelConfirm' => OW::getLanguage()->text('video', 'confirm_delete'),
            'txtMarkFeatured' => OW::getLanguage()->text('video', 'mark_featured'),
            'txtRemoveFromFeatured' => OW::getLanguage()->text('video', 'remove_from_featured'),
            'txtApprove' => OW::getLanguage()->text('base', 'approve'),
            'txtDisapprove' => OW::getLanguage()->text('base', 'disapprove')
        );

        $script =
            "$(document).ready(function(){
                var clip = new videoClip( " . json_encode($objParams) . ");
            }); ";

        OW::getDocument()->addOnloadScript($script);

        OW::getDocument()->setHeading($clip->title);
        OW::getDocument()->setHeadingIconClass('ow_ic_video');

        $toolbar = array();

        array_push($toolbar, array(
            'href' => 'javascript://',
            'id' => 'btn-video-flag',
            'label' => $language->text('base', 'flag')
        ));

        if ( $ownerMode || $modPermissions )
        {
            array_push($toolbar, array(
                'href' => OW::getRouter()->urlForRoute('edit_clip', array('id' => $clip->id)),
                'label' => $language->text('base', 'edit')
            ));

            array_push($toolbar, array(
                'href' => 'javascript://',
                'id' => 'clip-delete',
                'label' => $language->text('base', 'delete')
            ));
        }

        if ( $modPermissions )
        {
            if ( $is_featured )
            {
                array_push($toolbar, array(
                    'href' => 'javascript://',
                    'id' => 'clip-mark-featured',
                    'rel' => 'remove_from_featured',
                    'label' => $language->text('video', 'remove_from_featured')
                ));
            }
            else
            {
                array_push($toolbar, array(
                    'href' => 'javascript://',
                    'id' => 'clip-mark-featured',
                    'rel' => 'mark_featured',
                    'label' => $language->text('video', 'mark_featured')
                ));
            }
            
            /*
            if ( $clip->status == 'approved' )
            {
                array_push($toolbar, array(
                    'href' => 'javascript://',
                    'id' => 'clip-set-approval-staus',
                    'rel' => 'disapprove',
                    'label' => $language->text('base', 'disapprove')
                ));
            }
            else
            {
                array_push($toolbar, array(
                    'href' => 'javascript://',
                    'id' => 'clip-set-approval-staus',
                    'rel' => 'approve',
                    'label' => $language->text('base', 'approve')
                ));
            }
            */
        }

        $this->assign('toolbar', $toolbar);

        $js = UTIL_JsGenerator::newInstance()
                ->jQueryEvent('#btn-video-flag', 'click', 'document.flag(e.data.entity, e.data.id, e.data.title, e.data.href, "video+flags");', array('e'),
                    array('entity' => 'video_clip', 'id' => $clip->id, 'title' => $clip->title, 'href' => OW::getRouter()->urlForRoute('view_clip', array('id' => $clip->id))
                ));

        OW::getDocument()->addOnloadScript($js, 1001);
        
        OW::getDocument()->setTitle($language->text('video', 'meta_title_video_view', array('title' => $clip->title)));
        $tagsArr = BOL_TagService::getInstance()->findEntityTags($clip->id, 'video');
    
        foreach ( $tagsArr as $t )
        {
            $labels[] = $t->label;
        }
        $tagStr = $tagsArr ? implode(', ', $labels) : '';
        OW::getDocument()->setDescription($language->text('video', 'meta_description_video_view', array('title' => $clip->title, 'tags' => $tagStr)));
    }
    
    public function edit( array $params )
    {
        if ( !isset($params['id']) || !($id = (int) $params['id']) )
        {
            throw new Redirect404Exception();
            return;
        }

        $clip = $this->clipService->findClipById($id);

        if ( !$clip )
        {
            throw new Redirect404Exception();
        }

        $language = OW_Language::getInstance();
        
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('video');
        $this->assign('moderatorMode', $modPermissions);

        $contentOwner = (int) $this->clipService->findClipOwner($id);
        $userId = OW::getUser()->getId();
        $ownerMode = $contentOwner == $userId;
        $this->assign('ownerMode', $ownerMode);

        if ( !$ownerMode && !$modPermissions )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }
        
        $videoEditForm = new videoEditForm($clip->id);
        $this->addForm($videoEditForm);

        $videoEditForm->getElement('id')->setValue($clip->id);
        $videoEditForm->getElement('title')->setValue($clip->title);
        $videoEditForm->getElement('description')->setValue($clip->description);
        $videoEditForm->getElement('code')->setValue($clip->code);

        if ( OW::getRequest()->isPost() && $videoEditForm->isValid($_POST) )
        {
            $res = $videoEditForm->process();
            OW::getFeedback()->info($language->text('video', 'clip_updated'));
            $this->redirect(OW::getRouter()->urlForRoute('view_clip', array('id' => $res['id'])));
        }
        
        OW::getDocument()->setHeading($language->text('video', 'tb_edit_clip'));
        OW::getDocument()->setHeadingIconClass('ow_ic_video');
        OW::getDocument()->setTitle($language->text('video', 'tb_edit_clip'));
    }

    /**
     * Video list view action
     *
     * @param array $params
     */
    public function viewList( array $params )
    {
        $listType = isset($params['listType']) ? trim($params['listType']) : 'latest';

        $validLists = array('featured', 'latest', 'toprated', 'tagged');

        if ( !in_array($listType, $validLists) )
        {
            $this->redirect(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'latest')));
        }
        
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('video');

        if ( !OW::getUser()->isAuthorized('video', 'view') && !$modPermissions )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }

        $this->addComponent('videoMenu', $this->menu);

        $el = $this->menu->getElement($listType);
        if ( $el )
        {
            $el->setActive(true);
        }

        $this->assign('listType', $listType);

        OW::getDocument()->setHeading(OW::getLanguage()->text('video', 'page_title_browse_video'));
        OW::getDocument()->setHeadingIconClass('ow_ic_video');
        OW::getDocument()->setTitle(OW::getLanguage()->text('video', 'meta_title_video_'.$listType));
        OW::getDocument()->setDescription(OW::getLanguage()->text('video', 'meta_description_video_'.$listType));

        $js = UTIL_JsGenerator::newInstance()
                ->newVariable('addNewUrl', OW::getRouter()->urlFor('VIDEO_CTRL_Add', 'index'))
                ->jQueryEvent('#btn-add-new-video', 'click', 'document.location.href = addNewUrl');

        OW::getDocument()->addOnloadScript($js);
    }

    /**
     * User video list view action
     *
     * @param array $params
     */
    public function viewUserVideoList( array $params )
    {
        if ( !isset($params['user']) || !strlen($userName = trim($params['user'])) )
        {
            throw new Redirect404Exception();
            return;
        }

        $user = BOL_UserService::getInstance()->findByUsername($userName);
        if ( !$user )
        {
            throw new Redirect404Exception();
            return;
        }

        $ownerMode = $user->id == OW::getUser()->getId();
        
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('video');

        if ( !OW::getUser()->isAuthorized('video', 'view') && !$modPermissions && !$ownerMode )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }
        
        // permissions check
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'video_view_video', 'ownerId' => $user->id, 'viewerId' => OW::getUser()->getId());
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);
        }

        $this->assign('userId', $user->id);

        $clipCount = VIDEO_BOL_ClipService::getInstance()->findUserClipsCount($user->id);
        $this->assign('total', $clipCount);

        $displayName = BOL_UserService::getInstance()->getDisplayName($user->id);
        $this->assign('userName', $displayName);

        $heading = OW::getLanguage()->text('video', 'page_title_video_by', array('user' => $displayName));

        OW::getDocument()->setHeading($heading);
        OW::getDocument()->setHeadingIconClass('ow_ic_video');
        OW::getDocument()->setTitle(OW::getLanguage()->text('video', 'meta_title_user_video', array('displayName' => $displayName)));
        OW::getDocument()->setDescription(OW::getLanguage()->text('video', 'meta_description_user_video', array('displayName' => $displayName)));
    }

    /**
     * Tagged video list view action
     *
     * @param array $params
     */
    public function viewTaggedList( array $params = null )
    {
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('video');

        if ( !OW::getUser()->isAuthorized('video', 'view') && !$modPermissions )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }
        
        $tag = !empty($params['tag']) ? trim(htmlspecialchars(urldecode($params['tag']))) : '';

        $this->addComponent('videoMenu', $this->menu);

        $this->menu->getElement('tagged')->setActive(true);

        $this->setTemplate(OW::getPluginManager()->getPlugin('video')->getCtrlViewDir() . 'video_view_list-tagged.html');

        $listUrl = OW::getRouter()->urlForRoute('view_tagged_list_st');

        OW::getDocument()->addScript($this->pluginJsUrl . 'video_tag_search.js');

        $objParams = array(
            'listUrl' => $listUrl
        );

        $script =
            "$(document).ready(function(){
                var videoSearch = new videoTagSearch(" . json_encode($objParams) . ");
            }); ";

        OW::getDocument()->addOnloadScript($script);

        if ( strlen($tag) )
        {
            $this->assign('tag', $tag);
            
            OW::getDocument()->setTitle(OW::getLanguage()->text('video', 'meta_title_video_tagged_as', array('tag' => $tag)));
            OW::getDocument()->setDescription(OW::getLanguage()->text('video', 'meta_description_video_tagged_as', array('tag' => $tag)));
        }
        else
        {
            $tags = new BASE_CMP_EntityTagCloud('video');
            $tags->setRouteName('view_tagged_list');
            $this->addComponent('tags', $tags);
            
            OW::getDocument()->setTitle(OW::getLanguage()->text('video', 'meta_title_video_tagged'));
            $tagsArr = BOL_TagService::getInstance()->findMostPopularTags('video', 20);
    
            foreach ( $tagsArr as $t )
            {
                $labels[] = $t['label'];
            }
            $tagStr = $tagsArr ? implode(', ', $labels) : '';
            OW::getDocument()->setDescription(OW::getLanguage()->text('video', 'meta_description_video_tagged', array('topTags' => $tagStr)));
        }

        $this->assign('listType', 'tagged');

        OW::getDocument()->setHeading(OW::getLanguage()->text('video', 'page_title_browse_video'));
        OW::getDocument()->setHeadingIconClass('ow_ic_video');

        $js = UTIL_JsGenerator::newInstance()
                ->newVariable('addNewUrl', OW::getRouter()->urlFor('VIDEO_CTRL_Add', 'index'))
                ->jQueryEvent('#btn-add-new-video', 'click', 'document.location.href = addNewUrl');

        OW::getDocument()->addOnloadScript($js);
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
     * Set video clip approval status (approved | blocked)
     *
     * @param array $params
     * @return array
     */
    public function ajaxSetApprovalStatus( $params )
    {
        $clipId = $params['clipId'];
        $status = $params['status'];

        $isModerator = OW::getUser()->isAuthorized('video');

        if ( !$isModerator )
        {
            throw new Redirect404Exception();
            return;
        }

        $setStatus = $this->clipService->updateClipStatus($clipId, $status);

        if ( $setStatus )
        {
            $return = array('result' => true, 'msg' => OW::getLanguage()->text('video', 'status_changed'));
        }
        else
        {
            $return = array('result' => false, 'error' => OW::getLanguage()->text('video', 'status_not_changed'));
        }

        return $return;
    }

    /**
     * Deletes video clip
     *
     * @param array $params
     * @return array
     */
    public function ajaxDeleteClip( $params )
    {
        $clipId = $params['clipId'];

        $ownerId = $this->clipService->findClipOwner($clipId);
        $isOwner = OW::getUser()->isAuthorized('video', 'add', $ownerId);
        $isModerator = OW::getUser()->isAuthorized('video');

        if ( !$isOwner && !$isModerator )
        {
            throw new Redirect404Exception();
            return;
        }

        $delResult = $this->clipService->deleteClip($clipId);

        if ( $delResult )
        {
            $return = array(
                'result' => true,
                'msg' => OW::getLanguage()->text('video', 'clip_deleted'),
                'url' => OW_Router::getInstance()->urlForRoute('video_view_list')
            );
        }
        else
        {
            $return = array(
                'result' => false,
                'error' => OW::getLanguage()->text('video', 'clip_not_deleted')
            );
        }

        return $return;
    }

    /**
     * Set 'is featured' status to video clip 
     *
     * @param array $params
     * @return array
     */
    public function ajaxSetFeaturedStatus( $params )
    {
        $clipId = $params['clipId'];
        $status = $params['status'];

        $isModerator = OW::getUser()->isAuthorized('video');

        if ( !$isModerator )
        {
            throw new Redirect404Exception();
            return;
        }

        $setResult = $this->clipService->updateClipFeaturedStatus($clipId, $status);

        if ( $setResult )
        {
            $return = array('result' => true, 'msg' => OW::getLanguage()->text('video', 'status_changed'));
        }
        else
        {
            $return = array('result' => false, 'error' => OW::getLanguage()->text('video', 'status_not_changed'));
        }

        return $return;
    }
}

/**
 * Video edit form class
 */
class videoEditForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct( $clipId )
    {
        parent::__construct('videoEditForm');

        $language = OW::getLanguage();

        // clip id field
        $clipIdField = new HiddenField('id');
        $clipIdField->setRequired(true);
        $this->addElement($clipIdField);

        // title Field
        $titleField = new TextField('title');
        $titleField->setRequired(true);
        $this->addElement($titleField->setLabel($language->text('video', 'title')));

        // description Field
        $descField = new WysiwygTextarea('description');
        $this->addElement($descField->setLabel($language->text('video', 'description')));

        $code = new Textarea('code');
        $code->setRequired(true);
        $this->addElement($code->setLabel($language->text('video', 'code')));
        
        $entityTags = BOL_TagService::getInstance()->findEntityTags($clipId, 'video');

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

        $this->addElement($tagsField->setLabel($language->text('video', 'tags')));

        $submit = new Submit('edit');
        $submit->setValue($language->text('video', 'btn_edit'));
        $this->addElement($submit);
    }

    /**
     * Updates video clip
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();
        $clipService = VIDEO_BOL_ClipService::getInstance();
        $language = OW::getLanguage();

        if ( $values['id'] )
        {
            $clip = $clipService->findClipById($values['id']);

            if ( $clip )
            {
                $clip->title = htmlspecialchars($values['title']);
                $description = UTIL_HtmlTag::stripJs($values['description']);
                $description = UTIL_HtmlTag::stripTags($description, array('frame', 'style'), array(), true);
                $clip->description = $description;
                $clip->code = $values['code'];

                if ( $clipService->updateClip($clip) )
                {
                    BOL_TagService::getInstance()->updateEntityTags(
                        $clip->id,
                        'video',
                        TagsField::getTags($values['tags'])
                    );

                    return array('result' => true, 'id' => $clip->id);
                }
            }
        }
        else
        {
            return array('result' => false, 'id' => $clip->id);
        }

        return false;
    }
}