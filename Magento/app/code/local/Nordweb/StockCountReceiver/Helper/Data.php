<?php

//umask(0);
//require 'app/Mage.php';


class Nordweb_StockCountReceiver_Helper_Data extends Mage_Core_Helper_Abstract {


    public function ReceiveStockCountPush()
    {
   
        Mage::log(' ');
        Mage::log(' ');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('=================== RECEIVING StockCountPush =================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
   
        Mage::log('Calling Data->ReceiveStockCountPush()');
      
       
        
        //{"StockCounts":[
        //{"Identity":"007078352208","Qty":-1,"StockID":31},{"Identity":"007044541807","Qty":-1,"StockID":172},
        //{"Identity":"006470290209","Qty":-1,"StockID":31},{"Identity":"008389731606","Qty":-1,"StockID":37},
        //{"Identity":"004156070205","Qty":-1,"StockID":31},{"Identity":"004156070209","Qty":-2,"StockID":31},
        //{"Identity":"006823210811","Qty":-1,"StockID":37},{"Identity":"006817930100","Qty":-1,"StockID":37},
        //{"Identity":"007044441807","Qty":-1,"StockID":172},{"Identity":"006817320209","Qty":-1,"StockID":31},
        //{"Identity":"007044521809","Qty":-1,"StockID":37},{"Identity":"004274530205","Qty":-1,"StockID":31},
        //{"Identity":"008063042212","Qty":-1,"StockID":31}],
        //"Time":"\/Date(1421428208000+0100)\/"}}
        
       
         
        // read JSon input
        $data_back = json_decode(file_get_contents('php://input'));
        Mage::log($data_back);
        
        // set json string to php variables
        $dateTime = $data_back->{"Time"};
        $stockCounts = $data_back->{"StockCounts"};
 

        Mage::log($dateTime);
        Mage::log($stockCounts);
        
        
         
        $allStockCountChangesArray = array();
        if(isset($stockCounts->StockCounts->StockCount) && is_array($stockCounts->StockCounts->StockCount))
        {
            Mage::log('$stockCounts->StockCounts->StockCount');
            Mage::log($stockCounts->StockCounts->StockCount);
            foreach ($stockCounts->StockCounts->StockCount as $stockCount) 
            {
                array_push( $allStockCountChangesArray, $stockCount);
            }
                   
        }
        else if(isset($stockCounts->StockCounts) && is_array($stockCounts->StockCounts))
        {
            Mage::log('$stockCounts->StockCounts');
            Mage::log($stockCounts->StockCounts);
            foreach ($stockCounts->StockCounts as $stockCount) 
            {
                array_push( $allStockCountChangesArray, $stockCount);
            }
                   
        }
        else if (isset($stockCounts) && is_array($stockCounts))
        {
            Mage::log('$stockCounts');
            Mage::log($stockCounts);
            foreach ($stockCounts as $stockCount) 
            {
                array_push( $allStockCountChangesArray, $stockCount);
            }
        }
        else //It's an object
        {
            Mage::log('$stockCounts');
            Mage::log($stockCounts);
            array_push( $allStockCountChangesArray, $stockCounts);   
        }
        

        Mage::log('$allStockCountChangesArray: ');
        Mage::log($allStockCountChangesArray);
        
        Mage::log(count($allStockCountChangesArray) . ' stockchanges from Front Systems coming in this push message');
       
        $UpdateStockNotReplace = true;
        $count = 0;
        foreach ($allStockCountChangesArray as $stockCount) 
        {
            if(!isset($stockCount->Identity))
                continue;
            $oneStockCountChangesArray = array();
            array_push($oneStockCountChangesArray, $stockCount);
            
            Mage::log('$stockCount->Identity');
            Mage::log($stockCount->Identity);
            Mage::log('$oneStockCountChangesArray');
            Mage::log($oneStockCountChangesArray);
            
            Mage::helper('getallstockcounts')->UpdateStockCountsForThisProduct($stockCount->Identity, $oneStockCountChangesArray, $UpdateStockNotReplace);
            $count = $count +1;
        }
        
        
         Mage::log('Updated ' . $count . ' products from a push message');
        
       
        
     
    }
    
    
     public function RegisteringForStockCountPush()
    {
   
        Mage::log(' ');
        Mage::log(' ');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('================ REGISTERING for StockCountPush ===============');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
   
        Mage::log('Calling Data->RegisteringForStockCountPush()');
      
        $url = Mage::getBaseUrl (Mage_Core_Model_Store::URL_TYPE_WEB) . 'stockCountReceiver/Index/ReceiveStockCountPush';
    
        //auth
        Mage::log('Calling frontSystems->AuthenticateFS()');
        $returnValues = Mage::helper('addfsproducts')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        Mage::log('Front Systems Client authenticated');
        
        
        $errorMsg = Mage::getStoreConfig('nordweb/nordweb_group/feilmeldingbruker_input',Mage::app()->getStore());
        
       
        
        //registerStockCountNotification
        Mage::log('Calling frontSystems->registerStockCountNotification()');
        $retval = $clientAuthenticated->registerStockCountNotification(array('key'=>$fsKey, 'fromDate'=>strtotime('now'), 'URL'=>$url));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
             Mage::throwException($errorMsg . '"<i>' . $retval->faultstring . '</i>"<br/><br/>' );
        }
        
        Mage::log('Response: ');     
        Mage::log($retval);
        
        $registerStockCountNotification = $retval->registerStockCountNotificationResult;
        Mage::log('Result: ');     
        Mage::log($registerStockCountNotification);
     
    }
    
}?>