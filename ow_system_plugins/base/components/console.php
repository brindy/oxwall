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
 * User console component class.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.components
 * @since 1.0
 */
class BASE_CMP_Console extends OW_Component
{
    const EVENT_NAME = 'base.add_console_item';
    const DATA_KEY_ICON_CLASS = 'icon_class';
    const DATA_KEY_URL = 'url';
    const DATA_KEY_ID = 'id';
    const DATA_KEY_BLOCK = 'block';
    const DATA_KEY_BLOCK_ID = 'block_id';
    const DATA_KEY_ITEMS_LABEL = 'block_items_count';
    const DATA_KEY_BLOCK_CLASS = 'block_class';
    const DATA_KEY_TITLE = 'title';
    const DATA_KEY_HIDDEN_CONTENT = 'hidden_content';

    const VALUE_BLOCK_CLASS_GREEN = 'ow_mild_green';
    const VALUE_BLOCK_CLASS_RED = 'ow_mild_red';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if ( OW::getUser()->isAuthenticated() )
        {
            $this->member();
        }
        else
        {
            $this->guest();
        }

        $this->addComponent('switchLanguage', new BASE_CMP_SwitchLanguage());
    }

    private function guest()
    {
        $this->addComponent('sign_in', new BASE_CMP_AjaxSignIn());
        $this->addComponent('connectButtonList', new BASE_CMP_ConnectButtonList());
    }

    private function member()
    {
        $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCmpViewDir() . 'console_member.html');
        $language = OW::getLanguage();
        $router = OW::getRouter();

        $event = new BASE_CLASS_EventCollector(self::EVENT_NAME);
        OW::getEventManager()->trigger($event);
        $data = $event->getData();
        $this->assign('items', $data);

        $displayName = BOL_UserService::getInstance()->getDisplayName(OW::getUser()->getId());
        $this->assign('displayName', $displayName);

        $this->assign('links', array(
            'profile' => $router->urlForRoute('base_member_profile'),
            'sign_out' => $router->urlForRoute('base_sign_out')
        ));

        $this->assign('titles', array(
            'profile' => $language->text('base', 'console_item_label_profile'),
            'sign_out' => mb_strtolower($language->text('base', 'console_item_label_sign_out'))
        ));
    }
}