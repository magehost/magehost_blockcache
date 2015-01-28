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


class JeroenVermeulen_BlockCache_Model_System_Config_Source_Cacheflushes
{
    public function toOptionArray()
    {
        return array(
            array('value'=>1, 'label'=>Mage::helper('adminhtml')->__('Allow Cache Flushes')),
            array('value'=>0, 'label'=>Mage::helper('adminhtml')->__('Ignore Cache Flushes')),
        );
    }
}
