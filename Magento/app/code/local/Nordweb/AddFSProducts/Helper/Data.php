<?php

//umask(0);
//require 'app/Mage.php';


class Nordweb_AddFSProducts_Helper_Data extends Mage_Core_Helper_Abstract {


    public function GetProductsFromFSBySKU($SKUOfConfigurable)
    {
        Mage::log('Calling Data->GetProductsFromFSBySKU()');
        
     
    
        //auth
        Mage::log('Calling frontSystems->AuthenticateFS()');
        $returnValues = Mage::helper('frontSystems')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        Mage::log('Front Systems Client authenticated');
        
        
        //GetFullProductInfo
        Mage::log('Calling frontSystems->GetFullProductInfo()');
        $retval = $clientAuthenticated->GetFullProductInfo(array('key'=>$fsKey, 'productid'=>$SKUOfConfigurable));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
        }
        $allProductsAndStockCountForThisConfigurableProduct = $retval->GetFullProductInfoResult;
        Mage::log('Front Systems products & stockCount gotten by SKU');
        

        //Store in Magento
        $this->StoreSimpleProductsUnderCallingConfigurable($SKUOfConfigurable, $allProductsAndStockCountForThisConfigurableProduct);
        
     
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
    

    public function StoreSimpleProductsUnderCallingConfigurable($SKUOfConfigurable, $allProductsAndStockCountForThisConfigurableProduct)
    {
        Mage::log('Calling Data->StoreSimpleProductsUnderCallingConfigurable()');
            
        //Get configurable product
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $configurableProductInMagento = Mage::getModel('catalog/product')->loadByAttribute('sku', $SKUOfConfigurable);
        
        
        //deleting existing products
        Mage::log('Calling Data->deleteAllExistingSimpleProductsBelongingToThis()');
        $this->deleteAllExistingSimpleProductsBelongingToThis($configurableProductInMagento);
        
        //Sum all stockcounts
        Mage::log('Summarizing all stockCounts');
        $stockCountArray = array(); //identity, stockcount
        foreach ($allProductsAndStockCountForThisConfigurableProduct->StockCounts->StockCount as $stockCount) {

            
            $sum = $stockCountArray[$stockCount->Identity];
            if(!empty($sum) && $sum > 0)
            {
                $sum = $sum + $stockCount->Qty;
            }
            else
            {
                $sum =  $stockCount->Qty;
            }
            
            $stockCountArray[$stockCount->Identity] = $sum;
     
        }
         Mage::log("Sum of stockcounts: ");
         Mage::log($stockCountArray);
        
        
        $configurable_attribute = "size"; 
        $attr_id = 136; 
        $simpleProducts = array(); 
        
        
         try {

        // Loop through a pre-populated array of data gathered from the CSV files (or database) of old system.. 
        Mage::log('Looping through all ' . count($allProductsAndStockCountForThisConfigurableProduct->Products->Product) . ' products from Front Systems');
        foreach ($allProductsAndStockCountForThisConfigurableProduct->Products->Product as $oneFSProduct) {
        

                $attr_value = $oneFSProduct->Label;
                
                //Fallback if label is empty front systems
                if (empty($attr_value)) 
                {
                    Mage::log('A label from Front Systems was empty, setting it to "[N/A]"');
                    $attr_value = "[N/A]";
                }
        
                // Again, I have more logic to determine these fields, but for clarity, I'm still including the variables here hardcoded.. $attr_value = $simple_product_data['size']; 
                $attr_id = 136;   

                // We need the actual option ID of the attribute value ("XXL", "Large", etc..) so we can assign it to the product model later.. 
                // The code for getAttributeOptionValue and addAttributeOption is part of another article (linked below this code snippet) 
                $configurableAttributeOptionId = $this->getAttributeOptionValue($configurable_attribute, $attr_value); 
                if (!$configurableAttributeOptionId) { 
                    $configurableAttributeOptionId = $this->addAttributeOption($configurable_attribute, $attr_value); 
                }   

                $stockCount = 0;
              
                if(isset( $stockCountArray[$oneFSProduct->IDENTITY] ))
                {
                   $stockCount =  $stockCountArray[$oneFSProduct->IDENTITY];
                }
                
                // Create the Magento product model 
                $sProduct = Mage::getModel('catalog/product'); 
                $sProduct 
                    ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) 
                    ->setWebsiteIds(array(1)) 
                    ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED) 
                    ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) 
                    ->setData($configurable_attribute, $configurableAttributeOptionId) 
                    ->setAttributeSetId(4) //ID of a attribute set named 'default'
                    ->setCreatedAt(strtotime('now')) //product creation time
                    ->setSku($oneFSProduct->IDENTITY) //SKU
                    ->setName("[FS] " . $configurableProductInMagento->getName() . "-" . $attr_value) //product name
                    ->setWeight(1.00)
                    ->setTaxClassId($configurableProductInMagento->getTaxClassId()) //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
                    ->setPrice($configurableProductInMagento->getPrice()) //price in form 11.22
                    ->setDescription($configurableProductInMagento->getDescription())
                    ->setShortDescription($configurableProductInMagento->getShortDescription())
                    ->setStockData(
                        array(
                            'use_config_manage_stock' => 1, //'Use config settings' checkbox
                            'is_in_stock' => 1, //Stock Availability
                            'qty' => $stockCount //qty
                        )
                );
            
