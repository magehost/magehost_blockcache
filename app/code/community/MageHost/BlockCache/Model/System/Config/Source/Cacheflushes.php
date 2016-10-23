<?php
/**
 * MageHost_BlockCache
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category     MageHost
 * @package      MageHost_BlockCache
 * @copyright    Copyright (c) 2016 MagentoHosting.pro (https://www.magentohosting.pro)
 */


class MageHost_BlockCache_Model_System_Config_Source_Cacheflushes
{
    public function toOptionArray()
    {
        return array(
            array('value'=>1, 'label'=>Mage::helper('adminhtml')->__('Allow Cache Flushes')),
            array('value'=>0, 'label'=>Mage::helper('adminhtml')->__('Ignore Cache Flushes')),
        );
    }
}
