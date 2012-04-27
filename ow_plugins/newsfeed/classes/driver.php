<?php

abstract class NEWSFEED_CLASS_Driver
{
    protected $params = array(), $defaultParams = array();
    protected $actionList = array();
    protected $count = false;

    protected $actionIdList = array();

    /**
     *
     * @var NEWSFEED_BOL_Service
     */
    protected $service;

    public function __construct()
    {
        $this->service = NEWSFEED_BOL_Service::getInstance();

        $this->defaultParams = array(
            'offset' => 0,
            'length' => false,
            'displayCount' => 20
        );
    }

    public function setup( $params )
    {
        $this->params = array_merge($this->defaultParams, $params);

        $this->count = $this->params['length'];
    }

    public function moveCursor( $to = null )
    {
        $this->params['offset'] = empty($to) ? $this->params['offset'] + $this->params['displayCount'] : $to;
    }

    public function getState()
    {
        $this->params['length'] = $this->count;

        return array(
            'class' => get_class($this),
            'params' => $this->params
        );
    }

    public function getActionList()
    {
        $actionList = $this->findActionList($this->params);

        if ( empty($actionList) )
        {
            $this->count = 0;

            return array();
        }

        $this->count = $this->getActionCount();

        foreach ( $actionList as $actionDto )
        {
            $this->actionIdList[$actionDto->entityType . ':' . $actionDto->entityId] = $actionDto->id;
        }

        $activityList = $this->findActivityList($this->params, array_values($this->actionIdList));

        foreach ( $activityList as $activity )
        {
            $actionActivityList[$activity->actionId][$activity->id] = $activity;
        }

        foreach ( $actionList as $actionDto )
        {
            /* @var $actionDto NEWSFEED_BOL_Action */
           $action = $this->makeAction($actionDto, $actionActivityList[$actionDto->id]);

           if ( $action !== null )
           {
                $this->actionList[$actionDto->id] = $action;
           }
        }

        return $this->actionList;
    }

    public function getActionById( $actionId )
    {
        if ( empty($this->actionList[$actionId]) )
        {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findActionById($actionId);

            if ( $action === null )
            {
                return null;
            }

            $action= $this->convertDtoToAction($action);
            $activityList = $this->findActivityList($this->params, array($actionId));
            $action = $this->makeAction($action, $activityList);

            if ( $action === null )
            {
                return null;
            }

            $actionKey = $action->getEntity()->type .':'. $action->getEntity()->id;

            $this->actionList[$action->getId()] = $action;
            $this->actionIdList[$actionKey] = $action->getId();
        }

        return $this->actionList[$actionId];
    }

    public function getAction( $entityType, $entityId )
    {
        $actionKey = $entityType .':'. $entityId;

        if ( empty($this->actionIdList[$actionKey]) )
        {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);

            if ( $action === null )
            {
                return null;
            }

            $activityList = $this->findActivityList($this->params, array($action->id));
            $action = $this->makeAction($action, $activityList);

            if ( $action === null )
            {
                return null;
            }

            $this->actionList[$action->getId()] = $action;
            $this->actionIdList[$actionKey] = $action->getId();
        }

        return $this->actionList[$this->actionIdList[$actionKey]];
    }

    public function getActionCount()
    {
        if ( $this->count === false )
        {
            return $this->findActionCount($this->params);
        }

        return $this->count;
    }

    abstract protected function findActionList( $params );
    abstract protected function findActionCount( $params );
    abstract protected function findActivityList( $params, $actionIdList );

    /**
     *
     * @param NEWSFEED_BOL_Action $dto
     * @return NEWSFEED_CLASS_Action
     */
    private function makeAction( $actionDto, $activityList )
    {
        if ( empty($activityList) )
        {
            return null;
        }

        /* @var $createActivity NEWSFEED_BOL_Activity */
        $createActivity = null;
        foreach ( $activityList as $activity )
        {
            if ( $createActivity === null && $activity->activityType == NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE )
            {
                $createActivity =  $activity;
            }
        }

        if ( empty($createActivity) )
        {
            return null;
        }

        $action = new NEWSFEED_CLASS_Action();
        $action->setId($actionDto->id);
        $action->setCreateTime($createActivity->timeStamp);
        $action->setData( json_decode($actionDto->data, true) );
        $action->setEntity($actionDto->entityType, $actionDto->entityId);
        $action->setPluginKey($actionDto->pluginKey);
        $action->setUserId($createActivity->userId);

        $action->setActivityList($activityList);

        return $action;
    }
}