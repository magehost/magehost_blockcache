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
        $currentBackEnd = get_class( Mage::app()->getCacheInstance()->getFrontEnd()->getBackend() );
        $currentBackEnd = preg_replace( '/^Zend_Cache_Backend_/','', $currentBackEnd );
        $message = '';
        $dependClasses = array('Cm_Cache_Backend_File', 'Cm_Cache_Backend_Redis');
        $optionClasses = array();
        $or = "' " . $this->__('or') . " '";
        foreach( $dependClasses as $dependClass ) {
            $ourClass = 'MageHost_' . $dependClass;
            if ( mageFindClassFile($dependClass) ) {
                $goodBackEnds[] = $ourClass;
            } else {
                $optionClasses[$dependClass] = $ourClass;
            }
        }
        if ( empty($goodBackEnds) ) {
            $message .= 'ERROR:';
            $message .= '<br />' . $this->__("This extension requires one of these classes to exist: '%s'", join($or,$dependClasses));
        } elseif ( ! in_array( $currentBackEnd, $goodBackEnds ) ) {
            $message .= 'ERROR:';
            $message .= '<br />' . $this->__("This extension requires cache backend: '%s'", join($or,$goodBackEnds) );
            $message .= '<br />' . $this->__("Current setting: '%s'", $currentBackEnd);
            $message .= '<br />';
            foreach( $optionClasses as $dependClass => $ourClass ) {
                $message .= '<br />' . $this->__("If you would install '%s' you could also use '%s'.", $dependClass, $ourClass );
            }
            if ( in_array('MageHost_Cm_Cache_Backend_File',$goodBackEnds) ) {
                $message .= <<<EOF
<br />
Please update your <code style="font-weight: normal;">app/etc/local.xml</code>, for example:<br />
<code style="font-weight: normal;">
&lt;config&gt;<br />
&nbsp;&nbsp;&nbsp;&nbsp;&lt;global&gt;<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;...<br />
<strong>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;cache&gt;<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;backend&gt;MageHost_Cm_Cache_Backend_File&lt;/backend&gt;<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;/cache&gt;<br />
</strong>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;...<br />
&nbsp;&nbsp;&nbsp;&nbsp;&lt;/global&gt;<br />
&lt;/config&gt;<br />
</code>
EOF;
            }
        }
        if ( !empty($message) ) {
            $result.= sprintf( '<ul class="messages"><li class="error-msg"><ul><li><span>%s</span></li></ul></li></ul>', $message );
        }
        $result .= sprintf( '<p>%s<br />%s</p>',
                            $this->__( 'Most settings have direct affect.' ),
                            $this->__( 'Settings which affect cache tags have no effect on already cached blocks.' ) );
        $result .= parent::_getHeaderHtml( $element );
        return $result;
    }

}
