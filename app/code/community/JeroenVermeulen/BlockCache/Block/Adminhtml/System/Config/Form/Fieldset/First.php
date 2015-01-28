<?php
/** @noinspection PhpUndefinedClassInspection */
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

class JeroenVermeulen_BlockCache_Block_Adminhtml_System_Config_Form_Fieldset_First
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Show explanation
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderHtml($element) {
        $result = '';
        $goodBackEnds = array();
        $currentBackEnd = strval( Mage::getConfig()->getNode('global/cache/backend') );
        $message = '';
        $dependClasses = array('Cm_Cache_Backend_File', 'Cm_Cache_Backend_Redis');
        $or = ' ' . $this->__('or') . ' ';
        foreach( $dependClasses as $dependClass ) {
            if ( mageFindClassFile($dependClass) ) {
                $goodBackEnds[] = 'JeroenVermeulen_' . $dependClass;
            }
        }
        if ( empty($goodBackEnds) ) {
            $message .= 'ERROR:';
            $message .= '<br />' . $this->__("This extension requires one of these classes to exist: %s", join($or,$dependClasses));
        } elseif ( ! in_array( $currentBackEnd, $goodBackEnds ) ) {
            $message .= 'ERROR:';
            $message .= '<br />' . $this->__("This extension requires cache backend: %s", join($or,$goodBackEnds));
            $message .= '<br />' . $this->__("Current setting: %s", $currentBackEnd);
        }
        if ( !empty($message) ) {
            $result.= sprintf( '<ul class="messages"><li class="error-msg"><ul><li><span>%s</span></li></ul></li></ul>', $message );
        }
        $result .= sprintf( '<p>%s</p>',
                            $this->__( 'These settings have direct affect, no need to flush the cache.' ) );
        $result .= parent::_getHeaderHtml( $element );
        return $result;
    }

}
