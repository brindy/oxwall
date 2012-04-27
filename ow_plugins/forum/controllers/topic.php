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
 * Forum topic action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.controllers
 * @since 1.0
 */
class FORUM_CTRL_Topic extends OW_ActionController
{
    private $forumService;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->forumService = FORUM_BOL_ForumService::getInstance();

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'forum', 'forum');
        }
    }

    /**
     * Controller's default action
     *
     * @param array $params
     */
    public function index( array $params )
    {
        if ( !isset($params['topicId']) || !($topicId = (int) $params['topicId']) )
        {
            throw new Redirect404Exception();
        }

        $topicDto = $this->forumService->findTopicById($topicId);

        if ( !$topicDto )
        {
            throw new Redirect404Exception();
        }

        $forumGroup = $this->forumService->findGroupById($topicDto->groupId);
        $forumSection = $this->forumService->findSectionById($forumGroup->sectionId);

        $isHidden = $forumSection->isHidden;

        $userId = OW::getUser()->getId();
        $isOwner = ( $topicDto->userId == $userId ) ? true : false;

        if ( $isHidden )
        {
            $isModerator = OW::getUser()->isAuthorized($forumSection->entity);

            //check permissions
            //$canView = OW::getUser()->isAuthorized($forumSection->entity, 'view');
            $event = new OW_Event('forum.can_view', array(
                'entity' => $forumSection->entity,
                'entityId' => $forumGroup->entityId
            ), true);
            OW::getEventManager()->trigger($event);

            $canView = $event->getData();

            $canEdit = $isOwner || $isModerator;
            $canPost = OW::getUser()->isAuthorized($forumSection->entity, 'add_topic');
            $canMoveToHidden = BOL_AuthorizationService::getInstance()->isActionAuthorized($forumSection->entity, 'move_topic_to_hidden') && $isModerator;

            $eventParams = array('pluginKey' => $forumSection->entity, 'action' => 'add_post');
        }
        else
        {
            $isModerator = OW::getUser()->isAuthorized('forum');

            //check permissions
            $canView = OW::getUser()->isAuthorized('forum', 'view');
            $canEdit = $isOwner || $isModerator;
            $canPost = OW::getUser()->isAuthorized('forum', 'edit');
            $canMoveToHidden = BOL_AuthorizationService::getInstance()->isActionAuthorized('forum', 'move_topic_to_hidden') && $isModerator;

            $eventParams = array('pluginKey' => 'forum', 'action' => 'add_post');
        }

        if ( !$canView && !$isModerator )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
            return;
        }

        if ( $forumGroup->isPrivate )
        {
            if ( !$userId )
            {
                $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
                return;
            }
            else if ( !$isModerator )
            {
                if ( !$this->forumService->isPrivateGroupAvailable($userId, json_decode($forumGroup->roles)) )
                {
                    $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');
                    return;
                }
            }
        }

        $page = !empty($_GET['page']) && (int) $_GET['page'] ? abs((int) $_GET['page']) : 1;

        $addPostForm = $this->generateAddPostForm($topicId);
        $this->addForm($addPostForm);

        $addPostInputId = $addPostForm->getElement('text')->getId();

        //update topic's view count
        $topicDto->viewCount += 1;
        $this->forumService->saveOrUpdateTopic($topicDto);

        //update user read info
        $this->forumService->setTopicRead($topicId, $userId);

        $topicInfo = $this->forumService->getTopicInfo($topicId);
        $postList = $this->forumService->getTopicPostList($topicId, $page);
        $this->assign('isHidden', $isHidden);

        // adds forum caption if any
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

            $event = new OW_Event('forum.check_permissions', array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId));
            OW::getEventManager()->trigger($event);
            $canPost = $canPost && $event->getData();
        }

        $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams);

        if ( $canPost && $credits === false )
        {
            $canPost = false;
        }

        $this->assign('isOwner', $isOwner);
        $this->assign('canPost', $canPost);
        $this->assign('canSubscribe', OW::getUser()->isAuthorized('forum', 'subscribe'));
        $this->assign('isSubscribed', $userId && FORUM_BOL_SubscriptionService::getInstance()->isUserSubscribed($userId, $topicId));

        if ( !$postList )
        {
            throw new Redirect404Exception();
        }

        $toolbars = array();
        $lang = OW::getLanguage();

        $langQuote = $lang->text('forum', 'quote');
        $langFlag = $lang->text('base', 'flag');
        $langEdit = $lang->text('forum', 'edit');
        $langDelete = $lang->text('forum', 'delete');

        $iteration = 0;
        $userIds = array();
        $editorIds = array();
        $postIds = array();
        $flagItems = array();

        foreach ( $postList as &$post )
        {
            $post['text'] = UTIL_HtmlTag::autoLink($post['text']);
            $post['permalink'] = $this->forumService->getPostUrl($post['topicId'], $post['id'], true, $page);
            $post['number'] = ($page - 1) * $this->forumService->getPostPerPageConfig() + $iteration + 1;

            if ( $iteration == 0 )
            {
                $firstPostText = htmlspecialchars(strip_tags($post['text']));
            }

            // get list of users
            if ( !in_array($post['userId'], $userIds) )
                $userIds[$post['userId']] = $post['userId'];

            $toolbar = array();

            if ( $userId )
            {
                if ( !$topicDto->locked && ($canEdit || $canPost) )
                {
                    array_push($toolbar, array('id' => $post['id'], 'class' => 'quote_post', 'href' => 'javascript://', 'label' => $langQuote));
                }

                $lagItemKey = 'flag_' . $post['id'];
                $flagItems[$lagItemKey] = array(
                    'id' => $post['id'],
                    'title' => $post['text'],
                    'href' => $this->forumService->getPostUrl($post['topicId'], $post['id'])
                );

                array_push($toolbar, array('label' => $langFlag, 'href' => 'javascript://', 'id' => $lagItemKey, 'class' => 'post_flag_item'));
            }

            if ( $isModerator || ($userId == (int) $post['userId'] && !$topicDto->locked) )
            {
                $href = $iteration == 0 && $page == 1 ?
                    OW::getRouter()->urlForRoute('edit-topic', array('id' => $post['topicId'])) :
                    OW::getRouter()->urlForRoute('edit-post', array('id' => $post['id']));

                array_push($toolbar, array('id' => $post['id'], 'href' => $href, 'label' => $langEdit));

                if ( !($iteration == 0 && $page == 1) )
                {
                    array_push($toolbar, array('id' => $post['id'], 'class' => 'delete_post', 'href' => 'javascript://', 'label' => $langDelete));
                }
            }

            $toolbars[$post['id']] = $toolbar;

            if ( count($post['edited']) && !in_array($post['edited']['userId'], $userIds) )
                $userIds[$post['edited']['userId']] = $post['edited']['userId'];

            $iteration++;

            array_push($postIds, $post['id']);
        }

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'jquery-fieldselection.js');

        $js = UTIL_JsGenerator::newInstance()
                ->newVariable('flagItems', $flagItems)
                ->jQueryEvent('.post_flag_item a', 'click', 'var inf = flagItems[this.id]; document.flag("forum_post", inf.id, inf.title, inf.href, "forum+flags");');

        OW::getDocument()->addOnloadScript($js, 1001);

        $this->assign('toolbars', $toolbars);

        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($userIds);
        $this->assign('avatars', $avatars);

        $enableAttachments = OW::getConfig()->getValue('forum', 'enable_attachments');
        $this->assign('enableAttachments', $enableAttachments);

        if ( $enableAttachments )
        {
            $attachments = FORUM_BOL_PostAttachmentService::getInstance()->findAttachmentsByPostIdList($postIds);
            $this->assign('attachments', $attachments);
        }

        $plugin = OW::getPluginManager()->getPlugin('forum');

        $indexUrl = OW::getRouter()->urlForRoute('forum-default');
        $groupUrl = OW::getRouter()->urlForRoute('group-default', array('groupId' => $topicDto->groupId));
        $deletePostUrl = OW::getRouter()->urlForRoute('delete-post', array('topicId' => $topicId, 'postId' => 'postId'));
        $stickyTopicUrl = OW::getRouter()->urlForRoute('sticky-topic', array('topicId' => $topicId, 'page' => $page));
        $lockTopicUrl = OW::getRouter()->urlForRoute('lock-topic', array('topicId' => $topicId, 'page' => $page));
        $deleteTopicUrl = OW::getRouter()->urlForRoute('delete-topic', array('topicId' => $topicId));
        $getPostUrl = OW::getRouter()->urlForRoute('get-post', array('postId' => 'postId'));
        $moveTopicUrl = OW::getRouter()->urlForRoute('move-topic');
        $subscribeTopicUrl = OW::getRouter()->urlForRoute('subscribe-topic', array('id' => $topicId));
        $unsubscribeTopicUrl = OW::getRouter()->urlForRoute('unsubscribe-topic', array('id' => $topicId));


        $topicInfoJs = json_encode(array('sticky' => $topicDto->sticky, 'locked' => $topicDto->locked, 'ishidden' => $isHidden && !$canMoveToHidden ));

        $onloadJs = "
			ForumTopic.deletePostUrl = '$deletePostUrl';
			ForumTopic.stickyTopicUrl = '$stickyTopicUrl';
			ForumTopic.lockTopicUrl = '$lockTopicUrl';
			ForumTopic.subscribeTopicUrl = '$subscribeTopicUrl';
			ForumTopic.unsubscribeTopicUrl = '$unsubscribeTopicUrl';
			ForumTopic.deleteTopicUrl = '$deleteTopicUrl';
			ForumTopic.getPostUrl = '$getPostUrl';
			ForumTopic.add_post_input_id = '$addPostInputId';
			ForumTopic.construct($topicInfoJs);
			";

        OW::getDocument()->addOnloadScript($onloadJs);

        OW::getDocument()->addScript($plugin->getStaticJsUrl() . "forum.js");

        // add language keys for javascript
        $lang->addKeyForJs('forum', 'sticky_topic_confirm');
        $lang->addKeyForJs('forum', 'unsticky_topic_confirm');
        $lang->addKeyForJs('forum', 'lock_topic_confirm');
        $lang->addKeyForJs('forum', 'unlock_topic_confirm');
        $lang->addKeyForJs('forum', 'delete_topic_confirm');
        $lang->addKeyForJs('forum', 'delete_post_confirm');
        $lang->addKeyForJs('forum', 'edit_topic_title');
        $lang->addKeyForJs('forum', 'edit_post_title');
        $lang->addKeyForJs('forum', 'move_topic_title');
        $lang->addKeyForJs('forum', 'confirm_delete_attachment');
        $lang->addKeyForJs('forum', 'forum_quote');
        $lang->addKeyForJs('forum', 'forum_quote_from');

        // first topic's post
        $postDto = $this->forumService->findTopicFirstPost($topicId);

        //posts count on page
        $count = $this->forumService->getPostPerPageConfig();

        $postCount = $this->forumService->findTopicPostCount($topicId);
        $pageCount = ceil($postCount / $count);

        $groupSelect = $this->forumService->getGroupSelectList($topicDto->groupId, $canMoveToHidden, $userId);
        $moveTopicForm = $this->generateMoveTopicForm($moveTopicUrl, $groupSelect, $topicDto);
        $this->addForm($moveTopicForm);

        $Paging = new BASE_CMP_Paging($page, $pageCount, $count);

        $this->assign('paging', $Paging->render());

        if ( $isHidden )
        {
            OW::getNavigation()->deactivateMenuItems(OW_Navigation::MAIN);
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, $forumSection->entity, $eventData['key']);

            OW::getDocument()->setHeading(OW::getLanguage()->text($forumSection->entity, 'topic_page_heading', array('topic' => $topicInfo['title'], 'group' => $topicInfo['groupName'])));

            $bcItems = array(
                array(
                    'href' => OW::getRouter()->urlForRoute('group-default', array('groupId' => $forumGroup->getId())),
                    'label' => OW::getLanguage()->text($forumSection->entity, 'view_all_topics')
                )
            );

            $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems);
            $this->addComponent('breadcrumb', $breadCrumbCmp);
        }
        else
        {
            $bcItems = array(
                array(
                    'href' => $indexUrl,
                    'label' => $lang->text('forum', 'forum_index')
                ),
                array(
                    'href' => OW::getRouter()->urlForRoute('forum-default') . '#section-' . $topicInfo['sectionId'],
                    'label' => $topicInfo['sectionName']
                ),
                array(
                    'href' => $groupUrl,
                    'label' => $topicInfo['groupName']
                )
            );

            $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems, $lang->text('forum', 'topic_location'));
            $this->addComponent('breadcrumb', $breadCrumbCmp);

            OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'topic_page_heading', array('topic' => $topicInfo['title'])));
        }

        OW::getDocument()->setHeadingIconClass('ow_ic_script');

        $this->assign('indexUrl', $indexUrl);
        $this->assign('groupUrl', $groupUrl);

        $this->assign('topicInfo', $topicInfo);
        $this->assign('postList', $postList);
        $this->assign('page', $page);

        $this->assign('userId', $userId);
        $this->assign('isModerator', $isModerator);
        $this->assign('canEdit', $canEdit);
        $this->assign('canMoveToHidden', $canMoveToHidden);

        OW::getDocument()->setTitle($topicInfo['title']);
        OW::getDocument()->setDescription($firstPostText);

        $this->addComponent('search', new FORUM_CMP_ForumSearch(array('scope' => 'topic', 'topicId' => $topicId)));
    }

    public function ajaxDeleteAttachment()
    {
        if ( !isset($_POST['attachmentId']) )
        {
            $result = array('result' => false);
        }
        else
        {
            $attachmentService = FORUM_BOL_PostAttachmentService::getInstance();
            $forumService = FORUM_BOL_ForumService::getInstance();
            $lang = OW::getLanguage();

            $attachment = $attachmentService->findPostAttachmentById((int) $_POST['attachmentId']);

            if ( $attachment )
            {
                $userId = OW::getUser()->getId();
                $isModerator = OW::getUser()->isAuthorized('forum');

                $post = $forumService->findPostById($attachment->postId);

                if ( $post )
                {
                    if ( $isModerator || $post->userId == $userId )
                    {
                        $attachmentService->deleteAttachment($attachment->id);

                        $result = array('result' => true, 'msg' => $lang->text('forum', 'attachment_deleted'));
                    }
                }
            }
            else
            {
                $result = array('result' => false);
            }
        }

        exit(json_encode($result));
    }

    /**
     * This action adds a post and after execution redirects to default action
     *
     * @param array $params
     */
    public function addPost( array $params )
    {
        if ( !isset($params['topicId']) || !($topicId = (int) $params['topicId']) )
        {
            throw new Redirect404Exception();
        }

        $topicDto = $this->forumService->findTopicById($topicId);

        if ( !$topicDto )
        {
            throw new Redirect404Exception();
        }

        $addPostForm = $this->generateAddPostForm($topicId);

        if ( OW::getRequest()->isPost() && $addPostForm->isValid($_POST) )
        {
            $data = $addPostForm->getValues();

            if ( $data['topic'] && $data['topic'] == $topicDto->id && !$topicDto->locked )
            {
                if ( !OW::getUser()->getId() )
                {
                    throw new AuthenticateException();
                }

                $postDto = new FORUM_BOL_Post();
                $postDto->topicId = $data['topic'];
                $postDto->userId = OW::getUser()->getId();

                $postDto->text = $data['text'];

                $postDto->createStamp = time();
                $this->forumService->saveOrUpdatePost($postDto);

                $topicDto->lastPostId = $postDto->getId();
                $this->forumService->saveOrUpdateTopic($topicDto);

                $this->forumService->deleteByTopicId($topicId);

                if ( isset($data['attachments']) && count($data['attachments']) )
                {
                    $filesArray = $data['attachments'];
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
                        OW::getFeedback()->warning(OW::getLanguage()->text('forum', 'not_all_attachments_added'));
                    }
                }

                $postUrl = $this->forumService->getPostUrl($topicId, $postDto->id);

                $event = new OW_Event('forum.add_post', array('postId' => $postDto->id, 'topicId' => $topicId, 'userId' => $postDto->userId));
                OW::getEventManager()->trigger($event);

                $forumGroup = $this->forumService->findGroupById($topicDto->groupId);
                if ( $forumGroup )
                {
                    $forumSection = $this->forumService->findSectionById($forumGroup->sectionId);
                    if ( $forumSection )
                    {
                        $pluginKey = $forumSection->isHidden ? $forumSection->entity : 'forum';
                        $eventParams = array('pluginKey' => $pluginKey, 'action' => 'add_post');

                        if ( $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams) === true )
                        {
                            OW::getEventManager()->call('usercredits.track_action', $eventParams);
                        }
                    }
                }

                $this->redirect($postUrl);
            }
        }
        else
        {
            $this->redirect(OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId)));
        }
    }

    /**
     * This action deletes thread post
     * and after execution redirects to default action
     *
     * @param array $params
     */
    public function deletePost( array $params )
    {
        if ( !isset($params['topicId']) || !($topicId = (int) $params['topicId'])
            || !isset($params['postId']) || !($postId = (int) $params['postId']) )
        {
            throw new Redirect404Exception();
        }

        $topicDto = $this->forumService->findTopicById($topicId);
        $postDto = $this->forumService->findPostById($postId);

        $userId = OW::getUser()->getId();
        $isModerator = OW::getUser()->isAuthorized('forum');

        if ( $topicDto && $postDto && ($postDto->userId == $userId || $isModerator) )
        {
            $prevPostDto = $this->forumService->findPreviousPost($topicId, $postId);

            $topicDto->lastPostId = $prevPostDto->id;
            $this->forumService->saveOrUpdateTopic($topicDto);

            $this->forumService->deletePost($postId);
            $postUrl = $this->forumService->getPostUrl($topicId, $prevPostDto->id, false);
        }
        else
        {
            $postUrl = $this->forumService->getPostUrl($topicId, $postId, false);
        }

        $this->redirect($postUrl);
    }

    /**
     * This action sets the topic sticky or unsticky
     * and after execution redirects to default action
     *
     * @param array $params
     */
    public function stickyTopic( array $params )
    {
        if ( !isset($params['topicId']) || !($topicId = (int) $params['topicId'])
            || !isset($params['page']) || !($page = (int) $params['page']) )
        {
            throw new Redirect404Exception();
        }
        $isModerator = OW::getUser()->isAuthorized('forum');

        $topicDto = $this->forumService->findTopicById($topicId);

        if ( $topicDto && $isModerator )
        {
            $topicDto->sticky = ($topicDto->sticky) ? 0 : 1;
            $this->forumService->saveOrUpdateTopic($topicDto);
        }

        $topicUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId));

        $this->redirect($topicUrl . "?page=$page");
    }

    /**
     * This action locks or unlocks the topic
     * and after execution redirects to default action
     *
     * @param array $params
     */
    public function lockTopic( array $params )
    {
        if ( !isset($params['topicId']) || !($topicId = (int) $params['topicId'])
            || !isset($params['page']) || !($page = (int) $params['page']) )
        {
            throw new Redirect404Exception();
        }

        $isModerator = OW::getUser()->isAuthorized('forum');

        $topicDto = $this->forumService->findTopicById($topicId);

        if ( $topicDto && $isModerator )
        {
            $topicDto->locked = ($topicDto->locked) ? 0 : 1;
            $this->forumService->saveOrUpdateTopic($topicDto);
        }

        $topicUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId));

        $this->redirect($topicUrl . "?page=$page");
    }

    /**
     * This action deletes the topic
     * and after execution redirects to default action
     *
     * @param array $params
     */
    public function deleteTopic( array $params )
    {
        if ( !isset($params['topicId']) || !($topicId = (int) $params['topicId']) )
        {
            throw new Redirect404Exception();
        }

        $isModerator = OW::getUser()->isAuthorized('forum');

        $topicDto = $this->forumService->findTopicById($topicId);
        $userId = OW::getUser()->getId();

        if ( $topicDto && ($isModerator || $userId == $topicDto->userId) )
        {
            $groupId = $topicDto->groupId;
            $this->forumService->deleteTopic($topicId);

            $redirectUrl = OW::getRouter()->urlForRoute('group-default', array('groupId' => $groupId));
        }
        else
        {
            $redirectUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId));
        }

        $this->redirect($redirectUrl);
    }

    /**
     * This action gets the post called by ajax request
     *
     * @param int $postId
     */
    public function getPost( array $params )
    {
        if ( isset($params['postId']) && $postId = (int) $params['postId'] )
        {
            if ( OW::getRequest()->isAjax() )
            {
                $postDto = $this->forumService->findPostById($postId);

                $post = array(
                    'from' => BOL_UserService::getInstance()->getDisplayName($postDto->userId),
                    'text' => $postDto->text
                );

                echo json_encode($post);
            }
            else
            {
                throw new Redirect404Exception();
            }
        }

        exit();
    }

    public function subscribeTopic( $params )
    {
        if ( !empty($params['id']) )
        {
            $subscribeService = FORUM_BOL_SubscriptionService::getInstance();
            $userId = OW::getUser()->getId();
            $topicId = (int)$params['id'];

            if ( OW::getUser()->isAuthorized('forum', 'subscribe') && !$subscribeService->isUserSubscribed($userId, $topicId) )
            {
                $subscription = new FORUM_BOL_Subscription;
                $subscription->userId = $userId;
                $subscription->topicId = $topicId;

                $subscribeService->addSubscription($subscription);

                echo json_encode(array('msg' => OW::getLanguage()->text('forum', 'subscription-added')));
            }
        }
        
        exit();
    }

    public function unsubscribeTopic( $params )
    {
        if ( !empty($params['id']) )
        {
            $subscribeService = FORUM_BOL_SubscriptionService::getInstance();
            $userId = OW::getUser()->getId();
            $topicId = (int)$params['id'];

            if ( $subscribeService->isUserSubscribed($userId, $topicId) )
            {
                $subscribeService->deleteSubscription($userId, $topicId);

                echo json_encode(array('msg' => OW::getLanguage()->text('forum', 'subscription-canceled')));
            }
        }
        
        exit();
    }

    /**
     * This action moves the topic called by ajax request
     */
    public function moveTopic()
    {
        $userId = OW::getUser()->getId();
        $isModerator = OW::getUser()->isAuthorized('forum');

        if ( OW::getRequest()->isAjax() && $_POST['topic-id'] )
        {
            $topicId = (int) $_POST['topic-id'];
            $groupId = (int) $_POST['group-id'];

            $groupDto = $this->forumService->findGroupById($groupId);
            $topicDto = $this->forumService->findTopicById($topicId);

            if ( $groupDto === null || $topicDto === null || !$isModerator )
            {
                exit();
            }

            //create replace topic
            $replaceTopicDto = new FORUM_BOL_Topic();

            $replaceTopicDto->groupId = $topicDto->groupId;
            $replaceTopicDto->userId = $userId;
            $replaceTopicDto->title = $topicDto->title;
            $replaceTopicDto->locked = 1;

            $this->forumService->saveOrUpdateTopic($replaceTopicDto);

            $oldGroupDto = $this->forumService->findGroupById($topicDto->groupId);

            $topicUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicDto->id));
            $oldGroupUrl = OW::getRouter()->urlForRoute('group-default', array('groupId' => $topicDto->groupId));

            //create replace topic's post
            $replacePostDto = new FORUM_BOL_Post();

            $replacePostDto->topicId = $replaceTopicDto->id;
            $replacePostDto->userId = $userId;
            $replacePostDto->createStamp = time();
            $replacePostDto->text = OW::getLanguage()->text('forum', 'moved_to', array('topicUrl' => $topicUrl));

            $this->forumService->saveOrUpdatePost($replacePostDto);

            $replaceTopicDto->lastPostId = $replacePostDto->id;
            $this->forumService->saveOrUpdateTopic($replaceTopicDto);

            //create notification post
            $postDto = new FORUM_BOL_Post();

            $postDto->topicId = $topicDto->id;
            $postDto->userId = $userId;
            $postDto->createStamp = time();
            $postDto->text = OW::getLanguage()->text('forum', 'moved_from', array('groupUrl' => $oldGroupUrl, 'groupName' => $oldGroupDto->name));

            $this->forumService->saveOrUpdatePost($postDto);

            $topicDto->groupId = $groupDto->id;
            $topicDto->lastPostId = $postDto->id;

            $this->forumService->saveOrUpdateTopic($topicDto);

            echo json_encode($this->forumService->getPostUrl($topicDto->id, $postDto->id, false));
        }
        else
        {
            throw new Redirect404Exception();
        }

        exit();
    }

    /**
     * Generates add post form.
     *
     * @param int $topicId
     * @return Form
     */
    private function generateAddPostForm( $topicId )
    {
        $form = new Form('add-post-form');

        $form->setEnctype('multipart/form-data');

        $addPostUrl = OW::getRouter()->urlForRoute('add-post', array('topicId' => $topicId));
        $form->setAction($addPostUrl);

        $topicIdField = new HiddenField('topic');
        $topicIdField->setValue($topicId);
        $form->addElement($topicIdField);

        $btnSet = array(BOL_TextFormatService::WS_BTN_IMAGE, BOL_TextFormatService::WS_BTN_VIDEO, BOL_TextFormatService::WS_BTN_HTML);
        $postText = new WysiwygTextarea('text', $btnSet);
        $postText->setRequired(true);
        $postText->addValidator(new StringValidator(1, 50000));
        $form->addElement($postText);

        $submit = new Submit('submit');
        $submit->setValue(OW::getLanguage()->text('forum', 'add_post_btn'));
        $form->addElement($submit);

        $attachmentField = new MultiFileField('attachments', 5);
        $form->addElement($attachmentField);

        return $form;
    }

    /**
     * Generates move topic form.
     *
     * @param stirng $actionUrl
     * @param FORUM_BOL_Topic
     * @return Form
     */
    private function generateMoveTopicForm( $actionUrl, $groupSelect, $topicDto )
    {
        $form = new Form('move-topic-form');

        $form->setAction($actionUrl);

        $topicIdField = new HiddenField('topic-id');
        $topicIdField->setValue($topicDto->id);
        $form->addElement($topicIdField);

        $group = new ForumSelectBox('group-id');
        $group->setOptions($groupSelect);
        $group->setValue($topicDto->groupId);
        $group->addAttribute("style", "width: 300px;");
        $group->setRequired(true);
        $form->addElement($group);

        $submit = new Submit('save');
        $submit->setValue(OW::getLanguage()->text('forum', 'move_topic_btn'));
        $form->addElement($submit);

        $form->setAjax(true);

        return $form;
    }
}