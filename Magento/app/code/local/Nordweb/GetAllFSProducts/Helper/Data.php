<?php

//umask(0);
//require 'app/Mage.php';


class Nordweb_GetAllFSProducts_Helper_Data extends Mage_Core_Helper_Abstract {


    public function GetAllProducts()
    {
   
        Mage::log(' ');
        Mage::log(' ');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('================ STARTING NEW GET ALL PRODUCTS ===============');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
   
        Mage::log('Calling Data->GetAllProducts()');
        
     
    
        //auth
        Mage::log('Calling frontSystems->AuthenticateFS()');
        $returnValues = Mage::helper('addfsproducts')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        Mage::log('Front Systems Client authenticated');
        
        
        //GetProducts
        Mage::log('Calling frontSystems->GetProducts()');
        $retval = $clientAuthenticated->GetProducts(array('key'=>$fsKey));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
             Mage::throwException('<b>Vi beklager</b><br/>Det har oppst&aring;tt en feil ved henting av produkter fra Front Systems. 
                Vennligst sjekk teknisk feilmelding og pr&oslash;v igjen. <br/>Hvis ikke det fungerer, kontakt support p&aring;: 
                <a href="mailto:rune@nordweb.no">rune@nordweb.no</a><br/><br/><b>Feilmelding fra teknisk system:</b><br/>"<i>' . 
                $retval->faultstring . '</i>"<br/><br/>' );
        }
        $allWebProductsFromFrontSystems = $retval->GetProductsResult;
        Mage::log(count($allWebProductsFromFrontSystems->Product) . ' web-products gotten from Front Systems');
        
        //auth
        Mage::log('Calling frontSystems->AuthenticateFS()');
        $returnValues = Mage::helper('addfsproducts')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        Mage::log('Front Systems Client authenticated');
        
