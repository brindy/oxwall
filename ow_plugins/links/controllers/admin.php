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
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow_plugins.links.controllers
 * @since 1.0
 */
class LINKS_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    public function __construct()
    {
        parent::__construct();

        $this->setPageHeading(OW::getLanguage()->text('links', 'admin_links_settings_heading'));
        $this->setPageHeadingIconClass('ow_ic_gear_wheel');
    }

    /**
     * Default action
     */
    public function index()
    {
        $form = new SettingsForm($this);

        if ( !empty($_POST) && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            OW::getConfig()->saveConfig('links', 'results_per_page', $data['results_per_page']);
            OW::getConfig()->saveConfig('links', 'result_mode', $data['mode']);

            OW::getFeedback()->info(OW::getLanguage()->text('links', 'updated'));
            $this->redirect(OW::getRouter()->getBaseUrl() . OW::getRouter()->getUri());
        }

        $this->addForm($form);
    }
}

class SettingsForm extends Form
{

    public function __construct( $ctrl )
    {
        parent::__construct('form');

        $configs = OW::getConfig()->getValues('links');

        $ctrl->assign('configs', $configs);

        $l = OW::getLanguage();

        $selectbox['mode'] = new Selectbox('mode');

        $selectbox['mode']->setValue($configs['result_mode'])->setOptions(array(LinkService::RESULT_MODE_SUM => $l->text('links', 'result_mode_sum'), LinkService::RESULT_MODE_DETAILED => $l->text('links', 'result_mode_detailed')))->setRequired(true);

        $this->addElement($selectbox['mode']);

        $textField['results_per_page'] = new TextField('results_per_page');

        $textField['results_per_page']->setLabel($l->text('links', 'settings_results_per_page'))
            ->setValue($configs['results_per_page'])
            ->addValidator(new IntValidator())
            ->setRequired(true);

        $this->addElement($textField['results_per_page']);

        $submit = new Submit('submit');

        $submit->setValue($l->text('links', 'save_btn_label'));

        $this->addElement($submit);
    }
}