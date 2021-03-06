<?php

//umask(0);
//require 'app/Mage.php';


class Nordweb_AddFSProducts_Helper_Data extends Mage_Core_Helper_Abstract {


    public function GetProductsFromFSBySKU($SKUOfConfigurableOrConfigurableToBe)
    {
    
        /***** Comment in if need to check StockIDs ********/
        ///***** Usually not commented in unless chanages in Stocks (lager) *******/
        //$this->GetAllStockIDsCommentedIn();
        //return;
        /***** END Section for getting StockIDs ********/
         
        Mage::log(' ');
        Mage::log(' ');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('============== STARTING NEW GET PRODUCT BY SKU ===============');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        
        //$addFSProductsConfig = Mage::getStoreConfig('design/my_or_their_group/my_config');
        //Mage::log('$addFSProductsConfig: ');
        //Mage::log($addFSProductsConfig);
   
        Mage::log('Calling Data->GetProductsFromFSBySKU()');
        
        //Checkin for digits only in SKU
        if(!is_numeric($SKUOfConfigurableOrConfigurableToBe))
        {
     
            Mage::throwException('<b>Sjekk av SKU f&oslash;r innsending</b><br/>SKU p&aring; dette produktet er <i>"' . $SKUOfConfigurableOrConfigurableToBe . '"</i> og inneholder bokstaver, noe som Front Systems ikke liker. Vennligst endre SKU til kun siffer.');
            return;
                
        }
    
        //auth
        Mage::log('Calling frontSystems->AuthenticateFS()');
        $returnValues = Mage::helper('addfsproducts')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        Mage::log('Front Systems Client authenticated');
      
        
        $errorMsg = Mage::getStoreConfig('nordweb/nordweb_group/feilmeldingbruker_input',Mage::app()->getStore());
        
        //GetFullProductInfo
        Mage::log('Calling frontSystems->GetFullProductInfo()');
        Mage::log('key: ' . $fsKey);
        Mage::log('productid: ' . $SKUOfConfigurableOrConfigurableToBe);
        $retval = $clientAuthenticated->GetFullProductInfo(array('key'=>$fsKey, 'productid'=>$SKUOfConfigurableOrConfigurableToBe));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
             Mage::throwException($errorMsg . '"<i>' . $retval->faultstring . '</i>"<br/><br/>' );
        }
        $allFSProductsAndStockCountForThisConfigurableProduct = $retval->GetFullProductInfoResult;
        Mage::log('Front Systems products & stockCount gotten by SKU');
        Mage::log($allFSProductsAndStockCountForThisConfigurableProduct);

