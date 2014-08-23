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
 * @copyright   Copyright (c) 2014 Jeroen Vermeulen (http://www.jeroenvermeulen.eu)
 */

class JeroenVermeulen_BlockCache_Block_Adminhtml_System_Config_Form_Fieldset_First
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

    /**
     * Show explanation
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderHtml($element) {
        $result = sprintf( '<p>%s</p>',
                           $this->__( 'These settings have direct affect, no need to flush the cache.' ) );
        $result .= parent::_getHeaderHtml( $element );
        return $result;
    }

}
