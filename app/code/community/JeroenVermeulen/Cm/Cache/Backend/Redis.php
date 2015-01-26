<?php

/**
 * Class JeroenVermeulen_Cm_Cache_Backend_File
 * This class adds some functionality to Cm_Cache_Backend_Redis, mainly events.
 *
 * {@inheritdoc}
 */
class JeroenVermeulen_Cm_Cache_Backend_Redis extends Cm_Cache_Backend_Redis
{
    const ADMIN_CLEAN_TIMEOUT = 3600;

    /**
     * {@inheritdoc}
     * This function will dispach the event 'jv_clean_backend_cache'. Event listeners can change the tags array.
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        $transportObject = new Varien_Object;
        $transportObject->setTags( $tags );
        Mage::dispatchEvent( 'jv_clean_backend_cache', array( 'transport' => $transportObject ) );
        $tags = $transportObject->getTags();
        if ( Mage::app()->getStore()->isAdmin() ) {
            // Long timeout when cleaning in Admin, to prevent timeout when cleaning lots of cache.
            $this->_redis->setReadTimeout( self::ADMIN_CLEAN_TIMEOUT );
        }
        parent::clean($mode, $tags);
    }

    /**
     * {@inheritdoc}
     * This function will dispach the event 'jv_cache_miss_jv' when a cache key miss occurs loading a key
     * from JeroenVermeulen_BlockCache.
     */
    public function load($id, $doNotTestCacheValidity = false) {
        $result = parent::load($id, $doNotTestCacheValidity);
        if ( false === $result && false !== strpos($id,'_JV_') ) {
            Mage::dispatchEvent('jv_cache_miss_jv', array('id' => $id));
        }
        return $result;
    }

}
