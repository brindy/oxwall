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
 * Forum add topic controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.controllers
 * @since 1.0
 */
class FORUM_CTRL_AddTopic extends OW_ActionController
{

    /**
     * Controller's default action
     * 
     * @param array $params
     */
    public function index( array $params = null )
    {
        $groupId = isset($params['groupId']) && (int) $params['groupId'] ? (int) $params['groupId'] : 0;

        $forumService = FORUM_BOL_ForumService::getInstance();

        $forumGroup = $forumService->getGroupInfo($groupId);
        if ( $forumGroup )
        {
            $forumSection = $forumService->findSectionById($forumGroup->sectionId);
            $isHidden = $forumSection->isHidden;
        }
        else 
        {
            $isHidden = false;
        }
        
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $userId = OW::getUser()->getId();

        $this->assign('authMsg', null);
        
        if ( $isHidden )
        {
            $isModerator = OW::getUser()->isAuthorized($forumSection->entity);
            $canEdit = OW::getUser()->isAuthorized($forumSection->entity, 'add_topic');

            $event = new OW_Event('forum.check_permissions', array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId));
            OW::getEventManager()->trigger($event);
            $canPost = $event->getData();

            $canEdit = ( $canEdit && $canPost || $isModerator) ? true : false;

            if ( !$canEdit )
            {
                $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
                return;
            }
            
            $eventParams = array('pluginKey' => 'groups', 'action' => 'add_post');
            $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams);
            
            if ( $credits === false )
            {
                $this->assign('authMsg', OW::getEventManager()->call('usercredits.error_message', $eventParams));
            }

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
                    'href' => OW::getRouter()->urlForRoute('group-default', array('groupId' => $forumGroup->getId())),
                    'label' => OW::getLanguage()->text($forumSection->entity, 'view_all_topics')
                )
            );

            $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems);
            $this->addComponent('breadcrumb', $breadCrumbCmp);

            OW::getNavigation()->deactivateMenuItems(OW_Navigation::MAIN);
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, $forumSection->entity, $eventData['key']);

            $groupSelect = array(array('label' => $forumGroup->name, 'value' => $forumGroup->getId(), 'disabled' => false));

            OW::getDocument()->setHeading(OW::getLanguage()->text($forumSection->entity, 'create_new_topic', array('group' => $forumGroup->name)));
        }
        else
        {
            $canEdit = OW::getUser()->isAuthorized('forum', 'edit');

            if ( !$userId || !$canEdit )
            {
                $this->assign('authMsg', OW::getLanguage()->text('base', 'authorization_failed_feedback'));
            }
            
            $eventParams = array('pluginKey' => 'forum', 'action' => 'add_post');
            $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams);
            
            if ( $credits === false )
            {
                $this->assign('authMsg', OW::getEventManager()->call('usercredits.error_message', $eventParams));
            }
            
            if ( !OW::getRequest()->isAjax() )
            {
                OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'forum', 'forum');
            }

            $groupSelect = $forumService->getGroupSelectList(0, false, $userId);

            OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'create_new_topic'));
        }

        OW::getDocument()->setHeadingIconClass('ow_ic_write');

        $this->assign('isHidden', $isHidden);

        $form = $this->generateForm($groupSelect, $groupId, $isHidden);

        OW::getDocument()->addStyleDeclaration('
			.disabled_option {
				color: #9F9F9F;
    		}
		');

        $enableAttachments = OW::getConfig()->getValue('forum', 'enable_attachments');
        $this->assign('enableAttachments', $enableAttachments);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            if ( $data['group'] )
            {
                $topicDto = new FORUM_BOL_Topic();

                $topicDto->userId = $userId;
                $topicDto->groupId = $data['group'];
                $topicDto->title = strip_tags($data['title']);

                $forumService->saveOrUpdateTopic($topicDto);

                $postDto = new FORUM_BOL_Post();

                $postDto->topicId = $topicDto->id;
                $postDto->userId = $userId;
                $postDto->text = $data['text'];
                $postDto->createStamp = time();

                $forumService->saveOrUpdatePost($postDto);

                $topicDto->lastPostId = $postDto->getId();

                $forumService->saveOrUpdateTopic($topicDto);

                $accepted = floatval(OW::getConfig()->getValue('forum', 'attachment_filesize') * 1024 * 1024);
                
                if ( isset($data['attachments']) && count($data['attachments']) )
                {
                    $filesArray = $data['attachments'];
                    $filesCount = count($filesArray['name']);
                    $attachmentService = FORUM_BOL_PostAttachmentService::getInstance();
                    $skipped = 0;

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
                        OW::getFeedback()->warning(OW::getLanguage()->text('forum', 'not_all_attachments_added'));
                    }
                }

                $topicUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicDto->id));

                //Newsfeed
                $params = array(
                    'pluginKey' => 'forum',
                    'entityType' => 'forum-topic',
                    'entityId' => $topicDto->id,
                    'userId' => $topicDto->userId
                );

                $event = new OW_Event('feed.action', $params);
                OW::getEventManager()->trigger($event);

                if ( $credits === true )
                {
                    OW::getEventManager()->call('usercredits.track_action', $eventParams);
                }
                
                if ( $isHidden )
                {
                    $params = array(
                        'topicId' => $topicDto->id, 
                        'entity' => $forumSection->entity, 
                        'entityId' => $forumGroup->entityId,
                        'userId' => $topicDto->userId,
                        'topicUrl' => $topicUrl,
                        'topicTitle' => $topicDto->title,
                        'postText' => $postDto->text
                    );
                    $event = new OW_Event('forum.topic_add', $params);
                    OW::getEventManager()->trigger($event);
                }
            
                $this->redirect($topicUrl);
            }
            else
            {
                $form->getElement('group')->addError(OW::getLanguage()->text('forum', 'select_group_error'));
            }
        }
    }

    /**
     * Generates Add Topic Form.
     *
     * @param array $groupSelect
     * @param int $groupId
     * @return Form
     */
    private function generateForm( $groupSelect, $groupId, $isHidden )
    {
        $form = new Form('add-topic-form');

        $form->setEnctype("multipart/form-data");

        $title = new TextField('title');
        $title->setRequired(true);
        $title->addValidator(new StringValidator(1, 255));
        $form->addElement($title);

        if ( $isHidden )
        {
            $group = new HiddenField('group');
            $group->setValue($groupId);
        }
        else
        {
            $group = new ForumSelectBox('group');
            $group->setOptions($groupSelect);
            if ( $groupId) 
            {
                $group->setValue($groupId);
            }
            $group->setRequired(true);
            $group->addValidator(new IntValidator());
        }

        $form->addElement($group);

        $btnSet = array(BOL_TextFormatService::WS_BTN_IMAGE, BOL_TextFormatService::WS_BTN_VIDEO, BOL_TextFormatService::WS_BTN_HTML);
        $text = new WysiwygTextarea('text', $btnSet);
        $text->setRequired(true);
        $text->addValidator(new StringValidator(1, 50000));
        $form->addElement($text);

        $post = new Submit('post');
        $post->setValue(OW::getLanguage()->text('forum', 'add_post_btn'));
        $form->addElement($post);

        $attachmentField = new MultiFileField('attachments', 5);
        $form->addElement($attachmentField);

        $this->addForm($form);

        return $form;
    }
}