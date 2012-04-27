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
 * Notifications Admin
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.notifications.controllers
 * @since 1.0
 */
class NOTIFICATIONS_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    public function settings()
    {
        $service = NOTIFICATIONS_BOL_Service::getInstance();
        
        OW::getDocument()->setHeading(OW::getLanguage()->text('notifications', 'config_page_heading'));
        OW::getDocument()->setTitle(OW::getLanguage()->text('notifications', 'config_page_title'));
        OW::getDocument()->setHeadingIconClass('ow_ic_mail');
        
        $form = new NOTIFICATIONS_SettingForm();
        
        if (OW::getRequest()->isPost())
        {
            $form->process($_POST);
            
            OW::getFeedback()->info(OW::getLanguage()->text('notifications', 'config_saved_message'));
            
            $this->redirect();
        }
                
        $this->addForm($form);
    }
}

class NOTIFICATIONS_SettingForm extends Form
{
    public function __construct()
    {
        parent::__construct('NotificationsSettingForm');
        
        $lang = OW::getLanguage();
        $config = OW::getConfig();
        
        $field = new Selectbox('day');
        $field->setHasInvitation(false);
        for ( $i = 0; $i < 7; $i++ )
        {
            $field->addOption($i, $lang->text('base', 'date_time_week_' . $i));    
        }
        
        $field->setValue($config->getValue('notifications', 'schedule_wday'));
        $this->addElement($field);
        
        $field = new Selectbox('hour');
        $field->setHasInvitation(false);
        for ( $i = 0; $i < 24; $i++ )
        {
            $hour = $i < 10 ? '0' . $i : $i;
            $field->addOption($hour, $hour);    
        }
        
        $field->setValue($config->getValue('notifications', 'schedule_dhour'));        
        $this->addElement($field);
        
        // submit
        $submit = new Submit('save');
        $submit->setValue($lang->text('notifications', 'save_config_btn'));
        $this->addElement($submit);
    }
    
    public function process($values)
    {
        $config = OW::getConfig();
        
        $config->saveConfig('notifications', 'schedule_wday', $values['day']);
        $config->saveConfig('notifications', 'schedule_dhour', $values['hour']);
    }
}