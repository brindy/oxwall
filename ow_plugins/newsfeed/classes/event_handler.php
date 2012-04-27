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
 * @package ow_plugins.newsfeed.classes
 * @since 1.0
 */
class NEWSFEED_CLASS_EventHandler
{
    /**
     * Singleton instance.
     *
     * @var NEWSFEED_CLASS_EventHandler
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return NEWSFEED_CLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     *
     * @var NEWSFEED_BOL_Service
     */
    private $service;

    private function __construct()
    {
        $this->service = NEWSFEED_BOL_Service::getInstance();
    }

    private function validateParams( $params, $requiredList, $orRequiredList = array() )
    {
        $fails = array();

        foreach ( $requiredList as $required )
        {
            if ( empty($params[$required]) )
            {
                $fails[] = $required;
            }
        }

        if ( !empty($fails) )
        {
            if ( !empty($orRequiredList) )
            {
                $this->validateParams($params, $orRequiredList);

                return;
            }

            throw new InvalidArgumentException('Next params are required: ' . implode(', ', $fails));
        }
    }

    private function extractEventParams( OW_Event $event )
    {
        $defaultParams = array(
            'postOnUserFeed' => true,
            'visibility' => NEWSFEED_BOL_Service::VISIBILITY_FULL,
            'replace' => false,
            'merge' => false
        );

        $params = $event->getParams();
        $data = $event->getData();

        if ( empty($params['userId']) )
        {
            $params['userId'] = OW::getUser()->getId();
        }

        if ( isset($data['params']) && is_array($data['params']) )
        {
            $params = array_merge($params, $data['params']);
        }

        return array_merge($defaultParams, $params);
    }

    private function extractEventData( OW_Event $event )
    {
        $data = $event->getData();
        unset($data['params']);

        return $data;
    }

