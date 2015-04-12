<?php

//umask(0);
//require 'app/Mage.php';


class Nordweb_GetAllStockCounts_Helper_Data extends Mage_Core_Helper_Abstract {


    public function GetAllStockCounts()
    {
   
        Mage::log(' ');
        Mage::log(' ');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('================ STARTING NEW GET ALL STOCKCOUNTS ===============');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
   
        Mage::log('Calling Data->GetAllStockCounts()');
        
     
    
        //auth
        Mage::log('Calling frontSystems->AuthenticateFS()');
        $returnValues = Mage::helper('addfsproducts')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        Mage::log('Front Systems Client authenticated');
        
        $errorMsg = Mage::getStoreConfig('nordweb/nordweb_group/feilmeldingbruker_input',Mage::app()->getStore());
       
       
        
        //GetStockCount
        Mage::log('Calling frontSystems->GetStockCount()');
        $retval = $clientAuthenticated->GetStockCount(array('key'=>$fsKey));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
            Mage::throwException($errorMsg . '"<i>' . $retval->faultstring . '</i>"<br/><br/>' );
        }
        $allStockCountsFromFrontSystems = $retval->GetStockCountResult;
        Mage::log('Collected ' . count($allStockCountsFromFrontSystems->StockCount) . ' StockCounts' );
        

        //Match & Store in Magento
        $this->UpdateStockCountsForAllProducts($allStockCountsFromFrontSystems);
        
        
        
