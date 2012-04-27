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
 * Forum edit post action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.controllers
 * @since 1.0
 */
class FORUM_CTRL_EditPost extends OW_ActionController
{

    public function index( array $params = null )
    {
        $forumService = FORUM_BOL_ForumService::getInstance();

        if ( !isset($params['id']) || !($postId = (int) $params['id']) )
        {
            throw new Redirect404Exception();
        }

        $postDto = $forumService->findPostById($postId);

        if ( !$postDto )
        {
            throw new Redirect404Exception();
        }

        $userId = OW::getUser()->getId();
        $topicId = $postDto->topicId;
        $topicDto = $forumService->findTopicById($topicId);

        $forumGroup = $forumService->getGroupInfo($topicDto->groupId);
        $forumSection = $forumService->findSectionById($forumGroup->sectionId);

        $isHidden = $forumSection->isHidden;

        if ( $isHidden )
        {
            $isModerator = OW::getUser()->isAuthorized($forumSection->entity);

            $event = new OW_Event('forum.check_permissions', array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId));
            OW::getEventManager()->trigger($event);
            $canPost = $event->getData();

            //check permissions
            $canEdit = OW::getUser()->isAuthorized($forumSection->entity, 'add_topic', $postDto->userId);
        }
        else
        {
            $isModerator = OW::getUser()->isAuthorized('forum');
            $canPost = true;
            $canEdit = $postDto->userId == OW::getUser()->getId();
        }

