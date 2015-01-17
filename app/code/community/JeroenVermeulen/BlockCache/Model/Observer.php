<?php
/**
 * JeroenVermeulen_BlockCache
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category     JeroenVermeulen
 * @package      JeroenVermeulen_BlockCache
 * @copyright    Copyright (c) 2014 Jeroen Vermeulen (http://www.jeroenvermeulen.eu)
 */

class JeroenVermeulen_BlockCache_Model_Observer extends Mage_Core_Model_Abstract
{
    const CONFIG_SECTION  = 'jeroenvermeulen_blockcache';
    const BLOCK_CACHE_TAG = 'BLOCK_HTML';
    const FLUSH_LOG_FILE  = 'cache_flush.log';

    /**
     * Apply cache settings to block
     * @param Varien_Event_Observer $observer
     */
    function coreBlockAbstractToHtmlBefore( $observer )
    {
        /** @var Mage_Core_Block_Template $block */
        /** @noinspection PhpUndefinedMethodInspection */
        $block         = $observer->getBlock();
        $cacheLifeTime = false;
        $cacheTags     = array();
        $cacheKeyData  = array();

        if ( $block instanceof Mage_Catalog_Block_Category_View ) {
            if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/category_page/enable_cache') ) {
                $currentCategory = Mage::registry('current_category');
                $cacheKeyData    = $this->getBlockCacheKeyData( $block, $currentCategory );
                $tagsCategory     = null;
                if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/category_page/enable_flush_category_change') ) {
                    $tagsCategory = $currentCategory;
                }
                $cacheTags       = $this->getBlockCacheTags( $tagsCategory );
                $cacheLifeTime   = intval(Mage::getStoreConfig(self::CONFIG_SECTION.'/category_page/lifetime'));
                $catalogSession = Mage::getSingleton('catalog/session');
                if ( $catalogSession ) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $cacheKeyData[] = 'so'.strval($catalogSession->getSortOrder());
                    /** @noinspection PhpUndefinedMethodInspection */
                    $cacheKeyData[] = 'sd'.strval($catalogSession->getSortDirection());
                    /** @noinspection PhpUndefinedMethodInspection */
                    $cacheKeyData[] = 'dm'.strval($catalogSession->getDisplayMode());
                    /** @noinspection PhpUndefinedMethodInspection */
                    $cacheKeyData[] = 'lp'.strval($catalogSession->getLimitPage());
                }
            } else {
                // Caching of this block is disabled in config
                $cacheLifeTime   = null;
            }
        }
        elseif ( $block instanceof Mage_Catalog_Block_Product_View ) {
            if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/product_detail/enable_cache') ) {
                $currentCategory = Mage::registry('current_category');
                $currentProduct  = Mage::registry('current_product');
                $cacheKeyData    = $this->getBlockCacheKeyData( $block, $currentCategory, $currentProduct );
                $tagsCategory    = null;
                if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/product_detail/enable_flush_category_change') ) {
                    $tagsCategory = $currentCategory;
                }
                $tagsProduct     = null;
                if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/product_detail/enable_flush_product_change') ) {
                    $tagsProduct = $currentProduct;
                }
                $cacheTags       = $this->getBlockCacheTags( $tagsCategory, $tagsProduct );
                $cacheLifeTime   = intval(Mage::getStoreConfig(self::CONFIG_SECTION.'/product_detail/lifetime'));
            } else {
                // Caching of this block is disabled in config
                $cacheLifeTime   = null;
            }
        }

        if ( false !== $cacheLifeTime ) {
            /** @noinspection PhpUndefinedMethodInspection */
            $block->setCacheLifetime( $cacheLifeTime );
            if ( null !== $cacheLifeTime ) {
                /** @noinspection PhpUndefinedMethodInspection */
                $block->setCacheKey( implode('_', $cacheKeyData) );
                /** @noinspection PhpUndefinedMethodInspection */
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
            /** @noinspection PhpUndefinedMethodInspection */
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
                /** @noinspection PhpUndefinedMethodInspection */
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

    /**
     * Observer for cache flush logging, processes several types of cache flush events
     * @param Varien_Event_Observer $observer
     */
    public function cacheCleanEvent( $observer ) {
        if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/general/enable_flush_log') ) {

            $message = 'Cache flush.';
            if ( $event = $observer->getEvent() ) {
                $message .= '  Event:' . $event->getName();
            }

            $tags = $observer->getTags();
            if ( is_string($tags) ) {
                $tags = array( $tags );
            }

            if ( is_array($tags) ) {
                if ( empty($tags) || '' == trim(implode('',$tags)) ) {
                    $tags[] = '[ALL]';
                }
                $message .= '  Tags:' . implode(',', $tags);
            }

            if ( $type = $observer->getType() ) {
                $message .= '  Type:' . $type;
            }

            if ( $request = Mage::app()->getRequest() ) {
                if ( $action = $request->getActionName() ) {
                    $message .= '  Action:' . $request->getModuleName().'/'.$request->getControllerName().'/'.$action;
                } elseif( $pathInfo = $request->getPathInfo() ) {
                    $message .= ' PathInfo:' . $pathInfo;
                }
            }

            Mage::log( $message, Zend_Log::INFO, self::FLUSH_LOG_FILE );
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
        /** @noinspection PhpUndefinedMethodInspection */
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        $currentUrl = preg_replace('/(\?|&)(utm_source|utm_medium|utm_campaign|gclid|cx|ie|cof|siteurl)=[^&]+/ms','$1',$currentUrl);
        $currentUrl = str_replace('?&','?',$currentUrl);
        /** @noinspection PhpUndefinedMethodInspection */
        $result = array( $currentUrl, // covers secure, store code, url param, page nr
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
                         Mage_Core_Model_Store::CACHE_TAG );
        if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/general/enable_flush_translation_change') ) {
            $result[] = Mage_Core_Model_Translate::CACHE_TAG;
        }
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