        //GetStockCount
        Mage::log('Calling frontSystems->GetStockCount()');
        $retval = $clientAuthenticated->GetStockCount(array('key'=>$fsKey));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
             Mage::throwException('<b>Vi beklager</b><br/>Det har oppst&aring;tt en feil ved henting av produkter fra Front Systems. 
                Vennligst sjekk teknisk feilmelding og pr&oslash;v igjen. <br/>Hvis ikke det fungerer, kontakt support p&aring;: 
                <a href="mailto:rune@nordweb.no">rune@nordweb.no</a><br/><br/><b>Feilmelding fra teknisk system:</b><br/>"<i>' . 
                $retval->faultstring . '</i>"<br/><br/>' );
        }
        $allStockCountsFromFrontSystems = $retval->GetStockCountResult;
        Mage::log('Collected ' . count($allStockCountsFromFrontSystems->StockCount) . ' StockCounts' );
        

        //Match & Store in Magento
        $this->StoreProductsForAllProductsNotHavingSimpleProductsChildrenYet($allWebProductsFromFrontSystems, $allStockCountsFromFrontSystems);
        
        Mage::log('Finished StoreProductsForAllProductsNotHavingSimpleProductsChildrenYet');
     
    }
    
    public function AuthenticateFS()
    {
       

        //Declare some paramaters for our soapclient and create it.
        $headerParams  = array("soap_version"=> SOAP_1_1,
                        "trace"=>1,
                        "exceptions"=>0,
                         "soap_defencoding"=>'UTF-8');
        
        
        $client = new SoapClient('https://dinbutikkdev.frontsystems.no/webshop/WebshopIntegration.svc?wsdl',$headerParams);
        //Mage::log('$client: ' .get_object_vars($client));
        
        
        //Logon
        $retval = $client->Logon(array('username'=>'follestadwebshop', 'password'=>'2*3er6'));
        //Mage::log('$retval: ' .get_object_vars($retval));
        
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
             Mage::throwException('<b>Vi beklager</b><br/>Det har oppst&aring;tt en feil ved henting av produkter fra Front Systems. 
                Vennligst sjekk teknisk feilmelding og pr&oslash;v igjen. <br/>Hvis ikke det fungerer, kontakt support p&aring;: 
                <a href="mailto:rune@nordweb.no">rune@nordweb.no</a><br/><br/><b>Feilmelding fra teknisk system:</b><br/>"<i>' . 
                $retval->faultstring . '</i>"<br/><br/>' );
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
    

    public function StoreProductsForAllProductsNotHavingSimpleProductsChildrenYet($allWebProductsFromFrontSystems, $allStockCountsFromFrontSystems)
    {
    
         try {
     
            Mage::log('Calling Data->StoreProductsForAllProductsNotHavingSimpleProductsChildrenYet()');
        
       
            //1. Get all:
            //   a) Simple Products in Magento that is to be turned into configurable products
            //   i.e. they have no parent (not a subproduct of another configurable)
            //   b) Products that already are configurable, but have no children
            //   SUM: Type no matter, but no parent and no children
        
            //2. Loop through all these Simple Products and see if there is a match in the array of web-products from FS
            //   If so, create them like when under a specific product - call that function which modifies the CP and adds the SPs
            //   Also send in StockCount from a seperate array here
    
            
            //1. Get all:
            //   a) Simple Products in Magento that is to be turned into configurable products
            //   i.e. they have no parent (not a subproduct of another configurable)
            //   b) Products that already are configurable, but have no children
            //   SUM: Type no matter, but no parent and no children
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
            //$allProducts = Mage::getResourceModel('catalog/product_collection');
            $allProductIds = Mage::getModel('catalog/product')->getCollection()->getAllIdsCache();
            //$db = Mage::getSingleton('core/resource')->getConnection('core_read');
            //$select = $db->select()->from('follestad_catalog_product_entity');
            //$allProductIds = $db->fetchAll($select);
            
            Mage::log('Got all Products from Magento, count:' . count($allProductIds));
            
            $allProductIDsWithNoParentAndChildren = array(); 
            
            //DEBUG
            
            
            
            
            foreach ($allProductIds as $oneProductID)  
            {   
                
               
                //DEBUG
                //if($oneProduct->Sku == null || substr($oneProduct->Sku,0,2) != '57' )
                //    continue;
                    
                //Check for parent
                //Mage::log('164');
                $oneProduct = Mage::getModel('catalog/product')->load($oneProductID);
                //Mage::log('166');
                
                if($oneProduct->getTypeId() == "simple") 
                {
                    //Mage::log('170');
                    $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($oneProduct->getId());
                    if(!$parentIds)
                        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($oneProduct->getId());
                    if(isset($parentIds[0])){
                       //has parent, skip
                       //Mage::log($oneProductID . ' has parent, moving on');
                       continue;
                    }
                }
                
                //Check for children
                if($oneProduct->getTypeId() == "configurable") 
                {
                
                    try {
                        //Mage::log('183');
                        $possibleChildren = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($oneProduct->getId());
                        //Mage::log('Children IDs:');
                        //Mage::log($possibleChildren);
                         
                        if(isset($possibleChildren[0]))
                        {
                           //has children, skip
                           //clear memory
                           //$possibleChildren->clearInstance();
                           //Mage::log($oneProductID . ' has children, skipping');
                           continue;
                        }
                     }
                     catch(Exception $e)
                     {
                            Mage::log($e->getMessage());
                             //debug
                            throw $e;
                     }
                }
                
                //qualified - no parent, no children
                 //Mage::log($oneProductID . ' qualified, no parents and children');
                 
                //DEBUG
                //$count = $count + 1;
                //if($count >= 100)
                //    break;
                
                //Mage::log('196');
                //Mage::log('Product with sku ' . $oneProduct->Sku . ' has no parents or children, adding for check');
                array_push( $allProductIDsWithNoParentAndChildren, $oneProduct->getId()); 
                
                //Mage::log('$allProductsWithNoParentAndChildren has count:' . count($allProductIDsWithNoParentAndChildren));
                //clear memory
                $oneProduct->clearInstance();
                //Mage::log('202');
            }
            
            //Mage::log('206');
            Mage::log('Got all Products With No Parent And Children from Magento, count:' . count($allProductIDsWithNoParentAndChildren));
      
            
            //2. Loop through all these Simple Products and see if there is a match in the array of web-products from FS
            //   If so, create them like when under a specific product - call that function which modifies the CP and adds the SPs
            //   Also send in StockCount from a seperate array here
            

            Mage::log('count($allWebProductsFromFrontSystems->Product):' . count($allWebProductsFromFrontSystems->Product));
            $updateCount = 0;
            
            foreach ($allProductIDsWithNoParentAndChildren as $oneMagentoProductID)  
            {   
                $oneMagentoProduct = Mage::getModel('catalog/product')->load($oneMagentoProductID);
                $allFSProductsForThisConfigurableProduct = array();
                $allFSStockCountForThisConfigurableProduct = array();
            
                foreach ($allWebProductsFromFrontSystems->Product as $oneFSProduct)  
                { 
                    //Mage::log('Comparing: $oneMagentoProduct->Sku: ' . $oneMagentoProduct->Sku . ' $oneFSProduct->PRODUCTID: ' . $oneFSProduct->PRODUCTID);
                    if($oneMagentoProduct->Sku != $oneFSProduct->PRODUCTID)
                        continue;
                    
                    //Match
                    //Mage::log('Found Magento-product with sku ' . $oneMagentoProduct->Sku . ' in FS-product with PRODUCTID ' . $oneFSProduct->PRODUCTID);
                   
                    //Collect FS-products with this Sku as Identity, i.e. all sizes from FS
                    //foreach ($allWebProductsFromFrontSystems->Product as $key => $value)
                    //{
                    //    if ($oneMagentoProduct->Sku == $value->PRODUCTID)  {
                    //        array_push( $allFSProductsForThisConfigurableProduct, $value); 
                    //    }
                    //}
                    //Mage::log('Collected ' . count($allFSProductsForThisConfigurableProduct) . ' sizes (FS-products) for FS-Product with PRODUCTID = Sku: ' . $oneMagentoProduct->Sku);
                    array_push( $allFSProductsForThisConfigurableProduct, $oneFSProduct); 
                       
                }
                
                //Collect FS-stockcounts for this Sku/Identity in FS
                foreach ($allStockCountsFromFrontSystems->StockCount as $value)
                {
                    //Mage::log(get_object_vars($value));
                        
                    if (strlen($oneMagentoProduct->Sku) == 6 && (strpos($value->Identity, '00'. $oneMagentoProduct->Sku) !== false ? true : false))  {
                        array_push( $allFSStockCountForThisConfigurableProduct, $value); 
                    }
                }
                     
                if(isset($allFSProductsForThisConfigurableProduct) && isset($allFSStockCountForThisConfigurableProduct) 
                    && count($allFSProductsForThisConfigurableProduct) > 0 && count($allFSStockCountForThisConfigurableProduct) > 0)
                {
                     Mage::log('Collected ' . count($allFSProductsForThisConfigurableProduct) . '  FS-Products with PRODUCTID = Sku: ' . $oneMagentoProduct->Sku);
                     Mage::log('Collected ' . count($allFSStockCountForThisConfigurableProduct) . ' stockcounts for FS-Product with Identity containing Sku: ' . $oneMagentoProduct->Sku);
               
                     Mage::helper('addfsproducts')->StoreSimpleProductsUnderCallingConfigurableOrConfigurableToBe($oneMagentoProduct->Sku, 
                        $allFSProductsForThisConfigurableProduct, $allFSStockCountForThisConfigurableProduct);
                        
                        $updateCount = $updateCount + 1;
                }
                
    
                //clear memory
                unset($oneMagentoProduct);
                unset($allFSProductsForThisConfigurableProduct);
                unset($allFSStockCountForThisConfigurableProduct);
              
                
            }
            
             Mage::log('updated ' . $updateCount . '  Magento-products with GetAllFSProducts');
            
            //if($allProductIDsWithNoParentAndChildren != null && is_object($allProductIDsWithNoParentAndChildren))
            //    $allProductIDsWithNoParentAndChildren->clearInstance();
            //if($allWebProductsFromFrontSystems != null && is_object($allWebProductsFromFrontSystems))
            //    $allWebProductsFromFrontSystems->clearInstance();
                
                
            Mage::log('Finished looping all products, indexing..');
            
            Mage::getResourceSingleton('cataloginventory/stock')->updateSetOutOfStock();
            Mage::getModel('index/process')->load(9)->reindexEverything();
            
             Mage::log('Finished indexing..');

         }
         catch(Exception $e)
         {
                Mage::log($e->getMessage());
                
                 //debug
                throw $e;
                
                Mage::throwException('<b>Vi beklager</b><br/>Det har oppst&aring;tt en feil ved henting av produkter fra Front Systems. 
                Vennligst sjekk teknisk feilmelding og pr&oslash;v igjen. <br/>Hvis ikke det fungerer, kontakt support p&aring;: 
                <a href="mailto:rune@nordweb.no">rune@nordweb.no</a><br/><br/><b>Feilmelding fra teknisk system:</b><br/>"<i>' . 
                $e->getMessage() . '</i>"<br/><br/>' );
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