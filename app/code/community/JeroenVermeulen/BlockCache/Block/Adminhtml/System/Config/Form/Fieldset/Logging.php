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
 * @copyright   Copyright (c) 2016 Jeroen Vermeulen (http://www.jeroenvermeulen.eu)
 */

class JeroenVermeulen_BlockCache_Block_Adminhtml_System_Config_Form_Fieldset_Logging
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Show explanation
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function _getHeaderCommentHtml( Varien_Data_Form_Element_Abstract $element ) {
        $result = '';
        $message = '';
        if ( ! Mage::getStoreConfigFlag('dev/log/active') ) {
            $message .= <<<EOF
Warning: Magento loggig is disabled. To use logging you need to enable it via:<br />
<i>System &gt; Configuration &gt; ADVANCED &gt; Developer &gt; Log Settings</i>
EOF;
        }
        if ( !empty($message) ) {
            $result.= sprintf( '<ul class="messages"><li class="error-msg"><ul><li><span>%s</span></li></ul></li></ul>', $message );
        }
        $result .= parent::_getHeaderCommentHtml( $element );
        return $result;
    }

}
