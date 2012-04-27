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
 * Video admin action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.video.controllers
 * @since 1.0
 */
class VIDEO_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    /**
     * Default action
     */
    public function index()
    {
        $language = OW::getLanguage();

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('video', 'admin_menu_general'));
        $item->setUrl(OW::getRouter()->urlForRoute('video_admin_config'));
        $item->setKey('general');
        $item->setIconClass('ow_ic_gear_wheel');

        $menu = new BASE_CMP_ContentMenu(array($item));
        $this->addComponent('menu', $menu);

        $configs = OW::getConfig()->getValues('video');

        $configSaveForm = new ConfigSaveForm();
        $this->addForm($configSaveForm);

        if ( OW::getRequest()->isPost() && $configSaveForm->isValid($_POST) )
        {
            $res = $configSaveForm->process();
            OW::getFeedback()->info($language->text('video', 'settings_updated'));
            $this->redirect(OW::getRouter()->urlForRoute('video_admin_config'));
        }

        if ( !OW::getRequest()->isAjax() )
        {
            $this->setPageHeading(OW::getLanguage()->text('video', 'admin_config'));
            $this->setPageHeadingIconClass('ow_ic_video');

            $menu->getElement('general')->setActive(true);
        }

        $configSaveForm->getElement('playerWidth')->setValue($configs['player_width']);
        $configSaveForm->getElement('playerHeight')->setValue($configs['player_height']);
        $configSaveForm->getElement('perPage')->setValue($configs['videos_per_page']);
        $configSaveForm->getElement('quota')->setValue($configs['user_quota']);
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
    public function __construct()
    {
        parent::__construct('configSaveForm');

        $language = OW::getLanguage();

        // player width Field
        $playerWidthField = new TextField('playerWidth');
        $playerWidthField->setRequired(true);
        $wValidator = new IntValidator(100, 1000);
        $playerWidthField->addValidator($wValidator);
        $this->addElement($playerWidthField);

        // player height Field
        $playerHeightField = new TextField('playerHeight');
        $playerHeightField->setRequired(true);
        $hValidator = new IntValidator(100, 1000);
        $playerHeightField->addValidator($hValidator);
        $this->addElement($playerHeightField);

        // per page Field
        $perPageField = new TextField('perPage');
        $perPageField->setRequired(true);
        $pValidator = new IntValidator(1, 100);
        $perPageField->addValidator($pValidator);
        $this->addElement($perPageField->setLabel($language->text('video', 'per_page')));

        // quota Field
        $quotaField = new TextField('quota');
        $quotaField->setRequired(true);
        $qValidator = new IntValidator(0, 10000);
        $quotaField->addValidator($qValidator);
        $this->addElement($quotaField->setLabel($language->text('video', 'quota')));

        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('video', 'btn_edit'));
        $this->addElement($submit);
    }

    /**
     * Updates video plugin configuration
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();

        $config = OW::getConfig();

        $config->saveConfig('video', 'player_width', $values['playerWidth']);
        $config->saveConfig('video', 'player_height', $values['playerHeight']);
        $config->saveConfig('video', 'videos_per_page', $values['perPage']);
        $config->saveConfig('video', 'user_quota', $values['quota']);

        return array('result' => true);
    }
}