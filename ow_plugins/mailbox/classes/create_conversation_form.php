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
 * New conversation form class
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.classes
 * @since 1.0
 */

class CreateConversationForm extends Form
{
    const DISPLAY_CAPTCHA_TIMEOUT = 20;

    /**
     * Class constructor
     *
     */
    public $displayCapcha = false;


    public function __construct($interlocutorId)
    {
        $language = OW::getLanguage();
        
        parent::__construct('mailbox-create-conversation-form');
        
        $this->setId('mailbox-create-conversation-form');
        $this->setEnctype('multipart/form-data');
        $this->setAjax();
        $this->setAjaxResetOnSuccess(false);

        $hidden = new HiddenField('userId');
        $hidden->setValue($interlocutorId);
        $this->addElement($hidden);

        //thickbox
        $subject = new TextField('subject');
        $subject->setLabel($language->text('mailbox', 'subject'))->addAttribute('class', 'ow_text');
        $subject->addValidator(new StringValidator(0, 2048));
        $subject->setRequired(true);
        $this->addElement($subject);

        $message = new WysiwygTextarea('message', array( BOL_TextFormatService::WS_BTN_IMAGE, BOL_TextFormatService::WS_BTN_VIDEO ), false);
        $message->setLabel($language->text('mailbox', 'text'))->addAttribute('class', 'ow_text');
        $message->addAttribute('rows', '10');
        $message->addValidator(new StringValidator(0, 24000));
        $message->setRequired(true);
        $this->addElement($message);

        $multiUpload = new MAILBOX_CLASS_AjaxFileUpload('attachments');
        $multiUpload->setId('attachments');
        $this->addElement($multiUpload);

        $captcha = new MailboxCaptchaField('captcha');
        $captcha->addValidator(new MailboxCaptchaValidator($captcha->getId()));

        $LastSendStamp = BOL_PreferenceService::getInstance()->getPreferenceValue('mailbox_create_conversation_stamp', OW::getUser()->getId());
        $this->displayCapcha = BOL_PreferenceService::getInstance()->getPreferenceValue('mailbox_create_conversation_display_capcha', OW::getUser()->getId());
        
        if ( !$this->displayCapcha && ($LastSendStamp + self::DISPLAY_CAPTCHA_TIMEOUT) > time() )
        {
            BOL_PreferenceService::getInstance()->savePreferenceValue('mailbox_create_conversation_display_capcha', true, OW::getUser()->getId());
            $this->displayCapcha = true;
        }

        $captcha->addAttribute('disabled', 'disabled');

        $this->addElement($captcha);

        $submit = new Submit('send');
        $submit->setValue($language->text('mailbox', 'send_button'));
        $submit->addAttribute('class', 'ow_button ow_ic_mail');
        $this->addElement($submit);
        
        if ( !OW::getRequest()->isAjax() )
        {
            $messageError = $language->text('mailbox', 'create_conversation_fail_message');
            $messageSuccess = $language->text('mailbox', 'create_conversation_message');
            
            $js = " owForms['mailbox-create-conversation-form'].bind( 'success',
            function( json )
            {
                var from = $('#mailbox-create-conversation-form');
                var captcha = from.find('input[name=captcha]');

                if ( json.result == 'permission_denied' )
                {
                    if ( json.message != undefined )
                    {
                        OW.error(json.message);
                    }
                    else
                    {
                        OW.error(". json_encode(OW::getLanguage()->text('mailbox', 'write_permission_denied')).");
                    }
                }
                else if ( json.result == 'display_captcha' )
            	{
                   window.". $captcha->jsObjectName .".refresh();

                   if ( captcha.attr('disabled') != 'disabled' )
                   {
                        owForms['mailbox-create-conversation-form'].getElement('captcha').showError(". json_encode(OW::getLanguage()->text('base', 'form_validator_captcha_error_message')) . ");
                   }
                   else
                   {
                        captcha.removeAttr('disabled');
                   }

                   from.find('tr.captcha').show();
                   from.find('tr.mailbox_conversation').hide();
                }
                else if ( json.result == true )
            	{
            	    window.mailbox_send_message_floatbox.close();
                    $('#attach_file_inputs').hide();

                    captcha.attr('disabled','disabled');
                    from.find('tr.captcha').hide();
                    owForms['mailbox-create-conversation-form'].resetForm();
                    window.". $captcha->jsObjectName .".refresh();

            	    OW.info('{$messageSuccess}');
                    from.find('tr.captcha').hide();
                    from.find('tr.mailbox_conversation').show();
                }
                else
                {
                    OW.error('{$messageError}');
                }

                $('#mailbox-create-conversation-form input[name=userId]').val(" . $interlocutorId . ");

        	} ); ";

            OW::getDocument()->addOnloadScript( $js );
        }
    }

    /**
     * Creates new conversation
     *
     * @param int $initiatorId
     * @param int $interlocutorId
     */

    public function process( $initiatorId, $interlocutorId )
    {
        if ( OW::getRequest()->isAjax() )
        {
            if ( empty($initiatorId) || empty($interlocutorId) )
            {
                echo json_encode(array('result'=> false ));
                exit();
            }

            $isAuthorized = OW::getUser()->isAuthorized( 'mailbox', 'send_message' );
            
            if( !$isAuthorized )
            {
                echo json_encode(array('result'=> 'permission_denied' ));
                exit();
            }

            // credits check
            $eventParams = array('pluginKey' => 'mailbox', 'action' => 'send_message');
            $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams);
            
            if ( $credits === false )
            {
                $error = OW::getEventManager()->call('usercredits.error_message', $eventParams);
                echo json_encode(array('result'=> 'permission_denied', 'message' => $error));
                exit();
            }

            $captcha = $this->getElement('captcha');
            $captcha->setRequired();
            
            if ( $this->displayCapcha && ( !$captcha->isValid() || !UTIL_Validator::isCaptchaValid($captcha->getValue()) ) )
            {
                echo json_encode(array('result'=> 'display_captcha' ));
                exit();
            }

            $values = $this->getValues();
            $conversationService = MAILBOX_BOL_ConversationService::getInstance();
            $uploadFiles = MAILBOX_BOL_FileUploadService::getInstance();
            $conversation = $conversationService->createConversation($initiatorId, $interlocutorId, htmlspecialchars($values['subject']), $values['message']);
            $message = $conversationService->getLastMessages($conversation->id);

            $fileDtoList = $uploadFiles->findUploadFileList($values['attachments']);

            foreach( $fileDtoList as $fileDto )
            {
                $attachmentDto = new MAILBOX_BOL_Attachment();
                $attachmentDto->messageId = $message->initiatorMessageId;
                $attachmentDto->fileName = htmlspecialchars($fileDto->fileName);
                $attachmentDto->fileSize = $fileDto->fileSize;
                $attachmentDto->hash = $fileDto->hash;
                
                if ( $conversationService->fileExtensionIsAllowed( UTIL_File::getExtension($fileDto->fileName) ) )
                {
                    $conversationService->addAttachment($attachmentDto, $fileDto->filePath);
                }

                $uploadFiles->deleteUploadFile($fileDto->hash, $fileDto->userId);
            }

            // credits track
            if ( $credits === true )
            {
                OW::getEventManager()->call('usercredits.track_action', $eventParams);
            }

            BOL_PreferenceService::getInstance()->savePreferenceValue('mailbox_create_conversation_display_capcha', false, OW::getUser()->getId());

            $timestamp = 0;
            if( $this->displayCapcha == false )
            {
                $timestamp = time();
            }

            BOL_PreferenceService::getInstance()->savePreferenceValue('mailbox_create_conversation_stamp', $timestamp, OW::getUser()->getId());

            echo json_encode(array('result'=> true ));
            exit();
        }
    }
}


