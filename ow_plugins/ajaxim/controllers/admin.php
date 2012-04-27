<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2009, Skalfa LLC
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
 *  - Neither the name of the Skalfa LLC nor the names of its contributors may be used to endorse or promote products
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
 * IM admin controller.
 *
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow.ow_plugins.ajaxim.controllers
 * @since 1.0
 */
class AJAXIM_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    public function settings()
    {
        $lang = OW::getLanguage();
        $form = new AJAXIMSettingsForm();

        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) && $form->process() )
            {
                OW::getFeedback()->info($lang->text('ajaxim', 'settings_saved'));
                $this->redirect();
            }
        }

        if ( !OW::getRequest()->isAjax() )
        {
            $this->setPageTitle(OW::getLanguage()->text('ajaxim', 'admin_settings_title'));
            $this->setPageHeading(OW::getLanguage()->text('ajaxim', 'admin_settings_title'));
            $this->setPageHeadingIconClass('ow_ic_chat');
        }


        $this->addForm($form);
    }


}

class AJAXIMSettingsForm extends Form
{

    public function __construct()
    {
        parent::__construct('AJAXIMSettingForm');

        $lang = OW::getLanguage();

        $imService = AJAXIM_BOL_Service::getInstance();
        $configs = OW::getConfig();

        $friendsOnlyField = new CheckboxField('friends_only');
        $friendsOnlyField->setValue($configs->getValue('ajaxim', 'friends_only'));
        $this->addElement($friendsOnlyField);

        // submit
        $submit = new Submit('save');
        $submit->setValue($lang->text('ajaxim', 'save_btn'));
        $this->addElement($submit);
    }

    public function process()
    {
        $values = $this->getValues();

        OW::getConfig()->saveConfig('ajaxim', 'friends_only', (bool)$values['friends_only']);

        return true;
    }
}