<?php

/**
 * Class JeroenVermeulen_Cm_Cache_Backend_File
 * This class adds some functionality to Cm_Cache_Backend_File, mainly events.
 *
 * {@inheritdoc}
 */
class JeroenVermeulen_Cm_Cache_Backend_File extends Cm_Cache_Backend_File
{
    /**
     * {@inheritdoc}
     * This function will dispach the event 'jv_clean_backend_cache'. Event listeners can change the tags array.
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {
        $transportObject = new Varien_Object;
        $transportObject->setTags($tags);
        Mage::dispatchEvent('jv_clean_backend_cache', array('transport' => $transportObject));
        $tags = $transportObject->getTags();
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
