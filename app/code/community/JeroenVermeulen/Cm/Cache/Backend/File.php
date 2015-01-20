<?php

class JeroenVermeulen_Cm_Cache_Backend_File extends Cm_Cache_Backend_File
{
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {
        $transportObject = new Varien_Object;
        $transportObject->setTags($tags);
        Mage::dispatchEvent('jv_clean_backend_cache', array('transport' => $transportObject));
        $tags = $transportObject->getTags();
        parent::clean($mode, $tags);
    }
}
