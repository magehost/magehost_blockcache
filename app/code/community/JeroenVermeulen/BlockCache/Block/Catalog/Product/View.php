<?php
 
class JeroenVermeulen_BlockCache_Block_Catalog_Product_View extends Mage_Catalog_Block_Product_View {

    /**
     * Retrieve block cache tags based on category
     *
     * @return array
     */
    public function getCacheTags() {
        if ( Mage::getStoreConfigFlag('jeroenvermeulen_blockcache/product_detail/enable_flush_product_change') ) {
            return parent::getCacheTags();
        } else {
            return Mage_Core_Block_Template::getCacheTags();
        }
    }

}