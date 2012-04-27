<?php

class UPDATE_Logger
{
    private static $classInstance;

    private $logger;
    /**
     * @return UPDATE_Logger
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
        $this->logger = OW_Log::getInstance('update');
    }
}