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
 * Group Brief Info
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.groups.components
 * @since 1.0
 */
class GROUPS_CMP_BriefInfo extends OW_Component
{
    /**
     * @return Constructor.
     */
    public function __construct($groupId)
    {
        parent::__construct();
        
        $this->addComponent('content', new GROUPS_CMP_BriefInfoContent($groupId));
        
        $this->assign('box', $this->getBoxParmList($groupId));
    }
    
    private function getBoxParmList($groupId)
    {
        $settings = GROUPS_CMP_BriefInfoWidget::getStandardSettingValueList();
        $defaultSettings = BOL_ComponentAdminService::getInstance()->findSettingList('group-GROUPS_CMP_BriefInfoWidget');
        $customSettings = BOL_ComponentEntityService::getInstance()->findSettingList('group-GROUPS_CMP_BriefInfoWidget', $groupId);
        
        $out = array_merge($settings, $defaultSettings, $customSettings);
        $out['type'] = $out['wrap_in_box'] ? '' : 'empty';
        
        return $out;
    }
}