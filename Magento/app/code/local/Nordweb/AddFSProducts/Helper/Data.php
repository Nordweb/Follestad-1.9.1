<?php

//umask(0);
//require 'app/Mage.php';


class Nordweb_AddFSProducts_Helper_Data extends Mage_Core_Helper_Abstract {


    public function GetProductsFromFSBySKU($SKUOfConfigurableOrConfigurableToBe)
    {
    
    
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('============== STARTING NEW GET PRODUCT BY SKU ===============');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
    
   
        Mage::log('Calling Data->GetProductsFromFSBySKU()');
        
     
    
        //auth
        Mage::log('Calling frontSystems->AuthenticateFS()');
        $returnValues = Mage::helper('frontSystems')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        Mage::log('Front Systems Client authenticated');
        
        
        //GetFullProductInfo
        Mage::log('Calling frontSystems->GetFullProductInfo()');
        $retval = $clientAuthenticated->GetFullProductInfo(array('key'=>$fsKey, 'productid'=>$SKUOfConfigurableOrConfigurableToBe));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
             Mage::throwException('<b>Vi beklager</b><br/>Det har oppst&aring;tt en feil ved henting av produkter fra Front Systems. 
                Vennligst sjekk teknisk feilmelding og pr&oslash;v igjen. <br/>Hvis ikke det fungerer, kontakt support p&aring;: 
                <a href="mailto:rune@nordweb.no">rune@nordweb.no</a><br/><br/><b>Feilmelding fra teknisk system:</b><br/>"<i>' . 
                $retval->faultstring . '</i>"<br/><br/>' );
        }
        $allFSProductsAndStockCountForThisConfigurableProduct = $retval->GetFullProductInfoResult;
        Mage::log('Front Systems products & stockCount gotten by SKU');
        

        //Store in Magento
        $this->HandleSimpleProductsForOneCallingConfigurable($SKUOfConfigurableOrConfigurableToBe, $allFSProductsAndStockCountForThisConfigurableProduct->Products, 
            $allFSProductsAndStockCountForThisConfigurableProduct->StockCounts);
        
      
     
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
    

