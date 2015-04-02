<?php

class JeroenVermeulen_BlockCache_Adminhtml_Jv_BlockcacheController extends Mage_Adminhtml_Controller_Action
{
    // URL:  http://[MAGROOT]/admin/jv_blockcache/flush/key/###########/
    // If "storecode in url" is enabled there will be "/admin/admin" before "/jv_blockcache/"
    public function flushAction()
    {
        $this->loadLayout();
        $this->renderLayout();
        /**
         * Layout will be chosen by @see Mage_Core_Controller_Varien_Action::addActionLayoutHandles
         * Layout file:        /app/design/adminhtml/default/default/layout/JeroenVermeulen/BlockCache.xml
         * Item in that file:  adminhtml_jv_blockcache_flush
         */
        // Enable to debug layout XML:
        // header( 'Content-Type: text/xml' ); echo $this->getLayout()->getXmlString(); exit;
    }

    // URL:  http://[MAGROOT]/admin/jv_blockcache/flush_post/key/###########/
    // If "storecode in url" is enabled there will be "/admin/admin" before "/jv_blockcache/flush_post"
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
            $session->setData( 'JeroenVermeulen_BlockCache_PurgeURL', '' );
        } else {
            $flushUrl = $url;
            if ( false === strpos($flushUrl,'?') ) {
                $flushUrl .= '?jvflush';
            } else {
                $flushUrl .= '&jvflush';
            }
            Mage::log( sprintf( 'JeroenVermeulen_BlockCache_Adminhtml_Jv_BlockcacheController "%s"', $flushUrl ),
                Zend_Log::INFO,
                'system.log' );
            $httpClient = new Zend_Http_Client($flushUrl);
            $httpClient->setCookie( 'jvflush', '1' );
            $httpClient->setConfig( array('httpversion' => Zend_Http_Client::HTTP_0) );
            $response = $httpClient->request();
            
            $sLink = sprintf( '<a href="%s" target="_blank">%s</a>', $url, $url );

            if ( $response->isSuccessful() ) {
                $msg = $this->__( 'The URL %s has been flushed.', $sLink );
                Mage::log( $msg, Zend_Log::INFO );
                $session->addSuccess( $msg );
            } else {
                $msg = $this->__( 'Error flusing URL %s.', $sLink );
                Mage::log( $msg, Zend_Log::INFO );
                $session->addError( $msg );
            }

            $session->setData( 'JeroenVermeulen_BlockCache_FlushURL', $url );
        }
        $this->_redirect( '*/*/flush' ); // Redirect to index action
    }
}