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
 * Video add action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.video.controllers
 * @since 1.0
 */
class VIDEO_CTRL_Add extends OW_ActionController
{

    /**
     * Default action
     */
    public function index()
    {
        $language = OW::getLanguage();
        $clipService = VIDEO_BOL_ClipService::getInstance();
        $userId = OW::getUser()->getId();

        if ( !OW::getUser()->isAuthorized('video', 'add') )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }

        $eventParams = array('pluginKey' => 'video', 'action' => 'add_video');
        $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams);
        
        if ( $credits === false )
        {
            $this->assign('auth_msg', OW::getEventManager()->call('usercredits.error_message', $eventParams));
        }
        else if ( !($clipService->findUserClipsCount($userId) <= $clipService->getUserQuotaConfig()) )
        {
            $this->assign('auth_msg', $language->text('video', 'quota_exceeded', array('limit' => $clipService->getUserQuotaConfig())));
        }
        else
        {
            $this->assign('auth_msg', null);

            $videoAddForm = new videoAddForm();
            $this->addForm($videoAddForm);

            $userId = OW::getUser()->getId();

            if ( OW::getRequest()->isPost() && $videoAddForm->isValid($_POST) )
            {
                $values = $videoAddForm->getValues();
                $code = $clipService->validateClipCode($values['code']);
                
                if ( !BOL_TextFormatService::getInstance()->isCodeResourceValid($code) )
                {
                    OW::getFeedback()->warning($language->text('video', 'resource_not_allowed'));
                    $this->redirect();
                }
                
                $res = $videoAddForm->process();
                OW::getFeedback()->info($language->text('video', 'clip_added'));
                $this->redirect(OW::getRouter()->urlForRoute('view_clip', array('id' => $res['id'])));
            }
        }

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'video', 'video');
        }

        OW::getDocument()->setHeading($language->text('video', 'page_title_add_video'));
        OW::getDocument()->setHeadingIconClass('ow_ic_video');
        OW::getDocument()->setTitle($language->text('video', 'meta_title_video_add'));
    }
}

/**
 * Video add form class
 */
class videoAddForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        parent::__construct('videoAddForm');

        $language = OW::getLanguage();

        // title Field
        $titleField = new TextField('title');
        $titleField->setRequired(true);
        $this->addElement($titleField->setLabel($language->text('video', 'title')));

        // description Field
        $descField = new WysiwygTextarea('description');
        $this->addElement($descField->setLabel($language->text('video', 'description')));

        // code Field
        $codeField = new Textarea('code');
        $codeField->setRequired(true);
        $this->addElement($codeField->setLabel($language->text('video', 'code')));

        $tagsField = new TagsField('tags');
        $this->addElement($tagsField->setLabel($language->text('video', 'tags')));

        $submit = new Submit('add');
        $submit->setValue($language->text('video', 'btn_add'));
        $this->addElement($submit);
    }

    /**
     * Adds video clip
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();
        $clipService = VIDEO_BOL_ClipService::getInstance();

        $clip = new VIDEO_BOL_Clip();
        $clip->title = htmlspecialchars($values['title']);
        $description = UTIL_HtmlTag::stripJs($values['description']);
        $description = UTIL_HtmlTag::stripTags($description, array('frame', 'style'), array(), true);
        $clip->description = $description;
        $clip->userId = OW::getUser()->getId();

        $clip->code = UTIL_HtmlTag::stripJs($values['code']);

        $prov = new VideoProviders($clip->code);

        $privacy = OW::getEventManager()->call(
            'plugin.privacy.get_privacy', 
            array('ownerId' => $clip->userId, 'action' => 'video_view_video')
        );
                    
        $clip->provider = $prov->detectProvider();
        $clip->addDatetime = time();
        $clip->status = 'approved';
        $clip->privacy = mb_strlen($privacy) ? $privacy : 'everybody';

        $eventParams = array('pluginKey' => 'video', 'action' => 'add_video');

        if ( OW::getEventManager()->call('usercredits.check_balance', $eventParams) === true )
        {
            OW::getEventManager()->call('usercredits.track_action', $eventParams);
        }
        
        if ( $clipService->addClip($clip) )
        {
            BOL_TagService::getInstance()->updateEntityTags($clip->id, 'video', TagsField::getTags($values['tags']));
            
            // Newsfeed
            $event = new OW_Event('feed.action', array(
                'pluginKey' => 'video',
                'entityType' => 'video_comments',
                'entityId' => $clip->id,
                'userId' => $clip->userId
            ));
            
            OW::getEventManager()->trigger($event);

            return array('result' => true, 'id' => $clip->id);
        }

        return false;
    }
}