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
     * Apply cache settings to block
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
                $catalogSession = Mage::getSingleton('catalog/session');
                if ( $catalogSession ) {
                    $cacheKeyData[] = 'so'.strval($catalogSession->getSortOrder());
                    $cacheKeyData[] = 'sd'.strval($catalogSession->getSortDirection());
                    $cacheKeyData[] = 'dm'.strval($catalogSession->getDisplayMode());
                    $cacheKeyData[] = 'lp'.strval($catalogSession->getLimitPage());
                }
            } else {
                // Caching of this block is disabled in config
                $cacheLifeTime   = null;
            }
        }
        elseif ( is_a($block,'Mage_Catalog_Block_Product_View') ) {
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
     * Fix form_key in html coming from cache
     * @param Varien_Event_Observer $observer
     */
    public function controllerFrontSendResponseBefore( $observer ) {
        if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/general/enable_formkey_fix') &&
             version_compare(Mage::getVersion(), '1.8', '>=') ) {
            /** @var Zend_Controller_Response_Http $response */
            $response   = $observer->getFront()->getResponse();
            $headers    = $response->getHeaders();
            $isHtml     = true; // Because it's default in PHP
            foreach ( $headers as $header ) {
                if ( 'Content-Type' == $header['name'] && false === strpos($header['value'],'text/html') ) {
                    $isHtml = false;
                    break;
                }
            }
            if ( $isHtml ) {
                $html       = $response->getBody();
                $newFormKey = Mage::getSingleton('core/session')->getFormKey();
                $urlParam   = '/'.Mage_Core_Model_Url::FORM_KEY.'/';
                $urlParamQ  = preg_quote($urlParam,'#');

                // Fix links
                $html = preg_replace('#'.$urlParamQ.'[a-zA-Z0-9]+#', $urlParam.$newFormKey, $html);

                // Fix hidden inputs in forms
                $matches = array();
                if ( preg_match_all('#<input\s[^>]*name=[\'"]{0,1}form_key[\'"]{0,1}[^>]*>#i',$html,$matches,PREG_SET_ORDER) ) {
                     foreach( $matches as $matchData ) {
                         $oldTag = $matchData[0];
                         $newTag = preg_replace('#value=[\'"]{0,1}[a-zA-Z0-9]+[\'"]{0,1}#i','value="'.$newFormKey.'"',$oldTag);
                         if ( $oldTag != $newTag ) {
                             $html = str_replace( $oldTag, $newTag, $html );
                         }
                     }
                }
                $response->setBody($html);
            }
        }
    }

    ////////////////////////////////////////////////////////////////////////////

    /**
     * @param Mage_Core_Block_Template $block
     * @param Mage_Catalog_Model_Category|null $category
     * @param Mage_Catalog_Model_Product|null $product
     * @return array;
     */
    protected function getBlockCacheKeyData( $block, $category=null, $product=null ) {
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        $currentUrl = preg_replace('/(\?|&)(utm_source|utm_medium|utm_campaign|gclid|cx|ie|cof|siteurl)=[^&]+/ms','$1',$currentUrl);
        $currentUrl = str_replace('?&','?',$currentUrl);
        $result = array( $currentUrl, // covers secure, storecode, url param, page nr
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

    // Mage::getSingleton('core/session')->getFormKey()
}