        if ( (!$canEdit || !$canPost) && !$isModerator )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }

        $this->assign('postId', $postId);

        $editPostForm = $this->generateEditPostForm($postDto);
        $this->addForm($editPostForm);

        $lang = OW::getLanguage();
        $router = OW::getRouter();

        if ( OW::getRequest()->isPost() && $editPostForm->isValid($_POST) )
        {
            $values = $editPostForm->getValues();
            
            $postId = (int) $values['post-id'];
            $text = $values['text'];
            $topicId = (int) $values['topic'];

            $topicUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId));

            $postDto = $forumService->findPostById($postId);

            if ( $postDto === null || ($postDto->userId != $userId && !$isModerator) )
            {
                $this->redirect($topicUrl);
            }

            //save post
            $postDto->text = $text;
            $forumService->saveOrUpdatePost($postDto);

            //save post edit info
            $editPostDto = $forumService->findEditPost($postId);

            if ( $editPostDto === null )
            {
                $editPostDto = new FORUM_BOL_EditPost();
            }

            $editPostDto->postId = $postId;
            $editPostDto->userId = $userId;
            $editPostDto->editStamp = time();

            $forumService->saveOrUpdateEditPost($editPostDto);

            if ( isset($_FILES['attachments']) && count($_FILES['attachments']) )
            {
                $filesArray = $_FILES['attachments'];
                $filesCount = count($filesArray['name']);
                $attachmentService = FORUM_BOL_PostAttachmentService::getInstance();
                $skipped = 0;

                $accepted = floatval(OW::getConfig()->getValue('forum', 'attachment_filesize') * 1024 * 1024);
                
                for ( $i = 0; $i < $filesCount; $i++ )
                {
                    if ( !strlen($filesArray['tmp_name'][$i]) )
                    {
                        continue;
                    }

                    // skip unsupported extensions
                    $ext = UTIL_File::getExtension($filesArray['name'][$i]);
                    if ( !$attachmentService->fileExtensionIsAllowed($ext) )
                    {
                        $skipped++;
                        continue;
                    }
                    
                    // skip too big files
                    if ( $filesArray['size'][$i] > $accepted )
                    {
                        $skipped++;
                        continue;
                    }

                    $attachmentDto = new FORUM_BOL_PostAttachment();
                    $attachmentDto->postId = $postDto->id;
                    $attachmentDto->fileName = htmlspecialchars($filesArray['name'][$i]);
                    $attachmentDto->fileSize = $filesArray['size'][$i];
                    $attachmentDto->hash = uniqid();

                    $added = $attachmentService->addAttachment($attachmentDto, $filesArray['tmp_name'][$i]);
                    
                    if ( !$added )
                    {
                        $skipped++;
                    }
                }
                
                if ( $skipped )
                {
                    OW::getFeedback()->warning($lang->text('forum', 'not_all_attachments_added'));
                    $this->redirect();
                }
            }

            $this->redirect($forumService->getPostUrl($topicId, $postId, true));
        }

        $enableAttachments = OW::getConfig()->getValue('forum', 'enable_attachments');
        $this->assign('enableAttachments', $enableAttachments);

        if ( $enableAttachments )
        {
            $attachments = FORUM_BOL_PostAttachmentService::getInstance()->findAttachmentsByPostIdList(array($postId));
            $this->assign('attachments', $attachments);
        }

        $topicInfo = $forumService->getTopicInfo($topicId);
        $groupUrl = $router->urlForRoute('group-default', array('groupId' => $topicDto->groupId));
        $topicUrl = $forumService->getPostUrl($topicId, $postId);

        $lang->addKeyForJs('forum', 'confirm_delete_attachment');

        OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'edit_post_title'));
        OW::getDocument()->setHeadingIconClass('ow_ic_edit');

        $this->assign('isHidden', $isHidden);

        if ( $isHidden )
        {
            $event = new OW_Event('forum.find_forum_caption', array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId));
            OW::getEventManager()->trigger($event);

            $eventData = $event->getData();
            $componentForumCaption = $eventData['component'];

            if (!empty($componentForumCaption))
            {
                $this->assign('componentForumCaption', $componentForumCaption->render());
            }
            else
            {
                $componentForumCaption = false;
                $this->assign('componentForumCaption', $componentForumCaption);
            }

            $bcItems = array(
                array(
                    'href' => OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId)),
                    'label' => OW::getLanguage()->text('forum', 'back_to_topic')
                )
            );

            $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems);
            $this->addComponent('breadcrumb', $breadCrumbCmp);

            OW::getNavigation()->deactivateMenuItems(OW_Navigation::MAIN);
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, $forumSection->entity, $eventData['key']);
        }
        else
        {
            $bcItems = array(
                array(
                    'href' => $router->urlForRoute('forum-default'),
                    'label' => $lang->text('forum', 'forum_index')
                ),
                array(
                    'href' => $router->urlForRoute('forum-default') . '#section-' . $topicInfo['sectionId'],
                    'label' => $topicInfo['sectionName']
                ),
                array(
                    'href' => $groupUrl,
                    'label' => $topicInfo['groupName']
                ),
                array(
                    'href' => $topicUrl,
                    'label' => htmlspecialchars($topicDto->title)
                )
            );


            $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems, $lang->text('forum', 'post_location'));
            $this->addComponent('breadcrumb', $breadCrumbCmp);

            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'forum', 'forum');
        }
    }

    /**
     * Generates edit post form.
     *
     * @param stirng $actionUrl
     * @param int $topicId
     * @return Form
     */
    private function generateEditPostForm( $postDto )
    {
        $form = new Form('edit-post-form');

        $form->setEnctype('multipart/form-data');

        $postIdField = new HiddenField('post-id');
        $postIdField->setValue($postDto->id);
        $form->addElement($postIdField);

        $topicIdField = new HiddenField('topic');
        $topicIdField->setValue($postDto->topicId);
        $form->addElement($topicIdField);

        $btnSet = array(BOL_TextFormatService::WS_BTN_IMAGE, BOL_TextFormatService::WS_BTN_VIDEO, BOL_TextFormatService::WS_BTN_HTML);
        $postText = new WysiwygTextarea('text', $btnSet);
        $postText->setRequired(true);
        $postText->setValue($postDto->text);
        $postText->addValidator(new StringValidator(1, 50000));
        $form->addElement($postText);

        $attachmentField = new MultiFileField('attachments', 5);
        $form->addElement($attachmentField);

        $submit = new Submit('save');
        $submit->setValue(OW::getLanguage()->text('base', 'edit_button'));
        $form->addElement($submit);

        return $form;
    }
}