        //Store in Magento
        $this->HandleSimpleProductsForOneCallingConfigurable($SKUOfConfigurableOrConfigurableToBe, $allFSProductsAndStockCountForThisConfigurableProduct->Products, 
            $allFSProductsAndStockCountForThisConfigurableProduct->StockCounts);
        
      
     
    }
    
    
    public function GetAllStockIDsCommentedIn()
    {
    
    
        Mage::log(' ');
        Mage::log(' ');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('=== STARTING A CALL TO GET ALL STOCK IDS, NOT USED NORMALLY ==');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        Mage::log('==============================================================');
        
        //$addFSProductsConfig = Mage::getStoreConfig('design/my_or_their_group/my_config');
        //Mage::log('$addFSProductsConfig: ');
        //Mage::log($addFSProductsConfig);
   
        Mage::log('Calling Data->GetAllStockIDsCommentedIn()');
        
       
    
        //auth
        Mage::log('Calling frontSystems->AuthenticateFS()');
        $returnValues = Mage::helper('addfsproducts')->AuthenticateFS();
        $clientAuthenticated = $returnValues[0];
        $fsKey = $returnValues[1];
        Mage::log('Front Systems Client authenticated');
        Mage::log('$fsKey: ' . $fsKey);
        
        $errorMsg = Mage::getStoreConfig('nordweb/nordweb_group/feilmeldingbruker_input',Mage::app()->getStore());
        
        //GetFullProductInfo
        Mage::log('Calling frontSystems->GetStocks()');
        $retval = $clientAuthenticated->GetStocks(array('key'=>$fsKey));
        if (is_soap_fault($retval)) {
            trigger_error("SOAP Fault: (faultcode: {$retval->faultcode}, faultstring: {$retval->faultstring})", E_USER_ERROR);
             Mage::throwException($errorMsg . '"<i>' . $retval->faultstring . '</i>"<br/><br/>' );
        }
        $ArrayOfStock = $retval->GetStocksResult;
        Mage::log('All Front Systems stocks gotten:');
        Mage::log($ArrayOfStock);
        
      
        
     
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
        
        Mage::log('Authentication:');
        Mage::log('$url: ' . $url);
        Mage::log('$user: ' . $user);
        Mage::log('$pwd: ' . $pwd);
        
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
        $clientAuthenticated = new SoapClient($url,$headerParamsAuth);
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
        
        
           
        
           
        

            $this->StoreSimpleProductsUnderCallingConfigurableOrConfigurableToBe($SKUOfConfigurableOrConfigurableToBe, $allFSProductsForThisConfigurableProduct->Product, 
                $allFSStockCountForThisConfigurableProduct->StockCount);
                
            Mage::getResourceSingleton('cataloginventory/stock')->updateSetOutOfStock();
            Mage::getModel('index/process')->load(9)->reindexEverything();
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
    
    
    public function StoreSimpleProductsUnderCallingConfigurableOrConfigurableToBe($SKUOfConfigurableOrConfigurableToBe, $allFSProductsForThisConfigurableProduct, 
        $allFSStockCountForThisConfigurableProduct)
    {
    
     try {
     
        Mage::log('Calling Data->StoreSimpleProductsUnderCallingConfigurableOrConfigurableToBe()');
            
       
        //Get configurable product
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $configurableProductInMagento = Mage::getModel('catalog/product')->loadByAttribute('sku', $SKUOfConfigurableOrConfigurableToBe);
        
        Mage::log('$configurableProductInMagento:');
        Mage::log(get_object_vars($configurableProductInMagento));
        
        //delete for both addFsProducts and GetAllFSProducts
        //deleting (any) existing products
        Mage::log('Calling Data->deleteAllExistingSimpleProductsBelongingToThis()');
        $this->deleteAllExistingSimpleProductsBelongingToThis($configurableProductInMagento);
        
        $configurable_attribute = Mage::getStoreConfig('nordweb/nordweb_group2/configurable_attribute_name',Mage::app()->getStore());
        $attr_id = (int) Mage::getStoreConfig('nordweb/nordweb_group2/configurable_attribute_id',Mage::app()->getStore());
        $simpleProducts = array(); 
        
         //Sum all stockcounts
        Mage::log('Summarizing all stockCounts');
        $stockCountArray = array(); //identity, stockcount
        
        $stockIDsToOmitArray = $this->GetStockIDsToOmit();
        
        foreach ($allFSStockCountForThisConfigurableProduct as $stockCount) 
        {
            //Mage::log('$stockCount: ');
            //Mage::log($stockCount);
            //Mage::log('$stockCount->Identity: '.$stockCount->Identity);
            
            //Ommit "Ordre_"-stocks
            if (in_array($stockCount->StockID, $stockIDsToOmitArray))
                continue;
            
            if(isset($stockCountArray[$stockCount->Identity]))
            {
                $sum = $stockCountArray[$stockCount->Identity];
            }
            else
            {
                $sum = 0;
            }
                
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
        if(!is_array($allFSProductsForThisConfigurableProduct))
        {
            array_push( $allFSProductsForThisConfigurableProductArray, $allFSProductsForThisConfigurableProduct);
        }
        else
        {
            $allFSProductsForThisConfigurableProductArray = $allFSProductsForThisConfigurableProduct;
        }
        foreach ($allFSProductsForThisConfigurableProductArray as $oneFSProduct) 
        {
                //Mage::log('$oneFSProduct: ');
                //Mage::log($oneFSProduct);
                //Mage::log('$oneFSProduct->Label: ');
                //Mage::log($oneFSProduct->Label);
                
                //$this->prettyPrintArray( $oneFSProduct );
                //Mage::log(get_object_vars($oneFSProduct));
                $attr_value = $oneFSProduct->Label;
                //Mage::log('206');
                //Skip
                if (empty($attr_value)) 
                {
                    //Mage::log('210');
                    continue;
                    //Fallback if label is empty front systems
                    //Mage::log('A label from Front Systems was empty, setting it to "[N/A]"');
                    //$attr_value = "[N/A]";
                }
        
                // Again, I have more logic to determine these fields, but for clarity, I'm still including the variables here hardcoded.. $attr_value = $simple_product_data['size']; 
                //$attr_id = 136;   
                //Mage::log('219');
                // We need the actual option ID of the attribute value ("XXL", "Large", etc..) so we can assign it to the product model later.. 
                // The code for getAttributeOptionValue and addAttributeOption is part of another article (linked below this code snippet) 
                $configurableAttributeOptionId = $this->getAttributeOptionValue($configurable_attribute, $attr_value);
               // Mage::log('223');
                if (!$configurableAttributeOptionId) { 
                    //Mage::log('225');
                    $configurableAttributeOptionId = $this->addAttributeOption($configurable_attribute, $attr_value); 
                }   

                $stockCount = 0;
                
                //Mage::log('231');
                //Mage::log('$oneFSProduct->IDENTITY: ');
                //Mage::log($oneFSProduct->IDENTITY);
                if(isset( $stockCountArray[$oneFSProduct->IDENTITY] ))
                {
                   //Mage::log('234');
                   $stockCount =  $stockCountArray[$oneFSProduct->IDENTITY];
                   //Mage::log('$stockCount: ');
                   //Mage::log($stockCount);
                }
                
                //if($stockCount == 0)
                //    continue;
                //Mage::log('241');
                // Create the Magento product model 
                $prefix = Mage::getStoreConfig('nordweb/nordweb_group2/prefix_input',Mage::app()->getStore());
                
                $sProduct = Mage::getModel('catalog/product'); 
                $sProduct->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE); 
                $sProduct->setWebsiteIds(array(1)) ; 
                //Mage::log('246');
                $sProduct->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED) ; 
                $sProduct->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);  
                $sProduct->setData($configurable_attribute, $configurableAttributeOptionId) ; 
                $sProduct->setAttributeSetId(4);  //ID of a attribute set named 'default'
                //Mage::log('251');
                $sProduct->setCreatedAt(strtotime('now'));  //product creation time
                //Mage::log('253');
                $sProduct->setSku($oneFSProduct->IDENTITY) ; //SKU
                //Mage::log('255');
                // Mage::log('$configurableProductInMagento->getName()');
                //Mage::log($configurableProductInMagento->getName() );
                // Mage::log('$attr_value');
                //Mage::log($attr_value);
                $sProduct->setName($prefix . ' ' . $configurableProductInMagento->getName() . "-" . $attr_value);  //product name
                //Mage::log('257');
                $sProduct->setWeight(1.00); 
                //Mage::log('259');
                $sProduct->setTaxClassId($configurableProductInMagento->getTaxClassId());  //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
                $sProduct->setPrice($configurableProductInMagento->getPrice()) ; //price in form 11.22
                $sProduct->setDescription($configurableProductInMagento->getDescription()); 
                $sProduct->setShortDescription($configurableProductInMagento->getShortDescription());
                //Mage::log('261');
                 $sProduct->save();  
                 $sProduct->load($sProduct->getId());
                
                
                 //Mage::log('Saving simple product with Varekode: ' . $oneFSProduct->IDENTITY);
                 //$sProduct->save();  
                
                 // $stockQty = 1
                $stockItem = Mage::getModel('cataloginventory/stock_item');
                $stockItem->assignProduct($sProduct);
                $stockItem->setData('is_in_stock', 1);
                $stockItem->setData('stock_id', 1);
                $stockItem->setData('store_id', 1);
                $stockItem->setData('manage_stock', 1); 
                $stockItem->setData('use_config_manage_stock', 0);
                $stockItem->setData('min_sale_qty', 0);
                $stockItem->setData('use_config_min_sale_qty', 0);
                $stockItem->setData('max_sale_qty', 1000);
                $stockItem->setData('use_config_max_sale_qty', 0);
                $stockItem->setData('qty', $stockCount);
                $stockItem->save();

                $sProduct->save();              

                //$stockItem = Mage::getModel('cataloginventory/stock_item');
               
                //$stockItem->setData('use_config_manage_stock', 1);
                //$stockItem->setData('is_in_stock', 1);
                //$stockItem->setData('qty', $stockCount);
                
                //$sProduct->setStockItem($stockItem);
                //$stockItem->save();
                
                // $stockItem->assignProduct($sProduct);
                // Mage::log('Saving simple product with Varekode: ' . $oneFSProduct->IDENTITY);
                // $sProduct->save();  
                //$stockItem->setData('use_config_manage_stock', 1);
                //    //$stockItem->setData('is_in_stock', 1);
                //    //$stockItem->setData('qty',$stockCount);
                //    //$stockItem->save();
                 
                //Check if there is a stock item object
                //$sstockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($sProduct->getId());
                //Mage::log($sstockItem);
                //Mage::log(get_object_vars($sstockItem));
                
                ////$sstockItemData = $sstockItem->getData();
                //if (empty($sstockItem) || !isset($sstockItem) || $sstockItem == null ) {
                    
                //    Mage::log('Stockdata is empty, adding it');
                   
                    
                //    Mage::log('Deleting any Stockdata just in case');
                //    $sProduct->removeStockData();
                //    $sProduct->save();
                     
                //    $sProduct->setStockData(
                //        array(
                //            'use_config_manage_stock' => 1, //'Use config settings' checkbox
                //            'is_in_stock' => 1, //Stock Availability
                //            'qty' => $stockCount //qty
                //        )
                //    );
                // }
                // else
                // {
                //    Mage::log('Stockdata exists, updating it');
                    


                //    //Mage::log($sstockItem);
                //    //Mage::log(get_object_vars($sstockItem));
                    
                //    //$sProduct->removeStockData();
                //    //$sProduct->save();
                     
                //    //$sProduct->setStockData(
                //    //    array(
                //    //        'use_config_manage_stock' => 1, //'Use config settings' checkbox
                //    //        'is_in_stock' => 1, //Stock Availability
                //    //        'qty' => $stockCount //qty
                //    //    )
                //    //);
                //    //$stockItem->setData('use_config_manage_stock', 1);
                //    //$stockItem->setData('is_in_stock', 1);
                //    //$stockItem->setData('qty',$stockCount);
                //    //$stockItem->save();
                // }
                
                // Mage::log('Adding stockinfo on simple product with Varekode: ' . $oneFSProduct->IDENTITY . ' and Quantity: ' . $stockCount);
                // $sProduct->save();
                ////Mage::log('263');
                
                
                
                
                //// Check if there is a stock item object
                //$sstockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($sProduct->getId());
                //$sstockItemData = $sstockItem->getData();
                //if (empty($sstockItemData)) {

                //    // Create the initial stock item object
                //    $sstockItem->setData('stock_id', 1); //1234567
                //    $sstockItem->setData('is_in_stock', 1);
                //    $sstockItem->setData('qty', $stockCount);
                //    $sstockItem->setData('use_config_manage_stock', 1);
                //    Mage::log('Not exisitng stockData, adding one');
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
                //    Mage::log('Has existing stockData, setting it');
                //    //$sstockItem->save();
                //}
                
                //$sProduct->setStockItem($sstockItem);
                //$sProduct->setStockData($$sstockItemData);
                
                
                
            
               

                 //Mage::log('324');
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
             //Mage::log('404');
       
        
            /******************************** Configurable stuff ********************************/
            
            $configurableProductInMagento->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE);
             //Mage::log('410');
            $configurableProductInMagento->setCanSaveConfigurableAttributes(true);
             //Mage::log('412');
            $configurableProductInMagento->setCanSaveCustomOptions(true);
             //Mage::log('414');
            $configurableProductInMagento->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED); 
            //Mage::log('416');
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
            
            //Mage::log('$attrbutesInfo: ');
            //Mage::log($attrbutesInfo);
            //Mage::log(get_object_vars($oneFSProduct));
            
            foreach($attrbutesInfo as $oneAttribute)
            {
                //Mage::log('$oneAttribute: ');
                //Mage::log($oneAttribute);
                //Mage::log('$attr_id: ');
                //Mage::log($attr_id);
               
                if($oneAttribute == $attr_id)
                {
                    Mage::log('Attribute Size already on configurable product');
                    $hasSizeAttribute = true;
                }
                   
            }
            
            if(!$hasSizeAttribute)
            {
                Mage::log('Attribute Size not on configurable product, adding it');
                $_attributeIds = array($configurable_attribute => $attr_id);   
                //$cProductTypeInstance->setUsedProductAttributeIds($_attributeIds);
                $cProductTypeInstance->setUsedProductAttributeIds(array($_attributeIds[$configurable_attribute]));
            }
            
            //Mage::log('394');
            

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
             Mage::log('setConfigurableAttributesData on configurable');
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
            Mage::log('associate the given simple products to this configurable product');
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
            
             Mage::log('setting stockdata on configurable');
            $stock_item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($configurableProductInMagento->getId());
            if (!$stock_item->getId()) {
                $stock_item->setData('product_id', $configurableProductInMagento->getId());
                $stock_item->setData('stock_id', 1); 
            }

            $stock_item->setData('qty', 1); // is 0 or 1
            $stock_item->setData('is_in_stock', 1); // is 0 or 1
            $stock_item->setData('manage_stock', 1); // should be 1 to make something out of stock
            $stock_item->save();
            
            
            //removing old custom options
             Mage::log('removing old custome options on configurable');
            $configurableProductInMagento = $this->removeOptions($configurableProductInMagento);
            //$configurableProductInMagento->setCanSaveCustomOptions(false);
            //$configurableProductInMagento->setHasOptions(0); //->save();
           
            Mage::log('Saving Configurable product with all connected simple products');
            $configurableProductInMagento->save();
            
             Mage::log('Saved Configurable product with all connected simple products');
           
        
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
    
    public function GetStockIDsToOmit() { 
        $stockIDsToOmitString = Mage::getStoreConfig('nordweb/nordweb_group4/stockids_to_omit',Mage::app()->getStore());
        $stockIDsToOmitArray = $this->multiexplode(array(","," "),$stockIDsToOmitString);
        Mage::log('GetStockIDsToOmit:');
        Mage::log($stockIDsToOmitArray);
        return $stockIDsToOmitArray;
    }
    
    function multiexplode ($delimiters,$string) {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);
        return  $launch;
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
    
    public function removeOptions( $product) 
    {
        Mage::log('removing custom options');
        
        //$resource = Mage::getSingleton('core/resource');
        //$writeConnection = $resource->getConnection('core_write');
        //$table = 'follestad_catalog_product_option O JOIN follestad_catalog_product_option_type_value V on O.option_id=V.option_id';
        //$query = "DELETE O, V FROM {$table}
        
         $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        //$table = 'UPDATE follestad_catalog_product_option SET is_require = 0 WHERE product_id = '. (int)$product->getId().'';
        $query = 'UPDATE follestad_catalog_product_option SET is_require = 0 WHERE product_id = '. (int)$product->getId().'';
        $writeConnection->query($query);
        
        //UPDATE `catalog_product_option` SET `is_require` = 0 WHERE 1

        //$product = Mage::getModel('catalog/product')->load($product_id);
        //$options = $product->getOptions();
        //$optionsArray = array();
        //foreach($options as $option) 
        //{

        //    //if(strtolower($option->getTitle()) == $option_name) 
        //    //{
        //        Mage::log('removing custom option: ');
        //        Mage::log($option->getTitle());
        //        $optionsData = $option->getData();
        //        $optionsData['is_delete'] = 1;

        //        array_push($optionsArray, array($option->getId() => $optionsData));
               
                
        //        //$product->save();
        //    //}

        //}
        
        //$product->setProductOptions($optionsArray);
       
        
        
        return $product;

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