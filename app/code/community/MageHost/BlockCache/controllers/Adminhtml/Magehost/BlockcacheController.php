<?php

class MageHost_BlockCache_Adminhtml_Magehost_BlockcacheController extends Mage_Adminhtml_Controller_Action
{
    // URL:  http://[MAGROOT]/admin/magehost_blockcache/flush/key/###########/
    // If "storecode in url" is enabled there will be "/admin/admin" before "/magehost_blockcache/"
    public function flushAction()
    {
        $this->loadLayout();
        $this->renderLayout();
        /**
         * Layout will be chosen by @see Mage_Core_Controller_Varien_Action::addActionLayoutHandles
         * Layout file:        /app/design/adminhtml/default/default/layout/magehost/blockcache.xml
         * Item in that file:  adminhtml_magehost_blockcache_flush
         */
        // Enable to debug layout XML:
        // header( 'Content-Type: text/xml' ); echo $this->getLayout()->getXmlString(); exit;
    }

    // URL:  http://[MAGROOT]/admin/magehost_blockcache/flush_post/key/###########/
    // If "storecode in url" is enabled there will be "/admin/admin" before "/magehost_blockcache/flush_post"
    public function flush_postAction()
    {
        $session     = Mage::getSingleton( 'core/session' );
        $post        = $this->getRequest()->getPost();
        $url         = trim( $post['flush_form']['url'] );
        if ( !preg_match('|https?://|',$url) ) {
            $session->addError( $this->__('Please use a full URL, start with "http://" or "https://"') );
        }
        $urlParts = parse_url( $url );
        if ( false === $urlParts || empty( $urlParts['host'] ) ) {
            $session->addError( $this->__('Invalid URL: <b>%s</b>.',$url) );
            $session->setData( 'MageHost_BlockCache_PurgeURL', '' );
        } else {
            $flushUrl = $url;
            if ( false === strpos($flushUrl,'?') ) {
                $flushUrl .= '?mhflush';
            } else {
                $flushUrl .= '&mhflush';
            }
            Mage::log( sprintf( '%s::%s "%s"', __CLASS__, __FUNCTION__, $flushUrl ),
                Zend_Log::INFO,
                'system.log' );
            $httpClient = new Zend_Http_Client($flushUrl);
            $httpClient->setConfig( array( 'timeout' => 60 ) );
            $httpClient->setCookie( 'mhflush', '1' );
            $httpClient->setConfig( array('httpversion' => Zend_Http_Client::HTTP_0) );
            $response = $httpClient->request();

            /** @noinspection HtmlUnknownTarget */
            $sLink = sprintf( '<a href="%s" target="_blank">%s</a>', $url, $url );

            if ( $response->isSuccessful() ) {
                $msg = $this->__( 'The URL %s has been flushed.', $sLink );
                Mage::log( $msg, Zend_Log::INFO );
                $session->addSuccess( $msg );
            } else {
                $msg = $this->__( 'Error flushing URL %s.', $sLink );
                Mage::log( $msg, Zend_Log::INFO );
                $session->addError( $msg );
            }

            $session->setData( 'MageHost_BlockCache_FlushURL', $url );
        }
        $this->_redirect( '*/*/flush' ); // Redirect to index action
    }
}