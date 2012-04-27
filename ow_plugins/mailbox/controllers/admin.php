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
 * Forum admin action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.controllers
 * @since 1.0
 */
class MAILBOX_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    /**
     * Default action
     */
    public function index()
    {
        $language = OW::getLanguage();

        $uploadMaxFilesize = (float) ini_get("upload_max_filesize");
        $postMaxSize = (float) ini_get("post_max_size");

        $maxUploadMaxFilesize = $uploadMaxFilesize >= $postMaxSize ? $postMaxSize : $uploadMaxFilesize;

        $this->assign('maxUploadMaxFilesize', $maxUploadMaxFilesize);

        $configSaveForm = new ConfigSaveForm($maxUploadMaxFilesize);
        $this->addForm($configSaveForm);

        if ( OW::getRequest()->isPost() && $configSaveForm->isValid($_POST) )
        {
            $configSaveForm->process();
            OW::getFeedback()->info($language->text('mailbox', 'settings_updated'));
            $this->redirect();
        }

        if ( !OW::getRequest()->isAjax() )
        {
            $this->setPageHeading(OW::getLanguage()->text('mailbox', 'admin_config'));
            $this->setPageHeadingIconClass('ow_ic_mail');
        }

        /* OW::getDocument()->addOnloadScript("
            $(\"form[name='configSaveForm'] input[name='enableAttachments']\").change(
                function()
                {
                    if( $(this).attr('checked') )
                    {
                        $(\"form[name='configSaveForm'] input[name='uploadMaxFileSize']\").removeAttr('disabled');
                    }
                    else
                    {
                        $(\"form[name='configSaveForm'] input[name='uploadMaxFileSize']\").attr('disabled','disabled');
                    }
                });
            "); */
    }
}

/**
 * Save Configurations form class
 */
class ConfigSaveForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct( $maxUploadFileSize )
    {
        parent::__construct('configSaveForm');

        $language = OW::getLanguage();

        $configs = OW::getConfig()->getValues('mailbox');

        $element = new CheckboxField('enableAttachments');
        $element->setValue($configs['enable_attachments']);
        $this->addElement($element);

        $element = new TextField('uploadMaxFileSize');
        $element->addAttribute('style', 'width:30px');
        
        /* if ( !$configs['enable_attachments'] )
        {
            $element->addAttribute('disabled', 'disabled');
        } */

        $validator = new FloatValidator(0, $maxUploadFileSize);
        $validator->setErrorMessage($language->text('admin', 'settings_max_upload_size_error'));

        $element->addValidator($validator);
        $element->setValue((float) $configs['upload_max_file_size']);
        
        $this->addElement($element);

        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('base', 'edit_button'));
        $this->addElement($submit);
    }

    /**
     * Updates forum plugin configuration
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();

        $enableAttachmentsValue = empty($values['enableAttachments']) ? false : (boolean)$values['enableAttachments'];

        $config = OW::getConfig();

        $config->saveConfig('mailbox', 'enable_attachments', $enableAttachmentsValue);

        if ( $enableAttachmentsValue )
        {
            $config->saveConfig('mailbox', 'upload_max_file_size', (float) $values['uploadMaxFileSize']);
        }

        return array('result' => true);
    }
}