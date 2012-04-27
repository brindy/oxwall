<?php

/**
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

/**
 * Membership cron job.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.membership.bol
 * @since 1.0
 */
class GROUPS_Cron extends OW_Cron
{
    const GROUPS_DELETE_LIMIT = 50;
    
    public function getRunInterval()
    {
        return 1;
    }
    
    public function run()
    {
        $config = OW::getConfig();

        // check if uninstall is in progress
        if ( !$config->getValue('groups', 'uninstall_inprogress') )
        {
            return;
        }

        if ( !$config->configExists('groups', 'uninstall_cron_busy') )
        {
            $config->addConfig('groups', 'uninstall_cron_busy', 0);
        }
        
        // check if cron queue is not busy
        if ( $config->getValue('groups', 'uninstall_cron_busy') )
        {
            return;
        }

        $config->saveConfig('groups', 'uninstall_cron_busy', 1);
        $service = GROUPS_BOL_Service::getInstance();
        $groups = $service->findLimitedList(self::GROUPS_DELETE_LIMIT);
        
        if ( empty($groups) ) 
        {
            BOL_PluginService::getInstance()->uninstall('groups');
            OW::getApplication()->setMaintenanceMode(false);
            
            return;
        }
        
        foreach ( $groups as $group )
        {
            $service->deleteGroup($group->id);
        }
        
        $config->saveConfig('groups', 'uninstall_cron_busy', 0);
    }
}