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
 * Forum search form class.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.components
 * @since 1.0
 */
class FORUM_CMP_ForumSearch extends OW_Component
{
    private $scope;
    
    public function __construct( array $params )
    {
        parent::__construct();
        
        $this->scope = $params['scope'];
        
        $value = isset($params['token']) ? trim(htmlspecialchars($params['token'])) : null;
        $invitation = $this->getInvitationLabel();
        
        $inputParams = array(
            'type' => 'text',
            'class' => !mb_strlen($value) ? 'invitation' : '',
            'value' => mb_strlen($value) ? $value : $invitation,
            'id' => UTIL_HtmlTag::generateAutoId('input')
        );
        $this->assign('input', UTIL_HtmlTag::generateTag('input', $inputParams));
        
        switch ( $this->scope )
        {
            case 'topic':
                $location = json_encode(OW::getRouter()->urlForRoute('forum_search_topic', array('topicId' => $params['topicId'])).'?q=');
                break;
                
            case 'group':
                $location = json_encode(OW::getRouter()->urlForRoute('forum_search_group', array('groupId' => $params['groupId'])).'?q=');
                break;

            default:
                $location = json_encode(OW::getRouter()->urlForRoute('forum_search').'?q=');
                break;
        }
        
        $script = '';
        
        if ( !mb_strlen($value) )
        {
            $script = '$("#'.$inputParams['id'].'").focus(function(){
                if ( $(this).val() == '.json_encode($invitation).' )
                {
                    $(this).removeClass("invitation");
                    $(this).val("");
                }
            });
            
            $("#'.$inputParams['id'].'").blur(function(){
                if ( $(this).val() == "" )
                {
                    $(this).addClass("invitation");
                    $(this).val('.json_encode($invitation).');
                }
            });';
        }
        $script .= 
            '
            $("form#forum_search").submit(function(){
                var search = encodeURIComponent($("#'.$inputParams['id'].'").attr("value"));
                if ( search != "" )
                {
                    location.href = '.$location.' + search;
                }
                return false;
            })';
        
        OW::getDocument()->addOnloadScript($script);
    }
    
    private function getInvitationLabel()
    {
        return OW::getLanguage()->text('forum', 'search_invitation_' . $this->scope);
    }
}