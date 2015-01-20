<?php
 
class JeroenVermeulen_BlockCache_Block_Discount_Catalog_Product_View_Tierprice extends Anylamp_Discount_Block_Catalog_Product_View_Tierprice {

    public function getCacheTags() {
        if ( Mage::getStoreConfigFlag('jeroenvermeulen_blockcache/product_detail/enable_flush_product_change') ) {
            return parent::getCacheTags();
        } else {
            return Mage_Core_Block_Template::getCacheTags();
        }
    }

}