    public function action( OW_Event $originalEvent )
    {
        $params = $this->extractEventParams($originalEvent);
        $this->validateParams($params, array('entityType', 'entityId'));

        $data = $originalEvent->getData();

        if ( is_array($params['merge']) )
        {
            $data['actionDto'] = $this->service->findAction($params['merge']['entityType'], $params['merge']['entityId']);
        }
        else
        {
            $data['actionDto'] = $this->service->findAction($params['entityType'], $params['entityId']);
        }

        $event = new OW_Event('feed.on_entity_action', $params, $data);
        OW::getEventManager()->trigger($event);

        $params = $this->extractEventParams($event);
        $data = $this->extractEventData($event);

        if ( $data['actionDto'] !== null )
        {
            $params['pluginKey'] = empty($params['pluginKey']) ? $data['actionDto']->pluginKey : $params['pluginKey'];
            $actionData = json_decode($data['actionDto']->data, true);
            $data = array_merge($actionData, $data);

            $event = new OW_Event('feed.on_entity_update', $params, $data);
            OW::getEventManager()->trigger($event);

            $params = $this->extractEventParams($event);
            $data = $this->extractEventData($event);

            if ( $params['replace'] )
            {
                $this->service->removeAction($data['actionDto']->entityType, $data['actionDto']->entityId);
                $data['actionDto']->id = null;
            }

            $action = $data['actionDto'];
            unset($data['actionDto']);

            $action->data = json_encode($data);

            $this->service->saveAction($action);

            $activityParams = array(
                'pluginKey' => $params['pluginKey'],
                'entityType' => $params['entityType'],
                'entityId' => $params['entityId'],
                'activityType' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
            );

            if ( isset($params['visibility']) )
            {
                $activityParams['visibility'] = $params['visibility'];
            }

            if ( isset($params['postOnUserFeed']) )
            {
                $activityParams['postOnUserFeed'] = $params['postOnUserFeed'];
            }

            if ( !empty($params['privacy']) )
            {
                $activityParams['privacy'] = $params['privacy'];
            }

            if ( !empty( $params['feedType']) && !empty($params['feedId']) )
            {
                $activityParams['feedType'] = $params['feedType'];
                $activityParams['feedId'] = $params['feedId'];
            }

            $temp = empty($data['ownerId']) ? $params['userId'] : $data['ownerId'];
            $userIds = !is_array($temp) ? array($temp) : $temp;

            foreach ( $userIds as $userId )
            {
                $activityParams['userId'] = (int) $userId;
                $activityParams['activityId'] = (int) $userId;

                $activityEvent = new OW_Event('feed.activity', $activityParams);
                $this->activity($activityEvent);
            }
        }
        else
        {
            $activityKey = "create.{$params['entityId']}:{$params['entityType']}.{$params['entityId']}:{$params['userId']}";

            if ( !$this->testActivity($activityKey) )
            {
                return;
            }

            $event = new OW_Event('feed.on_entity_add', $params, $data);
            OW::getEventManager()->trigger($event);

            $params = $this->extractEventParams($event);
            $data = $this->extractEventData($event);

            if ( empty($data['content']) && empty($data['string']) && empty($data['line']) )
            {
                return;
            }

            if ( is_array($params['replace']) )
            {
                $this->service->removeAction($params['replace']['entityType'], $params['replace']['entityId']);
            }

            $action = new NEWSFEED_BOL_Action();
            $action->entityType = $params['entityType'];
            $action->entityId = $params['entityId'];
            $action->pluginKey = $params['pluginKey'];
            $action->data = json_encode($data);

            $this->service->saveAction($action);

            $activityParams = array(
                'pluginKey' => $params['pluginKey'],
                'entityType' => $params['entityType'],
                'entityId' => $params['entityId'],
                'activityType' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
                'visibility' => (int) $params['visibility'],
                'actionId' => $action->id,
                'postOnUserFeed' => $params['postOnUserFeed'],
                'subscribe' => isset($params['subscribe']) ? (bool) $params['subscribe'] : true
            );

            if ( !empty($params['privacy']) )
            {
                $activityParams['privacy'] = $params['privacy'];
            }

            if ( !empty( $params['feedType']) && !empty($params['feedId']) )
            {
                $activityParams['feedType'] = $params['feedType'];
                $activityParams['feedId'] = $params['feedId'];
            }

            $temp = empty($data['ownerId']) ? $params['userId'] : $data['ownerId'];
            $userIds = !is_array($temp) ? array($temp) : $temp;

            foreach ( $userIds as $userId )
            {
                $activityParams['userId'] = (int) $userId;
                $activityParams['activityId'] = (int) $userId;

                $activityEvent = new OW_Event('feed.activity', $activityParams);
                $this->activity($activityEvent);
            }
        }
    }

