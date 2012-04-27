<?php

class DISKSPACE_BOL_FilesDao extends OW_BaseDao
{
    const PATH = 'path';
    const SIZE = 'size';
    
    /**
     * Singleton instance.
     *
     * @var DISKSPACE_BOL_FilesDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return DISKSPACE_BOL_FilesDao
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
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'DISKSPACE_BOL_Files';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'diskspace_cloud_files';
    }
    
    public function findFile( $path )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::PATH, $path);

        return $this->findObjectByExample($example);
    }

    public function deleteFile( $path )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::PATH, $path);

        return $this->deleteByExample($example);
    }

    public function batchReplace( array $objects )
    {
        $this->dbo->batchInsertOrUpdateObjectList($this->getTableName(), $objects);
        return $this->dbo->getAffectedRows();
    }
}