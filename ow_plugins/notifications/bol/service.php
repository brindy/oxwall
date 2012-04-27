<?php

class NOTIFICATIONS_BOL_Service
{
    const NOTIFY_TYPE_IMMEDIATELY = 'immediately';
    const NOTIFY_TYPE_DAILY = 'daily';
    const NOTIFY_TYPE_WEEKLY = 'weekly';

    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return NOTIFICATIONS_BOL_Service
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     *
     * @var NOTIFICATIONS_BOL_RuleDao
     */
    private $ruleDao;

    /**
     *
     * @var NOTIFICATIONS_BOL_ScheduleDao
     */
    private $scheduleDao;

    /**
     *
     * @var NOTIFICATIONS_BOL_QueueDao
     */
    private $queueDao;

    /**
     *
     * @var NOTIFICATIONS_BOL_CronJobDao
     */
    private $cronJobDao;

    /**
     *
     * @var NOTIFICATIONS_BOL_UnsubscribeDao
     */
    private $unsubscribeDao;

    private $defaultRuleList = array();

    public function __construct()
    {
        $this->ruleDao = NOTIFICATIONS_BOL_RuleDao::getInstance();
        $this->queueDao = NOTIFICATIONS_BOL_QueueDao::getInstance();
        $this->scheduleDao = NOTIFICATIONS_BOL_ScheduleDao::getInstance();
        $this->cronJobDao = NOTIFICATIONS_BOL_CronJobDao::getInstance();
        $this->unsubscribeDao = NOTIFICATIONS_BOL_UnsubscribeDao::getInstance();
    }

    public function collectActionList()
    {
        if ( empty($this->defaultRuleList) )
        {
            $event = new BASE_CLASS_EventCollector('base.notify_actions');
            OW::getEventManager()->trigger($event);

            $eventData = $event->getData();
            foreach ( $eventData as $item )
            {
                $this->defaultRuleList[$item['action']] = $item;
            }
        }

        return $this->defaultRuleList;
    }

    public function findRuleList( $userId, $actions = null )
    {
        $out = array();
        $list = $this->ruleDao->findRuleList($userId, $actions);
        foreach ( $list as $item )
        {
            $out[$item->action] = $item;
        }

        return $out;
    }

    public function saveRule( NOTIFICATIONS_BOL_Rule $rule )
    {
        $this->ruleDao->save($rule);
    }

    public function saveQueueItem( NOTIFICATIONS_BOL_Queue $item )
    {
        $this->queueDao->save($item);
    }

    public function findUserIdListForSend( $count, $timeStamp = null )
    {
        return $this->queueDao->findUserList($count, $timeStamp);
    }

    public function findQueue( $userId, $timeStamp = null )
    {
        return $this->queueDao->findList($userId, $timeStamp);
    }

    public function clearQueueByDtoList( $dtoList )
    {
        $idList = array();
        foreach ( $dtoList as $dto )
        {
            $idList[] = $dto->id;
        }

        $this->queueDao->deleteByIdList($idList);
    }

    public function saveSchedule( $userId, $schedule )
    {
        $dto = $this->scheduleDao->findByUserId($userId);

        if ( empty($dto) )
        {
            $dto = new NOTIFICATIONS_BOL_Schedule();
            $dto->userId = $userId;
        }

        if ($dto->value == $schedule)
        {
            return false;
        }

        $dto->value = $schedule;

        $this->scheduleDao->save($dto);

        return true;
    }

    public function findSchedule( $userId )
    {
        $dto = $this->scheduleDao->findByUserId($userId);
        if ( empty($dto) )
        {
            return self::NOTIFY_TYPE_DAILY;
        }

        return $dto->value;
    }

    public function findCronJobTime( $userId )
    {
        $dto = $this->cronJobDao->findByUserId($userId);

        if ( empty($dto) )
        {
            return null;
        }

        return (int) $dto->timeStamp;
    }

    public function setCronJobTime( $userId, $time )
    {
        $dto = $this->cronJobDao->findByUserId($userId);

        if ( empty($dto) )
        {
            $dto = new NOTIFICATIONS_BOL_CronJob();
            $dto->userId = $userId;
        }

        $dto->timeStamp = (int) $time;

        $this->cronJobDao->save($dto);
    }

    public function resetCronJobTime( $userId )
    {
        $this->cronJobDao->deleteByUserId($userId);
    }

    public function findUserIdByUnsubscribeCode( $code )
    {
        $dto = $this->unsubscribeDao->findByCode($code);

        return  empty($dto) ? null : $dto->userId;
    }

    private function getUnsubscribeCodeLifeTime()
    {
        return 60 * 60 * 24 * 7;
    }

    public function deleteExpiredUnsubscribeCodeList()
    {
        $time = time() - $this->getUnsubscribeCodeLifeTime();
        $this->unsubscribeDao->deleteExpired($time);
    }

    public function generateUnsubscribeCode( BOL_User $user )
    {
        $code = md5($user->email);
        $dto = new NOTIFICATIONS_BOL_Unsubscribe();
        $dto->userId = $user->id;
        $dto->code = $code;
        $dto->timeStamp = time();

        $this->unsubscribeDao->save($dto);

        return $code;
    }

    public function addToNotificationQueue( NOTIFICATIONS_CLASS_Notification $notification )
    {
        $dto = $notification->exportToQueueDto();

        $this->saveQueueItem($dto);
    }

    public function sendNotifications( $userId, $notifications, $compose = true )
    {
        $cmp = null;
        foreach ( $notifications as $item )
        {
            if ( empty($cmp) )
            {
                $cmp = new NOTIFICATIONS_CMP_Notification($userId, $compose);
            }

            $cmp->addItem($item);

            if ( !$compose )
            {
                $this->sendProcess($userId, $cmp);
                $cmp = null;
            }
        }

        if ( $compose && !empty($cmp) )
        {
            $this->sendProcess($userId, $cmp);
        }
    }

    private function sendProcess( $userId, NOTIFICATIONS_CMP_Notification $cmp )
    {
        $userService = BOL_UserService::getInstance();
        $user = $userService->findUserById($userId);

        if ( empty($user) )
        {
            return false;
        }

        $email = $user->email;
        $unsubscribeCode = $this->generateUnsubscribeCode($user);

        $cmp->setUnsubscribeCode($unsubscribeCode);

        $txt = $cmp->getTxt();
        $html = $cmp->getHtml();
        $subject = $cmp->getSubject();

        try
        {
            $mail = OW::getMailer()->createMail()
                ->addRecipientEmail($email)
                ->setTextContent($txt)
                ->setHtmlContent($html)
                ->setSubject($subject);

            OW::getMailer()->send($mail);
        }
        catch ( Exception $e )
        {
            //Skip invalid notification
        }
    }

}