    public function activity( OW_Event $activityEvent )
    {
        $params = $activityEvent->getParams();
        $data = $activityEvent->getData();
        $data = empty($data) ? array() : $data;

        $this->validateParams($params,
            array('activityType', 'activityId', 'entityType', 'entityId', 'userId', 'pluginKey'),
            array('activityType', 'activityId', 'actionId', 'userId', 'pluginKey'));

        $activityKey = "{$params['activityType']}.{$params['activityId']}:{$params['entityType']}.{$params['entityId']}:{$params['userId']}";
        if ( !$this->testActivity($activityKey) )
        {
            return;
        }

        $actionId = empty($params['actionId']) ? null : $params['actionId'];

        $onEvent = new OW_Event('feed.on_activity', $activityEvent->getParams(), array( 'data' => $data ));
        OW::getEventManager()->trigger($onEvent);
        $onData = $onEvent->getData();
        $data = $onData['data'];
        if ( !empty($onData['params']) && is_array($onData['params']) )
        {
            $params = array_merge($params, $onData['params']);
        }

        if ( empty($actionId) )
        {
            $actionDto = $this->service->findAction($params['entityType'], $params['entityId']);

            if ( $actionDto === null )
            {
                $actionEvent = new OW_Event('feed.action', array(
                    'pluginKey' => $params['pluginKey'],
                    'userId' => $params['userId'],
                    'entityType' => $params['entityType'],
                    'entityId' => $params['entityId']
                ), array(
                    'data' => $data
                ));

                $this->action($actionEvent);
                $actionDto = $this->service->findAction($params['entityType'], $params['entityId']);
            }

            if ( $actionDto === null )
            {
                return;
            }

            $actionId = (int) $actionDto->id;
        }

        $activity = $this->service->findActivityItem($params['activityType'], $params['activityId'], $actionId);

        if ( $activity === null )
        {
            $privacy = empty($params['privacy']) ? NEWSFEED_BOL_Service::PRIVACY_EVERYBODY : $params['privacy'];
            $activity = new NEWSFEED_BOL_Activity();
            $activity->activityType = $params['activityType'];
            $activity->activityId = $params['activityId'];
            $activity->actionId = $actionId;
            $activity->privacy = $privacy;
            $activity->userId = $params['userId'];
            $activity->visibility = empty($params['visibility']) ? NEWSFEED_BOL_Service::VISIBILITY_FULL : $params['visibility'];
            $activity->timeStamp = time();
            $activity->data = json_encode($data);
        }
        else
        {
            $activity->privacy = empty($params['privacy']) ? $activity->privacy : $params['privacy'];
            $activity->visibility = empty($params['visibility']) ? $activity->visibility : $params['visibility'];
            $_data = array_merge( json_decode($activity->data, true), $data );
            $activity->data = json_encode($_data);
        }

        $this->service->saveActivity($activity);

        if ( isset($params['subscribe']) && $params['subscribe'] )
        {
            $subscribe = new NEWSFEED_BOL_Activity();
            $subscribe->actionId = $actionId;
            $subscribe->userId = $params['userId'];
            $subscribe->visibility = NEWSFEED_BOL_Service::VISIBILITY_FULL;
            $subscribe->privacy = NEWSFEED_BOL_Service::PRIVACY_EVERYBODY;
            $subscribe->timeStamp = time();
            $subscribe->activityType = NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_SUBSCRIBE;
            $subscribe->activityId = $params['userId'];
            $subscribe->data = json_encode(array());

            $this->service->saveActivity($subscribe);
        }

        if ( isset($params['subscribe']) && !$params['subscribe'] )
        {
            $this->service->removeActivity("subscribe.{$params['userId']}:$actionId");
        }

        if ( !isset($params['postOnUserFeed']) || $params['postOnUserFeed'] )
        {
            $this->service->addActivityToFeed($activity, 'user', $activity->userId);
        }

        if ( isset($params['postOnUserFeed']) && !$params['postOnUserFeed'] )
        {
            $this->service->deleteActivityFromFeed($activity->id, 'user', $activity->userId);
        }

        if ( !empty($params['feedType']) && !empty($params['feedId']) )
        {
            $this->service->addActivityToFeed($activity, $params['feedType'], $params['feedId']);
        }

        $params = $activityEvent->getParams();
        $params['actionId'] = $actionId;

        $onEvent = new OW_Event('feed.after_activity', $params, array( 'data' => $data ));
        OW::getEventManager()->trigger($onEvent);
    }

    public function afterActivity( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        $this->service->clearUserFeedCahce($params['userId']);
    }

    public function onActivity( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( empty($params['privacy']) )
        {
            $activityKey = "{$params['activityType']}.{$params['activityId']}:{$params['entityType']}.{$params['entityId']}:{$params['userId']}";
            $action = $this->service->getPrivacyActionByActivityKey($activityKey);

            $privacy = NEWSFEED_BOL_Service::PRIVACY_EVERYBODY;

            if ( !empty($action) )
            {
                $t = OW::getEventManager()->call('plugin.privacy.get_privacy', array(
                    'ownerId' => $params['userId'],
                    'action' => $action
                ));

                $privacy = empty($t) ? $privacy : $t;
            }

            $data['params']['privacy'] = $privacy;

            $e->setData($data);
        }
    }

