<?php

class DISKSPACE_BOL_FilesService
{
    /**
     * @var BOL_Dao
     */
    private $filesDao;

    /**
     * Singleton instance.
     *
     * @var DISKSPACE_BOL_FilesService
     */
    private static $classInstance;

    /**
     * Constructor.
     *
     */
    private function __construct()
    {
        $this->filesDao = DISKSPACE_BOL_FilesDao::getInstance();
    }

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return DISKSPACE_BOL_FilesService
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function findFile( $path )
    {
        return $this->filesDao->findFile( $path );
    }

    public function deleteFile( $path )
    {
        return $this->filesDao->deleteFile( $path );
    }

    public function addFile( $path, $size )
    {
        if( empty($path) || !isset($size) )
        {
            return;
        }

        $dto = new DISKSPACE_BOL_Files();
        $dto->path = trim($path);
        $dto->size = $size; // byte

        return $this->filesDao->batchReplace(array($dto));
    }
}