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
 * @author Aybat Duyshokov <duyshokov@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
class BASE_CTRL_Flag extends OW_ActionController
{

    public function form()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            exit;
        }

        $type = $_POST['type'];
        $id = $_POST['id'];

        $title = $_POST['title'];
        $url = $_POST['url'];

        $langKey = $_POST['langKey'];

        $cmp = new BASE_CMP_Flag($type, $id, $title, $url, $langKey);

        if ( BOL_FlagService::getInstance()->isFlagged($type, $id, OW::getUser()->getId()) )
        {
            exit('{isFlagged: true}');
        }

        exit(
            json_encode(
                array(
                    'markup' => $cmp->render(),
                    'js' => OW::getDocument()->getOnloadScript(),
                    'include_js' => OW::getDocument()->getScripts(),
                    'css' => '.foo ul li{ float: left; width: 100px !important;}'// TODO: style via ajax
                )
            )
        );
    }

    public function flag()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            return;
        }

        $s = BOL_FlagService::getInstance();

        $s->isFlagged($_POST['type'], $_POST['id'], OW::getUser()->getId());

        $s->flag($_POST['type'], $_POST['id'], $_POST['reason'], $_POST['title'], $_POST['url'], $_POST['langKey'], OW::getUser()->getId());

        exit(json_encode(array(
                'result' => 'success',
                'js' => 'OW.info("' . OW::getLanguage()->text('base', 'flag_accepted') . '")'
            )));
    }

    public function delete( $params )
    {
        if ( !(OW::getUser()->isAdmin() || BOL_AuthorizationService::getInstance()->isModerator()) )
        {
            exit();
        }

        BOL_FlagService::getInstance()->deleteById($params['id']);
        OW::getFeedback()->info(OW::getLanguage()->text('base', 'flags_deleted'));
        $this->redirect($_SERVER['HTTP_REFERER']);
    }
}