<!--<a href="http://dev.follestad.no/frontSystems/Index/GetProducts">GetProducts</a><br />
<a href="http://dev.follestad.no/frontSystems/Index/GetStockCountByProductID?id=186104">GetStockCountByProductID</a>-->

<?php


class Nordweb_AddFSProducts_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action {
 
    
    public function GetProductsFromFSBySKUAction()
    {
        
        //Request SKU
       
         //$sku = $this->getRequest()->getParams('sku');
         //$id = $this->getRequest()->getParams('id');
         //$key = $this->getRequest()->getParams('key');
         $params =  $this->getRequest()->getParams();
          $sku = $params['sku'];
         $id = $params['id'];
         $key =  $params['key'];
         $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
         
          Mage::log($this->getRequest()->getParams());
        Mage::log('Controller: sku, id, key: ');
        Mage::log($product->sku);
        Mage::log($id);
        Mage::log($key);
       
        Mage::helper('addfsproducts')->GetProductsFromFSBySKU($product->sku);

        //http://www.dev.follestad.no/index.php/admin/catalog_product/edit/id/1319/key/d298d75526d9bd700e45c66d92792896/
       
        $returnURL =  Mage::getBaseUrl (Mage_Core_Model_Store::URL_TYPE_WEB) . 'index.php/admin/catalog_product/edit/id/' . $id . '/key/'. $key . '/';
        
        Mage::log('Controller: returnURL: ');
        Mage::log($returnURL);
        
        Mage::app()->getResponse()->setRedirect($returnURL);
    }


}



?>