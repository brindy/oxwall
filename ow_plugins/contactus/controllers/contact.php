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
 * Main page
 *
 * @author Nurlan Dzhumakaliev <nurlanj@live.com>
 * @package ow_plugins.contactus.controllers
 * @since 1.0
 */
class CONTACTUS_CTRL_Contact extends OW_ActionController
{

    public function index()
    {
        $this->setPageTitle(OW::getLanguage()->text('contactus', 'index_page_title'));
        $this->setPageHeading(OW::getLanguage()->text('contactus', 'index_page_heading'));

        $contactEmails = array();
        $contacts = CONTACTUS_BOL_Service::getInstance()->getDepartmentList();
        foreach ( $contacts as $contact )
        {
            /* @var $contact CONTACTUS_BOL_Department */
            $contactEmails[$contact->id]['label'] = CONTACTUS_BOL_Service::getInstance()->getDepartmentLabel($contact->id);
            $contactEmails[$contact->id]['email'] = $contact->email;
        }

        $form = new Form('contact_form');

        $fieldTo = new Selectbox('to');
        foreach ( $contactEmails as $id => $value )
        {
            $fieldTo->addOption($id, $value['label']);
        }
        $fieldTo->setRequired();
        $fieldTo->setHasInvitation(false);
        $fieldTo->setLabel($this->text('contactus', 'form_label_to'));
        $form->addElement($fieldTo);

        $fieldFrom = new TextField('from');
        $fieldFrom->setLabel($this->text('contactus', 'form_label_from'));
        $fieldFrom->setRequired();
        $fieldFrom->addValidator(new EmailValidator());
        $form->addElement($fieldFrom);

        $fieldSubject = new TextField('subject');
        $fieldSubject->setLabel($this->text('contactus', 'form_label_subject'));
        $fieldSubject->setRequired();
        $form->addElement($fieldSubject);

        $fieldMessage = new Textarea('message');
        $fieldMessage->setLabel($this->text('contactus', 'form_label_message'));
        $fieldMessage->setRequired();
        $form->addElement($fieldMessage);

        $fieldCaptcha = new CaptchaField('captcha');
        $fieldCaptcha->setLabel($this->text('contactus', 'form_label_captcha'));
        $form->addElement($fieldCaptcha);

        $submit = new Submit('send');
        $submit->setValue($this->text('contactus', 'form_label_submit'));
        $form->addElement($submit);

        $this->addForm($form);

        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                $data = $form->getValues();

                if ( !array_key_exists($data['to'], $contactEmails) )
                {
                    OW::getFeedback()->error($this->text('contactus', 'no_department'));
                    return;
                }

                $mail = OW::getMailer()->createMail();
                $mail->addRecipientEmail($contactEmails[$data['to']]['email']);
                $mail->setSender($data['from']);
                $mail->setSenderSuffix(false);
                $mail->setSubject($data['subject']);
                $mail->setTextContent($data['message']);
                OW::getMailer()->addToQueue($mail);

                OW::getSession()->set('contactus.dept', $contactEmails[$data['to']]['label']);
                $this->redirectToAction('sent');
            }
        }
    }

    public function sent()
    {
        $dept = null;

        if ( OW::getSession()->isKeySet('contactus.dept') )
        {
            $dept = OW::getSession()->get('contactus.dept');
            OW::getSession()->delete('contactus.dept');
        }
        else
        {
            $this->redirectToAction('index');
        }

        $feedback = $this->text('contactus', 'message_sent', ( $dept === null ) ? null : array('dept' => $dept));
        $this->assign('feedback', $feedback);
    }

    private function text( $prefix, $key, array $vars = null )
    {
        return OW::getLanguage()->text($prefix, $key, $vars);
    }
}