                Mage::log('Saving simple product with Varekode: ' . $oneFSProduct->IDENTITY . ' and Quantity: ' . $stockCount);
                $sProduct->save();   

                // Store some data for later once we've created the configurable product, so we can 
                // associate this simple product to it later.. 
                array_push( 
                    $simpleProducts, 
                        array( 
                            "id" => $sProduct->getId(), 
                            "price" => $sProduct->getPrice(), 
                            "attr_code" => $configurable_attribute, 
                            "attr_id" => $attr_id, 
                            "value" => $configurableAttributeOptionId, 
                            "label" => $attr_value 
                        ) 
                );   

  
            }
       
        
            /******************************** Configurable stuff ********************************/
            
            
            $configurableProductInMagento->setCanSaveConfigurableAttributes(true);
            $configurableProductInMagento->setCanSaveCustomOptions(true);
           
            $cProductTypeInstance = $configurableProductInMagento->getTypeInstance(); 
            // This array is is an array of attribute ID's which the configurable product swings around (i.e; where you say when you 
            // create a configurable product in the admin area what attributes to use as options) 
            // $_attributeIds is an array which maps the attribute(s) used for configuration so their numerical counterparts. 
            // (there's probably a better way of doing this, but i was lazy, and it saved extra db calls); 
            //$_attributeIds = array("size" => 999, "color", => 1000, "material" => 1001); // etc..   
            $_attributeIds = array("size" => 136);   

            // Now we need to get the information back in Magento's own format, and add bits of data to what it gives us.. 
            $attributes_array = $cProductTypeInstance->getConfigurableAttributesAsArray(); 
            foreach($attributes_array as $key => $attribute_array) { 
                $attributes_array[$key]['use_default'] = 1; 
                $attributes_array[$key]['position'] = 0;   
                if (isset($attribute_array['frontend_label'])) { 
                    $attributes_array[$key]['label'] = $attribute_array['frontend_label']; 
                } 
                else { 
                    $attributes_array[$key]['label'] = $attribute_array['attribute_code']; 
                } 
            }   

            // Add it back to the configurable product.. 
            $configurableProductInMagento->setConfigurableAttributesData($attributes_array);   

            // Remember that $simpleProducts array we created earlier? Now we need that data.. 
            $dataArray = array(); 
            foreach ($simpleProducts as $simpleArray) { 
                $dataArray[$simpleArray['id']] = array(); 
                foreach ($attributes_array as $attrArray) { 
                    array_push( 
                        $dataArray[$simpleArray['id']], 
                            array( 
                                "attribute_id" => $simpleArray['attr_id'], 
                                "label" => $simpleArray['label'], 
                                "is_percent" => false, 
                                "pricing_value" => $simpleArray['price'] 
                            ) 
                    ); 
                } 
            }   

            // This tells Magento to associate the given simple products to this configurable product.. 
            $configurableProductInMagento->setConfigurableProductsData($dataArray);   

           // Check if there is a stock item object
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($configurableProductInMagento->getId());
            $stockItemData = $stockItem->getData();
            if (empty($stockItemData)) {

                // Create the initial stock item object
                $stockItem->setData('manage_stock',0);
                $stockItem->setData('is_in_stock', 1);
                $stockItem->setData('use_config_manage_stock', 1);
                $stockItem->setData('product_id',$configurableProductInMagento->getId());
                $stockItem->setData('qty',0);
                $stockItem->save();

                // Init the object again after it has been saved so we get the full object
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($configurableProductInMagento->getId());
            }

            // Set the quantity
            $stockItem->setData('qty',0);
            $stockItem->setData('is_in_stock', 1);
            $stockItem->save();
           
            Mage::log('Saving Configurable product with all connected simple products');
            $configurableProductInMagento->save();
        
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