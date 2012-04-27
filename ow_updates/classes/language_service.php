<?php

class UPDATE_LanguageService
{
    private static $classInstance;

    private $service;
    /**
     *
     * @param <type> $includeCache
     * @return UPDATE_LanguageService
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function importPrefixFromZip($path, $key)
    {
        $this->service->importPrefixFromZip($path, $key, false);
    }

    private function __construct()
    {
        $this->service = BOL_LanguageService::getInstance();
    }
}