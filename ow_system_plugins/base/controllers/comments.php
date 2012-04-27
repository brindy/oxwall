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
 * @package ow_system_plugins.base.controllers
 * @since 1.0
 */
class BASE_CTRL_Comments extends OW_ActionController
{
    /**
     * @var BOL_CommentService
     */
    private $commentService;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        $this->commentService = BOL_CommentService::getInstance();
    }

    public function addComment()
    {
        $errorMessage = false;

        $params = $this->getParamsObject();

        if ( empty($_POST['commentText']) && empty($_POST['attch']) )
        {
            $errorMessage = OW::getLanguage()->text('base', 'comment_required_validator_message');
        }
        else if ( !empty($_POST['commentText']) )
        {
            $commentText = $_POST['commentText'];
        }
        else
        {
            $commentText = '';
        }

        if ( !OW::getUser()->isAuthorized($params->getPluginKey(), 'add_comment') )
        {
            $errorMessage = OW::getLanguage()->text('base', 'comment_add_auth_error');
        }
        else if ( BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $params->getOwnerId()) )
        {
            $errorMessage = OW::getLanguage()->text('base', 'user_block_message');
        }
        else
        {
            $eventParams = array('pluginKey' => $params->getPluginKey(), 'action' => 'add_comment');
            $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams);

            if ( $credits === false )
            {
                $errorMessage = OW::getEventManager()->call('usercredits.error_message', $eventParams);
            }
        }

        if ( $errorMessage )
        {
            echo json_encode(array(
                'error' => $errorMessage
            ));

            exit();
        }

        if ( BOL_TextFormatService::getInstance()->isCommentsRichMediaAllowed() )
        {
            $attachment = empty($_POST['attch']) ? null : $_POST['attch'];

            if ( $attachment !== null )
            {
                $tempArr = json_decode($attachment, true);
                if ( $tempArr['type'] == 'photo' )
                {
                    $tempArr['url'] = $tempArr['href'] = OW::getEventManager()->call('base.attachment_save_image', array('genId' => $tempArr['genId']));
                    unset($tempArr['uid']);
                }
                else if( $tempArr['type'] == 'video' )
                {
                    $tempArr['html'] = BOL_TextFormatService::getInstance()->validateVideoCode($tempArr['html']);
                }

                $attachment = json_encode($tempArr);

            }
            else
            {
                $matches = array();
                preg_match('/((http(s)?:\/\/)|www\.)([\w-]+\.)+([a-z,A-Z][\w-]*)(:[1-9][0-9]*)?(\/?([?\w-.\/:%+@&*=]+[\w-.\/:%+@&=*|]*)?)?(#(.*))?/i', $commentText, $matches);
                $url = empty($matches[0]) ? null : ( empty($matches[2]) ? 'http://' : '' ) . $matches[0];
                $oembed = $url ? UTIL_HttpResource::getOEmbed($url) : null;

                if ( !empty($oembed) )
                {
                    $attachment = json_encode($oembed);
                }
            }
        }
        else
        {
            $attachment = null;
        }

        $comment = $this->commentService->addComment($params->getEntityType(), $params->getEntityId(), $params->getPluginKey(), OW::getUser()->getId(), $commentText, $attachment);

        $commentListCmp = new BASE_CMP_CommentsList($params, $_POST['cid']);

        // trigger event comment add

        $event = new OW_Event('base_add_comment',
                array(
                    'entityType' => $params->getEntityType(),
                    'entityId' => $params->getEntityId(),
                    'userId' => OW::getUser()->getId(),
                    'commentId' => $comment->getId(),
                    'pluginKey' => $params->getPluginKey(),
                    'attachment' => json_decode($attachment, true)
            ));

        OW::getEventManager()->trigger($event);

        if ( $credits === true )
        {
            OW::getEventManager()->call('usercredits.track_action', $eventParams);
        }

        echo json_encode(array('commentList' => $commentListCmp->render(), 'onloadScript' => OW::getDocument()->getOnloadScript()));

        exit();
    }

    public function getCommentList()
    {
        $params = $this->getParamsObject();
        $page = ( isset($_POST['page']) && (int) $_POST['page'] > 0) ? (int) $_POST['page'] : 1;
        $commentsList = new BASE_CMP_CommentsList($params, $_POST['cid'], $page);
        exit(json_encode(array('onloadScript' => OW::getDocument()->getOnloadScript(), 'commentList' => $commentsList->render())));
    }

    public function deleteComment()
    {
        $commentArray = $this->getCommentInfoForDelete();
        $comment = $commentArray['comment'];
        $commentEntity = $commentArray['commentEntity'];
        $this->deleteAttachmentFiles($comment);
        $this->commentService->deleteComment($comment->getId());
        $commentCount = $this->commentService->findCommentCount($commentEntity->getEntityType(), $commentEntity->getEntityId());

        if ( $commentCount === 0 )
        {
            $this->commentService->deleteCommentEntity($commentEntity->getId());
        }

        $event = new OW_Event('base_delete_comment',
                array(
                    'entityType' => $commentEntity->getEntityType(),
                    'entityId' => $commentEntity->getEntityId(),
                    'userId' => $comment->getUserId(),
                    'commentId' => $comment->getId()
            ));

        OW::getEventManager()->trigger($event);

        $this->getCommentList();
    }

    public function deleteCommentAtatchment()
    {
        $commentArray = $this->getCommentInfoForDelete();
        $comment = $commentArray['comment'];
        $this->deleteAttachmentFiles($comment);
        $comment->setAttachment(null);
        $this->commentService->updateComment($comment);
    }

    private function deleteAttachmentFiles( BOL_Comment $comment )
    {
        // delete attachments
        $attch = $comment->getAttachment();

        if ( $attch !== null )
        {
            $tempArr = json_decode($attch, true);

            if ( $tempArr['type'] == 'photo' )
            {
                OW::getEventManager()->call('base.attachment_delete_image', array('url' => $tempArr['url']));
            }
        }
    }

    private function getCommentInfoForDelete()
    {
        if ( !isset($_POST['commentId']) || (int) $_POST['commentId'] < 1 )
        {
            echo json_encode(array('error' => OW::getLanguage()->text('base', 'comment_ajax_error')));
            exit();
        }

        /* @var $comment BOL_Comment */
        $comment = $this->commentService->findComment((int) $_POST['commentId']);
        /* @var $commentEntity BOL_CommentEntity */
        $commentEntity = $this->commentService->findCommentEntityById($comment->getCommentEntityId());

        if ( $comment === null || $commentEntity === null )
        {
            echo json_encode(array('error' => OW::getLanguage()->text('base', 'comment_ajax_error')));
            exit();
        }

        $params = $this->getParamsObject();

        $isModerator = OW::getUser()->isAuthorized($params->getPluginKey());
        $isOwnerAuthorized = ( (int) $params->getOwnerId() === (int) OW::getUser()->getId() && OW::getUser()->isAuthorized($params->getPluginKey(), 'delete_comment_by_content_owner', $params->getOwnerId()));
        $commentOwner = ( (int) OW::getUser()->getId() === (int) $comment->getUserId() );

        if ( !$isModerator && !$isOwnerAuthorized && !$commentOwner )
        {
            echo json_encode(array('error' => OW::getLanguage()->text('base', 'auth_ajax_error')));
            exit();
        }

        return array('comment' => $comment, 'commentEntity' => $commentEntity);
    }

    private function getParamsObject()
    {
        $errorMessage = false;

        $entityType = !isset($_POST['entityType']) ? null : trim($_POST['entityType']);
        $entityId = !isset($_POST['entityId']) ? null : (int) $_POST['entityId'];
        $pluginKey = !isset($_POST['pluginKey']) ? null : trim($_POST['pluginKey']);

        if ( !$entityType || !$entityId || !$pluginKey )
        {
            $errorMessage = OW::getLanguage()->text('base', 'comment_ajax_error');
        }

        $params = new BASE_CommentsParams($pluginKey, $entityType);
        $params->setEntityId($entityId);

        if ( isset($_POST['ownerId']) )
        {
            $params->setOwnerId((int) $_POST['ownerId']);
        }

        if ( isset($_POST['commentCountOnPage']) )
        {
            $params->setCommentCountOnPage((int) $_POST['commentCountOnPage']);
        }

        if ( isset($_POST['displayType']) )
        {
            $params->setDisplayType($_POST['displayType']);
        }

        if ( $errorMessage )
        {
            echo json_encode(array(
                'error' => $errorMessage
            ));

            exit();
        }

        return $params;
    }
}
