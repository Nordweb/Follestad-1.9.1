<?php

//umask(0);
//require 'app/Mage.php';


class Nordweb_GetAllFSProducts_Helper_Data extends Mage_Core_Helper_Abstract {


    public function GetAllProducts()
    {
   
   
        Mage::log('Calling Data->GetAllProducts()');
        
     
    
        //auth
        Mage::log('Calling frontSystems->AuthenticateFS()');
        $returnValues = Mage::helper('frontSystems')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        Mage::log('Front Systems Client authenticated');
        
        
        //GetFullProductInfo
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
        Mage::log('Front Systems web-products gotten');
        
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
        Mage::log('Front Systems stock counts gotten');
        

        //Match & Store in Magento
        $this->StoreProductsForAllProductsNotHavingSimpleProductsChildrenYet($allWebProductsFromFrontSystems, $allStockCountsFromFrontSystems);
        
      
     
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
            $allProducts = Mage::getModel('catalog/product')->getCollection();
            $allProductsWithNoParentAndChildren = array(); 
            foreach ($allProducts as $oneProduct)  
            {   
                //Check for parent
                if($oneProduct->getTypeId() == "simple") 
                {
                    $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($oneProduct->getId());
                    if(!$parentIds)
                        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                    if(isset($parentIds[0])){
                       //has parent, skip
                       continue;
                    }
                }
                
                //Check for children
                if($oneProduct->getTypeId() == "configurable") 
                {
                    $possibleChildren = $oneProduct->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
                    if(!$possibleChildren && isset($possibleChildren[0]))
                    {
                       //has children, skip
                       continue;
                    }
                }
                
                //qualified - no parent, no children
                array_push( $allProductsWithNoParentAndChildren, $oneProduct); 
            }
      
            
            //2. Loop through all these Simple Products and see if there is a match in the array of web-products from FS
            //   If so, create them like when under a specific product - call that function which modifies the CP and adds the SPs
            //   Also send in StockCount from a seperate array here
            
            $allFSProductsForThisConfigurableProduct = array();
            foreach ($allProductsWithNoParentAndChildren as $oneMagentoProduct)  
            {   
                foreach ($allWebProductsFromFrontSystems as $oneFSProduct)  
                { 
                    if($oneMagentoProduct->Sku != $oneFSProduct->PRODUCTID)
                        continue;
                    
                    //Match
                   
                    //Collect FS-products with this Sku as Identity, i.e. all sizes from FS
                    foreach ($allWebProductsFromFrontSystems as $key => $value)
                    {
                        if ($oneMagentoProduct->Sku == $value->PRODUCTID)  {
                            array_push( $allFSProductsForThisConfigurableProduct, $value); 
                        }
                    }
                    
                    //Collect FS-stockcounts for this Sku/Identity in FS
                    foreach ($allWebProductsFromFrontSystems as $key => $value)
                    {
                        if ($oneMagentoProduct->Sku == $value->PRODUCTID)  {
                            array_push( $allFSProductsForThisConfigurableProduct, $value); 
                        }
                    }
                    
                    Mage::helper('addfsproducts')->StoreSimpleProductsUnderCallingConfigurableOrConfigurableToBe($oneProduct->Sku, 
                        $allFSProductsForThisConfigurableProduct, $allFSStockCountForThisConfigurableProduct);
                    
                }
            }
            
            

         }
         catch(Exception $e)
         {
                Mage::log($e->getMessage());
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