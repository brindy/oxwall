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
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.components
 * @since 1.0
 */
class BASE_CMP_ProfileActionToolbar extends OW_Component
{
    /**
     * @deprecated constant
     */
    const REGISTRY_DATA_KEY = 'base_cmp_profile_action_toolbar';

    const EVENT_NAME = 'base.add_profile_action_toolbar';
    const DATA_KEY_LABEL = 'label';
    const DATA_KEY_LINK_ID = 'id';
    const DATA_KEY_LINK_CLASS = 'linkClass';
    const DATA_KEY_CMP_CLASS = 'cmpClass';
    const DATA_KEY_LINK_HREF = 'href';

    /**
     * Constructor.
     */
    public function __construct( $userId )
    {
        parent::__construct();

        $userId = (int) $userId;

        $event = new BASE_CLASS_EventCollector(self::EVENT_NAME, array('userId' => $userId));

        OW::getEventManager()->trigger($event);

        $addedData = $event->getData();

        if ( empty($addedData) )
        {
            $this->setVisible(false);
            return;
        }

        $dataToAssign = array();

        $cmpsMarkup = '';

        foreach ( $addedData as $key => $value )
        {
            $dataToAssign[$key]['label'] = $value[self::DATA_KEY_LABEL];

            $dataToAssign[$key]['href'] = isset($value[self::DATA_KEY_LINK_HREF]) ? $value[self::DATA_KEY_LINK_HREF] : 'javascript://';

            if ( isset($value[self::DATA_KEY_LINK_ID]) )
            {
                $dataToAssign[$key]['id'] = $value[self::DATA_KEY_LINK_ID];
            }

            if ( isset($value[self::DATA_KEY_LINK_CLASS]) )
            {
                $dataToAssign[$key]['class'] = $value[self::DATA_KEY_LINK_CLASS];
            }

            if ( isset($value[self::DATA_KEY_CMP_CLASS]) )
            {
                $reflectionClass = new ReflectionClass(trim($value[self::DATA_KEY_CMP_CLASS]));

                $cmp = call_user_func_array(array($reflectionClass, 'newInstance'), array(array('userId' => $userId)));

                $cmpsMarkup .= $cmp->render();
            }
        }

        $userService = BOL_UserService::getInstance();
        $this->assign('userId', $userId);
        $this->assign('isAdmin', OW::getUser()->isAuthorized('base'));
        $this->assign('isModerator', BOL_AuthorizationService::getInstance()->isModerator());

        $this->assign('myself', OW::getUser()->getId() == $userId);

        $this->assign('isApproved', $userService->isApproved($userId));

        $this->assign('isSuspended', $userService->isSuspended($userId));
        $this->assign('isFeatured', $userService->isUserFeatured($userId));
        $this->assign('isBlocked', $userService->isBlocked($userId));
        $this->assign('backUrl', OW::getRouter()->getBaseUrl() . OW::getRequest()->getRequestUri());

        $this->assign('links', $dataToAssign);
        $this->assign('cmpsMarkup', $cmpsMarkup);
        $this->assign('rolesLabel', json_encode(OW::getLanguage()->text('base', 'authorization_user_roles')));
    }
}