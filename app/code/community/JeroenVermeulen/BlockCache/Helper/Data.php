<?php
/**
 * JeroenVermeulen_BlockCache
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category    JeroenVermeulen
 * @package     JeroenVermeulen_BlockCache
 * @copyright   Copyright (c) 2015 Jeroen Vermeulen (http://www.jeroenvermeulen.eu)
 */

class JeroenVermeulen_BlockCache_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return JeroenVermeulen_BlockCache_Block_Dummy_Messages
     */
    public function getMessagesBlock() {
        return Mage::app()->getLayout()->createBlock('jeroenvermeulen_blockcache/dummy_messages', 'dummy_messages');
    }

}