    public function HandleSimpleProductsForOneCallingConfigurable($SKUOfConfigurableOrConfigurableToBe, $allFSProductsForThisConfigurableProduct, 
        $allFSStockCountForThisConfigurableProduct)
    {
    
         try {
     
            Mage::log('Calling Data->HandleSimpleProductsForOneCallingConfigurable()');
            
            //Get configurable product
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
            $configurableProductInMagento = Mage::getModel('catalog/product')->loadByAttribute('sku', $SKUOfConfigurableOrConfigurableToBe);
        
        
            //deleting existing products
            Mage::log('Calling Data->deleteAllExistingSimpleProductsBelongingToThis()');
            $this->deleteAllExistingSimpleProductsBelongingToThis($configurableProductInMagento);
        
           
        

            $this->StoreSimpleProductsUnderCallingConfigurableOrConfigurableToBe($SKUOfConfigurableOrConfigurableToBe, $allFSProductsForThisConfigurableProduct->Product, 
                $allFSStockCountForThisConfigurableProduct->StockCount);
                
            Mage::getResourceSingleton('cataloginventory/stock')->updateSetOutOfStock();
            Mage::getModel('index/process')->load(9)->reindexEverything();
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
    
    
    public function StoreSimpleProductsUnderCallingConfigurableOrConfigurableToBe($SKUOfConfigurableOrConfigurableToBe, $allFSProductsForThisConfigurableProduct, 
        $allFSStockCountForThisConfigurableProduct)
    {
    
     try {
     
        Mage::log('Calling Data->StoreSimpleProductsUnderCallingConfigurableOrConfigurableToBe()');
            
       
        //Get configurable product
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $configurableProductInMagento = Mage::getModel('catalog/product')->loadByAttribute('sku', $SKUOfConfigurableOrConfigurableToBe);
            
        $configurable_attribute = "size"; 
        $attr_id = 136; 
        $simpleProducts = array(); 
        
         //Sum all stockcounts
        Mage::log('Summarizing all stockCounts');
        $stockCountArray = array(); //identity, stockcount
        foreach ($allFSStockCountForThisConfigurableProduct as $stockCount) 
        {
            //Mage::log('$stockCount: ');
            //Mage::log($stockCount);
            //Mage::log('$stockCount->Identity: '.$stockCount->Identity);
            
            if(isset($stockCountArray[$stockCount->Identity]))
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
        
        
        

        // Loop through a pre-populated array of data gathered from the CSV files (or database) of old system.. 
        Mage::log('Looping through all ' . count($allFSProductsForThisConfigurableProduct) . ' products from Front Systems');
        
        $allFSProductsForThisConfigurableProductArray = array();
        //if(!is_array($allFSProductsForThisConfigurableProduct))
        //{
        //    array_push( $allFSProductsForThisConfigurableProductArray, $allFSProductsForThisConfigurableProduct);
        //}
        //else
        //{
            $allFSProductsForThisConfigurableProductArray = $allFSProductsForThisConfigurableProduct;
        //}
        foreach ($allFSProductsForThisConfigurableProductArray as $oneFSProduct) {
        
                //$this->prettyPrintArray( $oneFSProduct );
                Mage::log(get_object_vars($oneFSProduct));
                $attr_value = $oneFSProduct->Label;
                
                //Skip
                if (empty($attr_value)) 
                {
                    continue;
                    //Fallback if label is empty front systems
                    //Mage::log('A label from Front Systems was empty, setting it to "[N/A]"');
                    //$attr_value = "[N/A]";
                }
        
                // Again, I have more logic to determine these fields, but for clarity, I'm still including the variables here hardcoded.. $attr_value = $simple_product_data['size']; 
                //$attr_id = 136;   

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
                
                
                
                
                //// Check if there is a stock item object
                //$sstockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($sProduct->getId());
                //$sstockItemData = $sstockItem->getData();
                //if (empty($sstockItemData)) {

                //    // Create the initial stock item object
                //    $sstockItem->setData('stock_id', 1234567);
                //    $sstockItem->setData('is_in_stock', 1);
                //    $sstockItem->setData('qty', $stockCount);
                //    $sstockItem->setData('use_config_manage_stock', 1);
                //    //$sstockItem->save();
                

                //    //// Init the object again after it has been saved so we get the full object
                //    //$sstockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($sProduct->getId());
                //}
                //else
                //{

                //    // Set the quantity
                //    $sstockItem->setData('is_in_stock', 1);
                //    $sstockItem->setData('qty', $stockCount);
                //    $sstockItem->setData('use_config_manage_stock', 1);
                //    //$sstockItem->save();
                //}
                
                
                
                
            
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
            
            $configurableProductInMagento->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE); 
            $configurableProductInMagento->setCanSaveConfigurableAttributes(true);
            $configurableProductInMagento->setCanSaveCustomOptions(true);
            
            //More
            $configurableProductInMagento->setAttributeSetId(4);
            
           
            $cProductTypeInstance = $configurableProductInMagento->getTypeInstance(); 
            // This array is is an array of attribute ID's which the configurable product swings around (i.e; where you say when you 
            // create a configurable product in the admin area what attributes to use as options) 
            // $_attributeIds is an array which maps the attribute(s) used for configuration so their numerical counterparts. 
            // (there's probably a better way of doing this, but i was lazy, and it saved extra db calls); 
            //$_attributeIds = array("size" => 999, "color", => 1000, "material" => 1001); // etc..   
            
            //checkif exists
            //$attrbutesInfo = array($attributeId => $attributeValue)

            $hasSizeAttribute = false;
            $attrbutesInfo = $cProductTypeInstance->getUsedProductAttributeIds($configurableProductInMagento);
            
            Mage::log('$attrbutesInfo: ');
            Mage::log($attrbutesInfo);
            //Mage::log(get_object_vars($oneFSProduct));
            
            foreach($attrbutesInfo as $oneAttribute)
            {
                Mage::log('$oneAttribute: ');
                Mage::log($oneAttribute);
                Mage::log('$attr_id: ');
                Mage::log($attr_id);
               
                if($oneAttribute == $attr_id)
                {
                    Mage::log('Attribute Size already on configurable product');
                    $hasSizeAttribute = true;
                }
                   
            }
            
            if(!$hasSizeAttribute)
            {
                Mage::log('Attribute Size not on configurable product, adding it');
                $_attributeIds = array("size" => $attr_id);   
                //$cProductTypeInstance->setUsedProductAttributeIds($_attributeIds);
                $cProductTypeInstance->setUsedProductAttributeIds(array($_attributeIds[$configurable_attribute]));
            }
            
            
            

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
            //$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($configurableProductInMagento->getId());
            //$stockItemData = $stockItem->getData();
            //if (empty($stockItemData)) {

            //    // Create the initial stock item object
            //    $stockItem->setData('stock_id', 123456);
            //    $stockItem->setData('is_in_stock', 1);
            //    $stockItem->setData('manage_stock', 1);
            //    $stockItem->setData('use_config_manage_stock', 0);
            //    $stockItem->setData('product_id',$configurableProductInMagento->getId());
            //    $stockItem->save();

            //    // Init the object again after it has been saved so we get the full object
            //    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($configurableProductInMagento->getId());
            //}

            //// Set the quantity
            //$stockItem->setData('is_in_stock', 1);
            //$stockItem->setData('manage_stock', 1);
            //$stockItem->setData('use_config_manage_stock', 0);
            //$stockItem->setData('product_id',$configurableProductInMagento->getId());
            //$stockItem->save();
            
            
            $stock_item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($configurableProductInMagento->getId());
            if (!$stock_item->getId()) {
                $stock_item->setData('product_id', $configurableProductInMagento->getId());
                $stock_item->setData('stock_id', 1); 
            }

            $stock_item->setData('is_in_stock', 1); // is 0 or 1
            $stock_item->setData('manage_stock', 1); // should be 1 to make something out of stock
            $stock_item->save();
           
           
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