<?php

class JeroenVermeulen_BlockCache_Model_Observer extends Mage_Core_Model_Abstract
{

    /**
     * @param Varien_Event_Observer $observer
     */
    function coreBlockAbstractToHtmlBefore( $observer )
    {
        $block = $observer->getBlock();
        if ( is_a($block,'Mage_Catalog_Block_Product_Abstract') ) {  // @TODO make enable/disable configurable

            $cacheTags = array( Mage_Core_Model_Store::CACHE_TAG,
                                Mage_Catalog_Model_Category::CACHE_TAG,
                                Mage_Catalog_Model_Product::CACHE_TAG,
                                'BLOCK_HTML' );
            $currentCategory = Mage::registry('current_category');
            if ( $currentCategory ) {
                $cacheTags[] = Mage_Catalog_Model_Category::CACHE_TAG.$currentCategory->getId();     // found in Mage_Catalog_Model_Layer
                $cacheTags[] = Mage_Catalog_Model_Category::CACHE_TAG.'_'.$currentCategory->getId(); // found in Mage_Catalog_Model_Product
            }
            $currentProduct = Mage::registry('current_product');
            if ( $currentProduct ) {
                $cacheTags[] = Mage_Catalog_Model_Product::CACHE_TAG.'_'.$currentProduct->getId();
            }

            $cacheKeyData = array(
                get_class( $block ),
                Mage::helper('core/url')->getCurrentUrl(), // includes secure, storecode
                Mage::getSingleton('customer/session')->getCustomerGroupId(),
                $block->getTemplate(),
            );

            $block->setCacheKey( implode('_', $cacheKeyData) );
            $block->setCacheTags( $cacheTags );
            $block->setCacheLifetime( 3600 ); // 1 hour @TODO make configurable
        }
        /* TODO if category block:
        {
            $cacheTags = array( Mage_Core_Model_Store::CACHE_TAG,
                                Mage_Catalog_Model_Category::CACHE_TAG,
                               'BLOCK_HTML' );
            $currentCategory = Mage::registry('current_category');
            if ( $currentCategory ) {
                $cacheTags[] = Mage_Catalog_Model_Category::CACHE_TAG.$currentCategory->getId();
            }

            $cacheKeyData = array( get_class( $block ),
                                   Mage::app()->getStore()->getId(),
                                   $this->helper('core/url')->getCurrentUrl(),
                                   Mage::getSingleton('customer/session')->getCustomerGroupId(),
                                   $this->getTemplate() );

            $block->setCacheKey( implode('_', $cacheKeyData) );
            $this->setCacheTags( $cacheTags );
            $this->setCacheLifetime( 604800 ); // 1 week
        }
        */
    }

}