    private function testActivity( $activityKey )
    {
        $disbledActivity = NEWSFEED_BOL_CustomizationService::getInstance()->getDisabledEntityTypes();

        if ( empty($disbledActivity) )
        {
            return true;
        }

        return !$this->service->testActivityKey($activityKey, $disbledActivity);
    }

    public function addComment( OW_Event $e )
    {
        if ( !OW::getConfig()->getValue('newsfeed', 'allow_comments') )
        {
            return;
        }

        $params = $e->getParams();

        $data = array(
            'commentId' => $params['commentId']
        );

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
            'activityType' => 'comment',
            'activityId' => $params['commentId'],
            'entityId' => $params['entityId'],
            'entityType' => $params['entityType'],
            'userId' => $params['userId'],
            'pluginKey' => $params['pluginKey'],
            'subscribe' => true
        ), $data));
    }

    public function deleteComment( OW_Event $e )
    {
        $params = $e->getParams();
        $commentId = $params['commentId'];

        $event = new OW_Event('feed.delete_item', array(
            'entityType' => 'user-comment',
            'entityId' => $commentId
        ));

        OW::getEventManager()->trigger($event);
    }

    public function addLike( OW_Event $e )
    {
        $params = $e->getParams();

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
            'activityType' => 'like',
            'activityId' => $params['userId'],
            'entityId' => $params['entityId'],
            'entityType' => $params['entityType'],
            'userId' => $params['userId'],
            'pluginKey' => 'newsfeed'
        ), $e->getData()));
    }

    public function removeLike( OW_Event $e )
    {
        $params = $e->getParams();
        $this->validateParams($params, array('entityType', 'entityId', 'userId'));

        $activityKey = "like:{$params['entityType']}.{$params['entityId']}:{$params['userId']}";

        $this->service->removeActivity($activityKey);
    }

    public function removeActivity( OW_Event $e )
    {
        $params = $e->getParams();

        if ( isset($params['activityKey']) )
        {
            $activityKey = $params['activityKey'];
        }
        else
        {
            $keyData = array();

            foreach ( array('activityType', 'activityId', 'entityType', 'entityId', 'userId') as $item )
            {
                $keyData[$item] = empty($params[$item]) ? '*' : $params[$item];
            }

            $actionKey = empty($params['actionUniqId']) ? $keyData['entityType'] . '.' . $keyData['entityId'] : $params['actionUniqId'];
            $_activityKey = empty($params['activityUniqId']) ? $keyData['activityType'] . '.' . $keyData['activityId'] : $params['activityUniqId'];

            $activityKey = "$_activityKey:$actionKey:{$keyData['userId']}";
        }

        $this->service->removeActivity($activityKey);
    }

    public function addFollow( OW_Event $e )
    {
        $params = $e->getParams();

        $this->validateParams($params, array('feedType', 'feedId', 'userId'));

        $event = new BASE_CLASS_EventCollector('feed.collect_follow_permissions', $params);
        OW::getEventManager()->trigger($event);

        $data = $event->getData();
        $type = empty($data) ? NEWSFEED_BOL_Service::PRIVACY_EVERYBODY : end($data); // TODO to remove the stub

        $this->service->addFollow($params['userId'], $params['feedType'], $params['feedId'], $type);
    }

    public function removeFollow( OW_Event $e )
    {
        $params = $e->getParams();
        $this->validateParams($params, array('feedType', 'feedId', 'userId'));
        $this->service->removeFollow($params['userId'], $params['feedType'], $params['feedId']);
    }

    public function isFollow( OW_Event $e )
    {
        $params = $e->getParams();
        $this->validateParams($params, array('feedType', 'feedId', 'userId'));
        $result = $this->service->isFollow($params['userId'], $params['feedType'], $params['feedId']);
        $e->setData($result);

        return $result;
    }

    public function getAllFollows( OW_Event $e )
    {
        $params = $e->getParams();

        $this->validateParams($params, array('feedType', 'feedId'));

        $list = $this->service->findFollowList($params['feedType'], $params['feedId']);
        $out = array();

        foreach ( $list as $item )
        {
            $out[] = array(
                'userId' => $item->userId,
                'permission' => $item->permission
            );
        }

        $e->setData($out);

        return $out;
    }

    public function statusUpdate( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        $eventParams = array(
            'pluginKey' => 'newsfeed',
            'entityType' => $params['feedType'] . '-status',
            'entityId' => $data['statusId'],
            'postOnUserFeed' => false,
            'feedType' => $params['feedType'],
            'feedId' => $params['feedId']
        );

        $eventData = array(
            'string' => nl2br($data['status']),
            'content' => '[ph:attachment]',
            'view' => array(
                'iconClass' => 'ow_ic_comment'
            ),
            'attachment' => $data['attachment']
        );

        if ( !empty($params['visibility']) )
        {
            $eventParams['visibility'] = (int) $params['visibility'];
        }

        if ( !empty($data['visibility']) )
        {
            $eventParams['visibility'] = (int) $data['visibility'];
        }

        OW::getEventManager()->trigger( new OW_Event('feed.action', $eventParams, $eventData) );
    }

    public function installWidget( OW_Event $e )
    {
        $params = $e->getParams();

        $widgetService = BOL_ComponentAdminService::getInstance();

        try
        {
            $widget = $widgetService->addWidget('NEWSFEED_CMP_EntityFeedWidget', false);
            $widgetPlace = $widgetService->addWidgetToPlace($widget, $params['place']);
            $widgetService->addWidgetToPosition($widgetPlace, $params['section'], $params['order']);
        }
        catch ( Exception $event )
        {
            $e->setData(false);
        }

        $e->setData($widgetPlace->uniqName);
    }

    public function deleteAction( OW_Event $e )
    {
        $params = $e->getParams();
        $this->validateParams($params, array('entityType', 'entityId'));

        $this->service->removeAction($params['entityType'], $params['entityId']);
    }

    public function deleteActivity( OW_Event $e )
    {
        $params = $e->getParams();
        $this->validateParams($params, array('entityType', 'entityId', 'activityType, activityId'));

        $this->service->findActivity($params['entityType'], $params['entityId']);
    }

    public function onPluginDeactivate( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['pluginKey'] == 'newsfeed' )
        {
            return;
        }

        $this->service->setActionStatusByPluginKey($params['pluginKey'], NEWSFEED_BOL_Service::ACTION_STATUS_INACTIVE);
    }

    public function onPluginActivate( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['pluginKey'] == 'newsfeed' )
        {
            return;
        }

        $this->service->setActionStatusByPluginKey($params['pluginKey'], NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE);
    }

    public function onPluginUninstall( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['pluginKey'] == 'newsfeed' )
        {
            return;
        }

        $this->service->removeActionListByPluginKey($params['pluginKey']);
    }

    public function getUserStatus( OW_Event $e )
    {
        $params = $e->getParams();

        $event = new OW_Event('feed.get_status', array(
            'feedType' => 'user',
            'feedId' => $params['userId']
        ));

        $this->getStatus($event);

        $e->setData($event->getData());
    }

    public function getStatus( OW_Event $e )
    {
        $params = $e->getParams();

        $status = $this->service->getStatus($params['feedType'], $params['feedId']);

        $e->setData($status);
    }

    public function entityAdd( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( $params['entityType'] != 'base_profile_wall' )
        {
            return;
        }

        $comment = BOL_CommentService::getInstance()->findComment($data['data']['commentId']);

        $attachment = empty($comment->attachment) ? null : json_decode($comment->attachment, true);

        if ( empty($attachment) )
        {
            $data['attachment'] = null;
        }
        else
        {
            $data['attachment'] = array(
                'oembed' => $attachment,
                'attachId' => empty($attachment['genId'])
                    ? null
                    : $attachment['genId'],

                'url' => empty($attachment['url'])
                    ? null
                    : $attachment['url']
            );
        }

        $data['content'] = '[ph:attachment]';
        $data['string'] = strip_tags($comment->getMessage());
        $data['string'] = UTIL_HtmlTag::autoLink($data['string']);
        $data['context'] = array(
            'label' => BOL_UserService::getInstance()->getDisplayName($params['entityId']),
            'url' => BOL_UserService::getInstance()->getUserUrl($params['entityId'])
        );

        $data['params']['feedType'] = 'user';
        $data['params']['feedId'] = $params['entityId'];

        $data['params']['entityType'] = 'user-comment';
        $data['params']['entityId'] = $data['data']['commentId'];

        $data['view'] = array(
            'iconClass' => 'ow_ic_comment'
        );

        $e->setData($data);
    }

    public function itemRender( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( !empty($data['attachment']) && !empty($data['attachment']['oembed']) )
        {
            $oembed = array_filter($data['attachment']['oembed']);

            if ( !empty($oembed) )
            {
                //$canDelete = $params['createActivity']['userId'] == OW::getUser()->getId();
                $canDelete = false;

                $oembedCmp = new BASE_CMP_OembedAttachment($data['attachment']['oembed'], $canDelete);
                $oembedCmp->setContainerClass('newsfeed_attachment');
                $oembedCmp->setDeleteBtnClass('newsfeed_attachment_remove');
                $data['assign']['attachment'] = array('template'=>'attachment', 'vars' => array(
                    'content' => $oembedCmp->render()
                ));
            }
        }

        if ( in_array($params['action']['entityType'], array('user-comment')) && $params['feedType'] == 'user' && $params['createActivity']['userId'] != $params['feedId'] )
        {
            $data['context'] = null;
        }

        $e->setData($data);
    }

    public function userUnregister( OW_Event $e )
    {
        $params = $e->getParams();

        if ( !isset($params['deleteContent']) || !$params['deleteContent'] )
        {
            return;
        }

        $userId = (int) $params['userId'];
        $actions = $this->service->findActionsByUserId( $userId );

        foreach ( $actions as $action )
        {
            $this->service->removeAction($action->entityType, $action->entityId);
        }
    }

    public function onPrivacyChange( OW_Event $e )
    {
        $params = $e->getParams();

        $userId = (int) $params['userId'];
        $actionList = $params['actionList'];
        $actionList = is_array($actionList) ? $actionList : array();

        $privacyList = array();

        foreach ( $actionList as $action => $privacy )
        {
            $a = $this->service->getActivityKeysByPrivacyAction($action);
            foreach ( $a as $item )
            {
                $privacyList[$privacy][] = $item;
            }
        }

        foreach ( $privacyList as $privacy => $activityKeys )
        {
            $key = implode(',', array_filter($activityKeys));
            $this->service->setActivityPrivacy($key, $privacy, $userId);
        }
    }

    public function afterAppInit()
    {
        $this->service->collectPrivacy();
    }

    public function clearCache( OW_Event $e )
    {
        $params = $e->getParams();
        $this->validateParams($params, array('userId'));

        $this->service->clearUserFeedCahce($params['userId']);
    }

    public function userBlocked( OW_Event $e )
    {
        $params = $e->getParams();

        $event = new OW_Event('feed.remove_follow', array(
            'feedType' => 'user',
            'feedId' => $params['userId'],
            'userId' => $params['blockedUserId']
        ));
        OW::getEventManager()->trigger($event);

        $event = new OW_Event('feed.remove_follow', array(
            'feedType' => 'user',
            'feedId' => $params['blockedUserId'],
            'userId' => $params['userId']
        ));
        OW::getEventManager()->trigger($event);
    }
}