<?php
class SPODPUBLIC_BOL_AcommentDao extends OW_BaseDao
{
 
    /**
     * Constructor.
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }
    /**
     * Singleton instance.
     *
     * @var SPODPUBLIC_BOL_AcommentDao
     */
    private static $classInstance;
 
    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return SPODPUBLIC_BOL_AcommentDao
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
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'SPODPUBLIC_BOL_AcommentDao';
    }
 
    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'acomment';
    }
}
?>