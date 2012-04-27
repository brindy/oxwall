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

OW::getRouter()->addRoute(new OW_Route('contactus.index', 'contact', "CONTACTUS_CTRL_Contact", 'index'));
OW::getRouter()->addRoute(new OW_Route('contactus.admin', 'admin/plugins/contactus', "CONTACTUS_CTRL_Admin", 'dept'));

function contactus_handler_after_install( BASE_CLASS_EventCollector $event )
{
    if ( count(CONTACTUS_BOL_Service::getInstance()->getDepartmentList()) < 1 )
    {
        $url = OW::getRouter()->urlForRoute('contactus.admin');
        $event->add(OW::getLanguage()->text('contactus', 'after_install_notification', array('url' => $url)));
    }
}

OW::getEventManager()->bind('admin.add_admin_notification', 'contactus_handler_after_install');


function contactus_ads_enabled( BASE_EventCollector $event )
{
    $event->add('contactus');
}

OW::getEventManager()->bind('ads.enabled_plugins', 'contactus_ads_enabled');

OW::getRequestHandler()->addCatchAllRequestsExclude('base.suspended_user', 'CONTACTUS_CTRL_Contact');