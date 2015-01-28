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

    /**
     * Function to determine if we are currently in admin or cli.
     * This function must work in a very early stage so we can't use Mage::app()
     * @return bool
     */
    public static function isAdmin() {
        static $result = null;
        if ( is_null($result) ) {
            $result = false;
            $baseScript = basename($_SERVER['SCRIPT_FILENAME']);
            if ( 0 === strpos($baseScript,'n98') || 0 === strpos($baseScript,'cron') ) {
                // CLI or Cron
                $result = true;
            } else {
                $adminPath = null;
                $config = Mage::getConfig();
                if ( !empty($config) ) {
                    $useCustomAdminPath = (bool)(string)$config->getNode(Mage_Adminhtml_Helper_Data::XML_PATH_USE_CUSTOM_ADMIN_PATH);
                    if ($useCustomAdminPath) {
                        $adminPath = (string)$config->getNode(Mage_Adminhtml_Helper_Data::XML_PATH_CUSTOM_ADMIN_PATH);
                    }
                    if ( empty($adminPath) ) {
                        $adminPath = (string)$config->getNode(Mage_Adminhtml_Helper_Data::XML_PATH_ADMINHTML_ROUTER_FRONTNAME);
                    }
                    $request = new Zend_Controller_Request_Http;
                    $pathParts = explode( '/', trim($request->getPathInfo(),'/') );
                    if ( isset($pathParts[0]) && $pathParts[0] == $adminPath ) {
                        $result = true;
                    }
                }
            }
        }
        return $result;
    }

}