        //Now registering for StockCountPush immediately
        Mage::helper('stockcountreceiver')->RegisteringForStockCountPush();
        
      
     
    }
    
    public function AuthenticateFS()
    {
       

        //Declare some paramaters for our soapclient and create it.
        $headerParams  = array("soap_version"=> SOAP_1_1,
                        "trace"=>1,
                        "exceptions"=>0,
                         "soap_defencoding"=>'UTF-8');
        
        
        
        $url = Mage::getStoreConfig('nordweb/nordweb_group/frontsystemsapi_input',Mage::app()->getStore());
        $user = Mage::getStoreConfig('nordweb/nordweb_group/apiuser_input',Mage::app()->getStore());
        $pwd = Mage::getStoreConfig('nordweb/nordweb_group/apipwd_input',Mage::app()->getStore());
        
        $client = new SoapClient($url,$headerParams);
        $retval = $client->Logon(array('username'=>$user, 'password'=>$pwd));
        
        $errorMsg = Mage::getStoreConfig('nordweb/nordweb_group/feilmeldingbruker_input',Mage::app()->getStore());
       
        
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
             Mage::throwException($errorMsg . '"<i>' . $retval->faultstring . '</i>"<br/><br/>' );
        }
        $fsKey = $retval->LogonResult;

        //Declare some paramaters for our soapclient and create it.
        $headerParamsAuth  = array("soap_version"=> SOAP_1_1,
                       "trace"=>1,
                       "exceptions"=>0,
                        "soap_defencoding"=>'UTF-8',
                        'key'=>$fsKey,
                       );
        $clientAuthenticated = new SoapClient('https://dinbutikkdev.frontsystems.no/webshop/WebshopIntegration.svc?wsdl',$headerParamsAuth);
        return array ($clientAuthenticated, $fsKey);
    }
    

    public function UpdateStockCountsForAllProducts($allStockCountsFromFrontSystems)
    {
    
         try {
     
            Mage::log('Calling Data->UpdateStockCountsForAllProducts()');
        
       
           
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
           
            $allProductIds = Mage::getModel('catalog/product')->getCollection()->getAllIdsCache();
           
            
            Mage::log('Got all Products from Magento, count:' . count($allProductIds));
            
           
          
            //Getting all simple products that directly match Identity of stockcount. 
            //Also update parent-configurable to be "på lager"
            $count = 0;
            
            $stockIDsToOmitArray = Mage::helper('addfsproducts')->GetStockIDsToOmit();
            
            $UpdateStockNotReplace = false; //GetAllStockCount is a full replace, unlike Push notifications
           
            foreach ($allProductIds as $oneMagentoProductID) 
            {   
                //Mage::log('checking magento product ' .  $oneMagentoProductID);
                $oneMagentoProduct = Mage::getModel('catalog/product')->load($oneMagentoProductID);
                $allFSStockCountForThisProduct = array();
                
                //Collect FS-stockcounts for this Sku/Identity in FS
                foreach ($allStockCountsFromFrontSystems->StockCount as $oneStockCount)
                {
                
                    //Ommit "Ordre_"-stocks
                    if (in_array($oneStockCount->StockID, $stockIDsToOmitArray))
                        continue;

                    //math on simple product
                    if($oneMagentoProduct->getTypeId() == "simple" && $oneMagentoProduct->Sku == $oneStockCount->Identity) 
                    {
                        Mage::log('Found a stockCount @ store with ID ' . $oneStockCount->StockID);
                        array_push( $allFSStockCountForThisProduct, $oneStockCount); 
                    }
                    
                  
                   
                }
                
                //Mage::log('Gone through all stockcounts for this product');
                     
                if( isset($allFSStockCountForThisProduct) && count($allFSStockCountForThisProduct) > 0)
                {
                     
                     Mage::log('Collected ' . count($allFSStockCountForThisProduct) . ' stockcounts for FS-Product with Identity containing Sku: ' . $oneMagentoProduct->Sku);
               
                     $this->UpdateStockCountsForThisProduct($oneMagentoProduct->Sku, $allFSStockCountForThisProduct, $UpdateStockNotReplace);
                     
                      Mage::log('Updated stockcounts for this product, clearing variables');
                      $count = $count + 1;
                }
                
                 //Mage::log('After possible update of this product, clearing variables');
                
    
                //clear memory
                unset($oneMagentoProduct);
                unset($allFSStockCountForThisProduct);
              
                
            }
            
             Mage::log('Updated ' . $count . ' stockcounts with this call to GetAllStockCounts');
            
            //$allProductIDsWithNoParentAndChildren->clearInstance();
            //$allWebProductsFromFrontSystems->clearInstance();
            
            Mage::getResourceSingleton('cataloginventory/stock')->updateSetOutOfStock();
            Mage::getModel('index/process')->load(9)->reindexEverything();

         }
         catch(Exception $e)
         {
                Mage::log($e->getMessage());
                
                $errorMsg = Mage::getStoreConfig('nordweb/nordweb_group/feilmeldingbruker_input',Mage::app()->getStore());
        Mage::throwException($errorMsg . '"<i>' . $e->getMessage() . '</i>"<br/><br/>' );
                
               
         }
        
           
    }
    
     public function UpdateStockCountsForThisProduct($SKUOfProduct, $allFSStockCountForThisProduct, $UpdateStockNotReplace)
    {
    
     try {
     
        Mage::log('Calling Data->UpdateStockCountsForThisProduct()');
            
       
        //Get configurable product
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $simpleProductInMagento = Mage::getModel('catalog/product')->loadByAttribute('sku', $SKUOfProduct);
        
        //Mage::log('$simpleProductInMagento:');
        //Mage::log(get_object_vars($simpleProductInMagento));
        
       
        
         //Sum all stockcounts
        //Mage::log('Summarizing all stockCounts');
        $stockCount = 0; //identity, stockcount
        
        $allFSStockCountForThisProductArray = array();
        if(!is_array($allFSStockCountForThisProduct))
        {
            array_push( $allFSStockCountForThisProductArray, $allFSStockCountForThisProduct);
        }
        else
        {
            $allFSStockCountForThisProductArray = $allFSStockCountForThisProduct;
        }
        
        foreach ($allFSStockCountForThisProductArray as $oneStockCount) 
        {
            
            $stockCount = $stockCount + $oneStockCount->Qty;
     
        }
        
        $qty = 0;
        if($UpdateStockNotReplace) //StockItem should exist, only add/subtract stockcount, not replace
        {
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($simpleProductInMagento->getId());
            $qty = $stockItem->getQty() + $stockCount;
            $stockItem->setData('qty', $qty);
           
            
        }
        else
        {
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($simpleProductInMagento->getId());
            $qty = $stockCount;
            $stockItem->setData('qty', $qty);
           
        }
        Mage::log('Updating Stockcount to ' . $qty . ' for SKU ' . $simpleProductInMagento->Sku);
        
        $stockItem->assignProduct($simpleProductInMagento);
        $stockItem->setData('is_in_stock', 1);
        $stockItem->setData('stock_id', 1);
        $stockItem->setData('store_id', 1);
        $stockItem->setData('manage_stock', 0);
        $stockItem->setData('use_config_manage_stock', 1);
        $stockItem->setData('min_sale_qty', 0);
        $stockItem->setData('use_config_min_sale_qty', 0);
        $stockItem->setData('max_sale_qty', 1000);
        $stockItem->setData('use_config_max_sale_qty', 0);
        $stockItem->save();
        
        $simpleProductInMagento->save();              
        
  

          
         }
        
         catch(Exception $e)
         {
                Mage::log($e->getMessage());
                
                //debug
                throw $e;
                
                 $errorMsg = Mage::getStoreConfig('nordweb/nordweb_group/feilmeldingbruker_input',Mage::app()->getStore());
        Mage::throwException($errorMsg . '"<i>' . $e->getMessage() . '</i>"<br/><br/>' );
                
                
         }
        
        
           
    }
    
    public function getAttributeOptionValue($arg_attribute, $arg_value) { 
        $attribute_model = Mage::getModel('eav/entity_attribute'); 
        $attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;   
        $attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute); 
        $attribute = $attribute_model->load($attribute_code);   
        $attribute_table = $attribute_options_model->setAttribute($attribute); 
        $options = $attribute_options_model->getAllOptions(false);   
    
        foreach($options as $option) { 
            if ($option['label'] == $arg_value) { 
                return $option['value']; 
            } 
        }   
        return false; 
    }

    public function addAttributeOption($arg_attribute, $arg_value) { 
        $attribute_model = Mage::getModel('eav/entity_attribute'); 
        $attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;   
        
        $attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute); 
        $attribute = $attribute_model->load($attribute_code);   
       
        $attribute_table = $attribute_options_model->setAttribute($attribute); 
        $options = $attribute_options_model->getAllOptions(false);   
        
        $value['option'] = array($arg_value,$arg_value); 
        $result = array('value' => $value); 
        $attribute->setData('option',$result); 
        $attribute->save();   
        
        return $this->getAttributeOptionValue($arg_attribute, $arg_value); 
    }
    
    public function deleteAllExistingSimpleProductsBelongingToThis($_product) { 
    
        if($_product->getTypeId() == "configurable") {
	        $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($_product);
	        $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
	        foreach($simple_collection as $simple_product){
	           try{     
                    Mage::log("Deleting product: " . $simple_product->getId());
                    Mage::getModel("catalog/product")->load( $simple_product->getId()  )->delete(); 
               }
               catch(Exception $e)
               {     
                    Mage::log("Delete failed");
                    Mage::log($e->getMessage());
         
               }
	        }
        }

    }
    

   
    
    function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            .chr(125);// "}"
        return $uuid;
    }
}
    
  
 function prettyPrintArray( $array )
    {
        
        
        
?>
<pre>
    <?php
        print_r($array);
    ?>
</pre>
<?php

    }


}?>