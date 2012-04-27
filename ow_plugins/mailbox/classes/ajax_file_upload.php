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
 * Ajax File Upload Class
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.classes
 * @since 1.0
 */

class MAILBOX_CLASS_AjaxFileUpload extends FormElement
{
    /**
     * Constructor.
     *
     * @param string $name
     */
    public function __construct( $name )
    {
        parent::__construct($name);

        $this->addAttribute('type', 'file');
    }

    /**
     * @see FormElement::renderInput()
     *
     * @param array $params
     * @return string
     */
    public function renderInput( $params = null )
    {
        parent::renderInput($params);

        $fileElementId = $this->getId() . '_file';

        $entityId = $this->getValue();

        if ( empty($entityId) )
        {
            $entityId = uniqid('upload');
        }

        $iframeUrl = OW::getRouter()->urlFor('MAILBOX_CTRL_Mailbox', 'fileUpload', array('entityId' => $entityId, 'formElementId' => $fileElementId));

        $attachFileHtml = '<div id="file_attachment" class="ow_mailbox_attachment">
                               <span class="ow_mailbox_attachment_icon ow_ic_attach ">&nbsp;</span>
                               <a class="file" href="javascript://"></a> (<span class="filesize"></span>)
                               <a rel="40" class="ow_delete_attachment ow_lbutton ow_hidden" href="javascript://" style="display: none;">' . OW::getLanguage()->text('mailbox', 'attache_file_delete_button') . '</a>
                           </div>';

        $fileList = array();

        if ( !empty($entityId) )
        {
            $fileService = MAILBOX_BOL_FileUploadService::getInstance();
            $uploadFileDtoList = $fileService->findUploadFileList( $entityId );

            foreach( $uploadFileDtoList as $uploadFileDto )
            {
                $file = array();
                $file['hash'] = $uploadFileDto->hash;
                $file['filesize'] = round( $uploadFileDto->fileSize / 1024, 2 ) . 'Kb';
                $file['filename'] = $uploadFileDto->fileName;
                $file['fileUrl'] = $fileService->getUploadFileUrl($uploadFileDto->hash, UTIL_File::getExtension($uploadFileDto->fileName));
                $fileList[] = $file;
            }
        }

        $params = array(
            'elementId' => $fileElementId,
            'ajaxResponderUrl' => OW::getRouter()->urlFor("MAILBOX_CTRL_Mailbox", "responder"),
            'fileResponderUrl' => $iframeUrl,
            'attachFileHtml' => $attachFileHtml,
            'fileList' => $fileList
            );

        $script = "  window.fileUpload_" . $this->getId() . " = new fileUpload(" . json_encode($params) . ");
                        window.fileUpload_" . $this->getId() . ".init();";

        OW::getDocument()->addOnloadScript($script);
        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin("mailbox")->getStaticJsUrl() . 'ajax_file_upload.js' );

        $hiddenAttr = array(
            'id' => $this->getId(),
            'type' => 'hidden',
            'name' => $this->getName(),
            'value' => $entityId );

        $fileAttr = $this->attributes;
        unset($fileAttr['name']);
        $fileAttr['id'] = $fileElementId;

        return UTIL_HtmlTag::generateTag('input', $hiddenAttr)
                . '<span class="'. $fileElementId .'_class">' . UTIL_HtmlTag::generateTag('input', $fileAttr) . '</span>
                <div id="'. $fileElementId .'_list" class="ow_small ow_smallmargin">
                    <div class="ow_attachments_label mailbox_attachments_label ow_hidden">' . OW::getLanguage()->text('mailbox', 'attachments') . ' :</div>
                </div>';
    }

    public function getElementJs()
    {
        $jsString = "var formElement = new OwFormElement('" . $this->getId() . "', '" . $this->getName() . "');";

        $jsString .= "formElement.resetValue = function(){
		        window.fileUpload_" . $this->getId() . ".reset();
		    };";

        /** @var $value Validator  */
        foreach ( $this->validators as $value )
        {
            $jsString .= "formElement.addValidator(" . $value->getJsValidator() . ");";
        }
        
        return $jsString;
    }
}