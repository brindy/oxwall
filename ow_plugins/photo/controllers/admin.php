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
 * Photo administration action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.controllers
 * @since 1.0
 */
class PHOTO_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    /**
     * Default action
     */
    public function index()
    {
        $language = OW::getLanguage();

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('photo', 'admin_menu_general'));
        $item->setUrl(OW::getRouter()->urlForRoute('photo_admin_config'));
        $item->setKey('general');
        $item->setIconClass('ow_ic_gear_wheel');
        $item->setOrder(0);

        $menu = new BASE_CMP_ContentMenu(array($item));
        $this->addComponent('menu', $menu);

        $this->assign('iniValue', ini_get('upload_max_filesize'));

        $configs = OW::getConfig()->getValues('photo');

        $configSaveForm = new ConfigSaveForm();

        $this->addForm($configSaveForm);

        if ( OW::getRequest()->isPost() && $configSaveForm->isValid($_POST) )
        {
            $res = $configSaveForm->process();
            OW::getFeedback()->info($language->text('photo', 'settings_updated'));
            $this->redirect(OW::getRouter()->urlForRoute('photo_admin_config'));
        }

        if ( !OW::getRequest()->isAjax() )
        {
            $this->setPageHeading(OW::getLanguage()->text('photo', 'admin_config'));
            $this->setPageHeadingIconClass('ow_ic_picture');

            $elem = $menu->getElement('general');
            if ( $elem )
            {
                $elem->setActive(true);
            }
        }

        $configSaveForm->getElement('acceptedFilesize')->setValue($configs['accepted_filesize']);
        $configSaveForm->getElement('mainWidth')->setValue($configs['main_image_width']);
        $configSaveForm->getElement('mainHeight')->setValue($configs['main_image_height']);
        $configSaveForm->getElement('previewWidth')->setValue($configs['preview_image_width']);
        $configSaveForm->getElement('previewHeight')->setValue($configs['preview_image_height']);
        $configSaveForm->getElement('perPage')->setValue($configs['photos_per_page']);
        $configSaveForm->getElement('albumQuota')->setValue($configs['album_quota']);
        $configSaveForm->getElement('userQuota')->setValue($configs['user_quota']);
        $configSaveForm->getElement('storeFullsize')->setValue($configs['store_fullsize']);
        $configSaveForm->getElement('advancedUpload')->setValue($configs['advanced_upload_enabled']);
        $configSaveForm->getElement('fullsizeResolution')->setValue($configs['fullsize_resolution']);

        $photoService = PHOTO_BOL_PhotoService::getInstance();
        $fullsizeCount = $photoService->countFullsizePhotos();
        $this->assign('fullsizeCount', $fullsizeCount);
        $this->assign('storeFullsize', $configs['store_fullsize']);

        OW::getLanguage()->addKeyForJs('photo', 'delete_fullsize_confirm');
    }

    public function deleteFullsize()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
            return false;
        }
        PHOTO_BOL_PhotoService::getInstance()->deleteFullsizePhotos();

        OW::getConfig()->saveConfig('photo', 'store_fullsize', 0);

        $res = array('res' => true, 'msg' => OW::getLanguage()->text('photo', 'photos_deleted'));

        exit(json_encode($res));
    }
    
    public function uninstall()
    {
        if ( isset($_POST['action']) && $_POST['action'] == 'delete_content' )
        {
            OW::getConfig()->saveConfig('photo', 'uninstall_inprogress', 1);
            
            PHOTO_BOL_PhotoService::getInstance()->setMaintenanceMode(true);
            
            OW::getFeedback()->info(OW::getLanguage()->text('photo', 'plugin_set_for_uninstall'));
            $this->redirect();
        }
              
        $this->setPageHeading(OW::getLanguage()->text('photo', 'page_title_uninstall'));
        $this->setPageHeadingIconClass('ow_ic_delete');
        
        $this->assign('inprogress', (bool) OW::getConfig()->getValue('photo', 'uninstall_inprogress'));
        
        $js = new UTIL_JsGenerator();
        $js->jQueryEvent('#btn-delete-content', 'click', 'if ( !confirm("'.OW::getLanguage()->text('photo', 'confirm_delete_photos').'") ) return false;');
        
        OW::getDocument()->addOnloadScript($js);
    }
}

/**
 * Save photo configuration form class
 */
class ConfigSaveForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        parent::__construct('configSaveForm');

        $language = OW::getLanguage();

        // accepted file size Field
        $acceptedFilesizeField = new TextField('acceptedFilesize');
        $acceptedFilesizeField->setRequired(true);
        $sValidator = new FloatValidator();
        $max = intval(ini_get('upload_max_filesize')) ? intval(ini_get('upload_max_filesize')) : 2;
        $sValidator->setMaxValue($max);
        $sValidator->setErrorMessage($language->text('photo', 'file_size_validation_error'));
        $acceptedFilesizeField->addValidator($sValidator);
        $this->addElement($acceptedFilesizeField->setLabel($language->text('photo', 'accepted_filesize')));

        // main image width Field
        $mainWidthField = new TextField('mainWidth');
        $mainWidthField->setRequired(true);
        $mwValidator = new IntValidator(100, 1000);
        $mainWidthField->addValidator($mwValidator);
        $this->addElement($mainWidthField);

        // main image height Field
        $mainHeightField = new TextField('mainHeight');
        $mainHeightField->setRequired(true);
        $mhValidator = new IntValidator(100, 1000);
        $mainHeightField->addValidator($mhValidator);
        $this->addElement($mainHeightField);

        // preview image width Field
        $previewWidthField = new TextField('previewWidth');
        $previewWidthField->setRequired(true);
        $pwValidator = new IntValidator();
        $pwValidator->setMaxValue(300);
        $previewWidthField->addValidator($pwValidator);
        $this->addElement($previewWidthField);

        // preview image height Field
        $previewHeightField = new TextField('previewHeight');
        $previewHeightField->setRequired(true);
        $phValidator = new IntValidator();
        $phValidator->setMaxValue(300);
        $previewHeightField->addValidator($phValidator);
        $this->addElement($previewHeightField);

        // per page Field
        $perPageField = new TextField('perPage');
        $perPageField->setRequired(true);
        $pValidator = new IntValidator(1, 100);
        $perPageField->addValidator($pValidator);
        $this->addElement($perPageField->setLabel($language->text('photo', 'per_page')));

        // album quota Field
        $albumQuotaField = new TextField('albumQuota');
        $albumQuotaField->setRequired(true);
        $aqValidator = new IntValidator(0, 1000);
        $albumQuotaField->addValidator($aqValidator);
        $this->addElement($albumQuotaField->setLabel($language->text('photo', 'album_quota')));

        // user quota Field
        $userQuotaField = new TextField('userQuota');
        $userQuotaField->setRequired(true);
        $uqValidator = new IntValidator(0, 10000);
        $userQuotaField->addValidator($uqValidator);
        $this->addElement($userQuotaField->setLabel($language->text('photo', 'user_quota')));

        $storeFullsizeField = new CheckboxField('storeFullsize');
        $storeFullsizeField->setLabel($language->text('photo', 'store_full_size'));
        $this->addElement($storeFullsizeField);
        
        $fullsizeRes = new TextField('fullsizeResolution');
        $frValidator = new IntValidator();
        $frValidator->setMinValue(100);
        $fullsizeRes->addValidator($frValidator);
        $fullsizeRes->setLabel($language->text('photo', 'fullsize_resolution'));
        $this->addElement($fullsizeRes);

        $advancedUploadField = new CheckboxField('advancedUpload');
        $advancedUploadField->setLabel($language->text('photo', 'enable_advanced_upload'));
        $this->addElement($advancedUploadField);
        
        $js = UTIL_JsGenerator::composeJsString(
                '$(window.owForms.configSaveForm.getElement("storeFullsize").input).click(function(){
                if ( !this.checked ) {
                    $("#delete-fullsize-btn-node").css("display", "inline");
                    $("#fullsize_res_config").css("display", "none");
                }
                else {
                    $("#delete-fullsize-btn-node").css("display", "none");
                    $("#fullsize_res_config").css("display", "table-row");
                }
            })', array('confMsg' => $language->text('photo', 'store_fullsize_confirm')));

        OW::getDocument()->addOnloadScript($js);

        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('photo', 'btn_edit'));
        $this->addElement($submit);
    }

    /**
     * Updates photo plugin configuration
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();

        $config = OW::getConfig();

        $config->saveConfig('photo', 'accepted_filesize', $values['acceptedFilesize']);
        $config->saveConfig('photo', 'main_image_width', $values['mainWidth']);
        $config->saveConfig('photo', 'main_image_height', $values['mainHeight']);
        $config->saveConfig('photo', 'preview_image_width', $values['previewWidth']);
        $config->saveConfig('photo', 'preview_image_height', $values['previewHeight']);
        $config->saveConfig('photo', 'photos_per_page', $values['perPage']);
        $config->saveConfig('photo', 'album_quota', $values['albumQuota']);
        $config->saveConfig('photo', 'user_quota', $values['userQuota']);
        $config->saveConfig('photo', 'store_fullsize', $values['storeFullsize']);
        $config->saveConfig('photo', 'advanced_upload_enabled', $values['advancedUpload']);
        
        if ( $values['fullsizeResolution'] )
        {
            $config->saveConfig('photo', 'fullsize_resolution', $values['fullsizeResolution']);
        }

        return array('result' => true);
    }
}