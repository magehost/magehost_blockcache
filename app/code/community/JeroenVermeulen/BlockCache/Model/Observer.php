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
    const BLOCK_CACHE_TAG = 'block_html';
    const FLUSH_LOG_FILE  = 'cache_flush.log';
    const MISS_LOG_FILE   = 'cache_miss.log';
    /** @var null|string */
    var $logSuffix = null;
    var $filterUrlCache = array();

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
        $store         = Mage::app()->getStore();
        $keyPrefix     = 'JV_'; // We use this to make the file names a little less cryptic
        $cacheWarmerUserAgent = Mage::getStoreConfig(self::CONFIG_SECTION.'/cache_warmer/user_agent');
        $isCacheWarmer = ( !empty($cacheWarmerUserAgent) &&
                           false !== strpos($_SERVER['HTTP_USER_AGENT'],$cacheWarmerUserAgent) );
        if ( $block instanceof Mage_Catalog_Block_Category_View ) {
            if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/category_page/enable_cache') ) {
                $hasParam = ( false !== strpos( $this->filterUrl(), '?' ) );
                if ( $hasParam && ! Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/category_page/cache_when_url_param') ) {
                    // Caching of category with url param disabled in config
                    $cacheLifeTime   = null;
                } else {
                    $currentCategory = Mage::registry('current_category');
                    $cacheKeyData    = $this->getBlockCacheKeyData( $block, $store, $currentCategory );
                    $cacheTags       = $this->getBlockCacheTags( $currentCategory );
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
                    if ( $currentCategory instanceof Mage_Catalog_Model_Category ) {
                        $keyPrefix .= 'CAT'.$currentCategory->getId().'_';
                    }
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
                $cacheKeyData    = $this->getBlockCacheKeyData( $block, $store, $currentCategory, $currentProduct );
                $cacheTags       = $this->getBlockCacheTags( $currentCategory, $currentProduct );
                $cacheLifeTime   = intval(Mage::getStoreConfig(self::CONFIG_SECTION.'/product_detail/lifetime'));
                if ( $currentCategory instanceof Mage_Catalog_Model_Category ) {
                    $keyPrefix .= 'CAT'.$currentCategory->getId().'_';
                }
                if ( $currentProduct instanceof Mage_Catalog_Model_Product ) {
                    $keyPrefix .= 'PRD'.$currentProduct->getId().'_';
                }
            } else {
                // Caching of this block is disabled in config
                $cacheLifeTime   = null;
            }
        }
        elseif ( $block instanceof Mage_Cms_Block_Page ) {
            if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/cms_page/enable_cache') ) {
                $cacheKeyData = $this->getBlockCacheKeyData( $block, $store );
                $cacheTags    = $this->getBlockCacheTags();
                $cmsPage      = Mage::getSingleton( 'cms/page' );
                if ( $cmsPage instanceof Mage_Cms_Model_Page ) {
                    $cacheTags[] = Mage_Cms_Model_Page::CACHE_TAG . '_' . $cmsPage->getId();
                    $keyPrefix .= 'CMSP'.$cmsPage->getId().'_';
                }
                $cacheLifeTime = intval( Mage::getStoreConfig( self::CONFIG_SECTION . '/cms_page/lifetime' ) );
            }
        }
        elseif ( $block instanceof Mage_Cms_Block_Block || $block instanceof Mage_Cms_Block_Widget_Block ) {
            if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/cms_block/enable_cache') ) {
                $cacheKeyData   = $this->getBlockCacheKeyData( $block, $store );
                $cacheKeyData[] = $block->getBlockId();
                $cacheTags      = $this->getBlockCacheTags();
                $cacheTags[] = Mage_Cms_Model_Block::CACHE_TAG;
                $cacheLifeTime  = intval( Mage::getStoreConfig( self::CONFIG_SECTION . '/cms_block/lifetime' ) );
                $keyPrefix .= 'CMSB_';
            }
        }
        if ( false !== $cacheLifeTime ) {
            $cacheKey = $keyPrefix . md5( implode('|', $cacheKeyData) );
            if ( $isCacheWarmer && Mage::getStoreConfig(self::CONFIG_SECTION.'/cache_warmer/refresh_on_visit') ) {
                Mage::app()->removeCache( $cacheKey );
            }
            /** @noinspection PhpUndefinedMethodInspection */
            $block->setCacheLifetime( $cacheLifeTime );
            if ( null !== $cacheLifeTime ) {
                /** @noinspection PhpUndefinedMethodInspection */
                $block->setCacheKey( $cacheKey );
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
     * Event listener to filter cache flushes
     * @param Varien_Event_Observer $observer
     */
    public function cleanBackendCache( $observer ) {
        $transport = $observer->getTransport();
        $tags = $transport->getTags();
        $prefix = Mage::app()->getCacheInstance()->getFrontend()->getOption('cache_id_prefix');
        $oldTags = $tags;
        $doFilter = true;
        $changed = false;

        if ( $request = Mage::app()->getRequest() ) {
            if ('adminhtml' == $request->getRouteName() &&'cache' == $request->getControllerName()) {
                // We will always allow System > Cache Management
                $doFilter = false;
            }
        }
        if ( !empty($_SERVER['SCRIPT_FILENAME']) ) {
            $baseScript = basename($_SERVER['SCRIPT_FILENAME']);
            if ( 'n98-magerun.phar' == $baseScript || 'n98'  == $baseScript ) {
                // We will always allow N98 Magerun
                $doFilter = false;
            }
        }

        if ( $doFilter ) {
            if ( empty($tags) && ! Mage::getStoreConfigFlag( self::CONFIG_SECTION . '/flushes/_without_tags' )) {
                $changed = true; // so we will check if empty later on
            }
            $filters = array( 'catalog_product',
                              'catalog_category',
                              'cms_page',
                              'cms_block',
                              'translate',
                              'store',
                              'website',
                              'block_html',
                              'mage' );
            foreach ($filters as $filter) {
                $filterTag = strtoupper( $filter );
                if ( ! Mage::getStoreConfigFlag( self::CONFIG_SECTION . '/flushes/' . $filter ) ) {
                    $newTags = array();
                    foreach ($tags as $tag) {
                        if (0 !== strpos( $tag, $prefix . $filterTag )) {
                            $newTags[ ] = $tag;
                        } else {
                            $changed = true;
                        }
                    }
                    $tags = $newTags;
                }
            }
            if ( $changed && empty($tags) ) {
                $tags[] = 'JV_DUMMY_TAG';
            }
        }

        if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/flushes/_log') ) {
            $message = 'Cache flush.  Tags:' . $this->logTags($oldTags,$prefix);
            if ( $changed ) {
                $message .= '  AfterFilter:' . $this->logTags($tags,$prefix);
            }
            $message .= $this->getLogSuffix();
            Mage::log( $message, Zend_Log::INFO, self::FLUSH_LOG_FILE );
        }

        $transport->setTags($tags);
    }

    /**
     * Event listener used to log cache misses
     * @param Varien_Event_Observer $observer
     */
    public function cacheMiss( $observer ) {
        $id = $observer->getId();
        if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/general/log_miss') ) {
            $message = 'Cache miss.  Id:' . $id;
            $message .= $this->getLogSuffix();
            Mage::log( $message, Zend_Log::INFO, self::MISS_LOG_FILE );
        }
    }

    ////////////////////////////////////////////////////////////////////////////

    /**
     * @param Mage_Core_Block_Template $block
     * @param Mage_Core_Model_Store $store
     * @param Mage_Catalog_Model_Category|null $category
     * @param Mage_Catalog_Model_Product|null $product
     * @return array;
     */
    protected function getBlockCacheKeyData( $block, $store, $category=null, $product=null ) {
        /** @noinspection PhpUndefinedMethodInspection */
        $currentUrl = $this->filterUrl();
        /** @noinspection PhpUndefinedMethodInspection */
        $result = array( $currentUrl, // covers secure, store code, url param, page nr
                         get_class( $block ),
                         $block->getTemplate(),
                         Mage::getSingleton('customer/session')->getCustomerGroupId(),
                         $store->getCurrentCurrencyCode() );
        if ( $category instanceof Mage_Catalog_Model_Category ) {
            $result[] = 'c'.$category->getId();
        }
        if ( $product instanceof Mage_Catalog_Model_Product ) {
            $result[] = 'p'.$product->getId();
        }
        return $result;
    }

    protected function filterUrl( $url=null ) {
        if ( is_null($url) ) {
            $url = Mage::helper('core/url')->getCurrentUrl();
        }
        if ( !isset($this->filterUrlCache[$url]) ) {
            $filterUrl = $url;
            $filterUrl = preg_replace('/(\?|&)(utm_source|utm_medium|utm_campaign|gclid|cx|ie|cof|siteurl)=[^&]+/ms','$1',$filterUrl);
            $filterUrl = preg_replace('/\?&+/','?',$filterUrl);
            $filterUrl = preg_replace('/\&{2,}/','&',$filterUrl);
            $filterUrl = preg_replace('/\?$/','',$filterUrl);
            $this->filterUrlCache[$url] = $filterUrl;
        }
        return $this->filterUrlCache[$url];
    }

    /**
     * @param Mage_Catalog_Model_Category|null $category
     * @param Mage_Catalog_Model_Product|null $product
     * @return array;
     */
    protected function getBlockCacheTags( $category=null, $product=null ) {
        $result = array( self::BLOCK_CACHE_TAG );
        $result[] = Mage_Core_Model_Translate::CACHE_TAG;
        if ( $category instanceof Mage_Catalog_Model_Category ) {
            $result = array_merge( $result, $category->getCacheIdTags() );
        }
        if ( $product instanceof Mage_Catalog_Model_Product ) {
            $result[] = Mage_Catalog_Model_Product::CACHE_TAG.'_'.$product->getId();
            $result = array_merge( $result, $product->getCacheIdTags() );
        }
        return $result;
    }

    /**
     * Form array of tags for output in log.
     * @param array $tags
     * @param string $prefix
     * @return string
     */
    protected function logTags( $tags, $prefix ) {
        if ( empty($tags) ) {
            return '-empty-';
        } else {
            $preg = '/^'.preg_quote($prefix,'/').'/';
            $cleanTags = array();
            foreach ( $tags as $tag ) {
                $cleanTags[] = preg_replace( $preg, '', $tag );
            }
            return implode(',', $cleanTags);
        }
    }

    /**
     * Get a suffix string to add to logging lines which tells what is happening
     * @return string
     */
    protected function getLogSuffix()
    {
        if (is_null( $this->logSuffix )) {
            if ($request = Mage::app()->getRequest()) {
                if ($action = $request->getActionName()) {
                    $this->logSuffix .= '  Action:' . $request->getModuleName() . '/' . $request->getControllerName(
                        ) . '/' . $action;
                } elseif ($pathInfo = $request->getPathInfo()) {
                    $this->logSuffix .= '  PathInfo:' . $pathInfo;
                }
            }
        }
        if (is_null( $this->logSuffix )) {
            if (!empty( $_SERVER[ 'argv' ] )) {
                $this->logSuffix .= '  CommandLine:' . implode( ' ', $_SERVER[ 'argv' ] );
            }
        }
        return strval($this->logSuffix);
    }
}