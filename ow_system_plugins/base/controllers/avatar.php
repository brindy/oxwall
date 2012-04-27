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
 * Avatar action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_system_plugins.base.controllers
 * @since 1.0
 */
class BASE_CTRL_Avatar extends OW_ActionController
{
    /**
     * @var BOL_AvatarService
     */
    private $avatarService;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $this->avatarService = BOL_AvatarService::getInstance();
        $this->ajaxResponder = OW::getRouter()->urlFor('BASE_CTRL_Avatar', 'ajaxResponder');

        $avatarUploadForm = new avatarUploadForm();
        $this->addForm($avatarUploadForm);

        $language = OW::getLanguage();

        if ( OW::getRequest()->isPost() && !OW::getRequest()->isAjax() )
        {
            $res = $avatarUploadForm->process();
            if ( $res['result'] )
            {
                $this->redirect();
            }
            else
            {
                if ( isset($res['error']) && $res['error'] == -1 )
                {
                    OW::getFeedback()->warning($language->text('base', 'not_valid_image'));
                }
                else
                {
                    OW::getFeedback()->warning($language->text('base', 'avatar_select_image'));
                }
            }
        }

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading($language->text('base', 'avatar_change_avatar'));
            OW::getDocument()->setHeadingIconClass('ow_ic_picture');
        }
    }

    /**
     * Action cropping avatar
     */
    public function crop()
    {
        $language = OW::getLanguage();
        $avatarService = BOL_AvatarService::getInstance();
        $userService = BOL_UserService::getInstance();

        $userId = OW_Auth::getInstance()->getUserId();
        $hasAvatar = $avatarService->userHasAvatar($userId);
        $this->assign('hasAvatar', $hasAvatar);

        if ( $hasAvatar )
        {
            $this->assign('avatar', $avatarService->getAvatarUrl($userId, 2));
            $this->assign('original', $avatarService->getAvatarUrl($userId, 3));
        }
        else
        {
            $this->assign('default', $avatarService->getDefaultAvatarUrl(2));
        }

        $staticJsUrl = OW::getPluginManager()->getPlugin('base')->getStaticJsUrl();
        $staticCssUrl = OW::getPluginManager()->getPlugin('base')->getStaticCssUrl();

        OW::getDocument()->addScript($staticJsUrl . 'jquery.Jcrop.js');
        OW::getDocument()->addStyleSheet($staticCssUrl . 'jquery.Jcrop.css');

        OW::getDocument()->addScript($staticJsUrl . 'crop_avatar.js');

        $objParams = array(
            'ajaxResponder' => $this->ajaxResponder,
            'previewSize' => 100
        );

        $script =
            "$(document).ready(function(){
                var crop = new cropAvatar( " . json_encode($objParams) . ");
                crop.initCrop();
            }); ";

        OW::getDocument()->addOnloadScript($script);

        $profileEditUrl = OW::getRouter()->urlForRoute('base_edit');

        $js = new UTIL_JsGenerator();
        $js->newVariable('profileEditUrl', $profileEditUrl);
        $js->jQueryEvent('#button-profile-edit', 'click', 'window.location.href=profileEditUrl;');

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
        $request = json_decode($_POST['request'], true);

        if ( isset($request['ajaxFunc']) && OW::getRequest()->isAjax() )
        {
            $callFunc = (string) $request['ajaxFunc'];

            $result = call_user_func(array($this, $callFunc), $request);
        }
        else
        {
            return;
        }

        exit(json_encode($result));
    }

    public function ajaxCropPhoto( $params )
    {
        if ( isset($params['coords']) && isset($params['view_size']) )
        {
            $coords = $params['coords'];
            $viewSize = $params['view_size'];

            $userId = OW_Auth::getInstance()->getUserId();

            $avatarService = BOL_AvatarService::getInstance();

            $avatar = $avatarService->findByUserId($userId);
            $oldHash = $avatar->hash;
            $hash = time();

            try
            {
                $avatarService->cropAvatar($userId, $coords, $viewSize, $hash);

                // remove old avatar
                $oldAvatarPath = $avatarService->getAvatarPath($userId, 1, $oldHash);
                $avatarService->removeAvatarImage($oldAvatarPath);

                // update hash
                $avatar->hash = $hash;
                $avatarService->updateAvatar($avatar);

                // rename original
                $avatarService->renameAvatarOriginal($userId, $oldHash, $avatar->hash);

                $oldBigAvatarPath = $avatarService->getAvatarPath($userId, 2, $oldHash);
                $avatarService->removeAvatarImage($oldBigAvatarPath);

                $avatarService->trackAvatarChangeActivity($userId, $avatar->id);

                return array('result' => true, 'location' => OW_Router::getInstance()->urlForRoute('base_avatar_crop'));
            }
            catch ( Exception $e )
            {
                return array('result' => false);
            }
        }
        else
        {
            return array('result' => false);
        }
    }
}

/**
 * Avatar upload form class
 */
class avatarUploadForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        parent::__construct('avatarUploadForm');

        $language = OW::getLanguage();

        $this->setEnctype('multipart/form-data');

        $fileField = new FileField('avatar');
        $this->addElement($fileField);

        $submit = new Submit('upload');
        $submit->setValue($language->text('base', 'avatar_btn_upload'));
        $this->addElement($submit);
    }

    /**
     * Uploads avatar
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();

        $avatarService = BOL_AvatarService::getInstance();
        $userId = OW::getUser()->getId();

        if ( strlen($_FILES['avatar']['tmp_name']) )
        {
            if ( !UTIL_File::validateImage($_FILES['avatar']['name']) )
            {
                return array('result' => false, 'error' => -1);
            }

            $avatarSet = $avatarService->setUserAvatar($userId, $_FILES['avatar']['tmp_name']);

            $avatar = $avatarService->findByUserId($userId);
            if ( $avatar )
            {
                $avatarService->trackAvatarChangeActivity($userId, $avatar->id);
            }

            return array('result' => $avatarSet);
        }
        else
        {
            return array('result' => false);
        }
    }
}