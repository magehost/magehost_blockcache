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
 * @copyright    Copyright (c) 2015 Jeroen Vermeulen (http://www.jeroenvermeulen.eu)
 */

class JeroenVermeulen_BlockCache_Model_Observer extends Mage_Core_Model_Abstract
{
    const CONFIG_SECTION          = 'jeroenvermeulen_blockcache';
    const FLUSH_LOG_FILE          = 'cache_flush.log';
    const MISS_LOG_FILE           = 'cache_miss.log';
    const TAGS_LOG_FILE           = 'cache_tags.log';
    const BLOCK_GROUP_CATEGORY    = 'category_page';
    const BLOCK_GROUP_PRODUCT     = 'product_detail';
    const BLOCK_GROUP_CMS_PAGE    = 'cms_page';
    const BLOCK_GROUP_LAYERED_NAV = 'layered_navigation';
    const BLOCK_GROUP_CMS_BLOCK   = 'cms_block';
    const BLOCK_GROUP_CUSTOM_1    = 'custom_1';
    const BLOCK_GROUP_CUSTOM_2    = 'custom_2';
    const BLOCK_GROUP_CUSTOM_3    = 'custom_3';
    const BLOCK_GROUP_CUSTOM_4    = 'custom_4';
    const BLOCK_GROUP_CUSTOM_5    = 'custom_5';

    /** @var null|string */
    var $logSuffix = null;
    /** @var string */
    var $currentUrl = null;
    /** @var string */
    var $filterUrl = array();

    /**
     * Apply cache settings to block
     * @param Varien_Event_Observer $observer
     */
    function coreBlockAbstractToHtmlBefore( $observer ) {
        /** @var $block Mage_Core_Block_Abstract */
        /** @noinspection PhpUndefinedMethodInspection */
        $block         = $observer->getBlock();

        $this->applyCacheSettings( $block );

        /** @noinspection PhpUndefinedMethodInspection */
        $cacheKey = $block->getCacheKey();
        if ( $cacheKey ) {
            if ( $this->isFlushUrl() ) {
                Mage::app()->removeCache( $cacheKey );
            }
            elseif ( $this->isCacheWarmer() &&
                     2 == Mage::getStoreConfig(self::CONFIG_SECTION.'/cache_warmer/refresh_on_visit') ) {
                Mage::app()->removeCache( $cacheKey );
            }
            elseif ( 0 === strpos($cacheKey,'JV_') &&   // A block we manage caching for
                     $this->isCacheWarmer() &&
                     1 == Mage::getStoreConfig(self::CONFIG_SECTION.'/cache_warmer/refresh_on_visit') ) {
                Mage::app()->removeCache( $cacheKey );
            }
        }
    }

    /**
     * Apply cache settings to block
     * @param Mage_Core_Block_Abstract $block
     */
    function applyCacheSettings( &$block )
    {
        $store         = Mage::app()->getStore();
        $filterUrl     = $this->getFilterUrl();

        if ( ! Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/general/cache_when_url_param')
             && ( false !== strpos($filterUrl,'?') ) ) {
            // Caching of page with url param disabled in config, and this page has them.
            return;
        }

        if ( ! Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/general/cache_when_customer_logged_in')
             && Mage::getSingleton('customer/session')->isLoggedIn() ) {
            // Caching when customer logged in disabled in config, and customer is logged in.
            return;
        }

        $blockGroup = false;
        /** @noinspection PhpUndefinedClassInspection */
        if ($block instanceof Mage_Catalog_Block_Category_View ||
            $block instanceof JoomlArt_JmProducts_Block_List ) {
            $blockGroup = self::BLOCK_GROUP_CATEGORY;
        } elseif ($block instanceof Mage_Catalog_Block_Product_View) {
            $blockGroup = self::BLOCK_GROUP_PRODUCT;
        } /** @noinspection PhpUndefinedClassInspection */
        elseif ($block instanceof Mage_Catalog_Block_Layer_View ||
                $block instanceof Emico_Tweakwise_Block_Catalog_Layer_View ||
                $block instanceof Mana_Filters_Block_View) {
            $blockGroup = self::BLOCK_GROUP_LAYERED_NAV;
        } elseif ($block instanceof Mage_Cms_Block_Page) {
            $blockGroup = self::BLOCK_GROUP_CMS_PAGE;
        } elseif ($block instanceof Mage_Cms_Block_Block ||
                  $block instanceof Mage_Cms_Block_Widget_Block) {
            $blockGroup = self::BLOCK_GROUP_CMS_BLOCK;
        }
        for ( $c=1; $c<=5; $c++ ) {
            $matches = trim( Mage::getStoreConfig(self::CONFIG_SECTION.'/custom_'.$c.'/instanceof') );
            if ( $matches ) {
                $matches = explode("\n",$matches);
                foreach( $matches as $match ) {
                    $match = trim($match);
                    if ( empty($match) ) {
                        continue;
                    }
                    if ( false !== strpos($match,'/') ) {
                        $match = Mage::getConfig()->getBlockClassName($match);
                    }
                    if ( !empty($match) && $block instanceof $match ) {
                        $blockGroup = constant( 'self::BLOCK_GROUP_CUSTOM_'.$c );
                    }
                }
            }
        }
        if ( false === $blockGroup ) {
            // It is a block group we don't change caching for.
            return;
        }

        $cacheLifeTime = null;
        if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/'.$blockGroup.'/enable_cache') ) {
            $cacheLifeTime = intval(Mage::getStoreConfig(self::CONFIG_SECTION.'/'.$blockGroup.'/lifetime'));
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $block->setCacheLifetime( $cacheLifeTime );

        if ( $cacheLifeTime ) {
            $currentCategory = null;
            $currentProduct  = null;
            $cacheKey        = 'JV_';
            $cacheKeyData    = '';
            $cacheTags       = array();

            switch ($blockGroup) {

                case self::BLOCK_GROUP_CATEGORY:
                    $cacheKey .= 'CAT_';
                    $currentCategory = Mage::registry( 'current_category' );
                    // Add sorting & paging to cache key
                    $catalogSession = Mage::getSingleton( 'catalog/session' );
                    if ($catalogSession) {
                        /** @noinspection PhpUndefinedMethodInspection */
                        $cacheKeyData .= '|so' . strval( $catalogSession->getSortOrder() );
                        /** @noinspection PhpUndefinedMethodInspection */
                        $cacheKeyData .= '|sd' . strval( $catalogSession->getSortDirection() );
                        /** @noinspection PhpUndefinedMethodInspection */
                        $cacheKeyData .= '|dm' . strval( $catalogSession->getDisplayMode() );
                        /** @noinspection PhpUndefinedMethodInspection */
                        $cacheKeyData .= '|lp' . strval( $catalogSession->getLimitPage() );
                    }
                    break;

                case self::BLOCK_GROUP_PRODUCT:
                    $cacheKey .= 'PRD_';
                    $currentCategory = Mage::registry( 'current_category' );
                    $currentProduct  = Mage::registry( 'current_product' );
                    break;

                case self::BLOCK_GROUP_LAYERED_NAV:
                    $cacheKey      .= 'LNAV_';
                    $currentCategory = Mage::registry( 'current_category' );
                    break;

                case self::BLOCK_GROUP_CMS_PAGE:
                    $cacheKey .= 'CMSP_';
                    $cmsPage    = Mage::getSingleton( 'cms/page' );
                    if ($cmsPage instanceof Mage_Cms_Model_Page) {
                        $cacheTags[] = Mage_Cms_Model_Page::CACHE_TAG . '_' . $cmsPage->getId();
                        $cacheKey  .= 'P' . $cmsPage->getId() . '_';
                    }
                    break;

                case self::BLOCK_GROUP_CMS_BLOCK:
                    /** @noinspection PhpUndefinedMethodInspection */
                    $cacheKey .= 'CMSB_';
                    $cacheKeyData .= '|b' . $block->getBlockId(); // Example block_id: 'after_body_start'
                    $cacheTags[] = Mage_Cms_Model_Block::CACHE_TAG;
                    break;

                case self::BLOCK_GROUP_CUSTOM_1:
                case self::BLOCK_GROUP_CUSTOM_2:
                case self::BLOCK_GROUP_CUSTOM_3:
                case self::BLOCK_GROUP_CUSTOM_4:
                case self::BLOCK_GROUP_CUSTOM_5:
                    /** @noinspection PhpUndefinedMethodInspection */
                    // We don't know what it exactly is the user configured, so we throw everything in
                    $cacheKey .= strtoupper($blockGroup).'_';
                    $currentCategory = Mage::registry( 'current_category' );
                    $currentProduct  = Mage::registry( 'current_product' );
                    $allData = $block->getData();
                    unset( $allData['cache_lifetime'] );
                    $cacheKeyData .= '|D'.json_encode($allData,0,3); // All block data, 3 levels max
                    break;
            }
            if ($currentCategory instanceof Mage_Catalog_Model_Category) {
                $cacheKey .= 'C' . $currentCategory->getId();
            }
            if ($currentProduct instanceof Mage_Catalog_Model_Product) {
                $cacheKey .= 'P' . $currentProduct->getId();
            }
            $cacheKeyData .= $this->getBlockCacheKeyData( $block, $store, $currentCategory,
                $currentProduct );
            $cacheKey .= '_' . md5( $cacheKeyData );

            $this->addBlockCacheTags( $cacheTags, $currentCategory, $currentProduct );
            $cacheTags = array_unique( $cacheTags );

            /** @noinspection PhpUndefinedMethodInspection */
            $block->setCacheKey( $cacheKey );
            /** @noinspection PhpUndefinedMethodInspection */
            $block->setCacheTags( $cacheTags );

            if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/logging/tags') ) {
                $message = sprintf( 'Block %s Tags %s', $cacheKey, $this->logTags($cacheTags) );
                $message .= $this->getLogSuffix();
                Mage::log( $message, Zend_Log::INFO, self::TAGS_LOG_FILE );
            }
        }
    }

    /**
     * - Remove params for the Magento request we don't want to be cached inside blocks.
     * - Check if we should flush by URL tag.
     *
     * @param Varien_Event $observer
     */

    public function controllerFrontInitBefore( /** @noinspection PhpUnusedParameterInspection */ $observer ) {
        if ( ! $this->isAdmin() ) {
            $currentUrl = $this->getCurrentUrl();
            $filterUrl  = $this->getFilterUrl();

            // If URL contains jvflush param, clean all blocks with the URL tag from the cache
            if ( $this->isFlushUrl($currentUrl) ) {
                $cacheTag  = 'URL_' . md5( $filterUrl );
                Mage::app()->cleanCache( array( $cacheTag ) );
            }

            // We remove the params from the Magento request params, so they won't be used by Magento's getUrl(..)
            foreach (array( 'REQUEST_URI', 'REDIRECT_URL' ) as $field) {
                if (!empty( $_SERVER[ $field ] )) {
                    $_SERVER[ $field ] = $this->getFilterUrl( $_SERVER[ $field ] );
                }
            }
            foreach (array( 'QUERY_STRING', 'REDIRECT_QUERY_STRING' ) as $field) {
                if (!empty( $_SERVER[ $field ] )) {
                    $filterQueryString = $this->getFilterUrl( '?' . $_SERVER[ $field ] );
                    $_SERVER[ $field ] = ltrim( $filterQueryString, '?' );
                }
            }
            $removeParam = $this->getRemoveUrlParam();
            $request     = Mage::app()->getRequest();
            foreach ($removeParam as $key) {
                $request->setParam( $key, null );
                unset( $_GET[ $key ] );
                unset( $_POST[ $key ] );
                unset( $_REQUEST[ $key ] );
            }
            $request->setRequestUri( $this->getFilterUrl( $request->getRequestUri() ) );
            $request->setPathInfo();
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
        /** @noinspection PhpUndefinedMethodInspection */
        $transport = $observer->getTransport();
        /** @noinspection PhpUndefinedMethodInspection */
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
                if ( ! Mage::getStoreConfigFlag( self::CONFIG_SECTION . '/flushes/' . $filter ) ) {
                    $newTags = array();
                    foreach ($tags as $tag) {
                        if (0 !== stripos( $tag, $prefix . $filter )) {
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

        if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/logging/flushes') ) {
            $message = 'Cache flush.  Tags:' . $this->logTags($oldTags,$prefix);
            if ( $changed ) {
                $message .= '  AfterFilter:' . $this->logTags($tags,$prefix);
            }
            $message .= $this->getLogSuffix();
            Mage::log( $message, Zend_Log::INFO, self::FLUSH_LOG_FILE );
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $transport->setTags($tags);
    }

    /**
     * Event listener used to log cache misses
     * @param Varien_Event_Observer $observer
     */
    public function cacheMiss( $observer ) {
        $id = $observer->getId();
        if ( Mage::getStoreConfigFlag(self::CONFIG_SECTION.'/logging/misses') ) {
            $message = 'Cache miss.  Id:' . $id;
            $message .= $this->getLogSuffix();
            Mage::log( $message, Zend_Log::INFO, self::MISS_LOG_FILE );
        }
    }

    /**
     * Event listener used to add or remove cache tags
     * Listens to event "jv_cache_save_block"
     *
     * @param Varien_Event_Observer $observer
     */
    public function cacheSave( $observer ) {
        $prefix     = Mage::app()->getCacheInstance()->getFrontend()->getOption('cache_id_prefix');
        $pregPrefix = preg_quote($prefix,'|');
        $id         = $observer->getId();

        /** @noinspection PhpUndefinedMethodInspection */
        $transport = $observer->getTransport();
        /** @noinspection PhpUndefinedMethodInspection */
        $tags = $transport->getTags();

        /**
         * We need this extra cache tag to be able to flush per URL later on.
         * This is the only way because some cache keys or blocks can be session dependent.
         */
        $tags[] = $prefix.'URL_' . md5($this->getFilterUrl());

        if ( preg_match('|^'.$pregPrefix.'JV_PRD_|i',$id) ) {

            // Remove category tags if adding disabled
            $addCategoryTag = Mage::getStoreConfig(self::CONFIG_SECTION.'/'.self::BLOCK_GROUP_PRODUCT.'/add_category_tag');

            if ( ! is_null($addCategoryTag) && ! intval($addCategoryTag) ) {
                // Setting exists and is "Yes".

                foreach( $tags as $key => $value ) {
                    if ( preg_match('|^'.$pregPrefix.'CATALOG_CATEGORY|i', $value) ) {
                        unset( $tags[$key] );
                    }
                }
            }
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $transport->setTags($tags);
    }

    ////////////////////////////////////////////////////////////////////////////

    /**
     * @param Mage_Core_Block_Template $block
     * @param Mage_Core_Model_Store $store
     * @param Mage_Catalog_Model_Category|null $category
     * @param Mage_Catalog_Model_Product|null $product
     * @return string;
     */
    protected function getBlockCacheKeyData( $block, $store, $category=null, $product=null ) {
        /** @noinspection PhpUndefinedMethodInspection */
        $result = '|' . $this->getFilterUrl(); // covers secure, url param, page nr
        $result .= '|' . get_class( $block );
        $result .= '|' . $block->getTemplate();
        $result .= '|' . Mage::getSingleton('customer/session')->getCustomerGroupId();
        $result .= '|' . $store->getId();
        $result .= '|' . $store->getCurrentCurrencyCode();
        if ( $category instanceof Mage_Catalog_Model_Category ) {
            $result .= '|c' . $category->getId();
        }
        if ( $product instanceof Mage_Catalog_Model_Product ) {
            $result .= '|p' . $product->getId();
        }
        return $result;
    }

    /**
     * @return string
     */
    protected function getCurrentUrl() {
        if ( empty( $this->currentUrl ) ) {
            if ( version_compare( Mage::getVersion(), '1.4.2', '>=' ) ) {
                $this->currentUrl = Mage::helper('core/url')->getCurrentUrl();
            } else {
                // The getCurrentUrl() of Magento older then 1.4.2 is a bit too complicated to work during early events.
                // Warning: this fix does not guarantee the whole extension will work in Magento pre 1.6.2, use at own risk.
                $request = Mage::app()->getRequest();
                $port = $request->getServer('SERVER_PORT');
                if ($port) {
                    $port = ($port==80 || $port==443) ? '' : ':' . $port;
                }
                $this->currentUrl = $request->getScheme() . '://' . $request->getHttpHost() . $port . $request->getServer('REQUEST_URI');
            }
        }
        return $this->currentUrl;
    }

    /**
     * @param string|null $url - URL to clean
     * @return string
     */
    protected function getFilterUrl( $url=null ) {
        if ( empty($this->filterUrl[$url]) ) {
            $filterUrl = is_null($url) ? $this->getCurrentUrl() : $url;
            $removeParam = $this->getRemoveUrlParam();
            foreach ( $removeParam as $param ) {
                $filterUrl = preg_replace('/(\?|&)'.preg_quote($param,'/').'\b(=[^&]+)?/s','$1',$filterUrl);
            }
            $filterUrl = preg_replace('/\?&+/','?',$filterUrl);
            $filterUrl = preg_replace('/\&{2,}/','&',$filterUrl);
            $filterUrl = preg_replace('/[\?&]+$/','',$filterUrl);
            $this->filterUrl[$url] = $filterUrl;
        }
        return $this->filterUrl[$url];
    }

    /**
     * @param array $cacheTags
     * @param Mage_Catalog_Model_Category|null $category
     * @param Mage_Catalog_Model_Product|null $product
     */
    protected function addBlockCacheTags( &$cacheTags, $category=null, $product=null ) {
        $cacheTags[] = Mage_Core_Block_Abstract::CACHE_GROUP;
        $cacheTags[] = Mage_Core_Model_Translate::CACHE_TAG;
        if ( $category instanceof Mage_Catalog_Model_Category ) {
            $addTags = $category->getCacheIdTags();
            if ( is_array($addTags) ) {
                foreach( $addTags as $tag) { // a little faster then array_merge
                    $cacheTags[] = $tag;
                }
            }
        }
        if ( $product instanceof Mage_Catalog_Model_Product ) {
            $cacheTags[] = Mage_Catalog_Model_Product::CACHE_TAG.'_'.$product->getId();
            $addTags = $product->getCacheIdTags();
            if ( is_array($addTags) ) {
                foreach ($addTags as $tag) { // a little faster then array_merge
                    $cacheTags[ ] = $tag;
                }
            }
        }
    }

    /**
     * Form array of tags for output in log.
     * @param array $tags
     * @param string $prefix
     * @return string
     */
    protected function logTags( $tags, $prefix='' ) {
        if ( empty($tags) ) {
            return '-empty-';
        } else {
            if ( $prefix ) {
                $preg      = '/^' . preg_quote( $prefix, '/' ) . '/';
                $cleanTags = array();
                foreach ($tags as $tag) {
                    $cleanTags[ ] = preg_replace( $preg, '', $tag );
                }
            } else {
                $cleanTags = $tags;
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

    /**
     * @return bool
     */
    protected function isCacheWarmer() {
        $cacheWarmerUserAgent = Mage::getStoreConfig(self::CONFIG_SECTION.'/cache_warmer/user_agent');
        return ( !empty($cacheWarmerUserAgent) &&
                 !empty($_SERVER['HTTP_USER_AGENT']) &&
                 false !== strpos($_SERVER['HTTP_USER_AGENT'],$cacheWarmerUserAgent) );
    }

    /**
     * @param  string|null $url
     * @return bool
     */
    protected function isFlushUrl( $url = null ) {
        if ( is_null($url) ) {
            $url = $this->getCurrentUrl();
        }
        return ( preg_match( '/\?.*jvflush/', $url ) || !empty( $_COOKIE['jvflush'] ) );
    }

    protected function getRemoveUrlParam() {
        $configParam = explode( "\n", trim( Mage::getStoreConfig(self::CONFIG_SECTION.'/general/remove_url_param') ) );
        $result = array('jvflush');
        foreach( $configParam as $param ) {
            $param = trim($param);
            if ( !empty($param) ) {
                $result[] = $param;
            }
        }
        return $result;
    }

    protected function isAdmin( $url=null ) {
        if ( is_null($url) ) {
            $url = Mage::app()->getRequest()->getPathInfo();
        }
        $adminFrontName = strval( Mage::getConfig()->getNode( 'admin/routers/adminhtml/args/frontName' ) );
        return ( false !== strpos( $url, '/' . $adminFrontName . '/' ) );
    }
}