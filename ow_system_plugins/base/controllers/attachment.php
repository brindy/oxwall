<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_core
 * @since 1.0
 */
class BASE_CTRL_Attachment extends OW_ActionController
{

    public function addPhoto( $params )
    {
        $service = BOL_AttachmentService::getInstance();
        $language = OW::getLanguage();
        $error = false;
        
        if ( !OW::getUser()->isAuthenticated() || empty($_FILES['attachment']) || !is_uploaded_file($_FILES['attachment']['tmp_name']) || empty($params['uid']) )
        {
            $error = $language->text('base', 'upload_file_fail');
        }
        else if ( $_FILES['attachment']['error'] != UPLOAD_ERR_OK )
        {
            switch ( $_FILES['attachment']['error'] )
            {
                case UPLOAD_ERR_INI_SIZE:
                    $error = $language->text('base', 'upload_file_max_upload_filesize_error');
                    break;

                case UPLOAD_ERR_PARTIAL:
                    $error = $language->text('base', 'upload_file_file_partially_uploaded_error');
                    break;

                case UPLOAD_ERR_NO_FILE:
                    $error = $language->text('base', 'upload_file_no_file_error');
                    break;

                case UPLOAD_ERR_NO_TMP_DIR:
                    $error = $language->text('base', 'upload_file_no_tmp_dir_error');
                    break;

                case UPLOAD_ERR_CANT_WRITE:
                    $error = $language->text('base', 'upload_file_cant_write_file_error');
                    break;

                case UPLOAD_ERR_EXTENSION:
                    $error = $language->text('base', 'upload_file_invalid_extention_error');
                    break;

                default:
                    $error = $language->text('base', 'upload_file_fail');
            }
        }
        
        if ( !in_array(UTIL_File::getExtension($_FILES['attachment']['name']), array('jpeg', 'jpg', 'png', 'gif')) )
        {
            $error = $language->text('base', 'upload_file_extension_is_not_allowed');
        }

        if ( (int) $_FILES['attachment']['size'] > (float) OW::getConfig()->getValue('base', 'tf_max_pic_size') * 1024 * 1024 )
        {
            $error = $language->text('base', 'upload_file_max_upload_filesize_error');
        }

        if ( $error )
        {
            exit("<script>parent.window.OW.error(" . json_encode($error) . "); parent.window.owattachments['".$params['uid']."'].init();</script>");
        }

        $fileId = uniqid('attchfi'. md5($params['uid']));
        
        $uploadPath = $service->getAttachmentsTempDir() . $fileId . '.' . UTIL_File::getExtension($_FILES['attachment']['name']);
        $uploadUrl = $service->getAttachmentsTempDirUrl() . $fileId . '.' . UTIL_File::getExtension($_FILES['attachment']['name']);
        
        if( move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadPath) )
        {
            @chmod($uploadPath, 0666);
            
            $oembedCmp = new BASE_CMP_OembedAttachment( array('type' => 'photo', 'url' => $uploadUrl, 'href' => $uploadUrl), true);
            
            $returnArray = array(
                'cmp' => $oembedCmp->render(),
                'url' => $uploadUrl,
                'type' => 'photo',
                'uid' => $params['uid'],
                'genId' => $fileId
            );
            
            exit("<script>parent.window.owattachments['".$params['uid']."'].hideLoader().addItem(".  json_encode($returnArray).");</script>");
        }
    }

    public function addVideo( $params )
    {
        $cmp = new BASE_CMP_OembedAttachment( array('type' => 'video', 'html' => $_POST['code']), true);
        exit(json_encode(array('cmp' => $cmp->render(), 'uid' => $params['uid'], 'genId' => uniqid('attchvi'.md5($params['uid'])), 'type' => 'video', 'code' => $_POST['code'])));
    }

    public function delete( $params )
    {   
        if( !empty($params['genId']) )
        {
            
        }
    }
}
