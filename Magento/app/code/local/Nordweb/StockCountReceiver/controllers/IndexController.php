<!--<a href="http://dev.follestad.no/frontSystems/Index/GetProducts">GetProducts</a><br />
<a href="http://dev.follestad.no/frontSystems/Index/GetStockCountByProductID?id=186104">GetStockCountByProductID</a>-->

<?php


class Nordweb_StockCountReceiver_IndexController extends  Mage_Core_Controller_Front_Action {
 
    
    public function ReceiveStockCountPushAction()
    {
        Mage::log('Calling IndexController->ReceiveStockCountPushAction()');
        
        
       
        Mage::helper('stockcountreceiver')->ReceiveStockCountPush();

      
    }
    
     public function RegisteringForStockCountPushAction()
    {
        Mage::log('Calling IndexController->RegisteringForStockCountPushAction()');
        

        Mage::helper('stockcountreceiver')->RegisteringForStockCountPush();

      
    }


}



?>