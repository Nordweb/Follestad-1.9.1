<!--<a href="http://dev.follestad.no/frontSystems/Index/GetProducts">GetProducts</a><br />
<a href="http://dev.follestad.no/frontSystems/Index/GetStockCountByProductID?id=186104">GetStockCountByProductID</a>-->

<?php


class Nordweb_GetAllStockCounts_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action {
 
    
    public function GetAllStockCountsAction()
    {
        Mage::log('Calling IndexController->GetAllStockCountsAction()');
        
         $params =  $this->getRequest()->getParams();
         $key =  $params['key'];
       
        
       
        Mage::helper('getallstockcounts')->GetAllStockCounts();

        //http://www.dev.follestad.no/index.php/admin/catalog_product/index/key/217886b87872c975a203f1b71f721921/
       
        $returnURL =  Mage::getBaseUrl (Mage_Core_Model_Store::URL_TYPE_WEB) . 'index.php/admin/catalog_product/index/key/2e537bae675d54286f4a4363c3c5fc18/';
        
        Mage::log('IndexController: returnURL: ');
        Mage::log($returnURL);
        
        Mage::app()->getResponse()->setRedirect($returnURL);
    }


}



?>