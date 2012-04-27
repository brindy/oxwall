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
$plugin = OW::getPluginManager()->getPlugin('notifications');

OW::getRouter()->addRoute(new OW_Route('notifications-admin-settings', 'admin/plugins/email-notifications', 'NOTIFICATIONS_CTRL_Admin', 'settings'));
OW::getRouter()->addRoute(new OW_Route('notifications-settings', 'email-notifications', 'NOTIFICATIONS_CTRL_Notifications', 'settings'));
OW::getRouter()->addRoute(new OW_Route('notifications-unsubscribe', 'email-notifications/unsubscribe/:code/:action', 'NOTIFICATIONS_CTRL_Notifications', 'unsubscribe'));

function notification_user_action_listener( OW_Event $e )
{
    $params = $e->getParams();
    $userId = (int) $params['userId'];
    $action = trim($params['action']);
    
    $service = NOTIFICATIONS_BOL_Service::getInstance();
    $defaultRules = $service->collectActionList();
    $rules = $service->findRuleList($userId);

    if ( isset($rules[$action]) )
    {
        if ( !$rules[$action]->checked )
        {
            return;
        }
    }
    else
    {
        if ( empty($defaultRules[$action]['selected']) )
        {
            return;
        }
    }
    
    $event = new NOTIFICATIONS_CLASS_Notification();

    $event->userId = $userId;
    $event->action = trim($params['action']);
    $event->plugin = trim($params['plugin']);
    $event->content = empty($params['content']) ? null : $params['content'];
    $event->url = empty($params['url']) ? null : $params['url'];
    $event->string = empty($params['string']) ? null : $params['string'];
    $event->avatar = empty($params['avatar']) ? null : trim($params['avatar']);
    $event->timeStamp = empty($params['time']) ? time() : (int) $params['time'];

    $schedule = $service->findSchedule($userId);
    
    if ( $schedule == NOTIFICATIONS_BOL_Service::NOTIFY_TYPE_IMMEDIATELY )
    {
        $service->sendNotifications($userId, array($event), false);
    }
    else
    {
        $service->addToNotificationQueue($event);
    }
}
OW::getEventManager()->bind('base.notify', 'notification_user_action_listener');

function notifications_dashboard_menu_item( BASE_CLASS_EventCollector $event )
{
    $router = OW_Router::getInstance();
    $language = OW::getLanguage();

    $menuItems = array();

    $menuItem = new BASE_MenuItem();

    $menuItem->setKey('email_notifications');
    $menuItem->setLabel($language->text('notifications', 'dashboard_menu_item_label'));
    $menuItem->setIconClass('ow_ic_mail');
    $menuItem->setUrl($router->urlForRoute('notifications-settings'));
    $menuItem->setOrder(3);

    $event->add($menuItem);
}
OW::getEventManager()->bind('base.dashboard_menu_items', 'notifications_dashboard_menu_item');

function notifications_ads_enabled( BASE_EventCollector $event )
{
    $event->add('notifications');
}

OW::getEventManager()->bind('ads.enabled_plugins', 'notifications_ads_enabled');