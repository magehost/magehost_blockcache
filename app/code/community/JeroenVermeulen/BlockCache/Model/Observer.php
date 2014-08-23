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

class JeroenVermeulen_BlockCache_Model_Observer extends Mage_Core_Model_Abstract
{
    const CONFIG_SECTION = 'jeroenvermeulen_blockcache';

    /**
     * @param Varien_Event_Observer $observer
     */
    function coreBlockAbstractToHtmlBefore( $observer )
    {
        $block = $observer->getBlock();
        if ( is_a($block,'Mage_Catalog_Block_Product_Abstract')
             && Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/product_detail/enable_cache')) {

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
            $block->setCacheLifetime( intval(Mage::getStoreConfig(self::CONFIG_SECTION.'/product_detail/lifetime')) );
        }
        /* TODO if category block && Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/product_list/enable_cache')
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