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
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.controllers
 * @since 1.0
 */
class NEWSFEED_CTRL_Ajax extends OW_ActionController
{
    /**
     *
     * @var NEWSFEED_BOL_Service
     */
    private $service;

    public function __construct()
    {
        $this->service = NEWSFEED_BOL_Service::getInstance();
    }

    public function init()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }
    }

    public function like()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $entityType = $_GET['entityType'];
        $entityId = (int) $_GET['entityId'];

        $like = $this->service->addLike(OW::getUser()->getId(), $entityType, $entityId);

        $event = new OW_Event('feed.after_like_added', array(
            'entityType' => $entityType,
            'entityId' => $entityId,
            'userId' => OW::getUser()->getId()
        ), array(
            'likeId' => $like->id
        ));

        OW::getEventManager()->trigger($event);

        $cmp = new NEWSFEED_CMP_Likes($entityType, $entityId);

        echo json_encode(array(
            'count' => $cmp->getCount(),
            'markup' => $cmp->render()
        ));

        exit;
    }

    public function unlike()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $entityType = $_GET['entityType'];
        $entityId = (int) $_GET['entityId'];

        $this->service->removeLike(OW::getUser()->getId(), $entityType, $entityId);

        $event = new OW_Event('feed.after_like_removed', array(
            'entityType' => $entityType,
            'entityId' => $entityId,
            'userId' => OW::getUser()->getId()
        ));

        OW::getEventManager()->trigger($event);

        $cmp = new NEWSFEED_CMP_Likes($entityType, $entityId);

        echo json_encode(array(
            'count' => $cmp->getCount(),
            'markup' => $cmp->render()
        ));

        exit;
    }

    public function statusUpdate()
    {

        if ( empty($_POST['status']) && empty($_POST['attachment']) )
        {
            echo json_encode(false);
            exit;
        }

        $oembed = null;
        $url = null;
        $attachId = null;
        $status = empty($_POST['status']) ? '' : strip_tags($_POST['status']);

        if ( empty($_POST['attachment']) )
        {
            if ( !empty($status) )
            {
                $matches = array();
                preg_match('/((http(s)?:\/\/)|www\.)([\w-]+\.)+([a-z,A-Z][\w-]*)(:[1-9][0-9]*)?(\/?([?\w-.\/:%+@&*=]+[\w-.\/:%+@&=*|]*)?)?(#(.*))?/i', $status, $matches);
                $url = empty($matches[0]) ? null : ( empty($matches[2]) ? 'http://' : '' ) . $matches[0];

                $oembed = $url ? UTIL_HttpResource::getOEmbed($url) : null;
            }
        }
        else
        {
            $attach = json_decode($_POST['attachment'], true);

            if( $attach['type'] == 'photo' )
            {
                $attach['url'] = $attach['href'] = OW::getEventManager()->call('base.attachment_save_image', array( 'genId' => $attach['genId'] ));
            }
            else if( $attach['type'] == 'video' )
            {
                $attach['html'] = BOL_TextFormatService::getInstance()->validateVideoCode($attach['html']);
            }

            $oembed = $attach;
            $attachId = $attach['genId'];
        }

        $status = UTIL_HtmlTag::autoLink($status);

        $statusDto = NEWSFEED_BOL_Service::getInstance()->saveStatus($_POST['feedType'], (int) $_POST['feedId'], $status);

        $event = new OW_Event('feed.after_status_update', array(
            'feedType' => $_POST['feedType'],
            'feedId' =>  $_POST['feedId'],
            'visibility' => (int) $_POST['visibility']
        ), array(
            'status' => $status,
            'attachment' => array(
                'oembed' => $oembed,
                'url' => $url,
                'attachId' => $attachId
            ),
            'statusId' => (int) $statusDto->id
        ));

        OW::getEventManager()->trigger($event);

        echo json_encode(array(
            'entityType' => $_POST['feedType'] . '-status',
            'entityId' => $statusDto->id
        ));
        exit;
    }

    public function remove()
    {
        if ( empty($_GET['actionId']) )
        {
            throw new Redirect404Exception();
        }

        $id = (int) $_GET['actionId'];
        $dto = $this->service->findActionById($id);
        $data = json_decode($dto->data, true);

        if( !empty($data['attachment']) && $data['attachment']['oembed']['type'] == 'photo' )
        {
            OW::getEventManager()->call('base.attachment_delete_image', array('url' => $data['attachment']['oembed']['url']));
        }

        $this->service->removeActionById($id);

        exit;
    }

    public function removeAttachment()
    {
        if ( empty($_GET['actionId']) )
        {
            throw new Redirect404Exception();
        }

        $actionId = (int) $_GET['actionId'];
        $dto = $this->service->findActionById($actionId);
        $data = json_decode($dto->data, true);

        if( $data['attachment']['oembed']['type'] == 'photo' )
        {
            OW::getEventManager()->call('base.attachment_delete_image', array('url' => $data['attachment']['oembed']['url']));
        }

        unset($data['attachment']);
        $dto->data = json_encode($data);

        $this->service->saveAction($dto);

        exit;
    }

    public function loadItem()
    {
        $params = json_decode($_GET['p'], true);

        $feedData = $params['feedData'];

        $driverClass = $feedData['driver']['class'];
        /* @var $driver NEWSFEED_CLASS_Driver */
        $driver = new $driverClass;
        $driver->setup($feedData['driver']['params']);

        if ( isset($params['actionId']) )
        {
            $action = $driver->getActionById($params['actionId']);
        }
        else if ( isset($params['entityType']) && isset($params['entityId']) )
        {
            $action = $driver->getAction($params['entityType'], $params['entityId']);
        }
        else
        {
            throw new InvalidArgumentException('Invalid paraeters: `entityType` and `entityId` or `actionId`');
        }

        if ( $action === null )
        {
            $this->echoError('Action not found');
        }

        $data = $feedData['data'];

        $sharedData['feedAutoId'] = $data['feedAutoId'];
        $sharedData['feedType'] = $data['feedType'];
        $sharedData['feedId'] = $data['feedId'];

        $sharedData['configs'] = OW::getConfig()->getValues('newsfeed');

        $sharedData['usersInfo'] = array(
            'avatars' => BOL_AvatarService::getInstance()->getAvatarsUrlList(array( $action->getUserId() )),
            'urls' => BOL_UserService::getInstance()->getUserUrlsForList(array( $action->getUserId() )),
            'names' => BOL_UserService::getInstance()->getDisplayNamesForList(array( $action->getUserId() )),
            'roleLabels' => BOL_AvatarService::getInstance()->getDataForUserAvatars(array( $action->getUserId() ), false, false, false)
        );

        $cmp = new NEWSFEED_CMP_FeedItem($action, $sharedData);
        $html = $cmp->renderMarkup($params['cycle']);

        $this->synchronizeData($data['feedAutoId'], array(
            'data' => $data,
            'driver' => $driver->getState()
        ));

        $this->echoMarkup($html);
    }

    public function loadItemList()
    {
        $params = json_decode($_GET['p'], true);

        $event = new OW_Event('feed.on_ajax_load_list', $params);
        OW::getEventManager()->trigger($event);

        $driverClass = $params['driver']['class'];

        /*@var $cmp NEWSFEED_CLASS_Driver */
        $driver = new $driverClass();

        $driverParams = $params['driver']['params'];
        $driverParams['displayCount'] = $driverParams['displayCount'] > 20 ? 20 : $driverParams['displayCount'];

        $driver->setup($driverParams);

        $driver->moveCursor();
        $actionList = $driver->getActionList();

        $list = new NEWSFEED_CMP_FeedList($actionList, $params['data']);
        $html = $list->render();

        $this->synchronizeData($params['data']['feedAutoId'], array(
            'data' => $params['data'],
            'driver' => $driver->getState()
        ));

        $this->echoMarkup($html);
    }

    private function synchronizeData( $autoId, $data )
    {
        $script = UTIL_JsGenerator::newInstance()
                ->callFunction(array('window', 'ow_newsfeed_feed_list', $autoId, 'setData'), array($data));
        OW::getDocument()->addOnloadScript($script);
    }

    private function echoError( $msg, $code = null )
    {
        echo json_encode(array(
            'result' => 'error',
            'code' => $code,
            'msg' => $msg
        ));

        exit;
    }

    private function echoMarkup( $html )
    {
        /* @var $document OW_AjaxDocument */
        $document = OW::getDocument();

        $markup = array();

        $markup['result'] = 'success';
        $markup['html'] = $html;

        $onloadScript = $document->getOnloadScript();
        if ( !empty($onloadScript) )
        {
            $markup['onloadScript'] = $onloadScript;
        }

        $styleDeclarations = $document->getStyleDeclarations();
        if ( !empty($styleDeclarations) )
        {
            $markup['styleDeclarations'] = $styleDeclarations;
        }

        echo json_encode($markup);

        exit;
    }
}