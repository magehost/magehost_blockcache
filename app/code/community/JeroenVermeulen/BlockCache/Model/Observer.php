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
    const BLOCK_CACHE_TAG = 'BLOCK_HTML';

    /**
     * @param Varien_Event_Observer $observer
     */
    function coreBlockAbstractToHtmlBefore( $observer )
    {
        /** @var Mage_Core_Block_Template $block */
        $block = $observer->getBlock();
        $cacheLifeTime = false;

        if ( is_a($block,'Mage_Catalog_Block_Category_View') ) {
            if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/category_page/enable_cache') ) {
                $currentCategory = Mage::registry('current_category');
                $cacheKeyData    = $this->getBlockCacheKeyData( $block, $currentCategory );
                $cacheTags       = $this->getBlockCacheTags( $currentCategory );
                $cacheLifeTime   = intval(Mage::getStoreConfig(self::CONFIG_SECTION.'/category_page/lifetime'));
            } else {
                // Caching of this block is disabled in config
                $cacheLifeTime   = null;
            }
        }
        elseif ( is_a($block,'Mage_Catalog_Block_Product_Abstract') ) {
            if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/product_detail/enable_cache') ) {
                $currentCategory = Mage::registry('current_category');
                $currentProduct  = Mage::registry('current_product');
                $cacheKeyData    = $this->getBlockCacheKeyData( $block, $currentCategory, $currentProduct );
                $cacheTags       = $this->getBlockCacheTags( $currentCategory, $currentProduct );
                $cacheLifeTime   = intval(Mage::getStoreConfig(self::CONFIG_SECTION.'/product_detail/lifetime'));
            } else {
                // Caching of this block is disabled in config
                $cacheLifeTime   = null;
            }
        }

        if ( false !== $cacheLifeTime ) {
            $block->setCacheLifetime( $cacheLifeTime );
            if ( null !== $cacheLifeTime ) {
                $block->setCacheKey( implode('_', $cacheKeyData) );
                $block->setCacheTags( $cacheTags );
            }
        }
    }

    /**
     * @param Mage_Core_Block_Template $block
     * @param Mage_Catalog_Model_Category|null $category
     * @param Mage_Catalog_Model_Product|null $product
     * @return array;
     */
    protected function getBlockCacheKeyData( $block, $category=null, $product=null ) {
        $result = array( Mage::helper('core/url')->getCurrentUrl(), // covers secure, storecode, url param, page nr
                         get_class( $block ),
                         $block->getTemplate(),
                         Mage::getSingleton('customer/session')->getCustomerGroupId(),
                         Mage::app()->getStore()->getCurrentCurrencyCode() );
        if ( !empty($category) ) {
            $result[] = 'c'.$category->getId();
        }
        if ( !empty($product) ) {
            $result[] = 'p'.$product->getId();
        }
        return $result;
    }

    /**
     * @param Mage_Catalog_Model_Category|null $category
     * @param Mage_Catalog_Model_Product|null $product
     * @return array;
     */
    protected function getBlockCacheTags( $category=null, $product=null ) {
        $result = array( self::BLOCK_CACHE_TAG,
                         Mage_Core_Model_Store::CACHE_TAG,
                         Mage_Core_Model_Translate::CACHE_TAG );
        if ( !empty($category) ) {
            $result[] = Mage_Catalog_Model_Category::CACHE_TAG;
            $result[] = Mage_Catalog_Model_Category::CACHE_TAG.'_'.$category->getId();
        }
        if ( !empty($product) ) {
            $result[] = Mage_Catalog_Model_Product::CACHE_TAG;
            $result[] = Mage_Catalog_Model_Product::CACHE_TAG.'_'.$product->getId();
        }
        return $result;
    }
}