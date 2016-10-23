<?php
/**
 * MageHost_BlockCache
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category    MageHost
 * @package     MageHost_BlockCache
 * @copyright   Copyright (c) 2016 MagentoHosting.pro (https://www.magentohosting.pro)
 */

class MageHost_BlockCache_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return MageHost_BlockCache_Block_Dummy_Messages
     */
    public function getMessagesBlock() {
        return Mage::app()->getLayout()->createBlock('magehost_blockcache/dummy_messages', 'dummy_messages');
    }

}