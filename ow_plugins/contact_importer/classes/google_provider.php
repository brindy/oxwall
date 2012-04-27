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
 * @author Kambalin Sergey <greyexpert@gmail.com>
 * @package ow.ow_plugins.contact_importer
 * @since 1.0
 */

class CONTACTIMPORTER_CLASS_GoogleProvider extends CONTACTIMPORTER_CLASS_Provider
{
    public function __construct()
    {
        $staticUrl = OW::getPluginManager()->getPlugin('contactimporter')->getStaticUrl();

        parent::__construct(array(
            'key' => 'google',
            'title' => 'Google',
            'settigsUrl' => OW::getRouter()->urlForRoute('contactimporter_google_settings'),
            'iconClass' => 'ow_ic_gear_wheel'
        ));
    }

    public function prepareButton( $params )
    {
        $siteId = OW::getConfig()->getValue('contactimporter', 'google_site_id');

        if ( empty($siteId) )
        {
            return;
        }

        $staticUrl = OW::getPluginManager()->getPlugin('contactimporter')->getStaticUrl();
        $document = OW::getDocument();
        $document->addScript('http://www.google.com/jsapi');
        $document->addScript($staticUrl . 'js/google.js');

        $userId = OW::getUser()->getId();

        $js = UTIL_JsGenerator::newInstance();
        $js->newObject(array('window', 'CONTACTIMPORTER_Google'), 'CI_Google', array($siteId));

        $document->addOnloadScript($js);

        return array(
            'iconUrl' => $staticUrl . 'img/g.png',
            'onclick' => "CONTACTIMPORTER_Google.request(); return false;"
        );
    }
}