class MailboxCaptchaField extends CaptchaField
{
    public function  __construct( $name )
    {
        if ( $name === null || !$name || strlen(trim($name)) === 0 )
        {
            throw new InvalidArgumentException('Invalid form element name!');
        }

        $this->setName($name);

        $this->setId(UTIL_HtmlTag::generateAutoId('input'));

        $this->addAttribute('type', 'text');
        $this->jsObjectName = self::CAPTCHA_PREFIX . preg_replace('/[^\d^\w]/', '_', $this->getId());
        $this->addAttribute('style', 'width:100px;');
    }
}

class MailboxCaptchaValidator extends CaptchaValidator
{
    public $elementId = null;

    public function  __construct( $elementId )
    {
        parent::__construct();

        if ( empty($elementId) )
        {
            throw new InvalidArgumentException('Invalid elementId!');
        }

        $this->elementId = $elementId;
    }

    public function checkValue( $value )
    {
        return true;
    }

    public function getJsValidator()
    {
        return "{

                validate : function( value )
                {
                    if( $('#" . $this->elementId . "').attr('disabled') != 'disabled' && !window." . $this->jsObjectName . ".validateCaptcha() )
                    {
                        throw " . json_encode($this->getError()) . ";
                    }
                },

                getErrorMessage : function()
                {
                    return " . json_encode($this->getError()) . ";
                }
        }";
    }
}