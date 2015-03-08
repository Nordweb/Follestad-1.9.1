<!--<a href="http://dev.follestad.no/frontSystems/Index/GetProducts">GetProducts</a><br />
<a href="http://dev.follestad.no/frontSystems/Index/GetStockCountByProductID?id=186104">GetStockCountByProductID</a>-->

<?php


class Nordweb_AddFSProducts_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action {
 
    
    public function GetProductsFromFSBySKUAction()
    {
        
        //Request SKU
       
         $sku = $this->getRequest()->getParams('sku');
         $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
         
        Mage::log('Controller: SKU from Magento is: ');
        Mage::log($product->sku);
       
        Mage::helper('addfsproducts')->GetProductsFromFSBySKU($product->sku);

    }


}



?>