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
 * Update Status Component
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.components
 * @since 1.0
 */
class NEWSFEED_CMP_UpdateStatus extends OW_Component
{
    public function __construct( $feedAutoId, $feedType, $feedId, $actionVisibility = null )
    {
        parent::__construct();

        $form = new NEWSFEED_StatusForm($feedAutoId, $feedType, $feedId, $actionVisibility);
        $this->addForm($form);

        $attachmentId = 'nfa-' . $feedAutoId;
        $this->assign('uniqId', $attachmentId);

        $attachment = new BASE_CLASS_Attachment( $attachmentId );

        $this->addComponent('attachment', $attachment);

        $js = 'owForms[{$form}].bind("success", function(data){
                    OW.trigger("base.init_attachment", {$uniqId});
                    owForms[{$form}].getElement("attachment").setValue("");
                });
                owForms[{$form}].reset = false;
                OW.bind("base.attachment_added",
                    function(data){
                        if( data.uid == {$uniqId} ){
                            owForms[{$form}].getElement("attachment").setValue(JSON.stringify(data));
                        }
                    }
                );
                OW.bind("base.attachment_deleted",
                    function(data){
                        if( data.uid == {$uniqId} ){
                            owForms[{$form}].getElement("attachment").setValue("");
                        }
                    }
                );';

        $js = UTIL_JsGenerator::composeJsString($js , array(
            'form' => $form->getName(),
            'uniqId' => $attachmentId
        ));


        OW::getDocument()->addOnloadScript($js);
    }
}

class NEWSFEED_StatusForm extends Form
{
    public function __construct( $feedAutoId, $feedType, $feedId, $actionVisibility = null )
    {
        parent::__construct('newsfeed_update_status');

        $this->setAjax();
        $this->setAjaxResetOnSuccess(false);

        $field = new Textarea('status');
        $field->setHasInvitation(true);
        $field->setInvitation( OW::getLanguage()->text('newsfeed', 'status_field_invintation') );
        $this->addElement($field);

        $field = new HiddenField('attachment');
        $this->addElement($field);

        $field = new HiddenField('feedType');
        $field->setValue($feedType);
        $this->addElement($field);

        $field = new HiddenField('feedId');
        $field->setValue($feedId);
        $this->addElement($field);

        $field = new HiddenField('visibility');
        $field->setValue($actionVisibility);
        $this->addElement($field);

        $submit = new Submit('save');
        $submit->setValue(OW::getLanguage()->text('newsfeed', 'status_btn_label'));
        $this->addElement($submit);

        if ( !OW::getRequest()->isAjax() )
        {

            $js = UTIL_JsGenerator::composeJsString(' owForms["newsfeed_update_status"].bind( "success", function( r )
            {
                $(this.status).val("");

                if ( r )
                {
                    window.ow_newsfeed_feed_list[{$autoId}].loadNewItem(r, false);
                }
                else
                {
                    OW.error({$errorMessage});
                }
            });', array('autoId' => $feedAutoId, 'errorMessage' => OW::getLanguage()->text('base', 'form_validate_common_error_message') ));

            OW::getDocument()->addOnloadScript( $js );
        }

        $this->setAction( OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('NEWSFEED_CTRL_Ajax', 'statusUpdate')) );
    }
}