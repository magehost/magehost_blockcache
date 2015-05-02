<?php
/**
 * JeroenVermeulen_BlockCache
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category     JeroenVermeulen
 * @package      JeroenVermeulen_BlockCache
 * @copyright    Copyright (c) 2015 Jeroen Vermeulen (http://www.jeroenvermeulen.eu)
 */

/**
 * Class JeroenVermeulen_Cm_Cache_Backend_File
 * This class adds some functionality to Cm_Cache_Backend_File, mainly events.
 *
 * {@inheritdoc}
 */
class JeroenVermeulen_Cm_Cache_Backend_File extends Cm_Cache_Backend_File
{
    /** @var string|null */
    protected $frontendPrefix = null;

    /**
     * This method will dispatch the event 'jv_clean_backend_cache'.
     * Event listeners can change the mode or tags.
     *
     * {@inheritdoc}
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {
        $transportObject = new Varien_Object;
        /** @noinspection PhpUndefinedMethodInspection */
        $transportObject->setMode( $mode );
        /** @noinspection PhpUndefinedMethodInspection */
        $transportObject->setTags( $tags );
        Mage::dispatchEvent( 'jv_clean_backend_cache', array( 'transport' => $transportObject ) );
        /** @noinspection PhpUndefinedMethodInspection */
        $mode = $transportObject->getMode();
        /** @noinspection PhpUndefinedMethodInspection */
        $tags = $transportObject->getTags();
        parent::clean($mode, $tags);
    }

    /**
     * This method will dispatch the event 'jv_cache_miss_jv' when a cache key miss occurs loading a key
     * from JeroenVermeulen_BlockCache.
     *
     * {@inheritdoc}
     */
    public function load($id, $doNotTestCacheValidity = false) {
        $result = parent::load($id, $doNotTestCacheValidity);
        if ( false === $result && false !== strpos($id,'_JV_') ) {
            Mage::dispatchEvent('jv_cache_miss_jv', array('id' => $id));
        }
        return $result;
    }

    /**
     * This method will dispatch the event 'jv_cache_save_block' when cache is saved for a html block.
     *
     * {@inheritdoc}
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        if ( in_array( $this->getFrontendPrefix().'BLOCK_HTML', $tags ) ) {
            $transportObject = new Varien_Object;
            /** @noinspection PhpUndefinedMethodInspection */
            $transportObject->setTags($tags);
            Mage::dispatchEvent('jv_cache_save_block', array('id' => $id,'transport' => $transportObject));
            /** @noinspection PhpUndefinedMethodInspection */
            $tags = $transportObject->getTags();
        }
        return parent::save( $data, $id, $tags, $specificLifetime );
    }

    protected function getFrontendPrefix() {
        if ( is_null($this->frontendPrefix) ) {
            $this->frontendPrefix = Mage::app()->getCacheInstance()->getFrontend()->getOption('cache_id_prefix');
        }
        return $this->frontendPrefix;
    }
}
