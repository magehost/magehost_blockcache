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
 * Class JeroenVermeulen_Cm_Cache_Backend_Redis
 * This class adds some functionality to Cm_Cache_Backend_Redis, mainly events.
 * It also catches a number of Redis errors, to prevent the frontend from crashing when a large Redis cache is
 * being flushed.
 *
 * {@inheritdoc}
 */
class JeroenVermeulen_Cm_Cache_Backend_Redis extends Cm_Cache_Backend_Redis
{
    const ADMIN_READ_TIMEOUT = 7200;

    /**
     * This constructor is executed in a very early stage, during @see Mage_Core_Model_App->initCache()
     * Only few things of the Magento framework will be initialized at this time.
     *
     * {@inheritdoc}
     */
    public function __construct( $options ) {
        if ( JeroenVermeulen_BlockCache_Helper_Data::isAdmin() ) {
            if ( empty($options['read_timeout']) || $options['read_timeout'] < self::ADMIN_READ_TIMEOUT ) {
                $options['read_timeout'] = self::ADMIN_READ_TIMEOUT;
            }
        }
        try {
            parent::__construct( $options );
        } catch ( CredisException $e ) {
            $this->processRedisException( $e, 'constructor' );
        } catch ( RedisException $e ) {
            $this->processRedisException( $e, 'constructor' );
        }
    }

    /**
     * This method will dispatch the event 'jv_clean_backend_cache'. Event listeners can change the tags array.
     *
     * {@inheritdoc}
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        $transportObject = new Varien_Object;
        $transportObject->setTags( $tags );
        Mage::dispatchEvent( 'jv_clean_backend_cache', array( 'transport' => $transportObject ) );
        $tags = $transportObject->getTags();
        parent::clean($mode, $tags);
    }

    /**
     * This method will dispatch the event 'jv_cache_miss_jv' when a cache key miss occurs loading a key
     * from JeroenVermeulen_BlockCache.
     *
     * This method will also catch exceptions on Redis failure.
     *
     * {@inheritdoc}
     */
    public function load($id, $doNotTestCacheValidity = false) {
        try {
            $result = parent::load($id, $doNotTestCacheValidity);
        } catch ( CredisException $e ) {
            $this->processRedisException( $e, 'load' );
            $result = false;
        } catch ( RedisException $e ) {
            $this->processRedisException( $e, 'load' );
            $result = false;
        }
        if ( false === $result && false !== strpos($id,'_JV_') ) {
            Mage::dispatchEvent('jv_cache_miss_jv', array('id' => $id));
        }
        return $result;
    }

    /**
     * This method will catch exceptions on Redis failure.
     *
     * {@inheritdoc}
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false) {
        try {
            $result = parent::save($data, $id, $tags, $specificLifetime);
        } catch ( CredisException $e ) {
            $this->processRedisException( $e, 'save' );
            $result = false;
        } catch ( RedisException $e ) {
            $this->processRedisException( $e, 'save' );
            $result = false;
        }
        return $result;
    }

    /**
     * This method will catch exceptions on Redis failure.
     *
     * {@inheritdoc}
     */
    public function remove($id) {
        try {
            $result = parent::remove($id);
        } catch ( CredisException $e ) {
            $this->processRedisException( $e, 'remove' );
            $result = false;
        } catch ( RedisException $e ) {
            $this->processRedisException( $e, 'remove' );
            $result = false;
        }
        return $result;
    }

    /**
     * This method will catch exceptions on Redis failure.
     *
     * {@inheritdoc}
     */
    public function test($id) {
        try {
            $result = parent::test($id);
        } catch ( CredisException $e ) {
            $this->processRedisException( $e, 'test' );
            $result = false;
        } catch ( RedisException $e ) {
            $this->processRedisException( $e, 'test' );
            $result = false;
        }
        return $result;
    }

    protected function processRedisException($e, $doing) {
        $message = sprintf("JeroenVermeulen_Cm_Cache_Backend_Redis caught Redis Exception during '%s'", $doing);
        Mage::log( $message, Zend_Log::ERR, 'exception.log' );
        Mage::logException($e);
    }
}
